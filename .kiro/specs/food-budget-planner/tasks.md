# Implementation Plan: Food Budget Planner (Flutter App)

## Overview

Implement the Food Budget: Survival Mode Flutter app with Firebase Auth (Google Sign-In + Anonymous), Laravel API client for AI meal generation, Firestore real-time sync, Hive local caching, FCM push notifications, Firebase Analytics, BLoC state management, Clean Architecture, and go_router navigation. This plan covers the Flutter app only — the Laravel backend is a separate project.

## Tasks

- [x] 1. Project setup and dependency configuration
  - [x] 1.1 Update pubspec.yaml with all required dependencies
    - Add firebase_core, firebase_auth, cloud_firestore, firebase_analytics, firebase_messaging, google_sign_in
    - Add flutter_bloc, equatable, dio, hive, hive_flutter, go_router, uuid, intl, connectivity_plus
    - Add json_annotation, json_serializable, build_runner (dev)
    - Add flutter_localizations SDK dependency
    - Remove any old food database dependencies if present
    - _Requirements: Design Dependencies table_

  - [x] 1.2 Remove old food database files and references
    - Delete `assets/food_data/food_data_intl.json` and `assets/food_data/food_data_ph.json`
    - Remove food_data asset declarations from pubspec.yaml
    - Remove any FoodItem, FoodCategory model files and imports
    - _Requirements: Design Overview (no local food database)_

  - [x] 1.3 Set up Clean Architecture directory structure
    - Create `lib/core/`, `lib/data/`, `lib/domain/`, `lib/presentation/` directories
    - Create subdirectories: `domain/entities/`, `domain/repositories/`, `domain/usecases/`
    - Create subdirectories: `data/models/`, `data/repositories/`, `data/datasources/`
    - Create subdirectories: `presentation/blocs/`, `presentation/screens/`, `presentation/widgets/`
    - Create `lib/core/constants/`, `lib/core/errors/`, `lib/core/utils/`
    - _Requirements: Design Architecture_

- [x] 2. Data models and entities
  - [x] 2.1 Create domain entities
    - Implement `AuthUser` entity with uid, email, displayName, photoUrl, isAnonymous
    - Implement `MealPlanRequest` entity with totalBudget, currencyCode, numberOfDays, numberOfPersons, startDate, countryCode, preferredTier, skippedMealTypes
    - Implement `MealPlan` entity with id, userId, request, days, totalCost, remainingBudget, detectedTier, createdAt, updatedAt
    - Implement `DayPlan` entity with dayIndex, date, meals, dailyCost
    - Implement `Meal` entity with type, name, description, ingredients (List<String>), estimatedCost, isSkipped, isBasicMeal
    - Implement `SubscriptionInfo` entity with userId, status, productId, platform, expiresAt, purchasedAt
    - Implement `AppSettings` entity with countryCode, currencyCode, currencySymbol, languageCode, defaultPersons, defaultPeriod
    - Implement `NotificationMessage` entity with title, body, data
    - Implement enums: EconomicTier, MealType, BudgetPeriod, SubscriptionStatus
    - Use Equatable for value equality on all entities
    - _Requirements: 2.1–2.6, 5.4, 5.5, 5.6, 6.1, 6.2, 18.1_

  - [x] 2.2 Create data models with JSON serialization
    - Create JSON-serializable model classes for each entity using json_annotation
    - Implement fromJson/toJson for MealPlan, DayPlan, Meal, MealPlanRequest, SubscriptionInfo, AppSettings, AuthUser
    - Implement Hive TypeAdapters for MealPlan, DayPlan, Meal, AppSettings (for local caching)
    - Run build_runner to generate serialization code
    - _Requirements: 18.1, 18.2, 18.3_

  - [ ]* 2.3 Write property test for MealPlan serialization round trip
    - **Property 12: MealPlan Serialization Round Trip**
    - Generate random valid MealPlan objects, serialize to JSON, deserialize back, assert equivalence
    - **Validates: Requirement 18.3**

  - [x] 2.4 Implement MealPlanRequest validation logic
    - Validate totalBudget > 0, numberOfDays 1–30, numberOfPersons >= 1, startDate >= today, countryCode is valid ISO 3166-1 alpha-2
    - Return field-level validation errors for each invalid field
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ]* 2.5 Write property test for input validation
    - **Property 28: Input Validation Rejects Invalid Requests**
    - Generate random invalid inputs (budget ≤ 0, days outside 1–30, persons < 1, past dates, invalid country codes) and assert rejection
    - Generate random valid inputs and assert acceptance
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6**

