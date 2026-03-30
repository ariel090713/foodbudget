# Requirements Document

## Introduction

Food Budget: Survival Mode is a cross-platform Flutter mobile app that helps users plan meals within a set budget. Users input a budget amount, number of days (up to 30), number of people, and a start date. The app sends this request to a Laravel API backend, which calls the OpenAI GPT API to generate a day-by-day meal plan (breakfast, lunch, dinner, meryenda) tailored to the user's economic tier and country. The app uses Firebase for authentication (Google Sign-In and Guest/Anonymous auth), Cloud Firestore for real-time sync of meal plans across devices, Firebase Analytics for usage tracking, and Firebase Cloud Messaging for push notifications. The Laravel backend handles all AI meal generation (via OpenAI GPT), server-side data storage (MySQL/PostgreSQL), and subscription management including webhook processing from Google Play and App Store. There is no local food database — all meal suggestions are generated dynamically by the AI. Hive is retained only for local caching and offline support of previously synced meal plans. The UI follows a clean, light design theme with a cute beaver mascot named "Bitey".

## Glossary

- **Food Budget: Survival Mode**: The food budget planner mobile application
- **Laravel_Backend**: The server-side Laravel application that handles AI meal generation (via OpenAI GPT), subscription management, and data storage
- **OpenAI_GPT**: The OpenAI GPT API used by the Laravel backend to generate culturally appropriate meal suggestions
- **Firebase_Auth**: The Firebase Authentication service supporting Google Sign-In and Anonymous/Guest authentication
- **Firestore**: Cloud Firestore used for real-time synchronization of meal plans and settings across devices
- **FCM**: Firebase Cloud Messaging used for push notifications
- **Firebase_Analytics**: Firebase Analytics service for tracking user events and screen views
- **Hive_Cache**: Local Hive storage used for offline caching of previously synced meal plans and settings
- **Auth_Repository**: The component managing Firebase authentication flows (Google Sign-In, Anonymous, account linking)
- **Laravel_API_Client**: The component that communicates with the Laravel backend for meal generation, subscription verification, and user data
- **Firestore_Sync_Repository**: The component managing real-time synchronization of meal plans via Cloud Firestore
- **MealPlan_Cache_Repository**: The component responsible for local caching of meal plans in Hive for offline access
- **Settings_Repository**: The component managing user preferences including country, language, and defaults, persisted locally and synced via Firestore
- **Notification_Repository**: The component managing push notifications via Firebase Cloud Messaging
- **Analytics_Repository**: The component tracking user events and screen views via Firebase Analytics
- **MealPlan**: A complete day-by-day meal plan generated from user inputs via the Laravel backend and OpenAI GPT
- **DayPlan**: A single day's set of meals within a meal plan
- **Meal**: A single meal entry (breakfast, lunch, dinner, or meryenda) with name, description, ingredients, and estimated cost
- **EconomicTier**: A classification of budget level (Extreme Poverty, Poor, Middle Class, Rich) based on per-person daily budget and country thresholds, detected server-side
- **MealType**: One of four meal categories: breakfast, lunch, dinner, meryenda
- **BudgetPeriod**: A time range for budgeting: today, this week, this month, or custom (max 30 days)
- **MealPlan_BLoC**: The BLoC component managing meal plan state and events
- **Subscription_BLoC**: The BLoC component managing subscription state and purchase events
- **Auth_BLoC**: The BLoC component managing authentication state and events
- **SubscriptionInfo**: Server-side subscription record containing status, product ID, platform, and expiry date
- **Firebase_ID_Token**: A JWT issued by Firebase Auth, used to authenticate all requests to the Laravel backend

## Requirements

### Requirement 1: Authentication

**User Story:** As a user, I want to sign in with Google or continue as a guest, so that I can access the app and sync my data across devices.

#### Acceptance Criteria

1. WHEN a user taps "Sign in with Google", THE Auth_Repository SHALL initiate the Google Sign-In flow and authenticate the user via Firebase_Auth, returning an AuthUser with a valid Firebase UID
2. WHEN a user taps "Continue as Guest", THE Auth_Repository SHALL sign in anonymously via Firebase_Auth, returning an AuthUser with `isAnonymous` set to true
3. WHEN authentication succeeds (Google or Anonymous), THE Auth_BLoC SHALL send the Firebase_ID_Token to the Laravel_Backend via POST /api/auth/register to sync the user profile and retrieve subscription status
4. WHEN a guest user later taps "Link to Google Account", THE Auth_Repository SHALL link the anonymous Firebase account to a Google credential, preserving the existing Firebase UID and all associated data
5. WHEN authentication fails (network error, Google Sign-In cancelled, Firebase error), THE Food Budget: Survival Mode app SHALL display a descriptive error message and offer anonymous sign-in as a fallback
6. THE Auth_Repository SHALL expose an `authStateChanges` stream that emits the current AuthUser on every authentication state change
7. WHEN a user signs out, THE Auth_Repository SHALL clear the Firebase Auth session and THE Auth_BLoC SHALL navigate to the authentication screen

