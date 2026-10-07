

# **KORBO — 30-Day Full Development Work Module**

## **1\. Recommended architecture**

                   ┌──────────────────────┐  
                    │   Next.js / React    │  
                    │   Customer Frontend  │  
                    │   Agent Frontend     │  
                    └──────────┬───────────┘  
                               │  
                         REST API / JSON  
                               │  
                    ┌──────────▼───────────┐  
                    │       Laravel        │  
                    │      REST API        │  
                    │                      │  
                    │ Auth / RBAC          │  
                    │ Service Engine       │  
                    │ Task Engine          │  
                    │ Document Engine      │  
                    │ Agent Matching       │  
                    │ Payment              │  
                    │ Notification         │  
                    │ Review / Complaint   │  
                    └──────┬─────┬─────────┘  
                           │     │  
              ┌────────────┘     └─────────────┐  
              ▼                                ▼  
        PostgreSQL/MySQL                     Redis  
              │                           Queue/Cache  
              │  
              ▼  
       S3-compatible storage  
       Documents / Proofs

For the first month, **do not use microservices**. The specification itself recommends a Laravel monolith with React/Next.js for the MVP.

---

# **2\. Month target**

By the end of 30 days, the MVP should support this complete flow:

Customer  
   ↓  
Register/Login  
   ↓  
Browse Services  
   ↓  
Create Task  
   ↓  
Requirements  
   ↓  
Upload Documents  
   ↓  
Quote  
   ↓  
Confirm  
   ↓  
Payment  
   ↓  
Admin/Agent Assignment  
   ↓  
Agent Accepts  
   ↓  
Agent Requests Document Access  
   ↓  
Customer Approves  
   ↓  
Agent Works  
   ↓  
Status Updates  
   ↓  
Proof Upload  
   ↓  
Customer Confirmation  
   ↓  
Task Completed  
   ↓  
Agent Payout  
   ↓  
Review

This follows the source's core end-to-end workflow.

---

# **WEEK 1 — FOUNDATION**

## **Day 1 — Architecture \+ Project Setup**

### **Backend**

Set up:

* Laravel project  
* Git repository  
* `.env`  
* Database  
* Redis  
* Queue  
* Storage  
* API versioning  
* Sanctum  
* CORS  
* basic exception handling

Structure:

backend/  
├── app/  
│   ├── Actions/  
│   ├── Enums/  
│   ├── Events/  
│   ├── Exceptions/  
│   ├── Http/  
│   │   ├── Controllers/  
│   │   ├── Requests/  
│   │   └── Resources/  
│   ├── Jobs/  
│   ├── Listeners/  
│   ├── Models/  
│   ├── Notifications/  
│   ├── Policies/  
│   ├── Services/  
│   └── Support/  
├── database/  
│   ├── migrations/  
│   ├── seeders/  
│   └── factories/  
├── routes/  
│   └── api.php  
└── tests/

The supplied architecture specifically recommends separating Actions, Enums, Events, Jobs, Policies, Services, etc., rather than putting business logic in controllers.

### **Frontend**

Create Next.js project:

frontend/  
├── app/  
│   ├── (auth)/  
│   ├── dashboard/  
│   ├── services/  
│   ├── tasks/  
│   ├── documents/  
│   ├── payments/  
│   ├── notifications/  
│   ├── agent/  
│   └── profile/  
├── components/  
├── hooks/  
├── lib/  
├── services/  
├── types/  
└── public/

### **Deliverable**

Both projects should:

* run locally  
* connect to database/API  
* have Git branches  
* have `.env.example`  
* have basic README

---

# **Day 2 — Database Design**

Create the core database.

### **Authentication**

users  
roles  
permissions  
role\_user  
permission\_role

### **Customer**

customer\_profiles  
family\_members

### **Agent**

agents  
agent\_verifications  
agent\_skills  
agent\_service\_areas  
agent\_availability

