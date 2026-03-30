<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

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

        $basicMealNote = '';
        if (($params['isExtremelyLowBudget'] ?? false)) {
            $basicMealNote = "\n- The budget is EXTREMELY tight. Use basic fallback meals (rice with salt, instant noodles, water) and set isBasicMeal: true for those meals.";
        }

        return <<<PROMPT
You are a meal planning assistant. Generate a {$params['numberOfDays']}-day meal plan
for {$params['numberOfPersons']} person(s) in {$params['countryCode']}.

Budget: {$params['totalBudget']} {$params['currencyCode']} total
Daily budget per person: {$params['dailyBudgetPerPerson']} {$params['currencyCode']}
Economic tier: {$params['economicTier']}
Start date: {$params['startDate']}
Skipped meals: {$skipped}

Requirements:
- Use local cuisine and realistic local market prices for {$params['countryCode']}
- All prices in {$params['currencyCode']}
- Total cost of ALL meals must NOT exceed {$params['totalBudget']} {$params['currencyCode']}
- The running total cost must never exceed the total budget at any point in the plan
- Each day has 4 meal slots: breakfast, lunch, dinner, meryenda
- Skipped meals must have estimatedCost: 0, ingredients: [], isSkipped: true
- Even skipped meals must appear as entries
- Provide variety: do not repeat the same meal for the same meal type within the previous 2 days
- Select meals appropriate for the "{$params['economicTier']}" economic tier
- If meals are skipped, allocate the freed budget to remaining active meals{$basicMealNote}

Return ONLY a JSON object with this exact structure (no markdown, no explanation):
{
  "days": [
    {
      "dayIndex": 0,
      "meals": [
        {
          "type": "breakfast",
          "name": "Meal Name",
          "description": "Brief description",
          "ingredients": ["ingredient1", "ingredient2"],
          "estimatedCost": 50.0,
          "isSkipped": false,
          "isBasicMeal": false
        }
      ],
      "dailyCost": 200.0
    }
  ],
  "totalCost": 1400.0
}
PROMPT;
    }

    private function buildRegenerateDayPrompt(array $params, array $originalDay): string
    {
        $originalMeals = json_encode($originalDay['meals']);

        return <<<PROMPT
You are a meal planning assistant. Regenerate meals for day index {$params['dayIndex']}
of a meal plan for {$params['numberOfPersons']} person(s) in {$params['countryCode']}.

Budget for this day: {$params['dailyBudget']} {$params['currencyCode']}
Economic tier: {$params['economicTier']}
Date: {$params['date']}
Skipped meals: {$params['skippedMealTypes']}

The ORIGINAL meals for this day were:
{$originalMeals}

Requirements:
- Provide DIFFERENT meals from the originals above
- Use local cuisine and realistic local market prices for {$params['countryCode']}
- All prices in {$params['currencyCode']}
- Daily cost must not exceed {$params['dailyBudget']} {$params['currencyCode']}
- Each day has 4 meal slots: breakfast, lunch, dinner, meryenda
- Skipped meals keep estimatedCost: 0, ingredients: [], isSkipped: true

Return ONLY a JSON object (no markdown):
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
                $response = OpenAI::chat()->create([
                    'model' => config('budgetbite.openai_model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a meal planning assistant that returns only valid JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

                $content = $response->choices[0]->message->content;
                $parsed = json_decode($content, true);

                if ($parsed === null) {
                    Log::warning('OpenAI returned malformed JSON', ['attempt' => $attempt]);
                    $lastException = new \RuntimeException('Malformed JSON from OpenAI');
                    continue;
                }

                return $parsed;
            } catch (\OpenAI\Exceptions\ErrorException $e) {
                Log::error('OpenAI API error', [
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
                $lastException = $e;

                if ($attempt < $maxRetries) {
                    sleep(min(pow(2, $attempt), 8));
                }
            } catch (\Throwable $e) {
                Log::error('OpenAI unexpected error', [
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
                $lastException = $e;

                if ($attempt < $maxRetries) {
                    sleep(min(pow(2, $attempt), 8));
                }
            }
        }

        throw $lastException ?? new \RuntimeException('OpenAI call failed after retries');
    }
}
