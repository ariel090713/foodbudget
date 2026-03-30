# Requirements Document

## Introduction

Food Budget: Survival Mode Laravel API is the server-side backend for the Food Budget: Survival Mode Flutter mobile app. The Laravel application is responsible for Firebase token verification, user management, AI-powered meal plan generation via OpenAI GPT (gpt-4o-mini), meal plan storage in MySQL/PostgreSQL, subscription management (receipt verification, webhook processing, status tracking for Google Play and App Store), FCM token registration, rate limiting, economic tier detection, and free tier enforcement. The Flutter app communicates with this backend exclusively via authenticated REST API endpoints, sending a Firebase ID token as a Bearer token on every request. The backend never exposes the OpenAI API key to the client. The Laravel application also serves a single-page public landing page for the app. This requirements document is self-contained and can be used independently in a Laravel project workspace.

## Glossary

- **Laravel_Backend**: The Laravel application serving the Food Budget: Survival Mode REST API and landing page
- **Firebase_Admin_SDK**: The kreait/firebase-php package used to verify Firebase ID tokens server-side
- **OpenAI_Client**: The openai-php/client package used to call the OpenAI GPT API for meal plan generation
- **Auth_Middleware**: The Laravel middleware that verifies Firebase ID tokens on incoming requests and resolves the authenticated user
- **User_Controller**: The controller handling user registration and profile sync via Firebase ID tokens
- **MealPlan_Controller**: The controller handling meal plan generation and day regeneration requests
- **Subscription_Controller**: The controller handling subscription verification, status, and restore endpoints
- **Webhook_Controller**: The controller processing incoming webhooks from Google Play and App Store for subscription events
- **MealPlan_Service**: The service orchestrating meal plan generation: validation, subscription check, budget calculation, tier detection, OpenAI prompt building, response parsing, and storage
- **OpenAI_Service**: The service responsible for building structured prompts and calling the OpenAI GPT API
- **Subscription_Service**: The service responsible for receipt verification with store APIs and subscription record management
- **Tier_Service**: The service responsible for mapping per-person daily budgets to economic tiers using country-specific thresholds
- **User**: The Eloquent model representing a registered user (synced from Firebase)
- **MealPlan**: The Eloquent model representing a stored meal plan with request parameters and generated days
- **Subscription**: The Eloquent model representing a user's subscription record
- **FcmToken**: The Eloquent model representing a registered FCM device token
- **EconomicTier**: A classification of budget level (extremePoverty, poor, middleClass, rich) based on per-person daily budget and country-specific thresholds
- **MealType**: One of four meal categories: breakfast, lunch, dinner, meryenda
- **Firebase_ID_Token**: A JWT issued by Firebase Auth, sent by the Flutter app as a Bearer token to authenticate all API requests
- **DayPlan**: A single day's set of meals within a meal plan, containing dayIndex, date, meals array, and dailyCost
- **Meal**: A single meal entry with type, name, description, ingredients, estimatedCost, isSkipped, and isBasicMeal fields

## Requirements

### Requirement 1: Firebase Token Authentication Middleware

**User Story:** As the Flutter app, I want all API requests authenticated via Firebase ID tokens, so that only verified users can access the backend.

#### Acceptance Criteria

1. WHEN an API request is received with a Bearer token in the Authorization header, THE Auth_Middleware SHALL verify the token using the Firebase_Admin_SDK and extract the Firebase UID, email, and display name from the token claims
2. WHEN an API request is received without a Bearer token or with an invalid/expired token, THE Auth_Middleware SHALL reject the request with a 401 Unauthorized JSON response containing an error message
3. WHEN a valid Firebase ID token is verified, THE Auth_Middleware SHALL resolve the corresponding User record from the database (by firebase_uid) and make the authenticated user available to downstream controllers
4. WHEN a valid token is presented but no User record exists in the database, THE Auth_Middleware SHALL reject the request with a 401 response (user must register first via /api/auth/register)
5. THE Auth_Middleware SHALL apply to all API routes except POST /api/auth/register and webhook endpoints

### Requirement 2: User Registration and Sync

**User Story:** As a Flutter app user, I want to register/sync my Firebase account with the Laravel backend, so that the backend can associate my data with my Firebase identity.

#### Acceptance Criteria

