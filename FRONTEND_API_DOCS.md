# 🚀 KORBO API Documentation for Frontend Developer (v1)

**Base URL:** `http://localhost:8000/api/v1`  
*(Change port/domain according to your environment)*

**Standard Headers for all Requests:**
```http
Content-Type: application/json
Accept: application/json
```

For protected routes, include the Bearer token in the header:
```http
Authorization: Bearer <YOUR_ACCESS_TOKEN>
```

---

## 📌 Standard Error Response Format
All errors return consistent JSON (no HTML error pages):
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password field must be at least 4 characters."]
  }
}
```

---

## 1. Register User
Creates a new customer account and returns an auth token.

* **Endpoint:** `POST /api/v1/auth/register`
* **Auth Required:** No

### Request Body:
```json
{
  "name": "Meher Negar",
  "email": "mehernegar28@gmail.com",
  "phone": "01845353922", 
  "password": "yourpassword123",
  "role": "CUSTOMER"
}
```
*Note: Either `email` or `phone` is required. `role` defaults to `CUSTOMER` if omitted.*

### Success Response (`201 Created`):
```json
{
  "user": {
    "id": 1,
    "name": "Meher Negar",
    "email": "mehernegar28@gmail.com",
    "phone": "01845353922",
    "role": "CUSTOMER",
    "status": "ACTIVE",
    "email_verified": false,
    "phone_verified": false,
    "roles": ["CUSTOMER"],
    "permissions": []
  },
  "token": "1|OpcefGwwztDu9Rf4TBJps69TK7qmbPymFOpECIqi",
  "token_type": "Bearer",
  "message": "Registration successful."
}
```

---

## 2. Login with Password
Standard login using email or phone and password.

* **Endpoint:** `POST /api/v1/auth/login`
* **Auth Required:** No

### Request Body:
```json
{
  "login": "mehernegar28@gmail.com",
  "password": "yourpassword123"
}
```
*(You can pass `email` or `phone` in the `login` field, or send `"email": "..."` and `"password": "..."` explicitly).*

### Success Response (`200 OK`):
```json
{
  "user": {
    "id": 1,
    "name": "Meher Negar",
    "email": "mehernegar28@gmail.com",
    "phone": "01845353922",
    "role": "CUSTOMER",
    "status": "ACTIVE",
    "roles": ["CUSTOMER"],
    "permissions": []
  },
  "token": "2|ma2KVcnalP5xpYlmzDDtHUEuxIs1ahWDLqJDXTzR",
  "token_type": "Bearer",
  "message": "Login successful."
}
```

---

## 3. Request OTP (Passwordless Login / Verification)
Generates and sends a 6-digit verification code to the user's email or phone (valid for 5 minutes).

* **Endpoint:** `POST /api/v1/auth/login`
* **Auth Required:** No

### Request Body:
```json
{
  "email": "mehernegar28@gmail.com",
  "via_otp": true
}
```
*(Or use `"phone": "01845353922"`).*

### Success Response (`200 OK`):
```json
{
  "success": true,
  "requires_otp": true,
  "identifier": "mehernegar28@gmail.com",
  "message": "OTP has been sent successfully."
}
```

---

## 4. Verify OTP Code
Verifies the 6-digit code and issues the Bearer authentication token. If the user does not exist yet, an account is automatically created.

* **Endpoint:** `POST /api/v1/auth/otp/verify`  
  *(Alias: `POST /api/v1/auth/verify-otp`)*
* **Auth Required:** No

### Request Body:
```json
{
  "identifier": "mehernegar28@gmail.com",
  "otp": "223287"
}
```

### Success Response (`200 OK`):
```json
{
  "user": {
    "id": 1,
    "name": "Meher Negar",
    "email": "mehernegar28@gmail.com",
    "phone": null,
    "role": "CUSTOMER",
    "status": "ACTIVE",
    "email_verified": true,
    "phone_verified": false,
    "roles": ["CUSTOMER"],
    "permissions": []
  },
  "token": "3|yzQ9IjMsfarUSN6Np5AGhuBfUlFVGwlTiUAxoMx5",
  "token_type": "Bearer",
  "message": "OTP verified successfully."
}
```

---

## 5. Get Authenticated User Profile (`/me`)
Fetches profile info, customer details, agent details, and roles/permissions for the currently logged-in user.

* **Endpoints:** 
  * `GET /api/v1/me`
  * `GET /api/v1/profile`
  * `GET /api/v1/auth/me`
* **Auth Required:** **Yes** (`Authorization: Bearer <token>`)

### Success Response (`200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Meher Negar",
    "email": "mehernegar28@gmail.com",
    "phone": "01845353922",
    "avatar": "https://korbo.com/storage/avatars/avatar1.jpg",
    "language": "en",
    "role": "CUSTOMER",
    "status": "ACTIVE",
    "email_verified": true,
    "phone_verified": false,
    "roles": ["CUSTOMER"],
    "permissions": ["tasks.view_own", "tasks.create", "..."],
    "customer_profile": {
      "id": 1,
      "user_id": 1,
      "phone": "01845353922",
      "address": "Dhanmondi, Dhaka",
      "date_of_birth": "1995-05-15",
      "gender": "female",
      "emergency_contact": "01711111111"
    },
    "agent_profile": null,
    "created_at": "2026-10-08T10:42:10+00:00",
    "updated_at": "2026-10-08T10:42:10+00:00"
  }
}
```

---

## 6. Update User Profile (`PUT /api/v1/profile`)
Updates personal details, profile picture, address, language, or password.

* **Endpoints:** 
  * `PUT /api/v1/profile`
  * `PUT /api/v1/me`
* **Auth Required:** **Yes** (`Authorization: Bearer <token>`)
* **Content-Type:** `application/json` or `multipart/form-data` (when uploading avatar file)

### Request Body (JSON or FormData):
```json
{
  "name": "Meher Negar",
  "language": "bn",
  "address": "Banani, Dhaka",
  "date_of_birth": "1995-05-15",
  "gender": "female",
  "emergency_contact": "01711223344",
  "avatar": "https://example.com/avatar.jpg"
}
```
*(Or upload an image file under key `avatar`)*

#### Optional Password Change fields:
```json
{
  "current_password": "current_password123",
  "password": "new_secure_password",
  "password_confirmation": "new_secure_password"
}
```

#### Agent Profile Specific fields:
```json
{
  "bio": "Experienced legal and passport consultant.",
  "nid_number": "1994123456789",
  "service_area": "Dhaka North"
}
```

### Success Response (`200 OK`):
```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": {
    "id": 1,
    "name": "Meher Negar",
    "email": "mehernegar28@gmail.com",
    "phone": "01845353922",
    "avatar": "https://example.com/avatar.jpg",
    "language": "bn",
    "role": "CUSTOMER",
    "status": "ACTIVE",
    "customer_profile": {
      "address": "Banani, Dhaka",
      "date_of_birth": "1995-05-15",
      "gender": "female",
      "emergency_contact": "01711223344"
    }
  }
}
```

---

## 7. Logout
Revokes the current access token. Subsequent requests with this token will return `401 Unauthorized`.

* **Endpoint:** `POST /api/v1/auth/logout`
* **Auth Required:** **Yes** (`Authorization: Bearer <token>`)

### Success Response (`200 OK`):
```json
{
  "success": true,
  "message": "Successfully logged out."
}
```

---

## 8. Service Engine APIs (Day 6)

The Service Engine provides dynamic, database-driven services. Nothing is hardcoded; categories, required documents, execution steps, and fee breakdowns are loaded on-the-fly.

### 8.1 List Services (`GET /api/v1/services`)
Browse the marketplace catalogue. Supports filtering by category and search.

* **Endpoint:** `GET /api/v1/services`
* **Auth Required:** No (Public)
* **Query Parameters:**
  * `category`: Filter by category slug (e.g. `passport-travel-documents`) or ID.
  * `search` or `q`: Search keywords (e.g. `renewal` or `police`).
  * `with_details`: `true` to embed full requirements & steps.
  * `per_page`: Number of results (default: 25).

#### Success Response (`200 OK`):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Passport Renewal",
      "slug": "passport-renewal",
      "description": "Fast-track e-passport renewal support.",
      "estimated_days": "7-10 working days",
      "is_active": true,
      "category": {
        "id": 1,
        "name": "Passport & Travel Documents",
        "slug": "passport-travel-documents",
        "icon": "passport"
      },
      "fee_breakdown": {
        "government_fee": 4025,
        "korbo_fee": 500,
        "agent_fee": 800,
        "total_amount": 5325
      }
    }
  ]
}
```

