# Implementation Plan: BudgetBite Laravel API & Landing Page

## Overview

Implement the BudgetBite Laravel API backend on the existing Laravel 12 Livewire starter kit. This adds Firebase token authentication, REST API endpoints for the Flutter app, OpenAI GPT meal plan generation, subscription management with store webhooks, FCM token management, rate limiting, and a public landing page. The existing web auth (Livewire/Volt) remains untouched.

## Tasks

- [ ] 1. Install dependencies and configure environment
  - [ ] 1.1 Install required Composer packages
    - Run `composer require kreait/firebase-php openai-php/laravel`
    - These provide Firebase Admin SDK for token verification and OpenAI client for GPT calls
    - _Requirements: 1, 2, 6_

  - [ ] 1.2 Add environment variables to `.env.example`
    - Add OPENAI_API_KEY, FIREBASE_CREDENTIALS, GOOGLE_PLAY_KEY_FILE, APPSTORE_SHARED_SECRET
    - Add MEALPLAN_RATE_LIMIT, MEALPLAN_RATE_LIMIT_WINDOW, REGENERATE_RATE_LIMIT, REGENERATE_RATE_LIMIT_WINDOW
    - _Requirements: 24_

  - [ ] 1.3 Create tier thresholds config file
    - Create `config/tiers.php` with default international thresholds and country-specific thresholds (PH as initial)
    - _Requirements: 5, 24.6_

  - [ ] 1.4 Create `config/budgetbite.php` for app-specific config
    - Rate limit values, OpenAI model name, free tier day limit, basic meal threshold multiplier
    - All values read from env with sensible defaults
    - _Requirements: 24.5, 24.6_

- [ ] 2. Database migrations and Eloquent models
  - [ ] 2.1 Modify users migration to add Firebase fields
    - Add `firebase_uid` (string, unique, indexed), make `email` nullable, rename `name` to `display_name`, add `country` (nullable), make `password` nullable
    - Keep backward compatibility with existing Livewire auth
    - _Requirements: 17.1_

  - [ ] 2.2 Create meal_plans migration
    - id (UUID primary key), user_id (FK to users), request_json (JSON), days_json (JSON), total_cost (decimal 10,2), remaining_budget (decimal 10,2), detected_tier (string), timestamps
    - _Requirements: 17.2_

  - [ ] 2.3 Create subscriptions migration
    - id, user_id (FK, unique), product_id (nullable string), platform (nullable string), status (string, default 'none'), receipt (text, nullable), expires_at (nullable timestamp), purchased_at (nullable timestamp), timestamps
    - _Requirements: 17.3_

  - [ ] 2.4 Create fcm_tokens migration
    - id, user_id (FK), token (string, indexed), platform (string), timestamps
    - Unique constraint on [user_id, token]
    - _Requirements: 17.4, 17.5_

  - [ ] 2.5 Update User model
    - Add firebase_uid, display_name, country to fillable
    - Add relationships: mealPlans (hasMany), subscription (hasOne), fcmTokens (hasMany)
    - Keep existing Livewire auth compatibility
    - _Requirements: 17.1_

  - [ ] 2.6 Create MealPlan model
    - Use HasUuids trait, cast request_json and days_json as array, total_cost and remaining_budget as decimal
    - BelongsTo user relationship
    - _Requirements: 17.2, 23_

  - [ ] 2.7 Create Subscription model
    - Cast expires_at and purchased_at as datetime
    - BelongsTo user, isActive() helper method checking status and expiry
    - _Requirements: 17.3_

  - [ ] 2.8 Create FcmToken model
    - BelongsTo user relationship
    - _Requirements: 17.4_

- [ ] 3. Firebase Auth Middleware
  - [ ] 3.1 Create FirebaseAuthMiddleware
    - Extract Bearer token from Authorization header
    - Verify token using kreait/firebase-php Auth::verifyIdToken()
    - Extract firebase_uid, email, display name from token claims
    - Look up User by firebase_uid, reject with 401 if not found
    - Set authenticated user on request
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [ ] 3.2 Register middleware and API routes
    - Register FirebaseAuthMiddleware as 'firebase.auth' in bootstrap/app.php
    - Add `routes/api.php` to withRouting in bootstrap/app.php
    - Create `routes/api.php` with middleware groups
    - _Requirements: 1.5, 21_