### **Services**

service\_categories  
services  
service\_requirements  
service\_steps  
service\_fees

### **Tasks**

tasks  
task\_assignments  
task\_status\_histories  
task\_notes

### **Documents**

documents  
document\_access\_grants  
document\_access\_logs

### **Operations**

appointments  
submissions  
collections

### **Financial**

payments  
payment\_transactions  
invoices  
refunds  
agent\_payouts

### **Communication**

messages  
notifications

### **Trust**

reviews  
complaints  
disputes  
audit\_logs

These database modules come directly from the supplied architecture.

---

# **Day 3 — Authentication Backend**

Implement:

POST /api/v1/auth/register  
POST /api/v1/auth/login  
POST /api/v1/auth/logout  
POST /api/v1/auth/otp/verify  
GET  /api/v1/me

Features:

* registration  
* login  
* logout  
* Sanctum token/session  
* OTP architecture  
* password hashing  
* email/phone  
* user role  
* account status

Create:

AuthController  
AuthService  
RegisterRequest  
LoginRequest  
AuthResource  
---

# **Day 4 — Authentication Frontend**

Create:

/login  
/register  
/verify-otp  
/forgot-password  
/reset-password

Components:

Input  
Button  
Form  
OTPInput  
PasswordInput  
LoadingSpinner  
Alert

Implement:

* API integration  
* authentication state  
* protected routes  
* logout  
* error handling

---

# **Day 5 — RBAC \+ User Profiles**

### **Backend**

Implement roles:

CUSTOMER  
AGENT  
PROFESSIONAL  
OPERATIONS  
ADMIN  
SUPER\_ADMIN

Create policies:

TaskPolicy  
DocumentPolicy  
AgentPolicy  
PaymentPolicy  
ReviewPolicy

Example:

Customer → own tasks only

Agent → assigned tasks only

Admin → operational access

Super Admin → everything

The source explicitly requires role/permission separation and controlled access for customers, agents, professionals and administrators.

### **Frontend**

Create profile:

/profile

Sections:

* Personal information  
* Phone  
* Email  
* Address  
* Profile photo  
* Language  
* Password

---

# **Day 6 — Service Engine Backend**

This is one of the most important modules.

Do **not** hard-code passport, birth certificate, etc. into controllers.

Create database-driven services:

Service  
ServiceCategory  
ServiceRequirement  
ServiceStep  
ServiceFee

Example:

Passport Renewal  
│  
├── Requirements  
│   ├── NID  
│   ├── Existing Passport  
│   └── Photograph  
│  
├── Steps  
│   ├── Document verification  
│   ├── Application  
│   ├── Appointment  
│   └── Collection  
│  
└── Fees  
    ├── Government fee  
    ├── KORBO fee  
    └── Agent fee

The source explicitly requires admins to be able to create services without changing application code.

---

# **Day 7 — Service Frontend**

Build:

/services  
/services/\[slug\]

Home CTA:

> **আপনার কী কাজ?**

Service categories:

* Passport  
* Academic Documents  
* Birth Certificate  
* Trade License  
* Attestation  
* Tax/TIN

Service page:

Service Name  
Description

What we can do  
Requirements  
Estimated time  
Fee breakdown  
Process

\[Start This Task\]  
---

# **WEEK 2 — CORE TASK SYSTEM**

# **Day 8 — Task Database \+ Status Engine**

Create:

TaskStatus enum

Statuses:

NEW  
REQUIREMENTS\_PENDING  
DOCUMENT\_REVIEW  
QUOTE\_PENDING  
PAYMENT\_PENDING  
AGENT\_MATCHING  
AGENT\_ASSIGNED  
IN\_PROGRESS  
WAITING\_CUSTOMER  
WAITING\_AUTHORITY  
APPOINTMENT\_SCHEDULED  
SUBMITTED  
FOLLOW\_UP  
READY\_FOR\_COLLECTION  
COMPLETED  
CANCELLED  
DISPUTED