---

### 8.2 Get Service Details (`GET /api/v1/services/{id_or_slug}`)
Fetches the complete service breakdown: required checklist documents, ordered execution steps, and transparent fee breakdown.

* **Endpoint:** `GET /api/v1/services/{id_or_slug}` (accepts numeric ID or string slug like `passport-renewal`)
* **Auth Required:** No (Public)

#### Success Response (`200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Passport Renewal",
    "slug": "passport-renewal",
    "description": "Fast-track e-passport renewal support. Includes document audit, online form assistance, appointment slot coordination, and collection escort.",
    "estimated_days": "7-10 working days",
    "is_active": true,
    "category": {
      "id": 1,
      "name": "Passport & Travel Documents",
      "slug": "passport-travel-documents",
      "icon": "passport"
    },
    "requirements": [
      {
        "id": 1,
        "document_name": "National ID Card (NID)",
        "is_mandatory": true
      },
      {
        "id": 2,
        "document_name": "Existing Passport Copy",
        "is_mandatory": true
      },
      {
        "id": 3,
        "document_name": "Recent Lab-Print Photograph (35x45mm)",
        "is_mandatory": true
      }
    ],
    "steps": [
      {
        "id": 1,
        "step_name": "Document Verification & Audit",
        "step_order": 1
      },
      {
        "id": 2,
        "step_name": "Online Application & Challan Generation",
        "step_order": 2
      },
      {
        "id": 3,
        "step_name": "Biometric Appointment Coordination",
        "step_order": 3
      },
      {
        "id": 4,
        "step_name": "Regional Passport Office (RPO) Physical Escort",
        "step_order": 4
      },
      {
        "id": 5,
        "step_name": "Collection & Secure Home Delivery",
        "step_order": 5
      }
    ],
    "fees": [
      {
        "id": 1,
        "fee_type": "GOVERNMENT",
        "amount": 4025
      },
      {
        "id": 2,
        "fee_type": "KORBO",
        "amount": 500
      },
      {
        "id": 3,
        "fee_type": "AGENT",
        "amount": 800
      }
    ],
    "fee_breakdown": {
      "government_fee": 4025,
      "korbo_fee": 500,
      "agent_fee": 800,
      "total_amount": 5325
    }
  }
}
```

---

### 8.3 List Service Categories (`GET /api/v1/service-categories`)
Lists all active categories with counts or nested active services.

* **Endpoint:** `GET /api/v1/service-categories`
* **Query Parameters:** `with_services=true` (to embed active services inside each category)
* **Auth Required:** No (Public)

---

### 8.4 Admin: Create Database-Driven Service (`POST /api/v1/services`)
Allows platform administrators or operations to create a new service with all its requirements, workflow steps, and fees without touching code.

* **Endpoint:** `POST /api/v1/services`
* **Auth Required:** **Yes** (Role: `ADMIN`, `SUPER_ADMIN`, or `OPERATIONS`)

#### Request Body:
```json
{
  "service_category_id": 1,
  "name": "Police Clearance Certificate",
  "slug": "police-clearance-certificate",
  "description": "Official police verification assistance.",
  "estimated_days": "5-7 working days",
  "is_active": true,
  "requirements": [
    { "document_name": "Valid Passport", "is_mandatory": true },
    { "document_name": "National ID Card", "is_mandatory": true }
  ],
  "steps": [
    { "step_name": "Online Police Clearance Filing", "step_order": 1 },
    { "step_name": "Local Thana Liaison & Verification", "step_order": 2 },
    { "step_name": "Delivery of Sealed Certificate", "step_order": 3 }
  ],
  "fees": [
    { "fee_type": "GOVERNMENT", "amount": 500.00 },
    { "fee_type": "KORBO", "amount": 500.00 },
    { "fee_type": "AGENT", "amount": 800.00 }
  ]
}
```

---

### 8.5 Admin: Update Service (`PUT /api/v1/services/{id}`)
Updates service metadata and synchronizes requirements, steps, and fees.

* **Endpoint:** `PUT /api/v1/services/{id}`
* **Auth Required:** **Yes** (Role: `ADMIN`, `SUPER_ADMIN`, or `OPERATIONS`)

---

### 8.6 Admin: Delete Service (`DELETE /api/v1/services/{id}`)
* **Endpoint:** `DELETE /api/v1/services/{id}`
* **Auth Required:** **Yes** (Role: `ADMIN` or `SUPER_ADMIN`)

---

## 💡 Frontend Quick Tips
1. **Token Storage:** Store `token` in `localStorage` or secure cookie upon login/registration/OTP verify.
2. **Auto-attach Header:** Set Axios interceptor:
   ```javascript
   axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
   ```
3. **Session Check:** On initial app load, call `GET /api/v1/me` to check if the session is valid and populate the user state.

---

## 👥 User Roles & Role-Based Routing (RBAC Guide)

The backend supports **6 distinct roles** based on the KORBO blueprint. The user's role is always returned in `user.role` and `user.roles[]` in the auth responses.

### 1. Supported Roles

| Role Name | Description | Default Landing Page |
| :--- | :--- | :--- |
| **`CUSTOMER`** | Standard clients/citizens who request services, upload personal documents, pay fees, and track tasks. | `/dashboard` |
| **`AGENT`** | Verified field assistants who accept tasks, physically visit offices, submit paperwork, and upload task proofs. | `/agent/dashboard` |
| **`PROFESSIONAL`** | Certified experts (Tax consultants, lawyers, accountants) who handle specialized compliance tasks. | `/pro/dashboard` |
| **`OPERATIONS`** | Internal support staff managing customer inquiries, manual agent assignments, and task quality checks. | `/operations/dashboard` |
| **`ADMIN`** | Platform administrators managing services, pricing, agent verification approvals, payouts, and disputes. | `/admin/dashboard` |
| **`SUPER_ADMIN`** | Complete system access and developer controls. | `/admin/dashboard` |

---

### 2. How to Set Role During Registration

* **Default Behavior:** If no role is passed, the user is automatically registered as a **`CUSTOMER`**.
* **Agent Registration:** When an applicant signs up through the *"Become an Agent"* portal, pass `"role": "AGENT"`:

```json
POST /api/v1/auth/register
{
  "name": "Tanvir Hasan",
  "phone": "01712345678",
  "password": "agentpassword123",
  "role": "AGENT"
}
```

---

### 3. Role-Based Routing Logic (Next.js / React Example)

When the user logs in or when your app boots via `GET /api/v1/me`, inspect `response.data.data.role` to redirect them to the proper portal:

```javascript
// Example: After successful Login / OTP Verify or in AuthContext
const handlePostLoginRedirect = (user) => {
  switch (user.role) {
    case 'AGENT':
      router.push('/agent/dashboard');
      break;
    case 'ADMIN':
    case 'SUPER_ADMIN':
    case 'OPERATIONS':
      router.push('/admin/dashboard');
      break;
    case 'PROFESSIONAL':
      router.push('/pro/dashboard');
      break;
    case 'CUSTOMER':
    default:
      router.push('/dashboard');
      break;
  }
};
```

### 4. Protecting Protected Routes in Frontend (React/Next.js)

```javascript
// Middleware / ProtectedRoute Component
export function ProtectedRoute({ allowedRoles, children }) {
  const { user, loading } = useAuth();

  if (loading) return <Spinner />;

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  // Check if user's role is in the allowed list
  if (allowedRoles && !allowedRoles.includes(user.role)) {
    return <Navigate to="/unauthorized" replace />;
  }

  return children;
}

// Usage Example in Router:
// <ProtectedRoute allowedRoles={['CUSTOMER']}><CustomerDashboard /></ProtectedRoute>
// <ProtectedRoute allowedRoles={['AGENT']}><AgentDashboard /></ProtectedRoute>
// <ProtectedRoute allowedRoles={['ADMIN', 'SUPER_ADMIN']}><AdminPanel /></ProtectedRoute>
```

