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
    "role": "CUSTOMER",
    "status": "ACTIVE",
    "email_verified": true,
    "phone_verified": false,
    "roles": ["CUSTOMER"],
    "permissions": [],
    "customer_profile": {
      "id": 1,
      "user_id": 1,
      "phone": null,
      "address": null,
      "date_of_birth": null
    },
    "agent_profile": null,
    "created_at": "2026-10-08T10:42:10+00:00",
    "updated_at": "2026-10-08T10:42:10+00:00"
  }
}
```

---

## 6. Logout
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

