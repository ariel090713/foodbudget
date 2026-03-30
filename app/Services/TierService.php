<?php

namespace App\Services;

class TierService
{
    public function detectTier(float $dailyBudgetPerPerson, string $countryCode): string
    {
        $thresholds = $this->getThresholds($countryCode);

        if ($dailyBudgetPerPerson < $thresholds['poor_min']) {
            return 'extremePoverty';
        }

        if ($dailyBudgetPerPerson < $thresholds['middle_class_min']) {
            return 'poor';
        }

        if ($dailyBudgetPerPerson < $thresholds['rich_min']) {
            return 'middleClass';
        }

        return 'rich';
    }

    public function getThresholds(string $countryCode): array
    {
        return config("tiers.countries.{$countryCode}", config('tiers.default'));
    }
}