### Requirement 2: Meal Plan Input Validation

**User Story:** As a user, I want the app to validate my meal plan inputs, so that I only generate plans with valid parameters.

#### Acceptance Criteria

1. WHEN a user submits a meal plan request with a budget less than or equal to zero, THEN THE MealPlan_BLoC SHALL reject the request and display a field-level validation error for the budget field
2. WHEN a user submits a meal plan request with number of days less than 1 or greater than 30, THEN THE MealPlan_BLoC SHALL reject the request and display a field-level validation error for the days field
3. WHEN a user submits a meal plan request with number of persons less than 1, THEN THE MealPlan_BLoC SHALL reject the request and display a field-level validation error for the persons field
4. WHEN a user submits a meal plan request with a start date in the past, THEN THE MealPlan_BLoC SHALL reject the request and display a field-level validation error for the date field
5. WHEN a user submits a meal plan request with an invalid country code, THEN THE MealPlan_BLoC SHALL reject the request and display a field-level validation error for the country field
6. WHEN all input fields pass validation, THEN THE MealPlan_BLoC SHALL proceed with meal plan generation by sending the request to the Laravel_Backend

### Requirement 3: Daily Budget Calculation

**User Story:** As a user, I want the app to calculate my per-person daily budget automatically, so that I can understand how much is available for each person each day.

#### Acceptance Criteria

1. WHEN a valid meal plan request is received, THE Laravel_Backend SHALL calculate the per-person daily budget as total budget divided by the product of number of days and number of persons
2. THE Laravel_Backend SHALL produce a per-person daily budget that is greater than zero for all valid inputs
3. WHEN the per-person daily budget is calculated, THE Laravel_Backend SHALL ensure that the product of the result, number of days, and number of persons does not exceed the total budget

### Requirement 4: Economic Tier Detection

**User Story:** As a user, I want the app to automatically detect my economic tier based on my budget, so that meal suggestions match my spending level.

#### Acceptance Criteria

1. WHEN a per-person daily budget is calculated, THE Laravel_Backend SHALL map the budget to exactly one EconomicTier using country-specific threshold ranges
2. WHEN the user provides a preferred tier override, THE Laravel_Backend SHALL use the preferred tier instead of auto-detection
3. WHEN two daily budgets are compared for the same country where budget A is less than budget B, THE Laravel_Backend SHALL assign a tier to budget A that is equal to or lower than the tier assigned to budget B (monotonicity)
4. WHEN a country has no configured tier thresholds, THE Laravel_Backend SHALL fall back to default international thresholds

### Requirement 5: Meal Plan Generation via Laravel and OpenAI

**User Story:** As a user, I want to generate a complete day-by-day meal plan within my budget, so that I can plan my meals affordably.

#### Acceptance Criteria

1. WHEN a valid meal plan request is submitted, THE Laravel_API_Client SHALL send the request with the Firebase_ID_Token to the Laravel_Backend via POST /api/meal-plans
2. WHEN the Laravel_Backend receives a meal plan request, THE Laravel_Backend SHALL validate the request, check subscription status, calculate the daily budget and economic tier, and call the OpenAI_GPT API with a structured prompt to generate the meal plan
3. WHEN the OpenAI_GPT API returns a response, THE Laravel_Backend SHALL parse and validate the JSON response, store the plan in the server database, and return the MealPlan to the Flutter client
4. THE Laravel_Backend SHALL ensure the generated MealPlan contains exactly the requested number of DayPlan entries
5. THE Laravel_Backend SHALL ensure the total cost of the generated MealPlan does not exceed the requested total budget
6. THE Laravel_Backend SHALL calculate the remaining budget as the total budget minus the total cost, and the remaining budget SHALL be greater than or equal to zero
7. THE Laravel_Backend SHALL ensure the total cost of the MealPlan equals the sum of all DayPlan daily costs
8. WHEN generating meals for each day, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to ensure the running total cost never exceeds the total budget at any point in the plan

### Requirement 6: Meal Type Handling

**User Story:** As a user, I want each day to include breakfast, lunch, dinner, and meryenda, so that all my daily meals are planned.

#### Acceptance Criteria