These statuses are explicitly defined in the source.

Create a transition system:

NEW  
 ↓  
REQUIREMENTS\_PENDING  
 ↓  
DOCUMENT\_REVIEW  
 ↓  
QUOTE\_PENDING  
 ↓  
PAYMENT\_PENDING  
 ↓  
AGENT\_MATCHING  
 ↓  
AGENT\_ASSIGNED  
 ↓  
IN\_PROGRESS

**Important:** frontend cannot directly set any arbitrary status.

---

# **Day 9 — Task Creation Backend**

API:

POST /api/v1/tasks  
GET /api/v1/tasks  
GET /api/v1/tasks/{id}  
PATCH /api/v1/tasks/{id}

Task contains:

id  
customer\_id  
service\_id  
assigned\_agent\_id  
location  
priority  
price  
government\_fee  
service\_fee  
agent\_fee  
deadline  
appointment\_date  
status  
notes  
created\_at  
completed\_at

The specification defines these as core task fields.

---

# **Day 10 — Task Creation Frontend**

Create:

/tasks/create

Flow:

### **Step 1**

What do you need help with?

### **Step 2**

Select service.

### **Step 3**

Describe problem.

### **Step 4**

Location.

### **Step 5**

Requirements.

### **Step 6**

Upload documents.

### **Step 7**

Review.

### **Step 8**

Submit.

---

# **Day 11 — Document Vault Backend**

Implement secure document system.

Tables:

documents  
document\_access\_grants  
document\_access\_logs

Document metadata:

id  
owner\_id  
task\_id  
document\_type  
file\_name  
storage\_path  
mime\_type  
size  
expiry\_date  
verification\_status

Security:

* private storage  
* MIME validation  
* file size validation  
* encrypted storage architecture  
* signed temporary URLs  
* access expiration  
* download logging

The source specifically says agents must **not automatically receive every customer document** and must use task-based access.

---

# **Day 12 — Document Frontend**

Create:

/documents  
/documents/upload  
/documents/\[id\]

UI:

My Documents

NID  
✓ Verified

Passport  
✓ Verified

Academic Certificate  
Pending verification

Upload:

\[ Upload Document \]

Document Type  
File  
Expiry Date

\[Upload\]  
---

# **Day 13 — Document Permission System**

Customer:

Agent requests NID  
        ↓  
Customer receives notification  
        ↓  
Approve / Reject  
        ↓  
Temporary access  
        ↓  
Task completion  
        ↓  
Access expires

Agent:

Request Document Access

Admin:

Document Access Logs

Log:

Who  
Document  
Task  
Action  
Timestamp  
IP/device  
---

# **Day 14 — Task Timeline**

Create activity timeline.

Example:

✓ Task Created  
  10:15 AM

✓ Documents Requested  
  10:18 AM

✓ NID Uploaded  
  11:05 AM

✓ Agent Assigned  
  11:20 AM

● Application Submitted  
  Pending

○ Completion  
  Pending

Every important action must be logged according to the specification.

---

# **WEEK 3 — AGENT \+ PAYMENT \+ ADMIN**

# **Day 15 — Agent Backend**

Create:

POST /agents/apply  
GET /agents  
GET /agents/{id}  
GET /agent/tasks  
PATCH /agent/tasks/{id}

Agent profile:

Name  
Photo  
Verification  
Rating  
Completed Tasks  
Skills  
Service Areas  
Availability  
Experience  
Performance

The source defines these agent marketplace attributes and matching factors.

---

# **Day 16 — Agent Frontend**

Create:

/agent  
/agent/tasks  
/agent/tasks/\[id\]  
/agent/profile  
/agent/availability  
/agent/earnings

Dashboard:

Welcome, Agent

Assigned Tasks: 8  
Active: 3  
Completed: 35  
Earnings: ৳XX,XXX

