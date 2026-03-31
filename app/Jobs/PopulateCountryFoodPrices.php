<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PopulateCountryFoodPrices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public string $countryCode,
    ) {}

    public function handle(): void
    {
        Log::info("Auto-populating food prices for {$this->countryCode} (triggered by user registration)");

        Artisan::call('foodprices:populate', [
            '--country' => $this->countryCode,
            '--mode' => 'initial',
        ]);
    }
}