1. WHEN a DayPlan is generated without skipped meal types, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to include exactly one Meal entry for each of the four MealType values: breakfast, lunch, dinner, and meryenda
2. WHEN the user specifies meal types to skip globally, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to mark those meals as skipped with zero estimated cost and an empty ingredients list
3. WHEN meal types are skipped, THE Laravel_Backend SHALL ensure a Meal entry still exists for each skipped type with the isSkipped flag set to true
4. WHEN meal types are skipped, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to allocate the freed budget to the remaining active meal types

### Requirement 7: Country-Aware AI Meal Suggestions

**User Story:** As a user, I want meal suggestions that reflect my country's local cuisine and currency, so that the plan is relevant to where I live.

#### Acceptance Criteria

1. WHEN building the OpenAI prompt, THE Laravel_Backend SHALL include the user's country code so that the OpenAI_GPT API generates meals using local cuisine and realistic local market prices for that country
2. THE Laravel_Backend SHALL instruct the OpenAI_GPT API to select meals appropriate for the detected or preferred EconomicTier
3. WHEN generating meals, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to use prices denominated in the country's local currency
4. WHEN the user changes their country setting, THE Food Budget: Survival Mode app SHALL send the new country code with subsequent meal plan requests so that the OpenAI_GPT API adapts cuisine and pricing accordingly

### Requirement 8: Meal Variety

**User Story:** As a user, I want variety in my meal plan, so that I do not eat the same meals every day.

#### Acceptance Criteria

1. WHEN building the OpenAI prompt, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to avoid repeating the same meal for the same meal type within the previous two days
2. WHEN the OpenAI_GPT API returns a plan, THE Laravel_Backend SHALL validate that meal variety constraints are respected where feasible

### Requirement 9: Basic Meal Fallback

**User Story:** As a user with an extremely tight budget, I want the app to still provide a meal plan using very basic meals, so that I always get a usable plan.

#### Acceptance Criteria

1. WHEN the per-person daily budget is extremely low, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to generate basic fallback meals (rice with salt, instant noodles, water) and mark those meals with the isBasicMeal flag set to true
2. WHEN basic meal fallback is triggered, THE Food Budget: Survival Mode app SHALL display a warning to the user that the budget is extremely tight
3. WHEN basic meal fallback is triggered, THE Food Budget: Survival Mode app SHALL suggest the user increase the budget, reduce the number of days, or reduce the number of persons

### Requirement 10: Real-Time Sync via Firestore

**User Story:** As a user, I want my meal plans synced across all my devices in real time, so that I can access my plans from any device.

#### Acceptance Criteria

1. WHEN a MealPlan is generated, THE Firestore_Sync_Repository SHALL sync the plan to Cloud Firestore at the path `users/{userId}/meal_plans/{planId}`
2. WHEN a meal plan is added, modified, or deleted on one device, THE Firestore_Sync_Repository on other devices SHALL receive the update via real-time snapshot listeners and update the local cache and UI
3. WHEN a user deletes a meal plan, THE Firestore_Sync_Repository SHALL remove the plan document from Firestore
4. WHEN a specific day in a synced plan is updated, THE Firestore_Sync_Repository SHALL update only the affected day data in the Firestore document
5. THE Firestore_Sync_Repository SHALL sync user settings to Firestore at the path `users/{userId}/settings`
6. WHEN Firestore sync fails due to network or permission issues, THE Food Budget: Survival Mode app SHALL fall back to the local Hive_Cache and display a subtle indicator that sync is pending

### Requirement 11: Push Notifications via FCM

**User Story:** As a user, I want to receive push notifications, so that I am reminded about my meal plans and app updates.

#### Acceptance Criteria

1. WHEN the app launches for the first time, THE Notification_Repository SHALL initialize Firebase Cloud Messaging and request notification permissions from the user
2. WHEN an FCM token is obtained, THE Notification_Repository SHALL register the token with the Laravel_Backend via the API
3. WHEN a push notification is received while the app is in the foreground, THE Notification_Repository SHALL emit the notification via the `onMessage` stream for in-app display
4. WHEN a user taps a push notification (from background or terminated state), THE Notification_Repository SHALL emit the notification via the `onMessageOpenedApp` stream for deep linking

### Requirement 12: Analytics Tracking

**User Story:** As a product owner, I want to track user behavior and app usage, so that I can make data-driven decisions to improve the app.

#### Acceptance Criteria