- [ ] 4. Checkpoint — Run migrations, verify middleware registers, routes load
  - Run `php artisan migrate` and `php artisan route:list` to verify setup

- [ ] 5. User registration endpoint
  - [ ] 5.1 Create RegisterRequest FormRequest
    - Validate firebase_token is required string
    - _Requirements: 2.5_

  - [ ] 5.2 Create UserController with register method
    - Verify firebase_token via Firebase Admin SDK
    - Create or update User by firebase_uid with email, display_name from token claims
    - Return AuthUser JSON response (camelCase: uid, email, displayName, photoUrl, isAnonymous)
    - Handle invalid token with 401
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ] 5.3 Add POST /api/auth/register route (no auth middleware)
    - _Requirements: 21.1_

- [ ] 6. TierService
  - [ ] 6.1 Create TierService
    - detectTier(float $dailyBudgetPerPerson, string $countryCode): string
    - Read thresholds from config/tiers.php, fall back to default if country not configured
    - Return one of: extremePoverty, poor, middleClass, rich
    - Monotonic: higher budget never maps to lower tier
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 6.2 Register TierService in AppServiceProvider
    - Bind as singleton
    - _Requirements: 5_

- [ ] 7. OpenAIService
  - [ ] 7.1 Create OpenAIService
    - buildMealPlanPrompt(): construct structured prompt with all parameters (budget, daily budget, currency, days, persons, country, tier, skipped meals)
    - Include instructions for local cuisine, local prices, currency denomination, tier-appropriate meals, variety (no repeat within 2 days), budget constraint, running total constraint
    - Use gpt-4o-mini model with JSON response format
    - Parse response into DayPlan/Meal structures
    - Retry up to 3 times on rate limit (429) and server errors (500, 503) with exponential backoff
    - Retry up to 2 additional times on malformed JSON
    - Log all OpenAI API errors with request context (no PII)
    - _Requirements: 6.1–6.12, 7.1–7.4, 8.1, 19.1–19.5_

  - [ ] 7.2 Create buildRegenerateDayPrompt() method
    - Include original plan context, instruct different meals from original day
    - Respect original budget constraints
    - _Requirements: 9.2_

- [ ] 8. MealPlanService
  - [ ] 8.1 Create MealPlanService
    - generatePlan(): validate subscription status, calculate per-person daily budget, detect tier (or use preferred), call OpenAIService, validate response, build MealPlan, store in DB
    - calculateDailyBudget(): totalBudget / (days * persons)
    - Enforce free tier: reject numberOfDays > 1 for non-subscribers
    - Ensure totalCost <= totalBudget, remainingBudget >= 0
    - Ensure day count matches request
    - Ensure totalCost == sum of dailyCosts
    - Assign dates: startDate + dayIndex for each DayPlan
    - Handle basic meal fallback: flag warning when budget below extremePoverty threshold
    - _Requirements: 3.1–3.4, 4 (delegated to FormRequest), 7.1–7.4, 8.1–8.2, 10.1–10.3, 22.1–22.2_

  - [ ] 8.2 Create regenerateDay() method
    - Retrieve existing MealPlan, verify ownership
    - Call OpenAIService.regenerateDay with original plan context
    - Update days_json, recalculate totalCost and remainingBudget
    - Save updated MealPlan
    - _Requirements: 9.1, 9.2, 9.3_

  - [ ] 8.3 Register MealPlanService in AppServiceProvider
    - _Requirements: 3_

- [ ] 9. MealPlan Controller and FormRequests
  - [ ] 9.1 Create GenerateMealPlanRequest FormRequest
    - Validate: totalBudget (required, numeric, gt:0), numberOfDays (required, integer, between:1,30), numberOfPersons (required, integer, min:1), startDate (required, date, after_or_equal:today), countryCode (required, string, size:2, regex for ISO 3166-1 alpha-2), currencyCode (required, string, size:3), preferredTier (nullable, in:extremePoverty,poor,middleClass,rich), skippedMealTypes (nullable, array, each in:breakfast,lunch,dinner,meryenda)
    - _Requirements: 4.1–4.7_

  - [ ] 9.2 Create MealPlanController
    - store(): validate via FormRequest, call MealPlanService.generatePlan, return 201 with camelCase JSON
    - regenerateDay(): validate planId exists and belongs to user (404 if not), validate dayIndex in range (422 if not), call MealPlanService.regenerateDay, return 200 with DayPlan JSON
    - _Requirements: 3.5, 9.1–9.6, 18.1–18.6_

  - [ ] 9.3 Add meal plan API routes (authenticated)
    - POST /api/meal-plans
    - POST /api/meal-plans/{planId}/days/{dayIndex}/regenerate
    - _Requirements: 21.2, 21.3_

