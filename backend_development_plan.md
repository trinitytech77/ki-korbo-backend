# KORBO - 30-Day Backend Development Plan

This document extracts and organizes the backend-specific tasks from the 30-day KORBO master plan. It provides a structured, actionable guide for you (the backend developer) to build the Laravel API and administrative panel efficiently.

## 🛠️ Recommended Tech Stack & Packages
To move fast and securely in 30 days, leverage these proven Laravel tools:
*   **Framework:** Laravel 11.x
*   **Authentication:** Laravel Sanctum (Token-based for SPA/Mobile)
*   **Role-Based Access Control (RBAC):** `spatie/laravel-permission`
*   **Admin Panel:** Filament PHP v3 (Incredibly fast to build admin CRUDs)
*   **Activity Logging (Task Timeline):** `spatie/laravel-activitylog`
*   **State Machine (Task Statuses):** `spatie/laravel-model-states` (Optional, but great for strict status transitions)
*   **Storage:** Local for dev, S3 driver (AWS S3 or MinIO) for secure document storage.
*   **Cache/Queue:** Redis

## 🏛️ Architectural Pattern
As per the requirement, avoid bloated Controllers. Use the **Action Pattern**:
*   **Controllers:** Only handle HTTP requests, validation (`FormRequests`), and return `JsonResources`.
*   **Actions:** Handle business logic (e.g., `CreateTaskAction`, `AssignAgentAction`).
*   **Services:** Handle 3rd party integrations (e.g., `BkashPaymentService`, `SmsService`).

---

## 🗓️ WEEK 1: FOUNDATION & AUTH
**Goal:** Setup the skeleton, database, authentication, and the core Service Engine.

### Day 1: Project & Architecture Setup
*   Initialize Laravel project (`laravel new ki-korbo-backend --api`).
*   Configure `.env` (Database, Redis, Mail, S3).
*   Setup folder structure (`app/Actions`, `app/Enums`, `app/Services`).
*   Implement global exception handling for uniform API JSON error responses.

### Day 2: Database Design & Migrations
*   Create migrations for: `users`, `roles`, `permissions` (via Spatie).
*   Create migrations for: `customer_profiles`, `agents`, `agent_skills`.
*   Create migrations for: `services`, `service_requirements`, `service_steps`, `service_fees`.
*   *Tip: Use Laravel Factories and Seeders immediately to have dummy data.*

### Day 3: Authentication API
*   Install & configure Laravel Sanctum.
*   Implement OTP generation and verification logic (Store OTP in Cache/Redis with 5 min expiry).
*   **Endpoints:**
    *   `POST /api/v1/auth/register`
    *   `POST /api/v1/auth/login` (Sends OTP)
    *   `POST /api/v1/auth/verify-otp` (Returns Sanctum Token)
    *   `POST /api/v1/auth/logout`

### Day 5: RBAC & Profiles
*   Setup roles: `CUSTOMER`, `AGENT`, `ADMIN`.
*   Create Laravel Policies (`TaskPolicy`, `DocumentPolicy`).
*   **Endpoints:** `GET /api/v1/me`, `PUT /api/v1/profile`.

### Day 6: Service Engine (Crucial)
*   Build the CRUD APIs for Services so the frontend can dynamically load what tasks users can request.
*   **Endpoints:**
    *   `GET /api/v1/services` (List all with categories)
    *   `GET /api/v1/services/{id}` (Include requirements & fees)

---

## 🗓️ WEEK 2: CORE TASK SYSTEM
**Goal:** Allow users to create tasks, upload secure documents, and track status.

### Day 8: Task Database & Status Engine
*   Create `Task`, `TaskStatusHistory` migrations.
*   Create `TaskStatus` Enum (`NEW`, `REQUIREMENTS_PENDING`, `IN_PROGRESS`, etc.).
*   Implement a trait or service to handle task status transitions safely.

### Day 9: Task Creation API
*   **Endpoint:** `POST /api/v1/tasks`
*   *Action:* `CreateTaskAction` handles saving the task, calculating initial fees, and setting status to `NEW`.
*   **Endpoint:** `GET /api/v1/tasks` (With filters for the customer dashboard).

