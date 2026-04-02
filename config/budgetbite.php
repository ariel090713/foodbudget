<?php

return [
    'api_key' => env('FOODBUDGET_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'free_tier_max_days' => 1,
    'basic_meal_threshold_multiplier' => 0.5,

    'rate_limits' => [
        'meal_plan_generate' => [
            'max_attempts' => (int) env('MEALPLAN_RATE_LIMIT', 10),
            'decay_minutes' => (int) env('MEALPLAN_RATE_LIMIT_WINDOW', 60),
        ],
        'meal_plan_regenerate' => [
            'max_attempts' => (int) env('REGENERATE_RATE_LIMIT', 20),
            'decay_minutes' => (int) env('REGENERATE_RATE_LIMIT_WINDOW', 60),
        ],
        'api_general' => [
            'max_attempts' => 120,
            'decay_minutes' => 1,
        ],
    ],
];