1. WHEN a user generates a meal plan, THE Analytics_Repository SHALL log a meal plan generation event with parameters including country, tier, number of days, and number of persons
2. WHEN a user completes a subscription purchase, THE Analytics_Repository SHALL log a subscription event with the product ID and platform
3. WHEN a user navigates to a new screen, THE Analytics_Repository SHALL log a screen view event with the screen name
4. WHEN a user authenticates, THE Analytics_Repository SHALL set the user ID and user properties (country, subscription status) in Firebase_Analytics

### Requirement 13: Subscription Management via Laravel Backend

**User Story:** As a user, I want to subscribe to unlock premium features, so that I can access the full capabilities of the app.

#### Acceptance Criteria

1. WHEN a user initiates a subscription purchase, THE Food Budget: Survival Mode app SHALL use the native platform store (Google Play Billing or Apple StoreKit) to process the purchase
2. WHEN a purchase is completed, THE Laravel_API_Client SHALL send the purchase receipt and platform to the Laravel_Backend via POST /api/subscriptions/verify for server-side verification
3. WHEN the Laravel_Backend receives a receipt, THE Laravel_Backend SHALL verify the receipt with the respective store API (Google Play or App Store) and store the subscription record in the server database
4. WHEN a user requests to restore purchases, THE Laravel_API_Client SHALL send the receipt to the Laravel_Backend for re-verification and status restoration
5. WHILE the subscription is inactive, THE Laravel_Backend SHALL restrict meal plan generation to the free tier (1-day plans only) and reject requests for multi-day plans with a 403 status
6. WHEN the subscription expires, THE Subscription_BLoC SHALL update the subscription status and display the subscription paywall when the user attempts premium actions
7. WHEN Google Play or App Store sends a webhook (renewal, cancellation), THE Laravel_Backend SHALL process the webhook and update the subscription status in the server database
8. WHEN webhook processing fails or is delayed, THE Laravel_Backend SHALL periodically poll store APIs as a fallback for subscription status reconciliation

### Requirement 14: Offline Support via Hive Cache

**User Story:** As a user, I want to view my previously generated meal plans offline, so that I can access them without an internet connection.

#### Acceptance Criteria

1. WHEN a MealPlan is generated and synced, THE MealPlan_Cache_Repository SHALL cache the plan to local Hive storage for offline access
2. WHEN the device is offline, THE MealPlan_Cache_Repository SHALL serve cached meal plans from Hive
3. THE MealPlan_Cache_Repository SHALL support listing all cached meal plans
4. WHEN a user deletes a meal plan, THE MealPlan_Cache_Repository SHALL remove the plan from the local Hive cache
5. WHEN a user modifies a specific day in a cached plan, THE MealPlan_Cache_Repository SHALL update only the affected DayPlan entry while preserving the rest of the plan
6. WHEN Firestore real-time updates arrive, THE MealPlan_Cache_Repository SHALL update the local cache to reflect the latest data
7. WHEN the device has no internet connection and the user attempts to generate a new meal plan, THE Food Budget: Survival Mode app SHALL display a message stating that internet connectivity is required for meal plan generation and that previously saved plans are available offline

### Requirement 15: Day Regeneration

**User Story:** As a user, I want to regenerate meals for a specific day in my plan, so that I can get alternative suggestions without regenerating the entire plan.

#### Acceptance Criteria

1. WHEN a user requests regeneration for a specific day, THE Laravel_API_Client SHALL send a regeneration request to the Laravel_Backend, which SHALL call the OpenAI_GPT API to generate a new DayPlan for that day while respecting the original plan's budget constraints
2. WHEN a day is regenerated, THE Laravel_Backend SHALL instruct the OpenAI_GPT API to provide different meals from the original day's meals
3. WHEN a day is regenerated, THE Firestore_Sync_Repository SHALL update the synced plan and THE MealPlan_Cache_Repository SHALL update the local cache with the new DayPlan

### Requirement 16: User Settings Management

**User Story:** As a user, I want to configure my country, language, and default preferences, so that the app is personalized to my needs.

#### Acceptance Criteria

1. THE Settings_Repository SHALL persist user settings (country code, currency code, currency symbol, language code, default persons, default budget period) to local Hive storage and sync to Firestore
2. WHEN the app launches and no settings exist, THE Settings_Repository SHALL initialize default settings with the device's detected locale
3. WHEN the user changes the country setting, THE Food Budget: Survival Mode app SHALL update the currency symbol, currency code, and subsequent AI-generated meal suggestions to match the new country
4. WHEN the user changes the language setting, THE Food Budget: Survival Mode app SHALL reload all UI strings in the selected language

### Requirement 17: Date Sequence Integrity

**User Story:** As a user, I want each day in my meal plan to correspond to the correct calendar date, so that I can follow the plan day by day.

#### Acceptance Criteria

