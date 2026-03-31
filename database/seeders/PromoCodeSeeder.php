<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            // Launch promos — 30 days premium, 100 uses each
            ['code' => 'BUDGETBITE2025', 'duration_days' => 30, 'max_uses' => 100],
            ['code' => 'SURVIVORMODE', 'duration_days' => 30, 'max_uses' => 50],
            ['code' => 'BITEYLOVESYOU', 'duration_days' => 30, 'max_uses' => 50],

            // Beta tester codes — 90 days premium, 20 uses each
            ['code' => 'BETATESTER2025', 'duration_days' => 90, 'max_uses' => 20],
            ['code' => 'EARLYBIRD', 'duration_days' => 90, 'max_uses' => 20],

            // Influencer/review codes — 60 days, 10 uses each
            ['code' => 'FOODBLOGGER', 'duration_days' => 60, 'max_uses' => 10],
            ['code' => 'REVIEWCOPY', 'duration_days' => 60, 'max_uses' => 10],

            // Unlimited dev/test code — 365 days, unlimited uses
            ['code' => 'DEVTEST2025', 'duration_days' => 365, 'max_uses' => 0],

            // One-time personal codes — 30 days, 1 use
            ['code' => 'FRIEND001', 'duration_days' => 30, 'max_uses' => 1],
            ['code' => 'FRIEND002', 'duration_days' => 30, 'max_uses' => 1],
            ['code' => 'FRIEND003', 'duration_days' => 30, 'max_uses' => 1],
            ['code' => 'FRIEND004', 'duration_days' => 30, 'max_uses' => 1],
            ['code' => 'FRIEND005', 'duration_days' => 30, 'max_uses' => 1],
        ];

        foreach ($codes as $code) {
            PromoCode::updateOrCreate(
                ['code' => $code['code']],
                $code,
            );
        }
    }
}