1. WHEN a POST /api/auth/register request is received with a valid firebase_token in the request body, THE User_Controller SHALL verify the token using the Firebase_Admin_SDK and create or update a User record with the firebase_uid, email, and display_name extracted from the token claims
2. WHEN the user already exists (matched by firebase_uid), THE User_Controller SHALL update the email and display_name fields and return the existing user profile
3. WHEN the user is new, THE User_Controller SHALL create a new User record and return the user profile
4. THE User_Controller SHALL return a JSON response matching the AuthUser contract: `{ "uid": string, "email": string|null, "displayName": string|null, "photoUrl": string|null, "isAnonymous": boolean }`
5. WHEN the firebase_token is missing or invalid, THE User_Controller SHALL return a 401 Unauthorized JSON response


### Requirement 3: Meal Plan Generation

**User Story:** As a Flutter app user, I want to generate a complete day-by-day meal plan via the API, so that the backend handles AI generation, validation, and storage.

#### Acceptance Criteria

1. WHEN a POST /api/meal-plans request is received with valid parameters (totalBudget, currencyCode, numberOfDays, numberOfPersons, startDate, countryCode, preferredTier, skippedMealTypes), THE MealPlan_Service SHALL validate the input, check subscription status, calculate the per-person daily budget, detect the economic tier, build a structured prompt, call the OpenAI_Client, parse the response, store the plan, and return the MealPlan JSON
2. THE MealPlan_Service SHALL calculate the per-person daily budget as totalBudget divided by the product of numberOfDays and numberOfPersons
3. THE MealPlan_Service SHALL ensure the calculated per-person daily budget is greater than zero for all valid inputs
4. THE MealPlan_Service SHALL ensure that the product of the per-person daily budget, numberOfDays, and numberOfPersons does not exceed the totalBudget (accounting for floating-point precision)
5. THE MealPlan_Controller SHALL return a JSON response matching the MealPlan contract: `{ "id": string, "userId": string, "request": MealPlanRequest, "days": [DayPlan], "totalCost": number, "remainingBudget": number, "detectedTier": string, "createdAt": ISO8601, "updatedAt": ISO8601 }`
6. THE MealPlan_Service SHALL ensure the returned MealPlan contains exactly the requested numberOfDays DayPlan entries
7. THE MealPlan_Service SHALL ensure the totalCost of the generated MealPlan does not exceed the requested totalBudget
8. THE MealPlan_Service SHALL calculate remainingBudget as totalBudget minus totalCost, and remainingBudget SHALL be greater than or equal to zero
9. THE MealPlan_Service SHALL ensure totalCost equals the sum of all DayPlan dailyCost values
10. THE MealPlan_Service SHALL store the generated MealPlan in the database with request_json (original request parameters) and days_json (generated day plans)

### Requirement 4: Server-Side Input Validation

**User Story:** As the backend, I want to validate all meal plan request parameters server-side, so that invalid requests are rejected regardless of client-side validation.

#### Acceptance Criteria

1. WHEN a meal plan request has totalBudget less than or equal to zero, THE MealPlan_Controller SHALL reject the request with a 422 Unprocessable Entity response and a field-level validation error for totalBudget
2. WHEN a meal plan request has numberOfDays less than 1 or greater than 30, THE MealPlan_Controller SHALL reject the request with a 422 response and a field-level validation error for numberOfDays
3. WHEN a meal plan request has numberOfPersons less than 1, THE MealPlan_Controller SHALL reject the request with a 422 response and a field-level validation error for numberOfPersons
4. WHEN a meal plan request has a startDate in the past, THE MealPlan_Controller SHALL reject the request with a 422 response and a field-level validation error for startDate
5. WHEN a meal plan request has an invalid countryCode (not a valid ISO 3166-1 alpha-2 code), THE MealPlan_Controller SHALL reject the request with a 422 response and a field-level validation error for countryCode
6. WHEN a meal plan request has an invalid preferredTier value, THE MealPlan_Controller SHALL reject the request with a 422 response and a field-level validation error for preferredTier
7. WHEN a meal plan request has invalid skippedMealTypes values, THE MealPlan_Controller SHALL reject the request with a 422 response and a field-level validation error for skippedMealTypes

### Requirement 5: Economic Tier Detection