1. THE Laravel_Backend SHALL assign each DayPlan a date equal to the start date plus the day index in days
2. THE Laravel_Backend SHALL assign each DayPlan a zero-based day index matching its position in the plan's day list

### Requirement 18: Meal Plan Data Serialization

**User Story:** As a developer, I want meal plan data to be serialized and deserialized reliably, so that saved plans are always retrievable without data loss.

#### Acceptance Criteria

1. THE MealPlan_Cache_Repository SHALL serialize MealPlan objects to JSON format for Hive storage
2. THE MealPlan_Cache_Repository SHALL deserialize stored JSON back into equivalent MealPlan objects
3. FOR ALL valid MealPlan objects, serializing then deserializing SHALL produce an equivalent MealPlan object (round-trip property)

### Requirement 19: Error Handling

**User Story:** As a user, I want the app to handle errors gracefully, so that I have a smooth experience even when things go wrong.

#### Acceptance Criteria

1. IF the Laravel_Backend returns a server error (500, 503) or times out, THEN THE Food Budget: Survival Mode app SHALL display a user-friendly error message and retry with exponential backoff up to 3 attempts
2. IF the OpenAI_GPT API returns a malformed response, THEN THE Laravel_Backend SHALL catch the parsing error, retry the API call, and return an error to the Flutter client if retries fail
3. IF the Firebase_ID_Token has expired when calling the Laravel_Backend, THEN THE Laravel_API_Client SHALL refresh the token via Firebase_Auth and retry the request automatically
4. IF local Hive storage is full when caching a meal plan, THEN THE MealPlan_Cache_Repository SHALL catch the storage exception and notify the user that local storage is full
5. IF local storage is full, THEN THE Food Budget: Survival Mode app SHALL suggest deleting old cached plans, noting that plans remain accessible via Firestore
6. IF Firebase_Auth sign-in fails, THEN THE Food Budget: Survival Mode app SHALL display a descriptive error message and offer anonymous sign-in as a fallback
7. IF Firestore sync fails, THEN THE Food Budget: Survival Mode app SHALL fall back to the local Hive_Cache and display a subtle sync-pending indicator

### Requirement 20: Localization Support

**User Story:** As a user, I want the app available in my language, so that I can use it comfortably.

#### Acceptance Criteria

1. THE Food Budget: Survival Mode app SHALL support multiple languages using Flutter's localization framework
2. THE Food Budget: Survival Mode app SHALL default to English when the device locale is not supported
3. WHEN displaying AI-generated meal names, THE Food Budget: Survival Mode app SHALL show the names as returned by the OpenAI_GPT API, which generates culturally appropriate names based on the user's country and language context

### Requirement 21: UI Theme and Branding

**User Story:** As a user, I want a clean, friendly app interface with the beaver mascot, so that the app feels approachable and enjoyable to use.

#### Acceptance Criteria

1. THE Food Budget: Survival Mode app SHALL use a light theme with soft pastel accent colors throughout the interface
2. THE Food Budget: Survival Mode app SHALL display the beaver mascot ("Bitey") on the splash screen, empty states, and loading animations
3. THE Food Budget: Survival Mode app SHALL display currency amounts formatted according to the user's selected country and currency settings

### Requirement 22: Performance

**User Story:** As a user, I want the app to respond quickly, so that I have a smooth experience.

#### Acceptance Criteria

1. WHEN generating a meal plan, THE Food Budget: Survival Mode app SHALL display a loading animation with the Bitey mascot while awaiting the Laravel_Backend response (typically 3–10 seconds for a 30-day plan)
2. WHEN displaying a meal plan, THE Food Budget: Survival Mode app SHALL render visible days first and load remaining day details on scroll (lazy loading)
3. WHEN the app launches, THE Food Budget: Survival Mode app SHALL load cached meal plans from Hive for instant offline display

### Requirement 23: Security

**User Story:** As a user, I want my data to be secure, so that my meal plans and account information are protected.

#### Acceptance Criteria

1. THE Laravel_API_Client SHALL attach the Firebase_ID_Token as a Bearer token to every request sent to the Laravel_Backend
2. THE Laravel_Backend SHALL verify the Firebase_ID_Token using the Firebase Admin SDK on every incoming request
3. THE Firestore SHALL enforce security rules so that users can only read and write documents under their own `users/{userId}` path
4. THE Laravel_Backend SHALL store the OpenAI API key securely in environment variables and never expose the key to the Flutter client
5. THE Laravel_Backend SHALL implement rate limiting on meal plan generation endpoints to prevent abuse
6. THE Laravel_Backend SHALL validate and sanitize all user inputs on the server side in addition to client-side validation

