# KORBO Backend - Progress Summary (Day 1, Day 2 & Day 3)

This document tracks exactly what has been completed, configured, and created in the `ki-korbo-backend` workspace for the 30-day development module.

---

## 🚀 Day 1: Project & Architecture Setup

**Objective:** Initialize the Laravel API, configure environments, and enforce clean architecture.

### What was done:
1. **Fresh Laravel Installation:** Generated a clean Laravel 11 framework in the root directory and scaffolded API routing using `php artisan install:api`.
2. **Architecture Folders:** Scaffolded custom directories to prevent bloated controllers.
3. **Environment Configuration:** Bound the application to the local XAMPP MySQL database and configured required services.
4. **Global Exception Handling:** Forced Laravel to return standardized JSON arrays for all API errors (validation, 404, 500 server errors) instead of HTML pages.

### Files Changed / Created:
*   `/.env`: 
    *   Updated `DB_CONNECTION=mysql`, `DB_DATABASE=ki_korbo`, `DB_USERNAME=root`.
    *   Updated `FILESYSTEM_DISK=s3`.
    *   Verified `REDIS_*` and `MAIL_MAILER=log` default configurations.
*   `/bootstrap/app.php`: 
    *   Modified `->withExceptions()` to inject a custom `render()` method that catches `Throwable` and returns structured JSON arrays formatted with `success`, `message`, and `errors`.
*   `/app/Actions/` *(Created directory)*
*   `/app/Enums/` *(Created directory)*
*   `/app/Services/` *(Created directory)*

---

## 🗄️ Day 2: Database Design & Migrations

**Objective:** Construct the entire relational database schema strictly according to the KORBO blueprint.

### What was done:
1. **Authentication (Spatie):** Installed `spatie/laravel-permission` to handle RBAC (Role-Based Access Control) cleanly.
2. **Core Schema Migrations:** Manually created migrations for the Primary and Supplementary tables across all 8 major business modules (Customer, Agent, Services, Tasks, Documents, Operations, Financial, Trust).
3. **Eloquent Models:** Scaffolded the primary Models with their `$fillable` arrays and foundational relationships (e.g., `belongsTo`, `hasMany`).

### Files Changed / Created:

**1. Authentication & Trust:**
*   `/database/migrations/..._create_permission_tables.php` *(Spatie)*
*   `/database/migrations/..._create_notifications_table.php` *(Laravel default)*

**2. Core Migrations Created:**
*   `/database/migrations/2026_10_07_100001_create_customer_profiles_table.php`
*   `/database/migrations/2026_10_07_100002_create_agents_table.php`
*   `/database/migrations/2026_10_07_100003_create_agent_skills_table.php`
*   `/database/migrations/2026_10_07_100004_create_service_categories_table.php`
*   `/database/migrations/2026_10_07_100005_create_services_table.php`
*   `/database/migrations/2026_10_07_100006_create_service_requirements_table.php`
*   `/database/migrations/2026_10_07_100007_create_service_steps_table.php`
*   `/database/migrations/2026_10_07_100008_create_service_fees_table.php`
*   `/database/migrations/2026_10_07_100009_create_tasks_module_tables.php` *(tasks, task_status_histories)*
*   `/database/migrations/2026_10_07_100010_create_documents_module_tables.php` *(documents, document_access_grants)*
*   `/database/migrations/2026_10_07_100011_create_financial_module_tables.php` *(payments, agent_payouts)*
*   `/database/migrations/2026_10_07_100012_create_trust_communication_tables.php` *(reviews, messages, complaints)*
*   `/database/migrations/2026_10_07_100013_create_remaining_day2_tables.php` *(family_members, agent_verifications, agent_service_areas, agent_availability, task_assignments, task_notes, document_access_logs, appointments, submissions, collections, payment_transactions, invoices, refunds, disputes, audit_logs)*

**3. Eloquent Models Created:**
*   `/app/Models/CustomerProfile.php`
*   `/app/Models/Agent.php`
*   `/app/Models/AgentSkill.php`
*   `/app/Models/ServiceCategory.php`
*   `/app/Models/Service.php`
*   `/app/Models/Task.php`
*   `/app/Models/Document.php`

---

## 🔐 Day 3: Authentication Backend

**Objective:** Implement production-ready Authentication REST API with Laravel Sanctum, dual email/phone support, password & OTP authentication, user roles, account status, and automated testing.

### What was done:
1. **User Migration & Schema Enhancements:**
   * Added `phone` (unique), `role` (enum default `CUSTOMER`), `status` (enum default `ACTIVE`), and `phone_verified_at` to the `users` table (`2026_10_08_000001_add_phone_role_status_to_users_table.php`).
   * Configured `email` and `password` as nullable to seamlessly accommodate phone/OTP-only user signups typical in Bangladesh.
2. **User Model Enhancements (`App\Models\User`):**
   * Integrated `Laravel\Sanctum\HasApiTokens` and `Spatie\Permission\Traits\HasRoles`.
   * Configured `$fillable`, casting, and `customerProfile` & `agent` Eloquent relationships.