- [ ] 10. Checkpoint — Verify meal plan generation flow end-to-end
  - Test with a mock/real OpenAI call, verify JSON response structure

- [ ] 11. SubscriptionService
  - [ ] 11.1 Create SubscriptionService
    - verifyReceipt(): verify with Google Play or App Store API based on platform, create/update Subscription record
    - restorePurchase(): re-verify receipt, update subscription
    - getStatus(): return current subscription, check expiry
    - processWebhookEvent(): handle renewal (set active, extend expires_at), cancellation (set cancelled), expiration (set expired)
    - isActive(): check status == active and expires_at in future
    - _Requirements: 11.1–11.4, 12.1–12.3, 13.1–13.3, 14.1–14.5_

  - [ ] 11.2 Register SubscriptionService in AppServiceProvider
    - _Requirements: 11_

- [ ] 12. Subscription Controller and FormRequests
  - [ ] 12.1 Create VerifySubscriptionRequest and RestoreSubscriptionRequest FormRequests
    - Validate: receipt (required, string), platform (required, in:android,ios)
    - _Requirements: 11.1, 13.1_

  - [ ] 12.2 Create SubscriptionController
    - verify(): call SubscriptionService.verifyReceipt, return SubscriptionInfo JSON (camelCase)
    - status(): call SubscriptionService.getStatus, return SubscriptionInfo JSON
    - restore(): call SubscriptionService.restorePurchase, return SubscriptionInfo JSON
    - _Requirements: 11.4, 12.1–12.3, 13.1–13.3_

  - [ ] 12.3 Add subscription API routes (authenticated)
    - POST /api/subscriptions/verify
    - GET /api/subscriptions/status
    - POST /api/subscriptions/restore
    - _Requirements: 21.4, 21.5, 21.6_

- [ ] 13. Webhook Controller
  - [ ] 13.1 Create WebhookController
    - googlePlay(): validate webhook signature, extract event, call SubscriptionService.processWebhookEvent
    - appStore(): validate App Store Server Notifications V2 payload, extract event, call SubscriptionService.processWebhookEvent
    - Return 200 on success, 400 on invalid signature/payload
    - _Requirements: 14.1, 14.2, 14.3, 14.4_

  - [ ] 13.2 Add webhook routes (no auth middleware, signature validation only)
    - POST /api/webhooks/google-play
    - POST /api/webhooks/app-store
    - _Requirements: 21.9, 21.10_

  - [ ] 13.3 Create subscription reconciliation scheduled command
    - Artisan command that polls store APIs for subscription status updates
    - Schedule in `routes/console.php` to run periodically
    - _Requirements: 14.5_

- [ ] 14. FCM Token Controller
  - [ ] 14.1 Create StoreFcmTokenRequest and DestroyFcmTokenRequest FormRequests
    - Store: token (required, string), platform (required, in:android,ios)
    - Destroy: token (required, string)
    - _Requirements: 15.1, 15.3_

  - [ ] 14.2 Create FcmTokenController
    - store(): create or update FcmToken for authenticated user (upsert on user_id + token)
    - destroy(): delete FcmToken by token for authenticated user
    - _Requirements: 15.1, 15.2, 15.3, 15.4_

  - [ ] 14.3 Add FCM token API routes (authenticated)
    - POST /api/fcm-tokens
    - DELETE /api/fcm-tokens
    - _Requirements: 21.7, 21.8_

- [ ] 15. Rate Limiting
  - [ ] 15.1 Configure rate limiters in AppServiceProvider
    - 'meal-plan-generate': configurable per-user limit (default 10/hour) from config
    - 'meal-plan-regenerate': configurable per-user limit (default 20/hour) from config
    - 'api-general': general API rate limit for all endpoints
    - _Requirements: 16.1, 16.2, 16.3, 16.4_

  - [ ] 15.2 Apply rate limiters to routes
    - Apply 'meal-plan-generate' throttle to POST /api/meal-plans
    - Apply 'meal-plan-regenerate' throttle to regenerate endpoint
    - Apply 'api-general' throttle to all API routes
    - Ensure 429 response includes Retry-After header
    - _Requirements: 16.1, 16.2, 16.3, 16.4_

