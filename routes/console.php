<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// PRIORITY 1: Initial population — fill ALL countries first (15 at a time, every 2 min)
Schedule::command('foodprices:populate --batch=15 --mode=initial')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// PRIORITY 2: Tier thresholds for countries still on defaults
Schedule::command('tiers:populate --batch=30')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// PRIORITY 3: Expand — only runs after all countries have initial data (5 at a time, every 5 min)
Schedule::command('foodprices:populate --batch=5 --mode=expand')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// PRIORITY 4: Update prices weekly
Schedule::command('foodprices:populate --batch=2 --mode=update')
    ->weekly()
    ->withoutOverlapping()
    ->runInBackground();