**User Story:** As the backend, I want to detect the user's economic tier based on their per-person daily budget and country, so that the AI generates appropriately priced meals.

#### Acceptance Criteria

1. WHEN a per-person daily budget is calculated, THE Tier_Service SHALL map the budget to exactly one EconomicTier (extremePoverty, poor, middleClass, rich) using country-specific threshold ranges
2. WHEN the user provides a preferredTier in the request, THE Tier_Service SHALL use the preferred tier instead of auto-detection
3. WHEN two daily budgets A and B are compared for the same country where A is less than B, THE Tier_Service SHALL assign a tier to A that is equal to or lower than the tier assigned to B (monotonicity property)
4. WHEN a country has no configured tier thresholds, THE Tier_Service SHALL fall back to default international thresholds
5. THE Tier_Service SHALL support configurable country-specific thresholds (e.g., Philippines: extremePoverty < ₱100/person/day, poor ₱100–₱250, middleClass ₱250–₱800, rich > ₱800)


### Requirement 6: OpenAI GPT Prompt Construction and Response Parsing

**User Story:** As the backend, I want to build structured prompts for OpenAI GPT and parse the JSON responses, so that meal plans are generated correctly and reliably.

#### Acceptance Criteria

1. WHEN building the OpenAI prompt, THE OpenAI_Service SHALL include the totalBudget, per-person daily budget, currencyCode, numberOfDays, numberOfPersons, startDate, countryCode, economicTier, and skippedMealTypes in the structured prompt
2. WHEN building the prompt, THE OpenAI_Service SHALL instruct the OpenAI GPT API to generate meals using local cuisine and realistic local market prices for the specified countryCode
3. WHEN building the prompt, THE OpenAI_Service SHALL instruct the OpenAI GPT API to use prices denominated in the specified currencyCode
4. WHEN building the prompt, THE OpenAI_Service SHALL instruct the OpenAI GPT API to select meals appropriate for the detected EconomicTier
5. WHEN building the prompt, THE OpenAI_Service SHALL instruct the OpenAI GPT API to avoid repeating the same meal for the same meal type within the previous two days
6. WHEN building the prompt, THE OpenAI_Service SHALL instruct the OpenAI GPT API to ensure the total cost of all meals does not exceed the totalBudget
7. WHEN building the prompt, THE OpenAI_Service SHALL instruct the OpenAI GPT API to ensure the running total cost never exceeds the totalBudget at any point in the plan
8. THE OpenAI_Service SHALL call the OpenAI GPT API using the gpt-4o-mini model with JSON response format enabled
9. WHEN the OpenAI GPT API returns a JSON response, THE OpenAI_Service SHALL parse the response into DayPlan and Meal structures matching the Flutter app's expected contract
10. WHEN the OpenAI GPT API returns a malformed or incomplete JSON response, THE OpenAI_Service SHALL retry the API call up to 2 additional times before returning an error
11. THE OpenAI_Service SHALL format each DayPlan with: dayIndex (0-based), date (startDate + dayIndex days as ISO8601), meals array, and dailyCost
12. THE OpenAI_Service SHALL format each Meal with: type (breakfast|lunch|dinner|meryenda), name, description, ingredients array, estimatedCost, isSkipped boolean, and isBasicMeal boolean

### Requirement 7: Meal Type Handling

**User Story:** As the backend, I want to handle meal type skipping correctly, so that skipped meals have zero cost and the freed budget is allocated to active meals.

#### Acceptance Criteria

1. WHEN a DayPlan is generated without skipped meal types, THE OpenAI_Service SHALL instruct the OpenAI GPT API to include exactly one Meal entry for each of the four MealType values: breakfast, lunch, dinner, and meryenda
2. WHEN the user specifies skippedMealTypes in the request, THE OpenAI_Service SHALL instruct the OpenAI GPT API to mark those meals as skipped with estimatedCost of zero, an empty ingredients list, and isSkipped set to true
3. WHEN meal types are skipped, THE MealPlan_Service SHALL ensure a Meal entry still exists for each skipped type in the response
4. WHEN meal types are skipped, THE OpenAI_Service SHALL instruct the OpenAI GPT API to allocate the freed budget to the remaining active meal types

### Requirement 8: Basic Meal Fallback

