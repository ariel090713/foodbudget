<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\FoodPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PopulateFoodPrices extends Command
{
    protected $signature = 'foodprices:populate
        {--country= : Specific country code to populate}
        {--batch=5 : Number of countries per run}
        {--mode=expand : Mode: "initial" for first population, "expand" to add more items, "update" to refresh prices}';

    protected $description = 'Use AI to populate and expand food prices for countries';

    public function handle(): int
    {
        $mode = $this->option('mode');

        $query = Country::query();

        if ($code = $this->option('country')) {
            $query->where('code', strtoupper($code));
        } elseif ($mode === 'initial') {
            $query->where('prices_populated', false);
        } else {
            // expand/update: pick countries with oldest prices first
            $query->where('prices_populated', true)
                ->orderBy('prices_updated_at');
        }

        $countries = $query->limit((int) $this->option('batch'))->get();

        if ($countries->isEmpty()) {
            $this->info('No countries to process.');
            return 0;
        }

        foreach ($countries as $country) {
            $this->info("Processing {$country->name} ({$country->code}) — mode: {$mode}");

            try {
                match ($mode) {
                    'initial' => $this->initialPopulate($country),
                    'expand' => $this->expandItems($country),
                    'update' => $this->updatePrices($country),
                    default => $this->expandItems($country),
                };

                $itemCount = $country->foodPrices()->count();
                $this->info("  ✓ Done — {$itemCount} total items");
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                Log::error('Food price operation failed', [
                    'country' => $country->code,
                    'mode' => $mode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }

    private function initialPopulate(Country $country): void
    {
        $prompt = $this->buildInitialPrompt($country);
        $items = $this->callAI($prompt);
        $this->upsertItems($country, $items);

        $country->update([
            'prices_populated' => true,
            'prices_updated_at' => now(),
        ]);
    }

    private function expandItems(Country $country): void
    {
        $existing = $country->foodPrices()
            ->pluck('item_name')
            ->map(fn ($n) => strtolower($n))
            ->toArray();

        $currentCount = count($existing);

        // Skip if country already has enough items
        if ($currentCount >= 2000) {
            $this->info("  → Skipped (already has {$currentCount} items)");
            $country->update(['prices_updated_at' => now()]);
            return;
        }

        $existingList = implode(', ', array_slice($existing, 0, 80));

        $prompt = $this->buildExpandPrompt($country, $existingList, $currentCount);
        $items = $this->callAI($prompt);
        $this->upsertItems($country, $items);

        $country->update(['prices_updated_at' => now()]);
    }

    private function updatePrices(Country $country): void
    {
        $existing = $country->foodPrices()
            ->pluck('item_name')
            ->toArray();

        // Pick a random batch of 20 items to update
        $batch = collect($existing)->shuffle()->take(20)->values()->toArray();
        $itemList = implode(', ', $batch);

        $prompt = $this->buildUpdatePrompt($country, $itemList);
        $items = $this->callAI($prompt);
        $this->upsertItems($country, $items);

        $country->update(['prices_updated_at' => now()]);
    }

    private function buildInitialPrompt(Country $country): string
    {
        return <<<PROMPT
You are a grocery price database. Return a JSON object with an "items" array of 40-50 common food items with real 2025 market/grocery prices in {$country->name} ({$country->code}).

Currency: {$country->currency_code} ({$country->currency_symbol})

Include items across these categories:
- staple (8-10): rice, bread, flour, pasta, noodles, corn, potatoes, local staples
- protein (8-10): chicken, pork, beef, fish, eggs, tofu, beans, lentils, canned meat/fish, local proteins
- vegetable (8-10): common local vegetables
- fruit (5-6): common local fruits
- condiment (5-6): cooking oil, soy sauce, vinegar, salt, sugar, spices, local sauces
- dairy (3-4): milk, cheese, butter, yogurt
- snack (4-5): instant noodles, biscuits, street food, local snacks
- beverage (3-4): water, coffee, tea, juice

RULES:
- Use REAL 2025 grocery/market prices for {$country->name}
- Include local name if different from English
- Unit = standard purchase unit (1 kg, 1 pc, 1 L, 1 bundle, 1 can)
- Include at least 5 items unique to {$country->name}'s cuisine
- is_common: true for everyday items, false for specialty

Return JSON: {"items": [{"item_name":"Rice","local_name":"Bigas","unit":"1 kg","price_min":45,"price_max":55,"category":"staple","is_common":true}]}
PROMPT;
    }

    private function buildExpandPrompt(Country $country, string $existingList, int $currentCount): string
    {
        $target = max(20, 40 - (int) ($currentCount / 50));
        $currentMonth = now()->format('F');
        $currentSeason = $this->detectSeason($country->code);

        $focusAreas = [
            ["Street food and hawker/vendor items with vendor prices", "Cheap cooked meals sold by street vendors", "Local snack items from convenience stores"],
            ["Regional/provincial specialty ingredients from different regions of {$country->name}", "Ethnic minority cuisine ingredients", "Ingredients for traditional holiday/festival dishes"],
            ["Canned goods and preserved foods", "Dried foods (dried fish, dried meat, dried vegetables)", "Pickled and fermented items"],
            ["Breakfast-specific items and ingredients", "Bakery items (local breads, pastries)", "Spreads, jams, and toppings"],
            ["Organ meats and offal (liver, intestines, heart, etc.)", "Cheap cuts of meat and bones for soup", "Processed meats (hotdog, corned beef, luncheon meat)"],
            ["Leafy greens and herbs currently in season ({$currentMonth})", "Root vegetables and tubers", "Seasonal fruits available now in {$country->name}"],
            ["Sauces, pastes, curry bases, and marinades", "Spice mixes and seasoning packets", "Cooking essentials (flour, starch, baking items)"],
            ["Baby food and porridge items", "Instant/ready-to-eat meals and canned meals", "Budget survival foods (absolute cheapest items available)"],
            ["Seafood varieties (fresh, dried, canned, smoked)", "Shellfish and crustaceans", "Seaweed and aquatic vegetables"],
            ["Nuts, seeds, and legumes", "Grains beyond rice (millet, sorghum, quinoa, oats, etc.)", "Noodle and pasta varieties (local and imported)"],
            ["Beverages (local drinks, powdered drinks, fresh juices)", "Dairy products and alternatives", "Frozen foods and ice cream"],
            ["Condiments specific to {$country->name} cuisine", "Cooking oils and fats (different types)", "Vinegars, fermented sauces, and dipping sauces"],
            ["Foods currently in season in {$country->name} ({$currentMonth}, {$currentSeason})", "Seasonal price changes — items that are cheaper right now", "Festival/holiday foods for upcoming celebrations"],
            ["Complete cheap meals under the poverty line", "Combinations: staple + cheapest protein", "What a family of 4 eats on minimum wage in {$country->name}"],
        ];

        $focusIndex = (int) ($currentCount / 40) % count($focusAreas);
        $focus = implode("\n- ", $focusAreas[$focusIndex]);

        return <<<PROMPT
You are a food and grocery expert for {$country->name} ({$country->code}).
Currency: {$country->currency_code} ({$country->currency_symbol})
Current month: {$currentMonth} ({$currentSeason} season)

I have {$currentCount} food items. Generate {$target} NEW items NOT already in my database.

Existing items (DO NOT repeat):
{$existingList}

FOCUS THIS TIME ON:
- {$focus}

RULES:
- Every item must be ACTUALLY available in {$country->name} right now
- Use REAL {$currentMonth} 2025 prices (seasonal prices may differ from annual average)
- Include local names in the local language of {$country->name}
- Be SPECIFIC: "Pork belly" not "Pork", "Saba banana" not "Banana", "Jasmine rice" not "Rice"
- Include cooked/prepared street food with vendor prices
- For seasonal items, set availability to "seasonal" and note the season
- Add a brief note for interesting items (e.g. "Price doubles during Christmas season")

Return JSON:
{{"items": [{{"item_name":"...","local_name":"...","unit":"...","price_min":0,"price_max":0,"category":"...","is_common":true,"season":"{$currentSeason}","availability":"all_year","notes":"..."}}]}}
PROMPT;
    }

    private function detectSeason(string $countryCode): string
    {
        $month = (int) now()->format('n');

        // Southern hemisphere countries
        $southern = ['AU', 'NZ', 'AR', 'CL', 'ZA', 'BR', 'PY', 'UY', 'MZ', 'ZW', 'ZM'];
        if (in_array($countryCode, $southern)) {
            return match (true) {
                $month >= 3 && $month <= 5 => 'autumn',
                $month >= 6 && $month <= 8 => 'winter',
                $month >= 9 && $month <= 11 => 'spring',
                default => 'summer',
            };
        }

        // Tropical countries (wet/dry seasons)
        $tropical = ['PH', 'TH', 'VN', 'ID', 'MY', 'MM', 'KH', 'LA', 'BD', 'LK', 'IN', 'NG', 'GH', 'KE', 'TZ', 'UG', 'CM', 'CI', 'SN', 'BF', 'BJ', 'CD', 'MZ', 'ET', 'RW'];
        if (in_array($countryCode, $tropical)) {
            return ($month >= 6 && $month <= 11) ? 'wet_season' : 'dry_season';
        }

        // Northern hemisphere (default)
        return match (true) {
            $month >= 3 && $month <= 5 => 'spring',
            $month >= 6 && $month <= 8 => 'summer',
            $month >= 9 && $month <= 11 => 'autumn',
            default => 'winter',
        };
    }

    private function buildUpdatePrompt(Country $country, string $itemList): string
    {
        return <<<PROMPT
You are a grocery price database. Update the prices for these food items in {$country->name} ({$country->code}) to reflect current 2025 market prices.

Currency: {$country->currency_code} ({$country->currency_symbol})

Items to update: {$itemList}

Return the SAME items with updated price_min and price_max based on current 2025 real market prices.

Return JSON: {"items": [{"item_name":"...","local_name":"...","unit":"...","price_min":0,"price_max":0,"category":"...","is_common":true}]}
PROMPT;
    }

    private function upsertItems(Country $country, array $items): void
    {
        foreach ($items as $item) {
            $itemName = $item['item_name'] ?? null;
            if (! $itemName) {
                continue;
            }

            FoodPrice::updateOrCreate(
                [
                    'country_code' => $country->code,
                    'item_name' => $itemName,
                ],
                [
                    'local_name' => $item['local_name'] ?? null,
                    'unit' => $item['unit'] ?? '1 pc',
                    'price_min' => $item['price_min'] ?? 0,
                    'price_max' => $item['price_max'] ?? 0,
                    'currency_code' => $country->currency_code,
                    'category' => $item['category'] ?? 'staple',
                    'is_common' => $item['is_common'] ?? true,
                    'season' => $item['season'] ?? null,
                    'availability' => $item['availability'] ?? 'all_year',
                    'notes' => $item['notes'] ?? null,
                ],
            );
        }
    }

    private function callAI(string $prompt): array
    {
        $model = config('budgetbite.gemini_model', 'gemini-2.0-flash');

        $genConfig = new \Gemini\Data\GenerationConfig(
            temperature: 0.3,
            responseMimeType: \Gemini\Enums\ResponseMimeType::APPLICATION_JSON,
        );

        $result = \Gemini\Laravel\Facades\Gemini::generativeModel(model: $model)
            ->withGenerationConfig($genConfig)
            ->withSystemInstruction(\Gemini\Data\Content::parse(
                part: 'You are a grocery price database with accurate knowledge of real food prices worldwide. Return only valid JSON with an "items" array.',
            ))
            ->generateContent($prompt);

        $content = $result->text();

        // Strip markdown code blocks if present
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);
        $content = trim($content);

        $parsed = json_decode($content, true);

        if (! $parsed) {
            throw new \RuntimeException('Failed to parse Gemini response');
        }

        $items = $parsed['items'] ?? $parsed;
        if (! is_array($items) || empty($items)) {
            throw new \RuntimeException('No food items in Gemini response');
        }

        return $items;
    }
}
