<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function generateMealPlan(array $params): array
    {
        $prompt = $this->buildMealPlanPrompt($params);

        return $this->callWithRetry($prompt);
    }

    public function regenerateDay(array $params, array $originalDay): array
    {
        $prompt = $this->buildRegenerateDayPrompt($params, $originalDay);

        return $this->callWithRetry($prompt);
    }

    private function buildMealPlanPrompt(array $params): string
    {
        $skipped = ! empty($params['skippedMealTypes'])
            ? implode(', ', $params['skippedMealTypes'])
            : 'none';

        $skippedInstruction = '';
        if (! empty($params['skippedMealTypes'])) {
            $skippedList = implode(', ', $params['skippedMealTypes']);
            $skippedInstruction = "\n\nSKIPPED MEALS — ABSOLUTE RULES:\n"
                . "- These meal types are SKIPPED: {$skippedList}\n"
                . "- For EACH skipped type, return EXACTLY this:\n"
                . "  {\"type\":\"<type>\",\"name\":\"Skipped\",\"description\":\"Skipped by user\",\"ingredients\":[],\"estimatedCost\":0,\"isSkipped\":true,\"isBasicMeal\":false}\n"
                . "- Do NOT generate real food for skipped meals. No exceptions.\n"
                . "- The ENTIRE daily budget goes ONLY to non-skipped meals.";
        }

        $priceTable = $this->getPriceReference($params['countryCode']);

        $survivalNote = '';
        $dailyBudget = $params['dailyBudgetPerPerson'];
        if ($params['economicTier'] === 'extremePoverty' || $dailyBudget < 80) {
            $cheapItems = $this->getCheapFoodExamples($params['countryCode']);
            $survivalNote = "\n\nSURVIVAL MODE (budget is extremely low):\n"
                . "- Use the CHEAPEST possible real meals from this country. Prioritize variety even on tight budgets.\n"
                . "- CHEAP MEAL IDEAS for {$params['countryCode']} (mix and match, be creative):\n"
                . "{$cheapItems}\n"
                . "- ONLY use plain rice/bread with salt/sugar as ABSOLUTE LAST RESORT when nothing else fits\n"
                . "- Mark all survival meals with \"isBasicMeal\": true\n"
                . "- Be honest about costs — don't pretend expensive dishes can be made cheaply";
        }

        $premiumInstruction = '';
        $premiumMealFields = '';
        if ($params['includePremiumData'] ?? false) {
            $premiumInstruction = "\n\nPREMIUM DATA (include these extra fields for each non-skipped meal):\n"
                . "- \"nutrition\": {\"calories\": number, \"protein\": \"Xg\", \"carbs\": \"Xg\", \"fat\": \"Xg\", \"fiber\": \"Xg\"}\n"
                . "- \"pros\": [\"High in protein\", \"Budget-friendly\", ...] (2-3 benefits)\n"
                . "- \"cons\": [\"Low in vitamins\", \"High sodium\", ...] (1-2 downsides)\n"
                . "- Base nutrition estimates on the actual ingredients and quantities listed\n"
                . "- For skipped meals, omit nutrition/pros/cons";

            $premiumMealFields = ",\n          \"nutrition\": {\"calories\": 350, \"protein\": \"12g\", \"carbs\": \"45g\", \"fat\": \"8g\", \"fiber\": \"2g\"},\n"
                . "          \"pros\": [\"Good source of protein\", \"Budget-friendly\"],\n"
                . "          \"cons\": [\"Low in vegetables\"]";
        }

        $varietyRule = $params['numberOfDays'] > 1
            ? "- VARIETY IS CRITICAL: For multi-day plans, NEVER repeat the same meal name for the same meal type across ANY two days."
            : "- Use a good variety of local dishes for the 4 meals.";

        $previousMealsContext = '';
        if (! empty($params['previousMealsNote'])) {
            $previousMealsContext = "\n{$params['previousMealsNote']}\n";
        }

        $activeMeals = 3 - count($params['skippedMealTypes'] ?? []);
        $mealBudget = $activeMeals > 0 ? round($dailyBudget / $activeMeals, 2) : 0;

        return <<<PROMPT
You are a meal cost calculator with EXACT knowledge of real {$params['countryCode']} grocery/wet market prices as of 2025. You plan homemade meals, NOT restaurant meals.

Generate a {$params['numberOfDays']}-day meal plan for {$params['numberOfPersons']} person(s) in {$params['countryCode']}.
{$previousMealsContext}
Budget: {$params['totalBudget']} {$params['currencyCode']} total
Daily budget per person: {$dailyBudget} {$params['currencyCode']}
Economic tier: {$params['economicTier']}
Start date: {$params['startDate']}
Skipped meals: {$skipped}

{$priceTable}
{$skippedInstruction}
{$survivalNote}
{$premiumInstruction}

PRICING RULES — CRITICAL:
1. estimatedCost = actual cost to BUY the raw ingredients for {$params['numberOfPersons']} person(s)
2. List ingredients with EXACT quantities: "rice 1 cup (150g)", "egg 1 pc", "pork 100g"
3. Calculate cost per ingredient based on the price reference above, then SUM them
4. If the budget cannot afford a real dish, use survival meals (cheapest staple + cheapest side)
5. dailyCost MUST equal the SUM of all non-skipped meal estimatedCosts for that day
6. totalCost MUST equal the SUM of all dailyCosts
7. totalCost MUST NOT exceed {$params['totalBudget']} {$params['currencyCode']} — THIS IS A HARD LIMIT
8. BEFORE generating, calculate: budget per day = {$params['totalBudget']} / {$params['numberOfDays']} = {$dailyBudget} per person per day
9. Each meal's cost must fit within the daily budget. If 3 meals at {$dailyBudget}/day, each meal averages {$mealBudget}
10. DOUBLE CHECK: add up all meal costs. If total > {$params['totalBudget']}, reduce portions or use cheaper meals

MEAL RULES:
- Each day has 3 meal slots: breakfast, lunch, dinner
- All 3 slots must appear (skipped ones with isSkipped: true)
{$varietyRule}
- Meals must be appropriate for "{$params['economicTier']}" tier
- Use local {$params['countryCode']} cuisine and local ingredient names

Return ONLY valid JSON (no markdown, no code blocks, no explanation):
{
  "days": [
    {
      "dayIndex": 0,
      "meals": [
        {
          "type": "breakfast",
          "name": "Sinangag at Itlog",
          "description": "Garlic fried rice with fried egg",
          "ingredients": ["rice 1 cup (150g)", "egg 1 pc", "garlic 2 cloves", "cooking oil 1 tbsp"],
          "estimatedCost": 25.0,
          "isSkipped": false,
          "isBasicMeal": false{$premiumMealFields}
        },
        {"type": "lunch", "name": "...", "description": "...", "ingredients": [...], "estimatedCost": 0, "isSkipped": false, "isBasicMeal": false},
        {"type": "dinner", "name": "...", "description": "...", "ingredients": [...], "estimatedCost": 0, "isSkipped": false, "isBasicMeal": false}
      ],
      "dailyCost": 50.0
    }
  ],
  "totalCost": 50.0
}
PROMPT;
    }

    private function getCheapFoodExamples(string $countryCode): string
    {
        $currentMonth = now()->format('F');

        // Get cheapest items, prefer in-season and all-year items
        $cheapItems = \App\Models\FoodPrice::where('country_code', $countryCode)
            ->where('is_common', true)
            ->where(function ($q) {
                $q->where('availability', 'all_year')
                  ->orWhere('availability', 'seasonal');
            })
            ->orderBy('price_min')
            ->limit(25)
            ->get();

        if ($cheapItems->isEmpty()) {
            return "  * Use the cheapest local staples, street food, canned goods, eggs, dried fish, instant noodles\n"
                . "  * Combine a cheap staple (rice/bread) with the cheapest available protein or side";
        }

        $country = \App\Models\Country::find($countryCode);
        $sym = $country?->currency_symbol ?? '';

        $lines = ["  Current month: {$currentMonth} — use seasonal items when available"];
        foreach ($cheapItems as $item) {
            $name = $item->local_name && $item->local_name !== $item->item_name
                ? "{$item->item_name} ({$item->local_name})"
                : $item->item_name;
            $seasonal = $item->availability === 'seasonal' ? ' [seasonal]' : '';
            $lines[] = "  * {$name}: {$item->unit} = {$sym}{$item->price_min}-{$item->price_max}{$seasonal}";
        }

        $lines[] = "  * Combine cheap staple + cheapest protein/side for a real meal";
        $lines[] = "  * Street food portions are valid cheap meals";
        $lines[] = "  * Use seasonal items — they're fresher and cheaper right now";

        return implode("\n", $lines);
    }

    private function getPriceReference(string $countryCode): string
    {
        // Only include common items to keep prompt short and fast
        $prices = \App\Models\FoodPrice::where('country_code', $countryCode)
            ->where('is_common', true)
            ->orderBy('category')
            ->limit(30) // Cap at 30 items to keep prompt lean
            ->get();

        if ($prices->isEmpty()) {
            return "Use your best knowledge of real 2025 grocery prices for country code {$countryCode}. Do NOT underestimate.";
        }

        $country = \App\Models\Country::find($countryCode);
        $sym = $country?->currency_symbol ?? '';

        $lines = ["PRICE REFERENCE ({$country?->name}):"];

        foreach ($prices as $p) {
            $name = $p->local_name && $p->local_name !== $p->item_name
                ? "{$p->item_name} ({$p->local_name})"
                : $p->item_name;
            $lines[] = "{$name}: {$p->unit} = {$sym}{$p->price_min}-{$p->price_max}";
        }

        return implode("\n", $lines);
    }

    private function buildRegenerateDayPrompt(array $params, array $originalDay): string
    {
        $originalMeals = json_encode($originalDay['meals']);
        $priceTable = $this->getPriceReference($params['countryCode']);

        return <<<PROMPT
You are a meal cost calculator with EXACT knowledge of real {$params['countryCode']} grocery prices as of 2025.

Regenerate meals for day index {$params['dayIndex']} for {$params['numberOfPersons']} person(s) in {$params['countryCode']}.

Budget for this day: {$params['dailyBudget']} {$params['currencyCode']}
Economic tier: {$params['economicTier']}
Date: {$params['date']}
Skipped meals: {$params['skippedMealTypes']}

{$priceTable}

The ORIGINAL meals for this day (generate DIFFERENT ones):
{$originalMeals}

RULES:
- Provide DIFFERENT meals from the originals
- estimatedCost = actual cost to BUY raw ingredients for {$params['numberOfPersons']} person(s)
- List ingredients with exact quantities
- Daily cost must not exceed {$params['dailyBudget']} {$params['currencyCode']}
- 3 meal slots: breakfast, lunch, dinner
- Skipped meals: estimatedCost: 0, ingredients: [], isSkipped: true

Return ONLY valid JSON (no markdown, no code blocks):
{
  "dayIndex": {$params['dayIndex']},
  "meals": [...],
  "dailyCost": 0.0
}
PROMPT;
    }

    private function callWithRetry(string $prompt, int $maxRetries = 3): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                    'model' => config('budgetbite.openai_model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a meal budget calculator. You know EXACT real grocery prices. You calculate meal costs by summing individual ingredient costs. You never underestimate. If the budget is too low for real food, you suggest survival meals like plain rice with salt or sugar. You return only valid JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                ]);

                $content = $response->choices[0]->message->content;
                $parsed = json_decode($content, true);

                if ($parsed === null) {
                    Log::warning('OpenAI returned malformed JSON', ['attempt' => $attempt]);
                    $lastException = new \RuntimeException('Malformed JSON from OpenAI');
                    continue;
                }

                return $parsed;
            } catch (\Throwable $e) {
                Log::error('OpenAI API error', ['attempt' => $attempt, 'message' => $e->getMessage()]);
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    sleep(min(pow(2, $attempt), 8));
                }
            }
        }

        throw $lastException ?? new \RuntimeException('OpenAI call failed after retries');
    }
}