**User Story:** As the backend, I want to handle extremely tight budgets by generating basic fallback meals, so that users always receive a usable plan.

#### Acceptance Criteria

1. WHEN the per-person daily budget is extremely low (below the extremePoverty threshold), THE OpenAI_Service SHALL instruct the OpenAI GPT API to generate basic fallback meals (rice with salt, instant noodles, water) and mark those meals with isBasicMeal set to true
2. WHEN basic meal fallback is triggered, THE MealPlan_Controller SHALL include a warning flag in the response indicating the budget is extremely tight

### Requirement 9: Day Regeneration

**User Story:** As a Flutter app user, I want to regenerate meals for a specific day in my plan, so that I get alternative suggestions without regenerating the entire plan.

#### Acceptance Criteria

1. WHEN a POST /api/meal-plans/{planId}/days/{dayIndex}/regenerate request is received, THE MealPlan_Service SHALL retrieve the existing MealPlan from the database, call the OpenAI_Client to generate a new DayPlan for the specified dayIndex while respecting the original plan's budget constraints
2. WHEN a day is regenerated, THE OpenAI_Service SHALL instruct the OpenAI GPT API to provide different meals from the original day's meals
3. WHEN a day is regenerated, THE MealPlan_Service SHALL update the stored MealPlan in the database with the new DayPlan, recalculate totalCost and remainingBudget, and return the new DayPlan JSON
4. THE MealPlan_Controller SHALL return a JSON response matching the DayPlan contract: `{ "dayIndex": int, "date": ISO8601, "meals": [Meal], "dailyCost": number }`
5. WHEN the planId does not exist or does not belong to the authenticated user, THE MealPlan_Controller SHALL return a 404 Not Found response
6. WHEN the dayIndex is out of range for the plan, THE MealPlan_Controller SHALL return a 422 Unprocessable Entity response


### Requirement 10: Free Tier Enforcement

**User Story:** As the backend, I want to enforce free tier limits, so that non-subscribers can only generate 1-day meal plans.

#### Acceptance Criteria

1. WHILE the user's subscription status is not active, THE MealPlan_Service SHALL restrict meal plan generation to 1-day plans only
2. WHEN a non-subscribed user requests a meal plan with numberOfDays greater than 1, THE MealPlan_Controller SHALL reject the request with a 403 Forbidden JSON response containing a message indicating subscription is required for multi-day plans
3. WHILE the user's subscription status is active, THE MealPlan_Service SHALL allow meal plan generation for 1 to 30 days

### Requirement 11: Subscription Receipt Verification

**User Story:** As a Flutter app user, I want the backend to verify my subscription receipt with the store, so that my subscription status is accurately tracked.

#### Acceptance Criteria

1. WHEN a POST /api/subscriptions/verify request is received with receipt and platform (android or ios), THE Subscription_Service SHALL verify the receipt with the respective store API (Google Play Developer API or Apple App Store Server API)
2. WHEN the receipt is valid, THE Subscription_Service SHALL create or update the Subscription record in the database with product_id, platform, status set to active, receipt data, expires_at, and purchased_at
3. WHEN the receipt is invalid or verification fails, THE Subscription_Service SHALL return a SubscriptionInfo response with status set to none
4. THE Subscription_Controller SHALL return a JSON response matching the SubscriptionInfo contract: `{ "userId": string, "status": string, "productId": string|null, "platform": string|null, "expiresAt": ISO8601|null, "purchasedAt": ISO8601|null }`

### Requirement 12: Subscription Status Retrieval

**User Story:** As a Flutter app user, I want to check my current subscription status, so that the app can determine which features are available.

#### Acceptance Criteria

1. WHEN a GET /api/subscriptions/status request is received, THE Subscription_Controller SHALL return the authenticated user's current subscription status from the database
2. WHEN the user has no subscription record, THE Subscription_Controller SHALL return a SubscriptionInfo response with status set to none
3. WHEN the subscription has expired (expires_at is in the past), THE Subscription_Service SHALL return status as expired

### Requirement 13: Purchase Restoration

**User Story:** As a Flutter app user, I want to restore my previous purchases, so that my subscription is recognized on a new device or after reinstalling.

#### Acceptance Criteria