- [x] 3. Checkpoint — Ensure models and validation compile and tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Repository abstractions (domain layer)
  - [x] 4.1 Define abstract repository interfaces
    - Create `AuthRepository` abstract class with signInWithGoogle, signInAnonymously, currentUser, getIdToken, signOut, authStateChanges, linkWithGoogle
    - Create `LaravelApiClient` abstract class with registerUser, generateMealPlan, regenerateDay, verifySubscription, getSubscriptionStatus, restorePurchases
    - Create `FirestoreSyncRepository` abstract class with syncMealPlan, watchMealPlans, deleteMealPlan, updateDayPlan, syncSettings, watchSettings
    - Create `MealPlanCacheRepository` abstract class with cacheMealPlan, getCachedMealPlan, getAllCachedPlans, deleteCachedMealPlan, updateCachedDayPlan, clearCache
    - Create `SettingsRepository` abstract class with getSettings, saveSettings, getCountryCode, getCurrencySymbol, getLanguageCode
    - Create `NotificationRepository` abstract class with initialize, getFcmToken, registerToken, onMessage, onMessageOpenedApp
    - Create `AnalyticsRepository` abstract class with logEvent, logScreenView, setUserId, setUserProperty
    - _Requirements: 1.1–1.7, 5.1, 10.1–10.6, 11.1–11.4, 12.1–12.4, 13.1–13.4, 14.1–14.7, 16.1–16.4_

- [x] 5. Firebase Auth repository implementation
  - [x] 5.1 Implement FirebaseAuthRepository
    - Implement Google Sign-In flow using google_sign_in and firebase_auth packages
    - Implement anonymous sign-in via firebase_auth
    - Implement getIdToken() for Laravel API authentication
    - Implement authStateChanges stream
    - Implement signOut clearing Firebase Auth session
    - Implement linkWithGoogle for anonymous-to-Google account linking
    - Handle auth errors with descriptive messages and anonymous fallback
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 23.1_

  - [ ]* 5.2 Write unit tests for FirebaseAuthRepository
    - Test Google Sign-In success and failure paths
    - Test anonymous sign-in
    - Test getIdToken returns valid token
    - Test authStateChanges emits correct states
    - Test signOut clears session
    - Test linkWithGoogle preserves UID
    - _Requirements: 1.1, 1.2, 1.4, 1.5, 1.6, 1.7_

- [x] 6. Laravel API client implementation
  - [x] 6.1 Implement DioLaravelApiClient
    - Configure Dio with base URL, timeouts, and interceptors
    - Implement Bearer token interceptor that attaches Firebase ID token to all requests
    - Implement token refresh interceptor: on 401, refresh Firebase ID token and retry
    - Implement registerUser (POST /api/auth/register)
    - Implement generateMealPlan (POST /api/meal-plans)
    - Implement regenerateDay (POST /api/meal-plans/{planId}/days/{dayIndex}/regenerate)
    - Implement verifySubscription (POST /api/subscriptions/verify)
    - Implement getSubscriptionStatus (GET /api/subscriptions/status)
    - Implement restorePurchases (POST /api/subscriptions/restore)
    - Implement retry with exponential backoff (up to 3 attempts) for 500/503/timeout errors
    - Handle and map API errors (401, 403, 429, 500, network) to typed exceptions
    - _Requirements: 5.1, 5.2, 13.2, 13.4, 15.1, 19.1, 19.3, 23.1_

  - [ ]* 6.2 Write unit tests for DioLaravelApiClient
    - Test Bearer token attachment on requests
    - Test token refresh on 401 response
    - Test exponential backoff retry on 500/503
    - Test error mapping for various HTTP status codes
    - Test generateMealPlan request/response serialization
    - _Requirements: 5.1, 19.1, 19.3, 23.1_

- [x] 7. Firestore sync repository implementation
  - [x] 7.1 Implement FirestoreSyncRepositoryImpl
    - Implement syncMealPlan writing to `users/{userId}/meal_plans/{planId}`
    - Implement watchMealPlans with real-time snapshot listener
    - Implement deleteMealPlan removing document from Firestore
    - Implement updateDayPlan updating only the affected day in the document
    - Implement syncSettings writing to `users/{userId}/settings`
    - Implement watchSettings with real-time snapshot listener
    - Handle Firestore errors gracefully with fallback to local cache
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 19.7_

  - [ ]* 7.2 Write unit tests for FirestoreSyncRepositoryImpl
    - Test syncMealPlan writes correct document structure
    - Test watchMealPlans emits updates
    - Test deleteMealPlan removes document
    - Test updateDayPlan modifies only the target day
    - Test error handling falls back gracefully
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.6_

