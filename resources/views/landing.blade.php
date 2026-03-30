<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Budget: Survival Mode — AI Meal Planning Within Your Budget</title>
    <meta name="description" content="Plan affordable meals with AI. Set your budget, choose your days, and get a personalized meal plan with local cuisine tailored to your country and spending level.">
    <meta property="og:title" content="Food Budget: Survival Mode — AI Meal Planning App">
    <meta property="og:description" content="AI-powered meal planning that fits your budget. Get day-by-day meal plans with local cuisine for any country.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="keywords" content="food budget planner, meal planning app, survival mode food budget, AI meal planner, budget meals">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-amber-50 text-gray-800 antialiased">

    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 border-b border-amber-100 bg-amber-50/90 backdrop-blur-sm">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="text-3xl">🦫</span>
                <span class="text-lg font-bold text-amber-900">BudgetBite</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="#features" class="hidden text-sm text-amber-800 hover:text-amber-600 sm:inline">Features</a>
                <a href="#how-it-works" class="hidden text-sm text-amber-800 hover:text-amber-600 sm:inline">How It Works</a>
                <a href="#download" class="rounded-full bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700">Download</a>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="px-6 pb-16 pt-20 text-center">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6 text-7xl">🦫</div>
            <h1 class="mb-4 text-4xl font-bold tracking-tight text-amber-900 sm:text-5xl">
                Food Budget: Survival Mode
            </h1>
            <p class="mb-8 text-lg text-amber-800/80">
                AI-powered meal planning that fits your budget. Tell us how much you have, and Bitey will plan every meal — breakfast, lunch, dinner, and meryenda — with local cuisine tailored to your country.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="#download" class="inline-flex items-center gap-2 rounded-full bg-amber-600 px-6 py-3 font-medium text-white shadow-md hover:bg-amber-700">
                    Get the App
                </a>
                <a href="#how-it-works" class="inline-flex items-center gap-2 rounded-full border border-amber-300 bg-white px-6 py-3 font-medium text-amber-800 shadow-sm hover:bg-amber-50">
                    See How It Works
                </a>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section id="features" class="bg-white px-6 py-20">
        <div class="mx-auto max-w-5xl">
            <h2 class="mb-12 text-center text-3xl font-bold text-amber-900">What Makes BudgetBite Different</h2>
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-6">
                    <div class="mb-3 text-3xl">🤖</div>
                    <h3 class="mb-2 text-lg font-semibold text-amber-900">AI Meal Suggestions</h3>
                    <p class="text-sm text-amber-800/70">Powered by AI, every meal is generated fresh — no static database. Real meals, real prices, real variety.</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-6">
                    <div class="mb-3 text-3xl">💰</div>
                    <h3 class="mb-2 text-lg font-semibold text-amber-900">Budget-Aware Planning</h3>
                    <p class="text-sm text-amber-800/70">Set your total budget and the AI ensures every meal fits. It even detects your economic tier for smarter suggestions.</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-6">
                    <div class="mb-3 text-3xl">🌍</div>
                    <h3 class="mb-2 text-lg font-semibold text-amber-900">Country-Specific Cuisine</h3>
                    <p class="text-sm text-amber-800/70">Meals are tailored to your country with local dishes and realistic local market prices in your currency.</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-6">
                    <div class="mb-3 text-3xl">📊</div>
                    <h3 class="mb-2 text-lg font-semibold text-amber-900">Economic Tier Detection</h3>
                    <p class="text-sm text-amber-800/70">The app automatically detects your spending level and adjusts meal quality — from survival basics to premium dining.</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-6">
                    <div class="mb-3 text-3xl">📅</div>
                    <h3 class="mb-2 text-lg font-semibold text-amber-900">Multi-Day Planning</h3>
                    <p class="text-sm text-amber-800/70">Plan up to 30 days of meals at once. Each day gets breakfast, lunch, dinner, and meryenda with full variety.</p>
                </div>
                <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-6">
                    <div class="mb-3 text-3xl">🔄</div>
                    <h3 class="mb-2 text-lg font-semibold text-amber-900">Sync Across Devices</h3>
                    <p class="text-sm text-amber-800/70">Your meal plans sync in real time across all your devices. Access them offline too — cached plans are always available.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- How It Works Section --}}
    <section id="how-it-works" class="px-6 py-20">
        <div class="mx-auto max-w-4xl">
            <h2 class="mb-12 text-center text-3xl font-bold text-amber-900">How It Works</h2>
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-600 text-lg font-bold text-white">1</div>
                    <h3 class="mb-2 font-semibold text-amber-900">Set Your Budget</h3>
                    <p class="text-sm text-amber-800/70">Enter your total food budget in your local currency.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-600 text-lg font-bold text-white">2</div>
                    <h3 class="mb-2 font-semibold text-amber-900">Choose Days & Persons</h3>
                    <p class="text-sm text-amber-800/70">Pick how many days (up to 30) and how many people are eating.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-600 text-lg font-bold text-white">3</div>
                    <h3 class="mb-2 font-semibold text-amber-900">AI Generates Your Plan</h3>
                    <p class="text-sm text-amber-800/70">Bitey's AI creates a complete meal plan with local dishes that fit your budget.</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-600 text-lg font-bold text-white">4</div>
                    <h3 class="mb-2 font-semibold text-amber-900">Follow Your Plan</h3>
                    <p class="text-sm text-amber-800/70">View your day-by-day meals, regenerate any day you want, and stay on budget.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Download Section --}}
    <section id="download" class="bg-white px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <div class="mb-4 text-5xl">🦫</div>
            <h2 class="mb-4 text-3xl font-bold text-amber-900">Get BudgetBite Today</h2>
            <p class="mb-8 text-amber-800/70">Available on Android and iOS. Start planning meals within your budget.</p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="#" class="inline-flex items-center gap-3 rounded-xl bg-gray-900 px-6 py-3 text-white shadow-md hover:bg-gray-800">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.199l2.302 2.302a1 1 0 0 1 0 1.38l-2.302 2.302L15.396 13l2.302-2.492zM5.864 2.658L16.8 8.99l-2.302 2.302-8.635-8.635z"/></svg>
                    <div class="text-left">
                        <div class="text-xs opacity-70">GET IT ON</div>
                        <div class="text-sm font-semibold">Google Play</div>
                    </div>
                </a>
                <a href="#" class="inline-flex items-center gap-3 rounded-xl bg-gray-900 px-6 py-3 text-white shadow-md hover:bg-gray-800">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                    <div class="text-left">
                        <div class="text-xs opacity-70">Download on the</div>
                        <div class="text-sm font-semibold">App Store</div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-amber-100 bg-amber-50 px-6 py-8">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 sm:flex-row">
            <div class="flex items-center gap-2">
                <span class="text-xl">🦫</span>
                <span class="text-sm font-medium text-amber-900">BudgetBite</span>
            </div>
            <div class="flex gap-6 text-sm text-amber-800/70">
                <a href="{{ url('/privacy-policy') }}" class="hover:text-amber-600">Privacy Policy</a>
                <a href="{{ url('/terms-of-service') }}" class="hover:text-amber-600">Terms of Service</a>
            </div>
            <p class="text-xs text-amber-800/50">&copy; {{ date('Y') }} BudgetBite. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