\[Available Tasks\]  
---

# **Day 17 — Agent Verification**

Backend:

agent\_verifications

Verification stages:

PENDING  
UNDER\_REVIEW  
VERIFIED  
REJECTED  
SUSPENDED

Verification documents:

* identity  
* phone  
* address  
* skills  
* agreement

The source specifies identity, phone, address/profile, skills, training, agreement and admin approval as part of onboarding.

---

# **Day 18 — Agent Matching**

First version should be simple.

Score based on:

Service Skill  
\+  
Service Area  
\+  
Availability  
\+  
Rating  
\+  
Completion Rate  
\+  
Current Workload

Example:

Agent A  
Skill       100%  
Area        100%  
Available   100%  
Rating       90%  
Workload     80%

Score \= 94

Do not build AI matching in month one. The specification explicitly says to keep the initial matching service simple.

---

# **Day 19 — Quote \+ Pricing**

Every quote must separate:

Government Fee  
KORBO Service Fee  
Agent Assistance Fee  
Other Fee  
\-----------------  
Total

Example:

Government Fee       ৳500  
KORBO Service Fee    ৳300  
Agent Assistance     ৳500  
\--------------------------  
Total              ৳1,300

This separation is a core requirement of the product.

---

# **Day 20 — Payment Architecture**

Create:

payments  
payment\_transactions  
invoices  
refunds  
agent\_payouts

Status:

PENDING  
AUTHORIZED  
PAID  
FAILED  
REFUNDED  
PARTIALLY\_REFUNDED

Architecture:

PaymentService  
      │  
      ├── BkashGateway  
      ├── NagadGateway  
      └── CardGateway

For month one, make the gateway layer integration-ready even if production credentials aren't available.

---

# **Day 21 — Payment Frontend**

Create:

/tasks/\[id\]/payment  
/payments  
/invoices/\[id\]

Show:

Government Fee       ৳500  
Service Fee          ৳300  
Agent Fee            ৳500

Total                ৳1,300

\[Pay Now\]

Never hide the government fee inside the KORBO fee.

---

# **WEEK 4 — ADMIN \+ NOTIFICATION \+ QA**

# **Day 22 — Filament Admin Setup**

Create Laravel Filament panel:

/admin

Resources:

Users  
Customers  
Agents  
Professionals  
Services  
Categories  
Requirements  
Steps  
Pricing  
Tasks  
Appointments  
Documents  
Payments  
Refunds  
Payouts  
Complaints  
Disputes  
Reviews  
Notifications  
Audit Logs  
Reports

These correspond closely to the supplied admin panel requirements.

---

# **Day 23 — Admin Task Operations**

Admin should see:

Task \#KRB-10023

Customer  
Service  
Documents  
Quote  
Payment  
Agent  
Status  
Timeline  
Notes

Actions:

Assign Agent  
Change allowed status  
Request document  
Approve quote  
View payment  
Resolve complaint

Admin should **not** be able to bypass the workflow casually.

---

# **Day 24 — Notifications**

Implement Laravel Notifications \+ Queue.

Events:

TaskCreated  
DocumentUploaded  
DocumentAccessRequested  
DocumentAccessApproved  
QuoteCreated  
PaymentSuccessful  
AgentAssigned  
TaskStatusChanged  
TaskCompleted  
ReviewRequested

Channels:

In-app  
Email  
SMS

The product specification calls for email, SMS and web/in-app notifications.

---

# **Day 25 — Chat**

Implement basic task-based chat.

Customer  
   ↕  
Agent

Do not build a complicated WhatsApp-style system initially.

Message:

messages

Fields:

task\_id  
sender\_id  
receiver\_id  
message  
attachment  
read\_at  
created\_at

Frontend:

ChatWindow  
MessageBubble  
MessageInput  
AttachmentButton  
---

# **Day 26 — Reviews \+ Complaints \+ Disputes**

