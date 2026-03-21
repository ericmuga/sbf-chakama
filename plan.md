# SOBA Alumni Portal Development Plan

Tech Stack
- Laravel 12
- Filament Admin Panel
- Livewire
- Pest Testing
- MySQL
- M-PESA Daraja

---

# Step 0 — Database Schema (DONE)

use db.md
# Step 1 — Authentication (done)
# Step 1 — Authentication (done)

Install Laravel Breeze.

Add roles:

admin
member

RBAC via Spatie Permission.

Filament admin access restricted to admin role.

---

# Step 2 — Member Registration (done)

Create Livewire registration form capturing:

personal details
national ID
phone
email
next of kin
relationship
dependents

Upload documents.

Create Filament MemberResource.

---

# Step 2.1 — Next of Kin Registration (done)

Create Livewire form for next of kin capturing:

personal details
national ID
phone
email
relationship to member
contact preference

Link to member via `NextOfKin` model relationship.

Create Filament NextOfKinResource with member relationship.


# Step 3 — Member Profile

Create member dashboard showing:

profile
status
shares owned
ledger balance
claims history

---

# Step 4 — Billing Engine

Create service:

BillingService

Functions:

generateAnnualInvoice()
generateShareInvoices()

Annual SBF fee:

KES 1000

---

# Step 5 — Ledger System

Create ledger posting service.

Events:

InvoiceCreated
PaymentReceived

Automatically update ledger balance.

---

# Step 6 — M-PESA Integration

Integrate Safaricom Daraja:

STK Push
Callback handler
Payment reconciliation

Update invoice + ledger automatically.

---

# Step 7 — Claims Management (SBF)

Member can:

submit claim
upload documents

Admin can:

approve
reject
add notes

---

# Step 8 — Share Management (Chakama)

Features:

create share
assign share to member
allocate share to third party

---

# Step 9 — Project Fund Management

Admin can:

create projects
record expenses
upload receipts

---

# Step 10 — Messaging

Member ↔ Admin messaging.

Email notifications.

---

# Step 11 — Reporting

Reports:

member balances
share distribution
claims summary
fund movements

Export to Excel.

---

# Step 12 — Notifications

Automated alerts:

invoice reminders
payment confirmation
claim updates

---

# Step 13 — Admin Dashboard

Metrics:

active members
outstanding balances
total shares
fund balances

---

# Step 14 — Testing

Use Pest.

Tests:

registration
billing
payments
claims workflow
share allocation

---

# Step 15 — Deployment

Shared hosting compatible:

queue driver = database
file storage = local