- [ ] 16. JSON Response Formatting
  - [ ] 16.1 Create API Resource classes for camelCase responses
    - MealPlanResource: transforms MealPlan model to camelCase JSON matching Flutter contract
    - DayPlanResource: transforms day plan array to camelCase
    - SubscriptionInfoResource: transforms Subscription to camelCase SubscriptionInfo
    - AuthUserResource: transforms User to camelCase AuthUser
    - _Requirements: 18.1–18.6, 23.4_

  - [ ] 16.2 Configure global exception handler for API routes
    - Return JSON for all API exceptions (not Blade error pages)
    - 401: `{ "message": "..." }`
    - 403: `{ "message": "..." }`
    - 422: `{ "message": "...", "errors": { ... } }`
    - 429: `{ "message": "..." }`
    - 500: `{ "message": "An unexpected error occurred." }` (no internal details)
    - 503: `{ "message": "..." }`
    - _Requirements: 18.1–18.6, 19.4, 20.1_

- [ ] 17. Logging and Security
  - [ ] 17.1 Create API request logging middleware
    - Log method, path, authenticated user ID, response status code
    - Never log sensitive data (API keys, tokens, receipts, passwords)
    - _Requirements: 25.1, 25.5_

  - [ ] 17.2 Configure CORS for API routes
    - Update `config/cors.php` or middleware to allow only authorized origins
    - _Requirements: 20.4_

  - [ ] 17.3 Add OpenAI call logging in OpenAIService
    - Log request parameters (excluding PII) and response status
    - Log webhook events with type and processing result
    - _Requirements: 25.2, 25.3_

- [ ] 18. Checkpoint — Verify all API endpoints work, rate limiting active, error responses correct
  - Run `php artisan route:list --path=api` to verify all routes
  - Test error response formats

- [ ] 19. Public Landing Page
  - [ ] 19.1 Create landing page Blade template
    - Create `resources/views/landing.blade.php`
    - Hero section: "Food Budget: Survival Mode" title, Bitey mascot placeholder, tagline about AI-powered meal planning within budget
    - Features section: AI meal suggestions, budget-aware planning, country-specific cuisine, economic tier detection, multi-day planning
    - "How It Works" section: 4 steps (set budget → choose days & persons → AI generates plan → follow your meal plan)
    - Download section: Google Play Store and Apple App Store badge links (placeholder URLs)
    - Footer: privacy policy and terms of service links (placeholder routes)
    - Responsive and mobile-friendly using Tailwind CSS (already in project)
    - Light pastel theme with soft accent colors matching Flutter app branding
    - No JavaScript framework required
    - _Requirements: 26.1–26.9, 26.11, 26.12_

  - [ ] 19.2 Add SEO meta tags
    - Title, description, Open Graph tags optimized for "food budget planner", "meal planning app", "survival mode food budget"
    - _Requirements: 26.10_

  - [ ] 19.3 Update web route to serve landing page
    - Change `GET /` to return the landing Blade template instead of the default welcome view
    - Keep existing auth/dashboard routes untouched
    - _Requirements: 26.1_

  - [ ] 19.4 Create placeholder routes for privacy policy and terms of service
    - GET /privacy-policy and GET /terms-of-service returning simple Blade views
    - _Requirements: 26.11_

- [ ] 20. Final checkpoint — Full integration verification
  - Verify all API routes respond correctly
  - Verify landing page renders and is responsive
  - Verify rate limiting, error handling, and auth middleware
  - Run `php artisan test` to ensure existing tests still pass

## Notes

- The existing Livewire/Volt web auth system remains untouched — API auth is separate via Firebase middleware
- The User model is extended (not replaced) to support both web auth and Firebase API auth
- SQLite works for development; production should use MySQL or PostgreSQL as specified in requirements
- Store API integration (Google Play, App Store) for receipt verification will need real credentials for production; stub implementations are acceptable for initial development
- Firebase service account JSON file must be obtained from Firebase Console and path set in FIREBASE_CREDENTIALS env var
- OpenAI API key must be obtained from OpenAI platform and set in OPENAI_API_KEY env var