1. WHEN a POST /api/subscriptions/restore request is received with receipt and platform, THE Subscription_Service SHALL re-verify the receipt with the respective store API and update the subscription record accordingly
2. WHEN the restored receipt is valid and the subscription is still active, THE Subscription_Service SHALL update the Subscription record and return status as active
3. WHEN the restored receipt is invalid or the subscription has expired, THE Subscription_Service SHALL return the appropriate status (none or expired)

### Requirement 14: Subscription Webhook Processing

**User Story:** As the backend, I want to process webhooks from Google Play and App Store, so that subscription status is updated in real time when renewals, cancellations, or expirations occur.

#### Acceptance Criteria

1. WHEN a webhook is received from Google Play (via POST /api/webhooks/google-play), THE Webhook_Controller SHALL validate the webhook signature, extract the subscription event, and update the corresponding Subscription record in the database
2. WHEN a webhook is received from App Store (via POST /api/webhooks/app-store), THE Webhook_Controller SHALL validate the webhook payload using the App Store Server Notifications V2 format and update the corresponding Subscription record
3. WHEN a renewal webhook is received, THE Subscription_Service SHALL update the subscription status to active and extend the expires_at date
4. WHEN a cancellation or expiration webhook is received, THE Subscription_Service SHALL update the subscription status to cancelled or expired respectively
5. WHEN webhook processing fails or is delayed, THE Subscription_Service SHALL support a scheduled job that periodically polls store APIs for subscription status reconciliation


### Requirement 15: FCM Token Management

**User Story:** As the backend, I want to store FCM device tokens, so that push notifications can be sent to users' devices.

#### Acceptance Criteria

1. WHEN a POST /api/fcm-tokens request is received with token and platform, THE Laravel_Backend SHALL create or update the FcmToken record for the authenticated user, associating the token with the user and platform
2. WHEN the same token already exists for the user, THE Laravel_Backend SHALL update the existing record rather than creating a duplicate
3. WHEN a DELETE /api/fcm-tokens request is received with a token, THE Laravel_Backend SHALL remove the FcmToken record for the authenticated user
4. THE Laravel_Backend SHALL support multiple FCM tokens per user (one per device)

### Requirement 16: Rate Limiting

**User Story:** As the backend, I want to rate limit meal plan generation endpoints, so that the API is protected from abuse and excessive OpenAI API costs.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL apply rate limiting to the POST /api/meal-plans endpoint, restricting each authenticated user to a configurable maximum number of requests per time window (e.g., 10 requests per hour)
2. THE Laravel_Backend SHALL apply rate limiting to the POST /api/meal-plans/{planId}/days/{dayIndex}/regenerate endpoint with a configurable limit
3. WHEN a user exceeds the rate limit, THE Laravel_Backend SHALL return a 429 Too Many Requests JSON response with a Retry-After header indicating when the user can retry
4. THE Laravel_Backend SHALL apply a general rate limit to all API endpoints to prevent brute-force attacks

### Requirement 17: Database Schema

**User Story:** As a developer, I want a well-structured database schema, so that all application data is stored reliably and efficiently.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL create a users table with columns: id (auto-increment), firebase_uid (unique, indexed), email (nullable), display_name (nullable), country (nullable), created_at, updated_at
2. THE Laravel_Backend SHALL create a meal_plans table with columns: id (UUID primary key), user_id (foreign key to users), request_json (JSON column storing the original MealPlanRequest), days_json (JSON column storing the generated DayPlan array), total_cost (decimal), remaining_budget (decimal), detected_tier (string enum), created_at, updated_at
3. THE Laravel_Backend SHALL create a subscriptions table with columns: id (auto-increment), user_id (foreign key to users, unique), product_id (nullable string), platform (nullable string: android or ios), status (string enum: active, expired, cancelled, none), receipt (text, nullable), expires_at (nullable timestamp), purchased_at (nullable timestamp), created_at, updated_at
4. THE Laravel_Backend SHALL create an fcm_tokens table with columns: id (auto-increment), user_id (foreign key to users), token (string, indexed), platform (string: android or ios), created_at, updated_at
5. THE Laravel_Backend SHALL enforce a unique constraint on fcm_tokens for the combination of user_id and token

### Requirement 18: API Response Format Consistency