- [x] 8. Local cache repository implementation (Hive)
  - [x] 8.1 Implement HiveMealPlanCacheRepository
    - Initialize Hive boxes for meal plans
    - Implement cacheMealPlan storing serialized MealPlan
    - Implement getCachedMealPlan retrieving by planId
    - Implement getAllCachedPlans listing all cached plans
    - Implement deleteCachedMealPlan removing by planId
    - Implement updateCachedDayPlan updating a single day while preserving other days
    - Implement clearCache removing all cached plans
    - Handle storage exceptions (full storage) with user notification
    - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5, 14.6, 19.4, 19.5_

  - [ ]* 8.2 Write property test for cache round trip
    - **Property 13: Meal Plan Cache Round Trip**
    - Generate random valid MealPlan, cache it, retrieve by ID, assert equivalence
    - **Validates: Requirements 14.1, 14.2**

  - [ ]* 8.3 Write property test for partial day update preservation
    - **Property 15: Partial Day Update Preservation**
    - Cache a MealPlan, update one day, assert all other days unchanged
    - **Validates: Requirements 10.4, 14.5**

  - [ ]* 8.4 Write property test for plan deletion
    - **Property 14: Plan Deletion (Cache + Firestore)**
    - Cache a MealPlan, delete it, assert retrieval returns null and plan not in list
    - **Validates: Requirements 10.3, 14.4**

  - [ ]* 8.5 Write property test for plan list completeness
    - **Property 23: Plan List Completeness**
    - Cache N distinct MealPlans, list all, assert exactly N entries returned
    - **Validates: Requirement 14.3**

- [x] 9. Settings repository implementation
  - [x] 9.1 Implement HiveFirestoreSettingsRepository
    - Persist AppSettings to Hive locally
    - Sync AppSettings to Firestore via FirestoreSyncRepository
    - Initialize default settings from device locale on first launch
    - Implement getCountryCode, getCurrencySymbol, getLanguageCode
    - Update currency symbol and code when country changes
    - _Requirements: 16.1, 16.2, 16.3_

  - [ ]* 9.2 Write property test for settings persistence round trip
    - **Property 17: Settings Persistence Round Trip**
    - Generate random valid AppSettings, save, retrieve, assert equivalence
    - **Validates: Requirements 10.5, 16.1**

  - [ ]* 9.3 Write property test for country change updates currency
    - **Property 21: Country Change Updates Currency**
    - Change country code, assert currency symbol and code match new country
    - **Validates: Requirement 16.3**

- [x] 10. Notification repository implementation (FCM)
  - [x] 10.1 Implement FcmNotificationRepository
    - Initialize Firebase Messaging and request notification permissions
    - Implement getFcmToken and registerToken with Laravel backend
    - Implement onMessage stream for foreground notifications
    - Implement onMessageOpenedApp stream for background/terminated notification taps
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [x] 11. Analytics repository implementation
  - [x] 11.1 Implement FirebaseAnalyticsRepositoryImpl
    - Implement logEvent for custom events (meal plan generation, subscription)
    - Implement logScreenView for navigation tracking
    - Implement setUserId and setUserProperty (country, subscription status)
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