3. **Enums Created:**
   * `/app/Enums/UserRole.php` (`CUSTOMER`, `AGENT`, `PROFESSIONAL`, `OPERATIONS`, `ADMIN`, `SUPER_ADMIN`).
   * `/app/Enums/AccountStatus.php` (`ACTIVE`, `INACTIVE`, `PENDING`, `SUSPENDED`).
4. **Form Requests Created:**
   * `/app/Http/Requests/RegisterRequest.php`: Validates name, unique email or phone, password, and role.
   * `/app/Http/Requests/LoginRequest.php`: Supports password login (via email or phone) and OTP-trigger login.
   * `/app/Http/Requests/VerifyOtpRequest.php`: Validates 6-digit OTP and identifier.
5. **API Resources Created:**
   * `/app/Http/Resources/UserResource.php`: Standardized user payload with Spatie roles, permissions, and profile links.
   * `/app/Http/Resources/AuthResource.php`: Wraps user data with bearer tokens and status messages.
6. **Auth Service (`App\Services\AuthService`):**
   * Encapsulates registration logic, Spatie role sync, password hashing, token generation, 6-digit OTP generation with 5-minute cache expiry in Redis/Cache, OTP verification, and token revocation upon logout.
7. **Auth Controller (`App\Http\Controllers\Api\V1\AuthController`):**
   * Exposes clean REST API endpoints returning structured JSON responses.
8. **Routes Configured (`routes/api.php`):**
   * `POST /api/v1/auth/register`
   * `POST /api/v1/auth/login` (supports standard password and `via_otp: true`)
   * `POST /api/v1/auth/otp/verify` (alias `/api/v1/auth/verify-otp`)
   * `POST /api/v1/auth/logout` (protected with `auth:sanctum`)
   * `GET  /api/v1/auth/me` & `GET /api/v1/me` (protected with `auth:sanctum`)
9. **Automated Feature Tests (`tests/Feature/AuthTest.php`):**
   * 4 test suites with 31 assertions covering registration, password login, OTP request & verification, `/me` profile retrieval, and logout token revocation. All tests passing 100%.

---

## 🛡️ Day 5: RBAC + User Profiles Backend

**Objective:** Implement comprehensive Role-Based Access Control (RBAC), multi-role Spatie permissions, policy-based authorization across domain models, and complete User Profile management APIs.

### What was done:
1. **Roles Implemented (6 Total):**
   * `CUSTOMER`: Standard clients managing own tasks, personal documents, payments, and agent reviews.
   * `AGENT`: Field agents handling assigned tasks, task documents/grants, proofs, agent profile, and payout reviews.
   * `PROFESSIONAL`: Certified experts handling assigned compliance tasks and documents.
   * `OPERATIONS`: Operational staff managing task flow, agent dispatch, document verification, and disputes.
   * `ADMIN`: Administrators with complete operational access across all platform modules.
   * `SUPER_ADMIN`: Root access to all policies and system capabilities (bypasses gates via `before`).
2. **Role & Permission Seeder (`database/seeders/RolePermissionSeeder.php`):**
   * Created and mapped granular permissions (`tasks.*`, `documents.*`, `agents.*`, `payments.*`, `reviews.*`, `profiles.*`) to all 6 roles.
   * Integrated into `DatabaseSeeder.php`.
3. **Database Schema Enhancements:**
   * Added `avatar` and `language` to `users` table (`2026_10_11_000001_add_profile_fields_to_users_and_profiles.php`).
   * Added `gender` and `emergency_contact` to `customer_profiles` table.
   * Added `nid_number` and `service_area` to `agents` table.
4. **Domain Models Created & Enhanced:**
   * `/app/Models/Payment.php`: Payment model with user and task relationships.
   * `/app/Models/Review.php`: Review model with customer, agent, and task relationships.
   * `/app/Models/TaskAssignment.php`: Assignment history model.
   * `/app/Models/DocumentAccessGrant.php`: Task-scoped document access grants with expiry verification.
   * `/app/Models/User.php`: Added role helper methods (`isCustomer()`, `isAgent()`, `isAdmin()`, `isSuperAdmin()`, `isStaff()`, etc.) and profile relationships.
   * `/app/Models/Task.php`: Added `isAssignedTo()` helper and relationships to payments, reviews, assignments, and documents.
   * `/app/Models/Document.php`: Added `hasActiveGrantFor()` helper and relationships.
5. **Laravel Policies Created & Registered (`AppServiceProvider`):**
   * `/app/Policies/TaskPolicy.php`: Enforces Customer (own tasks only), Agent (assigned tasks only), Admin/Ops (operational access), Super Admin (everything).
   * `/app/Policies/DocumentPolicy.php`: Protects documents, supports task-based agent access and explicit unexpired grants, restricts document verification to staff.
   * `/app/Policies/AgentPolicy.php`: Protects agent profiles, allows self-updates by agents, restricts verification/approval to staff.
   * `/app/Policies/PaymentPolicy.php`: Customer views own payments, Agent views assigned task payments, Admin processes refunds.
   * `/app/Policies/ReviewPolicy.php`: Customers review agents for completed tasks, author/admin moderation.
