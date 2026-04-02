<?php

return [
    'api_key' => env('FOODBUDGET_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'pexels_api_key' => env('PEXELS_API_KEY'),
    'free_tier_max_days' => 1,
    'free_tier_max_persons' => 1,
    'basic_meal_threshold_multiplier' => 0.5,

    'rate_limits' => [
        'free_plans_per_day' => 5,
        'free_regenerations_per_day' => 5,
        'free_max_saved_plans' => 5,
        'premium_plans_per_day' => 10,
        'api_general' => [
            'max_attempts' => 120,
            'decay_minutes' => 1,
        ],
    ],
];