- [x] 12. Checkpoint — Ensure all repository implementations compile and tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 13. Use cases (domain layer)
  - [x] 13.1 Implement AuthUseCase
    - Orchestrate signInWithGoogle, signInAnonymously, signOut, linkWithGoogle
    - After successful auth, call LaravelApiClient.registerUser with Firebase ID token
    - Expose authStateChanges stream
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.7_

  - [x] 13.2 Implement MealPlanUseCase
    - Orchestrate generatePlan: validate request → get ID token → call LaravelApiClient → sync to Firestore → cache locally
    - Orchestrate regenerateDay: call LaravelApiClient → update Firestore → update cache
    - Orchestrate deletePlan: delete from Firestore → delete from cache
    - Orchestrate getSavedPlans: watch Firestore stream, fallback to cache when offline
    - _Requirements: 5.1, 5.2, 5.3, 10.1, 14.1, 14.2, 14.7, 15.1, 15.3_

  - [x] 13.3 Implement SettingsUseCase
    - Orchestrate getSettings, saveSettings with local + Firestore sync
    - Handle country change updating currency
    - _Requirements: 16.1, 16.2, 16.3, 16.4_

  - [x] 13.4 Implement SubscriptionUseCase
    - Orchestrate purchase flow: native store purchase → verify via LaravelApiClient → update status
    - Orchestrate restorePurchases
    - Orchestrate getSubscriptionStatus
    - Check subscription before allowing multi-day plan generation
    - _Requirements: 13.1, 13.2, 13.4, 13.5, 13.6_

  - [x] 13.5 Implement NotificationUseCase
    - Orchestrate FCM initialization, token registration, message handling
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [x] 14. BLoC layer
  - [x] 14.1 Implement AuthBloc
    - Events: GoogleSignInEvent, AnonymousSignInEvent, SignOutEvent, LinkWithGoogleEvent, CheckAuthEvent
    - States: AuthInitial, AuthLoading, AuthAuthenticated(user, subscriptionInfo), AuthUnauthenticated, AuthError(message)
    - On successful auth, register with Laravel backend and retrieve subscription status
    - On auth failure, emit error with descriptive message and offer anonymous fallback
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.7_

  - [x] 14.2 Implement MealPlanBloc
    - Events: GenerateMealPlanEvent(request), RegenerateDayEvent(planId, dayIndex), LoadSavedPlansEvent, DeletePlanEvent(planId)
    - States: MealPlanInitial, MealPlanLoading, MealPlanLoaded(plan), MealPlanListLoaded(plans), MealPlanError(message), MealPlanValidationError(fieldErrors)
    - Validate MealPlanRequest before sending to use case; emit field-level validation errors
    - Handle API errors with user-friendly messages
    - Handle offline state: show cached plans, display message for generation attempts
    - _Requirements: 2.1–2.6, 5.1, 9.2, 9.3, 14.7, 15.1, 19.1_

  - [x] 14.3 Implement SettingsBloc
    - Events: LoadSettingsEvent, UpdateSettingsEvent(settings), ChangeCountryEvent(countryCode), ChangeLanguageEvent(languageCode)
    - States: SettingsInitial, SettingsLoaded(settings), SettingsError(message)
    - On country change, update currency symbol/code and emit updated settings
    - On language change, trigger localization reload
    - _Requirements: 16.1, 16.2, 16.3, 16.4_

  - [x] 14.4 Implement SubscriptionBloc
    - Events: CheckSubscriptionEvent, PurchaseSubscriptionEvent, RestorePurchasesEvent
    - States: SubscriptionInitial, SubscriptionLoading, SubscriptionActive(info), SubscriptionInactive, SubscriptionError(message)
    - On expired subscription + premium action attempt, emit paywall state
    - _Requirements: 13.1, 13.2, 13.4, 13.5, 13.6_

  - [ ]* 14.5 Write unit tests for MealPlanBloc validation
    - Test that invalid inputs emit MealPlanValidationError with correct field errors
    - Test that valid inputs proceed to loading state
    - Test error handling for API failures
    - _Requirements: 2.1–2.6, 19.1_

- [x] 15. Checkpoint — Ensure use cases and BLoCs compile and tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 16. Localization and currency formatting
  - [x] 16.1 Set up Flutter localization
    - Configure l10n.yaml for Flutter's localization framework
    - Create/update ARB files (app_en.arb as base, add other languages as needed)
    - Generate localization delegates
    - Default to English when device locale is not supported
    - _Requirements: 20.1, 20.2_

  - [x] 16.2 Implement currency formatting utility
    - Create CurrencyFormatter that formats amounts with correct currency symbol based on country/currency settings
    - Map country codes to currency codes and symbols
    - Use intl package for locale-aware number formatting
    - _Requirements: 21.3_

  - [ ]* 16.3 Write property test for currency formatting consistency
    - **Property 20: Currency Formatting Consistency**
    - For random amounts and supported country/currency settings, assert formatted string includes correct currency symbol
    - **Validates: Requirement 21.3**