Customer:

★★★★★

How was your experience?

\[Submit Review\]

Complaint:

Category  
Description  
Attachment  
Submit

Dispute:

Task  
Reason  
Evidence  
Expected Resolution

Admin:

OPEN  
UNDER\_REVIEW  
RESOLVED  
REJECTED  
---

# **Day 27 — Customer Dashboard**

Final dashboard:

Welcome 👋

Active Tasks  
────────────

Passport Renewal  
Status: Appointment Scheduled

Academic Certificate  
Status: In Progress

Trade License  
Status: Completed

Navigation:

Dashboard  
My Tasks  
Services  
Documents  
Payments  
Messages  
Notifications  
Family  
Reminders  
Profile  
Support

The supplied blueprint lists these customer-facing modules, including task tracking, documents, payments, family, reminders and support.

---

# **Day 28 — Agent Dashboard Finalization**

Agent dashboard:

Dashboard

Today's Tasks  
Active Tasks  
Completed Tasks  
Pending Requests

Earnings  
Rating  
Acceptance Rate

Task:

Passport Renewal

Customer: XXX  
Location: Dhaka

Required Documents  
✓ NID  
✓ Passport

Current Status:  
Appointment Scheduled

\[Update Status\]  
\[Request Document\]  
\[Upload Proof\]  
\[Message Customer\]  
---

# **Day 29 — Security \+ Testing**

This day should be treated seriously.

## **Backend testing**

Test:

Authentication  
Authorization  
Task creation  
Status transitions  
Document upload  
Document permission  
Payment  
Agent assignment  
Review  
Complaint

## **Security**

Check:

* RBAC  
* policies  
* rate limiting  
* API throttling  
* CSRF  
* XSS  
* SQL injection  
* file upload validation  
* private storage  
* signed URLs  
* OTP security  
* session security  
* audit logging

The source specifically lists authentication, authorization, RBAC, policies, rate limiting, secure uploads, encrypted storage, audit logs and temporary document access.

---

# **Day 30 — Full Integration \+ Deployment**

Run the entire workflow:

Register  
 ↓  
Login  
 ↓  
Service  
 ↓  
Task  
 ↓  
Requirements  
 ↓  
Documents  
 ↓  
Quote  
 ↓  
Payment  
 ↓  
Agent  
 ↓  
Permission  
 ↓  
Task execution  
 ↓  
Proof  
 ↓  
Completion  
 ↓  
Payout  
 ↓  
Review

Then:

### **Backend**

* production `.env`  
* queue worker  
* scheduler  
* Redis  
* database backup  
* storage  
* logs

### **Frontend**

* production build  
* API URL  
* error pages  
* loading states  
* mobile responsiveness

### **Deployment**

Frontend → Vercel / Cloud hosting

Backend → VPS / AWS

Database → PostgreSQL/MySQL

Redis → Redis server

Storage → S3-compatible

Admin → Laravel/Filament  
---

# **FRONTEND MODULE BREAKDOWN**

For the frontend developer, I would divide the work into these exact modules:

| Module | Main Work |
| ----- | ----- |
| Authentication | Login, Register, OTP, Password |
| Home | Hero, service search, CTA |
| Services | Categories, listing, details |
| Task Creation | Multi-step task form |
| Requirements | Checklist |
| Documents | Upload, preview, permissions |
| Quote | Fee breakdown |
| Payment | Checkout, payment status |
| Customer Dashboard | Tasks, activity, summary |
| Task Tracking | Timeline \+ status |
| Agent Portal | Tasks, profile, earnings |
| Chat | Customer-agent messaging |
| Notifications | Alerts |
| Family | Family members |
| Reviews | Rating/review |
| Complaints | Support/dispute |
| Profile | Personal settings |

---

# **BACKEND MODULE BREAKDOWN**