### Day 11: Document Vault (Security Focus)
*   Create `documents`, `document_access_grants` migrations.
*   Configure S3 disk in `filesystems.php`. Set to `private`.
*   **Logic:** Files must be stored securely. Generate temporary signed URLs for downloading/viewing.

### Day 13: Document Permissions
*   Create logic for Agents to request access to a specific document for a specific task.
*   **Endpoints:**
    *   `POST /api/v1/tasks/{id}/documents/request-access`
    *   `POST /api/v1/documents/{id}/approve-access`

### Day 14: Task Timeline
*   Implement `spatie/laravel-activitylog` on the `Task` model.
*   Log every status change, document upload, and agent assignment.
*   **Endpoint:** `GET /api/v1/tasks/{id}/timeline`

---

## 🗓️ WEEK 3: AGENT, PRICING & PAYMENTS
**Goal:** Agent onboarding, quoting system, and payment gateway integration.

### Day 15: Agent API
*   **Endpoints:**
    *   `POST /api/v1/agents/apply` (Agent registration)
    *   `GET /api/v1/agent/tasks` (Assigned tasks for agent portal)
    *   `PATCH /api/v1/agent/tasks/{id}/status`

### Day 18: Simple Agent Matching
*   Create an `AgentMatchingService`.
*   For MVP: Simply query agents where `skill == task_service` AND `status == verified` AND `workload < threshold`.

### Day 19: Quote & Pricing Engine
*   Create a `CalculateTaskQuoteAction`.
*   Must strictly separate: `government_fee`, `korbo_fee`, `agent_fee`.

### Day 20: Payment Architecture
*   Create `PaymentGatewayInterface`.
*   Implement dummy/sandbox classes for `BkashGateway` and `NagadGateway`.
*   **Endpoints:**
    *   `POST /api/v1/tasks/{id}/pay` (Initiates payment, returns gateway URL)
    *   `POST /api/v1/payments/webhook` (Handles gateway callbacks safely)

---

## 🗓️ WEEK 4: ADMIN, NOTIFICATIONS & QA
**Goal:** Complete the admin panel, real-time notifications, and system testing.

### Day 22: Filament Admin Setup
*   Install Filament PHP (`composer require filament/filament`).
*   Create Resources for `User`, `Service`, `Task`, `Agent`, `Payment`.
*   Filament will automatically generate full CRUD interfaces for your admins in minutes.

### Day 24: Notifications (Queue)
*   Configure Redis queue worker.
*   Create Laravel Notification classes (`TaskCreatedNotification`, `PaymentReceivedNotification`).
*   Deliver via Database (In-app) and Mail/SMS.

### Day 25: Simple Chat
*   Create `Message` model.
*   **Endpoints:**
    *   `GET /api/v1/tasks/{id}/messages`
    *   `POST /api/v1/tasks/{id}/messages`
*   *(Optional for MVP)*: Broadcast via Laravel Reverb or Pusher for real-time chat.

### Day 26: Reviews & Complaints
*   Create migrations for `reviews` and `complaints`.
*   **Endpoints:**
    *   `POST /api/v1/tasks/{id}/review`
    *   `POST /api/v1/tasks/{id}/complaint`

### Day 29 & 30: Security, QA & Deployment
*   **Security Audit:**
    *   Ensure all API routes have proper middleware (`auth:sanctum`).
    *   Verify API Resources don't leak hidden fields (like passwords or sensitive admin notes).
    *   Ensure document URLs are temporary and signed.
*   Write basic tests (`php artisan make:test`) for Auth, Task Creation, and Payment logic.
*   Deploy to staging/VPS.

---

## 🚀 Next Steps to Start Coding
1. Ensure PHP 8.2+, Composer, and MySQL/PostgreSQL are installed on your Windows machine.
2. Run `composer create-project laravel/laravel ki-korbo-backend` in your `c:\Users\ThinkPad\Desktop\Trinity\` folder.
3. Start knocking out **Day 1 and Day 2**! I am here to pair-program any specific action, controller, or migration you want to build first.
