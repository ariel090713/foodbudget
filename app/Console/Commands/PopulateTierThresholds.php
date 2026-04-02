<?php

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PopulateTierThresholds extends Command
{
    protected $signature = 'tiers:populate
        {--country= : Specific country code}
        {--batch=10 : Countries per run}';

    protected $description = 'Use AI to set realistic economic tier thresholds per country';

    public function handle(): int
    {
        $query = Country::query()
            ->where('tier_poor_min', 2.15); // Still has default values

        if ($code = $this->option('country')) {
            $query->where('code', strtoupper($code));
        }

        $countries = $query->limit((int) $this->option('batch'))->get();

        if ($countries->isEmpty()) {
            $this->info('All countries already have custom thresholds.');
            return 0;
        }

        // Batch all countries into one AI call for efficiency
        $countryList = $countries->map(fn ($c) => "{$c->code}: {$c->name} ({$c->currency_code})")->implode("\n");

        $prompt = <<<PROMPT
For each country below, provide realistic 2025 economic tier thresholds for DAILY food budget PER PERSON in their LOCAL currency.

The tiers are:
- extremePoverty: below poor_min (can barely afford rice/bread)
- poor: poor_min to middle_class_min (basic meals, limited protein)
- middleClass: middle_class_min to rich_min (balanced meals with meat/fish)
- rich: above rich_min (premium ingredients, dining quality at home)

Countries:
{$countryList}

Base thresholds on real 2025 cost of living and food prices in each country.
Example: Philippines (PHP) → poor_min: 100, middle_class_min: 250, rich_min: 800

Return JSON: {"thresholds": [{"code":"PH","poor_min":100,"middle_class_min":250,"rich_min":800}]}
PROMPT;

        $this->info("Setting thresholds for {$countries->count()} countries...");

        try {
            $model = config('budgetbite.gemini_model', 'gemini-2.5-flash');
            $genConfig = new \Gemini\Data\GenerationConfig(
                temperature: 0.2,
                responseMimeType: \Gemini\Enums\ResponseMimeType::APPLICATION_JSON,
            );

            $result = \Gemini\Laravel\Facades\Gemini::generativeModel(model: $model)
                ->withGenerationConfig($genConfig)
                ->generateContent($prompt);

            $content = $result->text();
            $parsed = json_decode(trim($content), true);
            $items = $parsed['thresholds'] ?? $parsed;

            foreach ($items as $item) {
                $country = Country::find($item['code'] ?? '');
                if (! $country) continue;

                $country->update([
                    'tier_poor_min' => $item['poor_min'] ?? 2.15,
                    'tier_middle_class_min' => $item['middle_class_min'] ?? 10,
                    'tier_rich_min' => $item['rich_min'] ?? 50,
                ]);

                $this->info("  {$country->code}: poor>{$item['poor_min']}, middle>{$item['middle_class_min']}, rich>{$item['rich_min']}");
            }

            $this->info('Done.');
        } catch (\Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");
            Log::error('Tier threshold population failed', ['error' => $e->getMessage()]);
        }

        return 0;
    }
}