| Module | Main Work |
| ----- | ----- |
| Auth | Sanctum, OTP, sessions |
| RBAC | Roles, permissions, policies |
| User | Profiles |
| Customer | Customer-specific logic |
| Service | Service engine |
| Requirement | Dynamic requirements |
| Task | Task lifecycle |
| Workflow | Status transitions |
| Document | Vault \+ access |
| Agent | Agent management |
| Verification | Agent verification |
| Matching | Agent matching |
| Quote | Pricing |
| Payment | Gateway architecture |
| Invoice | Invoice generation |
| Payout | Agent payout |
| Notification | Email/SMS/in-app |
| Chat | Task messaging |
| Review | Ratings |
| Complaint | Support |
| Dispute | Dispute resolution |
| Audit | System logs |
| Reminder | Expiry/reminder system |
| Admin | Filament operations |

---

# **DATABASE RELATIONSHIP — SIMPLE VIEW**

USER  
 │  
 ├──── CUSTOMER PROFILE  
 │          │  
 │          ├──── FAMILY MEMBERS  
 │          │  
 │          └──── TASKS  
 │                 │  
 │                 ├──── SERVICE  
 │                 ├──── DOCUMENTS  
 │                 ├──── QUOTE  
 │                 ├──── PAYMENT  
 │                 ├──── AGENT  
 │                 ├──── APPOINTMENT  
 │                 ├──── STATUS HISTORY  
 │                 ├──── MESSAGES  
 │                 ├──── PROOF  
 │                 └──── REVIEW  
 │  
 └──── AGENT  
            │  
            ├──── SKILLS  
            ├──── AREAS  
            ├──── AVAILABILITY  
            ├──── VERIFICATION  
            └──── PAYOUTS  
---

# **WHAT SHOULD NOT BE BUILT IN THIS 30-DAY MVP**

Do **not** waste the first month on:

❌ Native Android app  
❌ Native iOS app  
❌ Complex AI agent  
❌ AI-based matching  
❌ Microservices  
❌ 50+ services  
❌ Advanced recommendation engine  
❌ Complex corporate subscription  
❌ WhatsApp automation  
❌ Full professional marketplace  
❌ Advanced analytics platform

The source also explicitly recommends proving the core workflow before adding these advanced features.

---

# **30-DAY TEAM SPLIT**

If you have **2 developers**, I recommend:

### **Backend Developer**

Week 1  
Architecture  
Database  
Auth  
RBAC  
Service Engine

Week 2  
Task  
Workflow  
Documents  
Permissions

Week 3  
Agent  
Matching  
Quote  
Payment

Week 4  
Admin  
Notifications  
Chat  
Reviews  
Security  
Testing

### **Frontend Developer**

Week 1  
Setup  
Design system  
Auth  
Home  
Services

Week 2  
Task creation  
Requirements  
Documents  
Task tracking

Week 3  
Agent portal  
Quote  
Payment  
Chat

Week 4  
Dashboard  
Notifications  
Reviews  
Complaints  
Responsive QA

### **Optional 3rd person**

**Admin/QA/DevOps**

Filament  
Testing  
Deployment  
Security  
Database backup  
Monitoring  
Bug tracking  
---

# **Final MVP Definition**

At the end of this month, don't judge the project by the number of pages.

Judge it by whether this works:

> **A real customer can submit a real administrative task → upload the required documents → receive a transparent quote → pay → get a verified agent → give the agent limited document access → track the work → receive proof → confirm completion → review the agent.**

That is the actual KORBO product. The blueprint explicitly defines the platform as a **task-completion marketplace**, where the platform structures, assigns, controls, tracks and closes real administrative tasks.

**One important recommendation:** for a one-month build, I would make the first release **Customer \+ Agent \+ Admin only**, and start with **Passport, Academic Document and Trade License** workflows. That matches the source's recommended MVP direction and keeps the team focused on proving the core marketplace workflow rather than trying to build the entire long-term platform at once.