6. **Profile Action & Endpoints:**
   * `/app/Actions/UpdateUserProfileAction.php`: Encapsulates updating personal info, avatar file upload/URL, password change with optional current password verification, customer profile details, and agent profile details.
   * `/app/Http/Requests/UpdateProfileRequest.php`: Form request validation for profile changes.
   * `/app/Http/Controllers/Api/V1/ProfileController.php`: Exposes `show` and `update` actions.
   * `/routes/api.php`:
     * `GET /api/v1/me` & `GET /api/v1/profile`
     * `PUT /api/v1/profile` & `PUT /api/v1/me`
7. **Automated Feature Tests:**
   * `tests/Feature/PolicyTest.php`: Tests authorization across all 5 policies for each role.
   * `tests/Feature/ProfileTest.php`: Tests profile fetch, profile updates, avatar uploads, password changes, agent field updates, and unauthorized rejection.
   * **Result:** All 18 tests passing with 111 assertions!

---

## ⚙️ Day 6: Service Engine (Crucial)

**Objective:** Build dynamic, database-driven service CRUD APIs allowing the frontend to dynamically load services, requirements, workflows, and fee breakdowns without hardcoded values in controllers.

### What was done:
1. **Dynamic Database Models Created & Enhanced:**
   * `/app/Models/ServiceCategory.php`: Category model with `services` and `activeServices` relationships, slug/id route binding.
   * `/app/Models/Service.php`: Core service model with `category`, `requirements`, `steps`, `fees`, `tasks` relations, and dynamic fee calculation accessors (`government_fee`, `korbo_fee`, `agent_fee`, `total_fee`, `fee_breakdown`).
   * `/app/Models/ServiceRequirement.php`: Document requirement model (`document_name`, `is_mandatory`).
   * `/app/Models/ServiceStep.php`: Execution step model with ordering (`step_name`, `step_order`).
   * `/app/Models/ServiceFee.php`: Dynamic fee breakdown model (`fee_type`: GOVERNMENT, KORBO, AGENT, `amount`).
2. **Atomic Actions Created:**
   * `/app/Actions/CreateServiceAction.php`: Atomically creates a service along with its requirements, steps, and fees in a single database transaction.
   * `/app/Actions/UpdateServiceAction.php`: Atomically updates service metadata and synchronizes child requirements, steps, and fee structures.
3. **Policies & Authorization (`ServicePolicy`, `ServiceCategoryPolicy`):**
   * Public users and customers can view all active services, categories, steps, requirements, and fee quotes.
   * Only staff (`ADMIN`, `SUPER_ADMIN`, `OPERATIONS`) can create, update, or delete services.
   * Registered in `AppServiceProvider`.
4. **Form Requests & Validation:**
   * `/app/Http/Requests/StoreServiceRequest.php`: Validates service data and nested requirements, steps, and fees arrays.
   * `/app/Http/Requests/UpdateServiceRequest.php`: Validates update payloads and unique slugs.
   * `/app/Http/Requests/StoreServiceCategoryRequest.php` & `UpdateServiceCategoryRequest.php`.
5. **API Resources Created:**
   * `/app/Http/Resources/ServiceResource.php`: Returns standardized service JSON with embedded requirements, steps, fees, and fee breakdown calculations.
   * `/app/Http/Resources/ServiceCategoryResource.php`, `ServiceRequirementResource.php`, `ServiceStepResource.php`, `ServiceFeeResource.php`.
6. **Controllers & Endpoints (`routes/api.php`):**
   * `GET /api/v1/services` (List all active services, category filter, keyword search)
   * `GET /api/v1/services/{id_or_slug}` (Get complete service with requirements, steps, fees)
   * `GET /api/v1/service-categories` & `GET /api/v1/service-categories/{id_or_slug}`
   * `POST /api/v1/services` (Admin create dynamic service)
   * `PUT /api/v1/services/{id}` (Admin update service & sync relations)
   * `DELETE /api/v1/services/{id}` (Admin delete service)
   * `POST|PUT|DELETE /api/v1/service-categories` (Admin category CRUD)
7. **Database Seeder (`ServiceEngineSeeder.php`):**
   * Pre-populates real database-driven services (Passport Renewal, New E-Passport, Digital Birth Certificate Correction, Trade License Issuance & Renewal) with authentic Bangladesh government fees, platform fees, agent fees, document requirements, and ordered workflow steps.
8. **Automated Feature Tests (`tests/Feature/ServiceEngineTest.php`):**
   * 8 comprehensive test suites covering service listing, filtering, search, slug/id detail retrieval with fee breakdown, admin creation, updates with relationship syncing, deletions, and RBAC security enforcement.
   * **Total Suite:** 26 passed tests, 298 assertions across the entire backend!