- [x] 17. UI screens
  - [x] 17.1 Implement Splash Screen
    - Display Bitey beaver mascot with app branding
    - Check auth state and navigate accordingly (authenticated → Home, unauthenticated → Auth)
    - Load cached settings and initialize Firebase
    - _Requirements: 21.2, 22.3_

  - [x] 17.2 Implement Auth Screen
    - "Sign in with Google" button triggering GoogleSignInEvent
    - "Continue as Guest" button triggering AnonymousSignInEvent
    - Error display with anonymous fallback option
    - BLoC listener for auth state changes and navigation
    - _Requirements: 1.1, 1.2, 1.5_

  - [x] 17.3 Implement Home Screen
    - Display list of saved meal plans from Firestore stream (with Hive fallback)
    - Show Bitey mascot on empty state
    - FAB or button to navigate to Meal Plan Input screen
    - Navigation to Settings, Subscription screens
    - Lazy loading for plan list
    - _Requirements: 14.2, 22.2, 22.3_

  - [x] 17.4 Implement Meal Plan Input Screen
    - Input fields: budget amount, number of days (1–30), number of persons, start date picker, country selector
    - Meal type skip toggles (breakfast, lunch, dinner, meryenda)
    - Field-level validation error display from MealPlanBloc
    - Submit button triggering GenerateMealPlanEvent
    - Loading animation with Bitey mascot during generation
    - Offline detection: show message that internet is required for generation
    - _Requirements: 2.1–2.6, 5.1, 14.7, 22.1_

  - [x] 17.5 Implement Meal Plan Result Screen
    - Display day-by-day meal plan with expandable day cards
    - Show meal details: name, description, ingredients, estimated cost, isBasicMeal indicator
    - Show total cost, remaining budget, detected economic tier
    - Regenerate button per day triggering RegenerateDayEvent
    - Basic meal warning banner when isBasicMeal meals are present
    - Budget increase/reduce days/reduce persons suggestions for tight budgets
    - Lazy loading: render visible days first, load remaining on scroll
    - _Requirements: 5.4, 5.5, 5.6, 6.1, 9.2, 9.3, 15.1, 22.2_

  - [x] 17.6 Implement Saved Plans Screen
    - List all saved meal plans with summary (dates, budget, cost)
    - Swipe-to-delete or delete button per plan
    - Tap to navigate to Meal Plan Result Screen
    - Works offline with cached plans
    - _Requirements: 14.2, 14.3, 14.4_

  - [x] 17.7 Implement Settings Screen
    - Country selector updating currency and language context
    - Language selector triggering localization reload
    - Default persons and budget period preferences
    - Link to Google Account option for anonymous users
    - Sign out button
    - _Requirements: 1.4, 1.7, 16.1, 16.3, 16.4_

  - [x] 17.8 Implement Subscription/Paywall Screen
    - Display subscription plans and pricing
    - Purchase button initiating native store purchase flow
    - Restore purchases button
    - Show current subscription status and expiry
    - Display when user attempts premium action without active subscription
    - _Requirements: 13.1, 13.2, 13.4, 13.6_

- [x] 18. Navigation and theming
  - [x] 18.1 Set up go_router navigation
    - Define routes: splash, auth, home, mealPlanInput, mealPlanResult, savedPlans, settings, subscription
    - Implement auth guard: redirect unauthenticated users to auth screen
    - Implement deep linking from FCM notification taps
    - Log screen views via AnalyticsRepository on route changes
    - _Requirements: 1.7, 11.4, 12.3_

  - [x] 18.2 Implement app theme
    - Define light theme with soft pastel accent colors
    - Configure text styles, button styles, card styles, input decoration
    - Ensure consistent Bitey branding across screens
    - _Requirements: 21.1, 21.2_

- [x] 19. Dependency injection and wiring
  - [x] 19.1 Set up dependency injection
    - Initialize Firebase (Core, Auth, Firestore, Analytics, Messaging) in main.dart
    - Initialize Hive and register TypeAdapters
    - Create and provide all repository implementations
    - Create and provide all use cases
    - Create and provide all BLoCs via BlocProvider/MultiBlocProvider
    - Configure Dio instance with base URL and interceptors
    - Wire go_router with auth state for guards
    - _Requirements: All — wiring all components together_

  - [x] 19.2 Configure app entry point
    - Set up MaterialApp.router with go_router, theme, localization delegates
    - Wrap with MultiBlocProvider for all BLoCs
    - Handle Firebase initialization errors gracefully
    - _Requirements: All — app bootstrap_

- [x] 20. Final checkpoint — Ensure all code compiles, tests pass, and app runs
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- This plan covers the Flutter app only — the Laravel backend is a separate project
- Firebase project setup (google-services.json, GoogleService-Info.plist, Firestore security rules) is assumed to be done outside this task list

