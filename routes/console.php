<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Initial population: fill countries that have no prices yet (5 at a time)
Schedule::command('foodprices:populate --batch=5 --mode=initial')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Expand: add more food items to countries with < 1000 items (2 at a time)
Schedule::command('foodprices:populate --batch=2 --mode=expand')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Update: refresh prices on existing items (2 countries at a time)
Schedule::command('foodprices:populate --batch=2 --mode=update')
    ->weekly()
    ->withoutOverlapping()
    ->runInBackground();

// Update tier thresholds for any countries still on defaults
Schedule::command('tiers:populate --batch=20')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();