**User Story:** As the Flutter app, I want all API responses to follow a consistent JSON format, so that the app can reliably parse responses and errors.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL return all successful responses as JSON with appropriate HTTP status codes (200 for retrieval, 201 for creation)
2. THE Laravel_Backend SHALL return all error responses as JSON with the structure: `{ "message": string, "errors": { field: [messages] } }` for validation errors (422)
3. THE Laravel_Backend SHALL return authentication errors as JSON with the structure: `{ "message": string }` and HTTP status 401
4. THE Laravel_Backend SHALL return authorization errors (subscription required) as JSON with the structure: `{ "message": string }` and HTTP status 403
5. THE Laravel_Backend SHALL return rate limit errors as JSON with the structure: `{ "message": string }` and HTTP status 429
6. THE Laravel_Backend SHALL return server errors as JSON with the structure: `{ "message": string }` and HTTP status 500, without exposing internal error details

### Requirement 19: OpenAI API Error Handling and Retry Logic

**User Story:** As the backend, I want to handle OpenAI API failures gracefully with retry logic, so that transient errors do not cause meal plan generation to fail unnecessarily.

#### Acceptance Criteria

1. IF the OpenAI GPT API returns a rate limit error (429), THEN THE OpenAI_Service SHALL retry the request with exponential backoff up to 3 attempts
2. IF the OpenAI GPT API returns a server error (500, 503) or times out, THEN THE OpenAI_Service SHALL retry the request with exponential backoff up to 3 attempts
3. IF the OpenAI GPT API returns a malformed JSON response that fails parsing, THEN THE OpenAI_Service SHALL retry the API call up to 2 additional times
4. IF all retry attempts are exhausted, THEN THE MealPlan_Controller SHALL return a 503 Service Unavailable JSON response with a message indicating meal plan generation is temporarily unavailable
5. THE OpenAI_Service SHALL log all OpenAI API errors with request context for debugging


### Requirement 20: Security

**User Story:** As the backend, I want to follow security best practices, so that user data and API keys are protected.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL store the OpenAI API key, Firebase service account credentials, and store API keys securely in environment variables and never expose them in API responses or logs
2. THE Laravel_Backend SHALL validate and sanitize all user inputs on the server side using Laravel's validation framework
3. THE Laravel_Backend SHALL enforce HTTPS for all API communication
4. THE Laravel_Backend SHALL configure CORS to allow requests only from authorized origins
5. THE Laravel_Backend SHALL never include the OpenAI API key, Firebase credentials, or database credentials in any API response

### Requirement 21: API Route Structure

**User Story:** As a developer, I want a clear and consistent API route structure, so that the Flutter app can call the correct endpoints.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL expose POST /api/auth/register for user registration (no auth middleware)
2. THE Laravel_Backend SHALL expose POST /api/meal-plans for meal plan generation (authenticated)
3. THE Laravel_Backend SHALL expose GET /api/meal-plans for listing the authenticated user's saved meal plans (authenticated)
4. THE Laravel_Backend SHALL expose DELETE /api/meal-plans/{planId} for deleting a meal plan owned by the authenticated user (authenticated)
5. THE Laravel_Backend SHALL expose POST /api/meal-plans/{planId}/days/{dayIndex}/regenerate for day regeneration (authenticated)
6. THE Laravel_Backend SHALL expose POST /api/subscriptions/verify for subscription receipt verification (authenticated)
7. THE Laravel_Backend SHALL expose GET /api/subscriptions/status for subscription status retrieval (authenticated)
8. THE Laravel_Backend SHALL expose POST /api/subscriptions/restore for purchase restoration (authenticated)
9. THE Laravel_Backend SHALL expose POST /api/fcm-tokens for FCM token registration (authenticated)
10. THE Laravel_Backend SHALL expose DELETE /api/fcm-tokens for FCM token removal (authenticated)
11. THE Laravel_Backend SHALL expose POST /api/webhooks/google-play for Google Play webhook processing (no auth middleware, webhook signature validation)
12. THE Laravel_Backend SHALL expose POST /api/webhooks/app-store for App Store webhook processing (no auth middleware, webhook payload validation)

### Requirement 22: Date Sequence Integrity

**User Story:** As the Flutter app, I want each day in the meal plan to have the correct calendar date and index, so that the app can display the plan day by day.

#### Acceptance Criteria

