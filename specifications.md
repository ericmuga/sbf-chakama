# SOBA Alumni Portal Functional Specification

## 1 Member Registration

The system shall allow users to register with:

- name
- national ID
- phone
- email
- next of kin
- dependents

Members receive a profile page.

---

## 2 Member Status

Status shall be automatically updated based on payments.

Statuses:

active
lapsed
suspended

Only active members may submit claims.

---

## 3 Subscription Billing

The system shall generate invoices automatically.

Annual SBF subscription:

KES 1000.

Invoices must appear in the member ledger.

---

## 4 Payment Processing

The system shall integrate with Safaricom M-PESA Daraja API. This will be left as a simulated setup awaiting actual testing.

Payments shall be automatically reconciled.

Receipts stored in the system.

Notifications for member due payments and receipts shall be shown on the member's profile

---

## 5 Claims Management

Members shall submit claims with document attachments.

Admins shall:

approve
reject
record reasons

All actions recorded in an audit trail.

---

## 6 Messaging

Members and admins shall communicate via an internal messaging system.

Email notifications shall be sent for:

billing reminders
payment confirmation
claim status

---

## 7 Share Management (Chakama)

Members shall subscribe to shares.

1 share = 10 acres.

Shares may be allocated to third parties.

---

## 8 Project Funding

Admins shall:

create projects
assign budgets
record expenditures

Receipts must be uploaded for withdrawals.

---

## 9 Reporting

Reports shall include:

member balances
share distribution
payments
fund allocations

Exports supported.

---

## 10 Admin Dashboard

Admin dashboard shall show:

billing activity
member statistics
payment summaries
project funds

---

## 11 Data Import

The system shall support bulk import of:

members
payments
shares

CSV or Excel format.