1. THE MealPlan_Service SHALL assign each DayPlan a date equal to the startDate plus the dayIndex in days (ISO 8601 format)
2. THE MealPlan_Service SHALL assign each DayPlan a zero-based dayIndex matching its position in the plan's day list

### Requirement 23: MealPlan JSON Serialization

**User Story:** As a developer, I want meal plan data to be serialized to and deserialized from JSON reliably, so that stored plans are always retrievable without data loss.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL serialize MealPlan data to JSON for storage in the days_json and request_json database columns
2. THE Laravel_Backend SHALL deserialize stored JSON back into equivalent MealPlan structures when retrieving plans
3. FOR ALL valid MealPlan data, serializing to JSON then deserializing SHALL produce equivalent data (round-trip property)
4. THE Laravel_Backend SHALL use camelCase field names in all JSON responses to match the Flutter app's expected contract (totalBudget, numberOfDays, numberOfPersons, startDate, countryCode, currencyCode, preferredTier, skippedMealTypes, dayIndex, dailyCost, estimatedCost, isSkipped, isBasicMeal, remainingBudget, detectedTier, createdAt, updatedAt, userId, productId, expiresAt, purchasedAt, isAnonymous, displayName, photoUrl)

### Requirement 24: Environment Configuration

**User Story:** As a developer, I want all external service credentials and configuration to be managed via environment variables, so that the application is portable and secure.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL read the OpenAI API key from the OPENAI_API_KEY environment variable
2. THE Laravel_Backend SHALL read the Firebase service account JSON path from the FIREBASE_CREDENTIALS environment variable
3. THE Laravel_Backend SHALL read the database connection details from standard Laravel environment variables (DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
4. THE Laravel_Backend SHALL read Google Play and App Store API credentials from environment variables
5. THE Laravel_Backend SHALL support configurable rate limit values via environment variables or config files
6. THE Laravel_Backend SHALL support configurable country-specific economic tier thresholds via a config file

### Requirement 25: Logging and Monitoring

**User Story:** As a developer, I want comprehensive logging, so that I can debug issues and monitor API health.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL log all incoming API requests with method, path, authenticated user ID, and response status code
2. THE Laravel_Backend SHALL log all OpenAI API calls with request parameters (excluding user PII) and response status
3. THE Laravel_Backend SHALL log all subscription webhook events with event type and processing result
4. IF an unhandled exception occurs, THEN THE Laravel_Backend SHALL log the full exception details and return a generic 500 error response without exposing internal details
5. THE Laravel_Backend SHALL never log sensitive data including API keys, Firebase tokens, subscription receipts, or user passwords

### Requirement 26: Public Landing Page

**User Story:** As a potential user visiting the app's website, I want to see an attractive landing page that explains the app and links to the app stores, so that I can learn about the app and download it.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL serve a single-page public landing page at the root URL (GET /) with no authentication required
2. THE Landing_Page SHALL use the same light theme with soft pastel accent colors as the Flutter app, maintaining consistent branding
3. THE Landing_Page SHALL display the app name "Food Budget: Survival Mode" prominently with the cute beaver mascot ("Bitey")
4. THE Landing_Page SHALL include a hero section with a tagline explaining the app's purpose (AI-powered meal planning within your budget)
5. THE Landing_Page SHALL include a features section highlighting key capabilities: AI meal suggestions, budget-aware planning, country-specific cuisine, economic tier detection, and multi-day planning
6. THE Landing_Page SHALL include download buttons/badges linking to the Google Play Store and Apple App Store listings
7. THE Landing_Page SHALL include a brief "How It Works" section with 3-4 steps explaining the user flow (set budget → choose days/persons → AI generates plan → follow your meal plan)
8. THE Landing_Page SHALL be fully responsive and mobile-friendly
9. THE Landing_Page SHALL be implemented as a Blade template served by a Laravel web route, not requiring any JavaScript framework
10. THE Landing_Page SHALL include basic SEO meta tags (title, description, Open Graph tags) optimized for "food budget planner", "meal planning app", and "survival mode food budget"
11. THE Landing_Page SHALL include a footer with links to privacy policy and terms of service pages (placeholder routes)
12. THE Landing_Page SHALL load quickly with minimal external dependencies (inline CSS or Tailwind CDN, no heavy JS frameworks)

