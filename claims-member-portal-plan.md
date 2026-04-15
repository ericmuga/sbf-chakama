# SOBA Alumni Portal — SBF Claims Module & Member Portal

## Implementation Plan for Codex (Step-by-Step)

**Scope:** SBF (Benevolent Fund) members only — Chakama not yet implemented
**Stack:** Laravel 13, Livewire 3, Filament 3, MySQL 8.0+, M-PESA Daraja API
**Auth:** Filament Shield (Spatie Permission) with RBAC
**Queue:** Database driver (shared-hosting compatible)

---

## How It All Connects to Your Finance Schema

```
MEMBER SUBMITS CLAIM                         MEMBER MAKES PAYMENT
       │                                            │
       ▼                                            ▼
┌──────────────┐                           ┌─────────────────┐
│    claims     │                           │ Member pays via  │
│  (new table)  │                           │ M-PESA / portal  │
│  status: draft│                           └────────┬────────┘
└──────┬───────┘                                     │
       │ approval chain                              ▼
       ▼                                    ┌─────────────────┐
┌──────────────┐                            │  cash_receipts   │ ← existing table
│ claim_approvals│                           │  (club receives) │
│ (new table)   │                           │  customer = member│
└──────┬───────┘                            └─────────────────┘
       │ all approved                                │
       ▼                                             ▼
┌──────────────────┐                        ┌──────────────────┐
│ purchase_headers  │ ← existing table      │ customer_ledger  │ ← existing
│ vendor = member   │   claim becomes PO    │ entries           │
│ + purchase_lines  │                       └──────────────────┘
└──────┬───────────┘
       │ posted
       ▼
┌──────────────────┐
│ vendor_payments   │ ← existing table
│ pays to member    │   (bank/M-PESA)
│ bank_account_id   │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│   gl_entries      │ ← single source of truth
│ vendor_ledger_entries │
└──────────────────┘
```

**Key insight:** Each SBF member is BOTH a `customer` (they pay subscriptions to the club) AND a `vendor` (the club pays claims to them). The member's user record links to both.

---

## Architecture Overview

```
app/
├── Models/
│   ├── Claim.php                          ← new
│   ├── ClaimLine.php                      ← new: itemised claim lines
│   ├── ClaimApproval.php                  ← new: approval chain entries
│   ├── ClaimAttachment.php                ← new: evidence uploads
│   ├── ClaimApprovalTemplate.php          ← new: preset approval lists
│   ├── ClaimApprovalTemplateStep.php      ← new: steps in template
│   ├── Member.php                         ← existing (extend)
│   ├── PurchaseHeader.php                 ← existing (extend: claim_id FK)
│   ├── VendorPayment.php                  ← existing (extend: claim_id FK)
│   ├── CashReceipt.php                    ← existing
│   └── ... (existing finance models)
│
├── Filament/
│   ├── AdminPanel/                        ← admin panel (existing)
│   │   └── Resources/
│   │       ├── ClaimResource.php
│   │       ├── ClaimApprovalTemplateResource.php
│   │       └── ... (existing resources)
│   │
│   └── MemberPanel/                       ← NEW: separate Filament panel for members
│       ├── MemberPanelProvider.php
│       ├── Pages/
│       │   └── MemberDashboard.php
│       ├── Resources/
│       │   ├── MyClaimResource.php
│       │   ├── MyPaymentResource.php
│       │   └── MyProjectResource.php
│       └── Widgets/
│           ├── MemberStatsOverview.php
│           ├── RecentClaimsWidget.php
│           ├── RecentPaymentsWidget.php
│           └── UpcomingDuesWidget.php
│
├── Services/
│   ├── ClaimService.php
│   ├── ClaimApprovalService.php
│   ├── MemberPaymentService.php           ← member pays → cash_receipt
│   └── NumberSeriesService.php            ← existing
│
├── Enums/
│   ├── ClaimStatus.php
│   ├── ClaimType.php
│   ├── ApprovalAction.php
│   └── PaymentMethod.php
│
├── Events/
│   ├── ClaimSubmitted.php
│   ├── ClaimApprovalActioned.php
│   ├── ClaimFullyApproved.php
│   ├── ClaimRejected.php
│   ├── ClaimPaymentCreated.php
│   └── MemberPaymentReceived.php
│
├── Notifications/
│   ├── ClaimSubmittedNotification.php
│   ├── ClaimApprovalRequestNotification.php
│   ├── ClaimApprovedNotification.php
│   ├── ClaimRejectedNotification.php
│   ├── ClaimPaymentNotification.php
│   ├── PaymentReceivedNotification.php
│   └── PaymentDueReminderNotification.php
│
├── Policies/
│   ├── ClaimPolicy.php
│   └── MemberPortalPolicy.php
│
└── Observers/
```

---

## Phase 0 — Enums

### Step 0.1 — ClaimStatus

**Codex prompt:**
> "Create `App\Enums\ClaimStatus` string-backed enum. Cases: DRAFT = 'draft', SUBMITTED = 'submitted', UNDER_REVIEW = 'under_review', APPROVED = 'approved', REJECTED = 'rejected', PURCHASE_CREATED = 'purchase_created', PAID = 'paid', CANCELLED = 'cancelled'. Add `label()` and `color()` methods: draft=gray, submitted=info, under_review=warning, approved=success, rejected=danger, purchase_created=primary, paid=success, cancelled=gray."

### Step 0.2 — ClaimType

**Codex prompt:**
> "Create `App\Enums\ClaimType` string-backed enum. Cases: MEDICAL = 'medical', FUNERAL = 'funeral', EDUCATION = 'education', EMERGENCY = 'emergency', OTHER = 'other'. Add `label()` and `color()` methods."

### Step 0.3 — ApprovalAction

**Codex prompt:**
> "Create `App\Enums\ApprovalAction` string-backed enum. Cases: PENDING = 'pending', APPROVED = 'approved', REJECTED = 'rejected', RETURNED = 'returned'. Add `label()` and `color()` methods."

### Step 0.4 — PaymentMethod

**Codex prompt:**
> "Create `App\Enums\PaymentMethod` string-backed enum. Cases: MPESA = 'mpesa', BANK_TRANSFER = 'bank_transfer', CHEQUE = 'cheque'. Add `label()` method."

---

## Phase 1 — Database Migrations

### Step 1.0 — Extend members table (if not already done)

Your members table likely exists. We need to ensure it links to `users`, `customers`, and `vendors`.

**Codex prompt:**
> "Create migration `add_sbf_fields_to_members`. Add to the `members` table (or create it if it doesn't exist — adjust accordingly):
> - `user_id` foreignId nullable constrained to users, unique — links member to login
> - `customer_id` foreignId nullable constrained to customers — member as payer
> - `vendor_id` foreignId nullable constrained to vendors — member as claim payee
> - `is_sbf` boolean default false — SBF membership flag
> - `is_chakama` boolean default false — for future use
> - `bank_name` string(255) nullable
> - `bank_account_name` string(255) nullable
> - `bank_account_no` string(50) nullable
> - `bank_branch` string(255) nullable
> - `mpesa_phone` string(20) nullable — for M-PESA claim payments
> - `preferred_payment_method` string(20) nullable — PaymentMethod enum
>
> Add indexes on user_id, customer_id, vendor_id, is_sbf.
>
> NOTE: If your members table already has some of these columns, only add the missing ones. The key columns are user_id, customer_id, vendor_id, is_sbf, and the bank/mpesa fields."

---

### Step 1.1 — Claim Approval Templates

Preset approval chains that admins configure once.

```
Schema: claim_approval_templates
├── id                  BIGINT UNSIGNED PK
├── name                VARCHAR(255) NOT NULL
├── claim_type          VARCHAR(20) NULLABLE              -- if null, applies to all types
├── is_default          BOOLEAN DEFAULT false
├── is_active           BOOLEAN DEFAULT true
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
```

**Codex prompt:**
> "Create migration for `claim_approval_templates`. Columns: id bigIncrements, name string(255), claim_type string(20) nullable (links to ClaimType enum — nullable means applies to all types), is_default boolean default false, is_active boolean default true, timestamps."

---

### Step 1.2 — Claim Approval Template Steps

```
Schema: claim_approval_template_steps
├── id                  BIGINT UNSIGNED PK
├── template_id         FK → claim_approval_templates.id CASCADE
├── step_order          INT UNSIGNED NOT NULL
├── approver_user_id    FK → users.id                    -- specific approver
├── role_name           VARCHAR(100) NULLABLE             -- OR anyone with this Spatie role
├── is_required         BOOLEAN DEFAULT true
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
UNIQUE(template_id, step_order)
```

**Codex prompt:**
> "Create migration for `claim_approval_template_steps`. Columns: id bigIncrements, template_id foreignId constrained to claim_approval_templates onDelete cascade, step_order unsignedInteger, approver_user_id foreignId nullable constrained to users (nullable — if role_name is used instead), role_name string(100) nullable (Spatie permission role — anyone with this role can approve this step), is_required boolean default true, timestamps. Add unique on [template_id, step_order]. Constraint: at least one of approver_user_id or role_name must be set — enforce in app logic."

---

### Step 1.3 — Claims Table

```
Schema: claims
├── id                  BIGINT UNSIGNED PK
├── no                  VARCHAR(50) UNIQUE                -- from number_series
├── member_id           FK → members.id
├── claim_type          VARCHAR(20) NOT NULL              -- ClaimType enum
├── subject             VARCHAR(255) NOT NULL
├── description         TEXT NULLABLE
├── claimed_amount      DECIMAL(18,4) NOT NULL
├── approved_amount     DECIMAL(18,4) NULLABLE            -- may differ after review
├── status              VARCHAR(20) DEFAULT 'draft'       -- ClaimStatus enum
├── approval_template_id FK → claim_approval_templates.id NULLABLE
├── current_step        INT UNSIGNED DEFAULT 0            -- which approval step we're on
│
│   ── Payee details (auto-populated from member, editable) ──
├── payee_name          VARCHAR(255) NOT NULL
├── payment_method      VARCHAR(20) NULLABLE              -- PaymentMethod enum
├── bank_name           VARCHAR(255) NULLABLE
├── bank_account_name   VARCHAR(255) NULLABLE
├── bank_account_no     VARCHAR(50) NULLABLE
├── bank_branch         VARCHAR(255) NULLABLE
├── mpesa_phone         VARCHAR(20) NULLABLE
│
│   ── Finance links (populated after approval) ──
├── purchase_header_id  FK → purchase_headers.id NULLABLE -- the PO created from this claim
├── vendor_payment_id   FK → vendor_payments.id NULLABLE  -- the payment to the member
├── vendor_id           FK → vendors.id NULLABLE          -- member's vendor record
│
├── submitted_at        TIMESTAMP NULLABLE
├── approved_at         TIMESTAMP NULLABLE
├── rejected_at         TIMESTAMP NULLABLE
├── rejection_reason    TEXT NULLABLE
├── number_series_code  VARCHAR(50)
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
```

**Codex prompt:**
> "Create migration for `claims`. Columns: id bigIncrements, no string(50) unique, member_id foreignId constrained to members, claim_type string(20) not null, subject string(255), description text nullable, claimed_amount decimal(18,4), approved_amount decimal(18,4) nullable, status string(20) default 'draft', approval_template_id foreignId nullable constrained to claim_approval_templates, current_step unsignedInteger default 0, payee_name string(255), payment_method string(20) nullable, bank_name string(255) nullable, bank_account_name string(255) nullable, bank_account_no string(50) nullable, bank_branch string(255) nullable, mpesa_phone string(20) nullable, purchase_header_id foreignId nullable constrained to purchase_headers, vendor_payment_id foreignId nullable constrained to vendor_payments, vendor_id foreignId nullable constrained to vendors, submitted_at timestamp nullable, approved_at timestamp nullable, rejected_at timestamp nullable, rejection_reason text nullable, number_series_code string(50), timestamps, softDeletes. FK number_series_code references code on number_series. Indexes: member_id, status, claim_type, submitted_at."

---

### Step 1.4 — Claim Lines Table

Itemised breakdown of the claim.

```
Schema: claim_lines
├── id                  BIGINT UNSIGNED PK
├── claim_id            FK → claims.id CASCADE
├── line_no             INT NOT NULL
├── description         VARCHAR(500) NOT NULL
├── quantity            DECIMAL(10,4) DEFAULT 1
├── unit_amount         DECIMAL(18,4) NOT NULL
├── line_amount         DECIMAL(18,4) NOT NULL
├── service_id          FK → services.id NULLABLE         -- maps to service for PO creation
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
UNIQUE(claim_id, line_no)
```

**Codex prompt:**
> "Create migration for `claim_lines`. Columns: id bigIncrements, claim_id foreignId constrained to claims onDelete cascade, line_no integer, description string(500), quantity decimal(10,4) default 1, unit_amount decimal(18,4), line_amount decimal(18,4), service_id foreignId nullable constrained to services, timestamps. Unique on [claim_id, line_no]."

---

### Step 1.5 — Claim Approvals Table

Each row = one approver's action on one claim.

```
Schema: claim_approvals
├── id                  BIGINT UNSIGNED PK
├── claim_id            FK → claims.id CASCADE
├── step_order          INT UNSIGNED NOT NULL
├── approver_user_id    FK → users.id
├── action              VARCHAR(20) DEFAULT 'pending'     -- ApprovalAction enum
├── comments            TEXT NULLABLE
├── actioned_at         TIMESTAMP NULLABLE
├── due_by              TIMESTAMP NULLABLE                -- SLA deadline
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
UNIQUE(claim_id, step_order)
```

**Codex prompt:**
> "Create migration for `claim_approvals`. Columns: id bigIncrements, claim_id foreignId constrained to claims onDelete cascade, step_order unsignedInteger, approver_user_id foreignId constrained to users, action string(20) default 'pending', comments text nullable, actioned_at timestamp nullable, due_by timestamp nullable, timestamps. Unique on [claim_id, step_order]."

---

### Step 1.6 — Claim Attachments Table

```
Schema: claim_attachments
├── id                  BIGINT UNSIGNED PK
├── claim_id            FK → claims.id CASCADE
├── uploaded_by         FK → users.id
├── document_type       VARCHAR(50)                       -- e.g. 'medical_report', 'receipt', 'id_copy', 'other'
├── file_name           VARCHAR(255)
├── file_path           VARCHAR(500)
├── file_size           INT UNSIGNED
├── mime_type           VARCHAR(100)
├── created_at          TIMESTAMP
```

**Codex prompt:**
> "Create migration for `claim_attachments`. Columns: id bigIncrements, claim_id foreignId constrained to claims onDelete cascade, uploaded_by foreignId constrained to users, document_type string(50), file_name string(255), file_path string(500), file_size unsignedInteger, mime_type string(100). Only created_at timestamp (append-only)."

---

### Step 1.7 — Number series for claims

**Codex prompt:**
> "Create seeder `ClaimNumberSeriesSeeder`. Insert into number_series: code 'CLAIM', description 'SBF Claim Numbers', prefix 'CLM-', last_no 0, length 6, is_active true.
>
> Also create migration `add_claim_nos_to_sales_setups`. Add to `sales_setups`: claim_nos string(50) nullable, FK references code on number_series nullOnDelete."

---

### Step 1.8 — Add claim_id to purchase_headers and vendor_payments

**Codex prompt:**
> "Create migration `add_claim_id_to_finance_tables`. Add:
> 1. `purchase_headers`: claim_id unsignedBigInteger nullable after project_id (or after status if project_id doesn't exist yet), index. FK to claims.id nullOnDelete.
> 2. `vendor_payments`: claim_id unsignedBigInteger nullable after status, index. FK to claims.id nullOnDelete.
>
> Down: drop FKs and columns."

---

## Phase 2 — Eloquent Models

### Step 2.1 — ClaimApprovalTemplate Model

**Codex prompt:**
> "Create `App\Models\ClaimApprovalTemplate`. Fillable: name, claim_type, is_default, is_active. Cast claim_type → ClaimType nullable. Relationships: `steps()` hasMany ClaimApprovalTemplateStep ordered by step_order. Scope: `scopeActive`, `scopeForType($query, ClaimType $type)` — returns templates where claim_type = $type OR claim_type is null (universal)."

---

### Step 2.2 — ClaimApprovalTemplateStep Model

**Codex prompt:**
> "Create `App\Models\ClaimApprovalTemplateStep`. Fillable: template_id, step_order, approver_user_id, role_name, is_required. Relationships: `template()` belongsTo ClaimApprovalTemplate, `approver()` belongsTo User via approver_user_id nullable."

---

### Step 2.3 — Claim Model

**Codex prompt:**
> "Create `App\Models\Claim` with SoftDeletes. 
>
> Cast: claim_type → ClaimType, status → ClaimStatus, payment_method → PaymentMethod nullable, claimed_amount → decimal:4, approved_amount → decimal:4, submitted_at → datetime, approved_at → datetime, rejected_at → datetime.
>
> Fillable: no, member_id, claim_type, subject, description, claimed_amount, approved_amount, status, approval_template_id, current_step, payee_name, payment_method, bank_name, bank_account_name, bank_account_no, bank_branch, mpesa_phone, purchase_header_id, vendor_payment_id, vendor_id, submitted_at, approved_at, rejected_at, rejection_reason, number_series_code.
>
> Relationships:
> - `member()` belongsTo Member
> - `lines()` hasMany ClaimLine ordered by line_no
> - `approvals()` hasMany ClaimApproval ordered by step_order
> - `attachments()` hasMany ClaimAttachment
> - `approvalTemplate()` belongsTo ClaimApprovalTemplate nullable
> - `purchaseHeader()` belongsTo PurchaseHeader nullable
> - `vendorPayment()` belongsTo VendorPayment nullable
> - `vendor()` belongsTo Vendor nullable
> - `numberSeries()` belongsTo NumberSeries via number_series_code referencing code
>
> Accessors:
> - `currentApproval` → approvals where step_order = current_step, first
> - `isFullyApproved` → all required approvals have action = approved
> - `totalLineAmount` → sum of lines.line_amount
> - `pendingApprover` → the user who needs to act next
>
> Scopes: scopeByStatus, scopeForMember($query, $memberId), scopePendingApproval, scopeByType."

---

### Step 2.4 — ClaimLine Model

**Codex prompt:**
> "Create `App\Models\ClaimLine`. Fillable: claim_id, line_no, description, quantity, unit_amount, line_amount, service_id. Cast: quantity → decimal:4, unit_amount → decimal:4, line_amount → decimal:4. Relationships: `claim()` belongsTo Claim, `service()` belongsTo Service nullable."

---

### Step 2.5 — ClaimApproval Model

**Codex prompt:**
> "Create `App\Models\ClaimApproval`. Fillable: claim_id, step_order, approver_user_id, action, comments, actioned_at, due_by. Cast: action → ApprovalAction, actioned_at → datetime, due_by → datetime. Relationships: `claim()` belongsTo Claim, `approver()` belongsTo User via approver_user_id. Scopes: `scopePending` where action = pending."

---

### Step 2.6 — ClaimAttachment Model

**Codex prompt:**
> "Create `App\Models\ClaimAttachment`. Set `const UPDATED_AT = null`. Fillable: claim_id, uploaded_by, document_type, file_name, file_path, file_size, mime_type. Relationships: `claim()` belongsTo Claim, `uploader()` belongsTo User via uploaded_by."

---

### Step 2.7 — Extend Member Model

**Codex prompt:**
> "In the existing `App\Models\Member` model, add to fillable: user_id, customer_id, vendor_id, is_sbf, is_chakama, bank_name, bank_account_name, bank_account_no, bank_branch, mpesa_phone, preferred_payment_method. Cast: is_sbf → boolean, is_chakama → boolean, preferred_payment_method → PaymentMethod nullable.
>
> Add relationships:
> - `user()` belongsTo User nullable
> - `customer()` belongsTo Customer nullable
> - `vendor()` belongsTo Vendor nullable
> - `claims()` hasMany Claim
>
> Add scope: `scopeSbfMembers` where is_sbf = true.
> Add accessor: `hasPaymentDetails` → bank_account_no is not null OR mpesa_phone is not null."

---

### Step 2.8 — Extend PurchaseHeader Model

**Codex prompt:**
> "In existing PurchaseHeader model, add to fillable: 'claim_id'. Add relationship: `claim()` belongsTo Claim nullable. Add scope: `scopeForClaim($query, $claimId)`."

---

### Step 2.9 — Extend VendorPayment Model (or create if doesn't exist)

**Codex prompt:**
> "In existing `App\Models\VendorPayment` model (or create it), add to fillable: 'claim_id'. Add relationship: `claim()` belongsTo Claim nullable."

---

## Phase 3 — Service Layer

### Step 3.1 — ClaimService

**Codex prompt:**
> "Create `App\Services\ClaimService`. Inject NumberSeriesService.
>
> Methods:
>
> 1. `createClaim(Member $member, array $data): Claim` — DB::transaction. Generate no from 'CLAIM' series. Auto-populate payee fields from member (payee_name = member name, bank details, mpesa_phone). Set status = draft, vendor_id = member.vendor_id. Create claim. Return it. Note: lines and attachments are added separately.
>
> 2. `addLine(Claim $claim, array $lineData): ClaimLine` — validate claim is draft. Auto-calculate line_amount = quantity * unit_amount. Auto-increment line_no (max existing + 10). Create and return.
>
> 3. `removeLine(ClaimLine $line): void` — validate claim is draft. Delete line.
>
> 4. `addAttachment(Claim $claim, array $data, User $uploader): ClaimAttachment` — store file to `claim-attachments/{claim.no}/`. Create record.
>
> 5. `submitClaim(Claim $claim, User $submitter): void` — validate: claim has at least one line, has required attachments, total line amount matches claimed_amount (or update claimed_amount from lines). DB::transaction: find applicable approval template (match by claim_type, fallback to default). Generate ClaimApproval rows from template steps (resolve role_name to a specific user if needed — pick the first user with that Spatie role). Set status = submitted, submitted_at = now(), current_step = 1, approval_template_id. Dispatch ClaimSubmitted event.
>
> 6. `convertToPurchase(Claim $claim): PurchaseHeader` — validate status = approved. DB::transaction: ensure member has a vendor record (create one if member.vendor_id is null — use NumberSeriesService with 'vendor_nos' series, assign vendor_posting_group). Create purchase_header: vendor_id = claim.vendor_id, posting_date = today, status = 'Open', claim_id = claim.id. For each claim_line: create purchase_line with matching description, quantity, unit_amount, line_amount, service_id. Set claim.purchase_header_id, status = purchase_created. Dispatch ClaimPaymentCreated event. Return PO.
>
> 7. `cancelClaim(Claim $claim, User $user, string $reason): void` — validate status is draft or submitted (not yet approved). Set status cancelled, rejection_reason. If submitted, clear pending approvals."

---

### Step 3.2 — ClaimApprovalService

**Codex prompt:**
> "Create `App\Services\ClaimApprovalService`.
>
> Methods:
>
> 1. `approve(ClaimApproval $approval, User $approver, ?string $comments = null, ?Decimal $approvedAmount = null): void` — validate: approval.action is pending, approver matches approval.approver_user_id. DB::transaction: set action = approved, comments, actioned_at = now(). Check if this was the last required step (all required approvals now approved). If yes: set claim.status = approved, claim.approved_at = now(), claim.approved_amount = $approvedAmount ?? claim.claimed_amount. Dispatch ClaimFullyApproved. If no: advance claim.current_step += 1, set claim.status = under_review. Dispatch ClaimApprovalActioned. Notify next approver.
>
> 2. `reject(ClaimApproval $approval, User $approver, string $reason): void` — validate same. DB::transaction: set action = rejected, comments = reason, actioned_at. Set claim.status = rejected, rejected_at = now(), rejection_reason. Dispatch ClaimRejected. Notify member.
>
> 3. `return(ClaimApproval $approval, User $approver, string $comments): void` — send back for more info. Set action = returned, comments, actioned_at. Set claim.status = draft (member can edit and resubmit). Notify member.
>
> 4. `getNextApprover(Claim $claim): ?User` — look at claim.current_step, find matching ClaimApproval, return its approver. If step uses role_name, resolve to user.
>
> 5. `resolveApproverForRole(string $roleName): User` — find first active user with the given Spatie role. Throw if none found."

---

### Step 3.3 — MemberPaymentService

Handles when a member makes a payment (subscription, fee, etc.) — on the member's side it's "I'm paying", on the club's side it creates a `cash_receipt`.

**Codex prompt:**
> "Create `App\Services\MemberPaymentService`. Inject NumberSeriesService.
>
> Methods:
>
> 1. `initiatePayment(Member $member, decimal $amount, string $description, ?int $bankAccountId = null): CashReceipt` — DB::transaction. Ensure member has a customer record (if member.customer_id is null, create one using NumberSeriesService 'customer_nos' series, assign customer_posting_group). Create cash_receipt: no from appropriate series, customer_id = member.customer_id, bank_account_id (use default M-PESA bank account if not specified), posting_date = today, amount, status = 'Open'. Dispatch MemberPaymentReceived event. Return receipt.
>
> 2. `recordMpesaCallback(string $transactionId, decimal $amount, string $phone): CashReceipt` — look up member by mpesa_phone. Call initiatePayment. Store M-PESA transaction reference. (Integration with your existing Daraja callback handler.)
>
> 3. `getMemberStatement(Member $member, ?Date $from = null, ?Date $to = null): Collection` — query customer_ledger_entries for member.customer_id, filtered by date range. Return with running balance."

---

## Phase 4 — Events & Notifications

### Step 4.1 — Events

**Codex prompt:**
> "Create these events in `App\Events`. All use SerializesModels.
> 1. `ClaimSubmitted` — public: Claim $claim
> 2. `ClaimApprovalActioned` — public: ClaimApproval $approval, Claim $claim
> 3. `ClaimFullyApproved` — public: Claim $claim
> 4. `ClaimRejected` — public: Claim $claim
> 5. `ClaimPaymentCreated` — public: Claim $claim, PurchaseHeader $purchaseHeader
> 6. `MemberPaymentReceived` — public: CashReceipt $receipt, Member $member"

---

### Step 4.2 — Notification Classes

**Codex prompt:**
> "Create these Notifications in `App\Notifications`. All ShouldQueue, via ['mail', 'database'].
>
> 1. `ClaimSubmittedNotification` — to admins. Subject: 'New claim {no} submitted by {member name}'.
>
> 2. `ClaimApprovalRequestNotification` — to next approver. Subject: 'Action required: Claim {no} awaits your approval'. Body: claim subject, amount, claimant name, step X of Y, link to review.
>
> 3. `ClaimApprovedNotification` — to member. Subject: 'Your claim {no} has been approved'. Body: approved amount, next steps.
>
> 4. `ClaimRejectedNotification` — to member. Subject: 'Your claim {no} was not approved'. Body: reason.
>
> 5. `ClaimPaymentNotification` — to member. Subject: 'Payment for claim {no} is being processed'. Body: amount, payment method, expected timeline.
>
> 6. `PaymentReceivedNotification` — to member. Subject: 'Payment of KES {amount} received'. Body: receipt no, date, new balance.
>
> 7. `PaymentDueReminderNotification` — to member. Subject: 'SBF subscription payment reminder'. Body: outstanding amount, due date."

---

### Step 4.3 — Event Subscriber

**Codex prompt:**
> "Create `App\Listeners\ClaimEventSubscriber`. Subscribe to:
> - ClaimSubmitted → notify the first approver with ClaimApprovalRequestNotification, notify admins with ClaimSubmittedNotification
> - ClaimApprovalActioned → if more steps remain, notify next approver. Notify member that step X was approved.
> - ClaimFullyApproved → notify member with ClaimApprovedNotification
> - ClaimRejected → notify member with ClaimRejectedNotification
> - ClaimPaymentCreated → notify member with ClaimPaymentNotification
> - MemberPaymentReceived → notify member with PaymentReceivedNotification
>
> Register in EventServiceProvider."

---

## Phase 5 — Admin Panel (Filament)

### Step 5.1 — ClaimApprovalTemplateResource

**Codex prompt:**
> "Create Filament resource `App\Filament\Resources\ClaimApprovalTemplateResource`. Navigation: icon heroicon-o-clipboard-document-check, group 'SBF Settings'.
>
> Form: name required, claim_type select from ClaimType nullable (null = all types), is_default toggle, is_active toggle.
>
> Table: name, claim_type badge, is_default (icon), is_active (icon), steps_count.
>
> Relation manager for `steps`: table with step_order sortable, approver name (or role_name), is_required toggle. Create/Edit modal: step_order auto-increment, approver_user_id select searchable (nullable), role_name select from Spatie roles (nullable), is_required toggle. Validation: at least one of approver_user_id or role_name. Reorderable by step_order."

---

### Step 5.2 — ClaimResource (Admin View)

**Codex prompt:**
> "Create Filament resource `App\Filament\Resources\ClaimResource`. Navigation: icon heroicon-o-document-text, group 'SBF Claims', sort 1.
>
> **Table columns:** no searchable sortable, member.name (label 'Claimant') searchable, claim_type badge, subject limit(40), claimed_amount money KES sortable, approved_amount money KES, status badge color from enum, current_step / total steps (e.g. '2/3'), submitted_at date sortable.
>
> **Filters:** SelectFilter status, SelectFilter claim_type, TernaryFilter 'pending_my_approval' (claims where current approval step has approver = auth user and action = pending).
>
> **Actions:** ViewAction, Action 'approve' (visible if current step approver = auth user and action = pending — modal: optional comments, optional approved_amount override, calls ClaimApprovalService::approve), Action 'reject' (same visibility, modal: reason required, calls reject), Action 'return' (same, modal: comments, calls return), Action 'convert_to_po' (visible if status = approved and purchase_header_id is null, confirmation, calls ClaimService::convertToPurchase).
>
> **Bulk actions:** none for now (approvals are individual).
>
> **Pages:** List, View (custom)."

---

### Step 5.3 — ClaimResource ViewClaim Page (Admin)

**Codex prompt:**
> "Create custom ViewClaim page for ClaimResource.
>
> **Header:** Claim no + subject. Status badge. Actions: approve/reject/return (same logic as table actions), convert_to_po, 'view_purchase' (link to PurchaseHeader if exists), 'view_payment' (link to VendorPayment if exists).
>
> **Infolist sections:**
> 1. 'Claim Details': member name, claim_type, subject, description, claimed_amount, approved_amount, submitted_at, status.
> 2. 'Payee Details': payee_name, payment_method, bank details or M-PESA phone.
> 3. 'Claim Lines' (RepeatableEntry): line_no, description, quantity, unit_amount, line_amount. Summary: total.
> 4. 'Approval Chain' (RepeatableEntry styled as timeline): step_order, approver name, action badge, comments, actioned_at. Highlight current step. Show pending steps grayed out.
> 5. 'Attachments': file list with download links, document_type badge.
> 6. 'Finance Trail' (visible if purchase created): link to PO, PO status, link to vendor payment, payment status."

---

## Phase 6 — Member Portal (Separate Filament Panel)

### Step 6.0 — Create Member Panel Provider

**Codex prompt:**
> "Create a second Filament panel `App\Providers\Filament\MemberPanelProvider`. Configuration:
> - id: 'member'
> - path: 'portal' (URL: /portal)
> - Login page: use Filament's built-in login
> - Colors: customize (different from admin — e.g. primary = indigo)
> - Guard: 'web' (same as admin, but tenant/scope will be different)
> - Middleware: add custom `EnsureMemberAccess` middleware that checks: user has a linked member record, member.is_sbf = true. Redirect to /login with error if not.
> - Navigation: simplified — only member-relevant pages
> - Widgets: register member dashboard widgets
> - Pages: MemberDashboard as default
>
> Register this panel in config/filament.php panels array."

---

### Step 6.1 — EnsureMemberAccess Middleware

**Codex prompt:**
> "Create `App\Http\Middleware\EnsureMemberAccess`. In handle(): check auth()->check(), then check the user has a related member record with is_sbf = true. If not, abort(403) or redirect to login with message 'You do not have member portal access'. Register in the MemberPanel middleware stack."

---

### Step 6.2 — Member Dashboard Page

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Pages\MemberDashboard` extending Filament Page. Set as default page for member panel. Route: '/portal'.
>
> **Widgets (getHeaderWidgets):**
> 1. `MemberStatsOverview` — 4 stat cards: 'Account Balance' (from customer_ledger_entries remaining_amount sum), 'Active Claims' (count of member's claims not in final states), 'Total Claimed This Year' (sum of claimed_amount for current year), 'Last Payment' (most recent cash_receipt amount and date).
>
> 2. `UpcomingDuesWidget` — shows outstanding invoices from sales_headers/customer_ledger_entries for this member's customer record. Table: invoice no, description, amount, due date, days overdue.
>
> 3. `RecentClaimsWidget` — last 5 claims with status badges.
>
> 4. `RecentPaymentsWidget` — last 5 cash_receipts (payments the member made).
>
> **Header actions:** 'New Claim' button (links to MyClaimResource create page), 'Make Payment' button (opens payment modal — see Step 6.5)."

---

### Step 6.3 — MyClaimResource (Member-Scoped)

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Resources\MyClaimResource` for Claim model.
>
> **CRITICAL SCOPING:** In `getEloquentQuery()`, override to: `parent::getEloquentQuery()->where('member_id', auth()->user()->member->id)`. This ensures a member ONLY sees their own claims.
>
> **Navigation:** icon heroicon-o-document-text, label 'My Claims'.
>
> **Table columns:** no, claim_type badge, subject, claimed_amount money KES, approved_amount money KES, status badge, approval progress (current_step / total), submitted_at date.
>
> **Filters:** status, claim_type.
>
> **Actions:** ViewAction, EditAction (only if status = draft or returned), DeleteAction (only if draft).
>
> **Pages:** ListMyClaims, CreateMyClaim (custom), EditMyClaim, ViewMyClaim (custom).
>
> **Table empty state:** message 'You have no claims yet', action 'Submit a Claim'."

---

### Step 6.4 — CreateMyClaim Page

**Codex prompt:**
> "Create custom CreateMyClaim page for MyClaimResource.
>
> **Form (wizard-style with Filament Wizard):**
>
> Step 1 'Claim Details':
> - claim_type Select from ClaimType required
> - subject TextInput required
> - description Textarea
>
> Step 2 'Claim Items' (Repeater):
> - Each item: description TextInput required, quantity TextInput numeric default 1, unit_amount TextInput numeric required prefix KES. Line amount auto-calculated (display only).
> - Total displayed at bottom.
>
> Step 3 'Payment Details':
> - payee_name TextInput (pre-filled from member name, editable)
> - payment_method Select from PaymentMethod
> - Conditional fields: if bank_transfer: bank_name, bank_account_name, bank_account_no, bank_branch (all pre-filled from member record if available). If mpesa: mpesa_phone (pre-filled from member.mpesa_phone).
>
> Step 4 'Supporting Documents':
> - FileUpload repeater: document_type select (medical_report, receipt, id_copy, death_certificate, other), file upload max 5MB each, at least 1 attachment required.
>
> **handleRecordCreation:** Call ClaimService::createClaim with member = auth user's member. Then add lines via ClaimService::addLine for each repeater entry. Then add attachments via ClaimService::addAttachment. DO NOT auto-submit — member can review first.
>
> After creation redirect to ViewMyClaim page."

---

### Step 6.5 — ViewMyClaim Page (Member View)

**Codex prompt:**
> "Create custom ViewMyClaim page.
>
> **Header:** Claim no + subject. Status badge.
>
> **Actions:**
> - 'Submit for Approval' — visible only if status = draft. Confirmation dialog. Calls ClaimService::submitClaim. Changes status to submitted.
> - 'Edit' — visible only if draft or returned.
> - 'Cancel' — visible if draft or submitted.
>
> **Infolist:**
> 1. Claim details: type, subject, description, amount, status, submitted_at.
> 2. Claim lines table: line items with amounts, total.
> 3. Approval progress: visual stepper showing each approval step, who the approver is (name), their action (approved/pending/rejected), comments, timestamp. Current step highlighted. Use a custom Blade component styled as a step indicator.
> 4. Payment details: payee name, method, bank/M-PESA details.
> 5. Attachments: list with download links.
> 6. Finance status (visible after approval): PO number and status, payment number and status.
>
> **NOTE:** Member should NOT see admin-only details like GL entries or internal finance data."

---

### Step 6.6 — MyPaymentResource (Member-Scoped Receipts)

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Resources\MyPaymentResource`. This shows cash_receipts where customer_id = member's customer_id.
>
> **Scoping:** In getEloquentQuery(), filter by customer_id = auth()->user()->member->customer_id.
>
> **Model:** CashReceipt.
>
> **Navigation:** icon heroicon-o-banknotes, label 'My Payments'.
>
> **Table columns:** no, posting_date date, amount money KES, status badge.
>
> **Actions:** ViewAction (shows receipt details in modal).
>
> **Header action:** 'Make Payment' — opens modal form: amount numeric required prefix KES, description text (e.g. 'Annual SBF Subscription 2026'). On submit calls MemberPaymentService::initiatePayment. If M-PESA configured, trigger STK push via Daraja.
>
> **This resource is READ-ONLY for past payments** except for the 'Make Payment' action."

---

### Step 6.7 — MyProjectResource (Member-Scoped Projects)

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Resources\MyProjectResource`. Shows projects where the member's user is in project_members.
>
> **Scoping:** In getEloquentQuery(): `Project::whereHas('members', fn ($q) => $q->where('user_id', auth()->id()))`.
>
> **Navigation:** icon heroicon-o-briefcase, label 'My Projects'.
>
> **Table:** no, name, module badge, status badge, priority badge, due_date.
>
> **View page:** simplified version of admin ViewProject — shows: description, milestones progress, budget/spent (if role is owner/manager, otherwise hide financial details), member list, status history. NO expense management — members can only view.
>
> **NOTE:** Only visible if the member is tagged in at least one project."

---

### Step 6.8 — Member Notifications Page

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Pages\MyNotifications` extending Page. Uses Laravel's built-in database notifications for auth user.
>
> Shows list of notifications: icon based on type, title, body, time ago, read/unread indicator. Mark as read on click. Mark all as read button.
>
> **Navigation:** icon heroicon-o-bell, label 'Notifications', badge showing unread count."

---

### Step 6.9 — Member Statement / Reports Page

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Pages\MyStatement` extending Page.
>
> Shows the member's financial statement: all customer_ledger_entries for their customer_id, ordered by posting_date desc.
>
> **Table columns:** posting_date, document_type, document_no, description, debit (payment), credit (invoice/charge), running balance.
>
> **Filters:** date range (from/to date pickers).
>
> **Actions:** 'Download PDF' — generates a PDF statement using the PDF skill/library. 'Print' — window.print().
>
> **Navigation:** icon heroicon-o-document-chart-bar, label 'My Statement'."

---

## Phase 7 — Policies & Permissions

### Step 7.1 — ClaimPolicy

**Codex prompt:**
> "Create `App\Policies\ClaimPolicy`.
> - viewAny: admin sees all; member sees own (scoped in resource)
> - view: admin OR claim.member.user_id = auth user OR current approver
> - create: SBF member only (is_sbf = true)
> - update: claim.member.user_id = auth AND status in [draft, returned]
> - delete: claim.member.user_id = auth AND status = draft
> - submit: claim.member.user_id = auth AND status = draft
> - approve/reject/return: auth user = current approval step's approver
> - convertToPurchase: admin only AND status = approved
> - cancel: (member AND status in draft/submitted) OR admin"

---

### Step 7.2 — Member Portal Navigation Policy

**Codex prompt:**
> "Create helper method or policy `MemberPortalPolicy` that determines which navigation items show:
> - 'My Claims': always (for SBF members)
> - 'My Payments': always
> - 'My Projects': only if member is tagged in any project
> - 'My Statement': always
> - 'Notifications': always
>
> Implement via Filament's `shouldRegisterNavigation()` on each resource/page."

---

## Phase 8 — Scheduled Tasks

### Step 8.1 — Approval Reminder

**Codex prompt:**
> "Create command `claims:remind-approvers`. Find claim_approvals where action = pending and created_at > 48 hours ago. Notify each approver with a reminder: 'Claim {no} is awaiting your approval for {X} days'. Schedule daily at 9 AM."

---

### Step 8.2 — Payment Due Reminder

**Codex prompt:**
> "Create command `members:payment-reminders`. Find SBF members with open customer_ledger_entries (is_open = true, document_type = Invoice). If due_date is within 7 days or past, notify member with PaymentDueReminderNotification. Schedule weekly Monday 8 AM."

---

## Phase 9 — Tests

### Step 9.1 — ClaimService Tests

**Codex prompt:**
> "Test: creates claim with number from series, auto-populates payee from member, adds lines with correct amounts, submit creates approval chain from template, submit sets status to submitted, cancel works on draft/submitted, convertToPurchase creates PO with correct lines and vendor."

### Step 9.2 — ClaimApprovalService Tests

**Codex prompt:**
> "Test: approve advances to next step, approve on final step sets claim approved, reject sets claim rejected with reason, return resets to draft, wrong approver throws exception, already actioned throws."

### Step 9.3 — MemberPaymentService Tests

**Codex prompt:**
> "Test: creates cash receipt with correct customer, auto-creates customer if member has none, amount matches, statement query returns correct entries with running balance."

### Step 9.4 — Member Portal Scoping Tests

**Codex prompt:**
> "Test: member can only see own claims, member cannot see other member's claims, member cannot access admin panel, non-SBF member cannot access portal, member can see projects they're tagged in but not others."

---

## Execution Order

| Order | Phase | What | Depends On |
|-------|-------|------|------------|
| 1 | Phase 0 | Enums | Nothing |
| 2 | Phase 1.0 | Extend members table | Nothing |
| 3 | Phase 1.1–1.2 | Approval templates tables | Nothing |
| 4 | Phase 1.3–1.6 | Claims + lines + approvals + attachments | Phase 1.0, 1.1 |
| 5 | Phase 1.7–1.8 | Number series + finance table FKs | Phase 1.3 |
| 6 | Phase 2 | Models | Phase 1 |
| 7 | Phase 3 | Services | Phase 2 |
| 8 | Phase 4 | Events & Notifications | Phase 2, 3 |
| 9 | Phase 5 | Admin Panel - Claims | Phase 2, 3, 4 |
| 10 | Phase 6.0–6.1 | Member Panel Provider + Middleware | Phase 2 |
| 11 | Phase 6.2–6.9 | Member Portal Pages & Resources | Phase 6.0, 3, 4 |
| 12 | Phase 7 | Policies | Phase 2, 6 |
| 13 | Phase 8 | Scheduled Commands | Phase 3 |
| 14 | Phase 9 | Tests | All above |

---

## Claim Lifecycle Diagram

```
MEMBER                          ADMIN / APPROVERS                    FINANCE ENGINE
──────                          ─────────────────                    ──────────────

1. Create claim (draft)
   ► add lines
   ► add attachments
   ► fill payee details
   (auto from member record)

2. Submit for approval ────────► 3. Approver 1 receives notification
   status: submitted                Reviews claim
                                    ├── Approve ──► next step (or fully approved)
                                    ├── Reject  ──► member notified, claim rejected
                                    └── Return  ──► member can edit & resubmit

                                4. Approver 2 (if multi-step)
                                    Same approve/reject/return

                                5. Final approval ──────────────► 6. Convert to Purchase
                                   status: approved                  ├── Create vendor (if needed)
                                                                     ├── purchase_headers (PO)
                                                                     ├── purchase_lines
                                                                     └── status: purchase_created

                                                                  7. Post PO
                                                                     ├── gl_entries
                                                                     ├── vendor_ledger_entries
                                                                     └── (existing posting engine)

                                                                  8. Create vendor_payment
                                                                     ├── bank_account or M-PESA
                                                                     ├── gl_entries
                                                                     └── status: paid

9. Member notified ◄──────────────────────────────────────────── Payment processed
   Can view claim status,
   PO reference, payment
   status in member portal
```

---

## Member Portal vs Admin Panel — What Each Sees

```
┌─────────────────────────────────────────────────────┐
│                  MEMBER PORTAL (/portal)             │
│                                                     │
│  Dashboard:                                         │
│  ├── Account balance (what they owe / are owed)     │
│  ├── Active claims count                            │
│  ├── Last payment amount & date                     │
│  └── Outstanding invoices / dues                    │
│                                                     │
│  My Claims:     OWN claims only                     │
│  My Payments:   OWN cash_receipts only              │
│  My Projects:   Projects they're tagged in          │
│  My Statement:  OWN customer_ledger_entries          │
│  Notifications: OWN notifications                   │
│                                                     │
│  Can DO:                                            │
│  ├── Create & submit claims                         │
│  ├── Make payments (creates cash_receipt)            │
│  ├── View own claim approval progress               │
│  └── Download own statement                         │
│                                                     │
│  CANNOT see:                                        │
│  ├── Other members' data                            │
│  ├── GL entries, posting groups, admin settings      │
│  ├── Other members' claims or payments              │
│  ├── Project financials (unless owner/manager)      │
│  └── Approval templates or admin config             │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│                  ADMIN PANEL (/admin)                │
│                                                     │
│  Everything the member sees, PLUS:                  │
│  ├── All claims across all members                  │
│  ├── Approve / reject / return claims               │
│  ├── Convert approved claims to POs                 │
│  ├── Process vendor payments                        │
│  ├── Configure approval templates                   │
│  ├── View all GL entries, ledgers, postings         │
│  ├── Manage members, vendors, customers             │
│  ├── Project management (full control)              │
│  ├── All reports and analytics                      │
│  └── System settings                                │
└─────────────────────────────────────────────────────┘
```

---

## Dual Identity: Member as Customer AND Vendor

```
┌──────────────┐
│    Member     │
│  John Doe     │
│  is_sbf: true │
├──────────────┤
│ user_id ──────►  users table (login)
│ customer_id ──►  customers table (pays subscriptions TO club)
│ vendor_id ────►  vendors table (receives claim payments FROM club)
│ bank details  │  (for receiving claim payouts)
│ mpesa_phone   │  (for receiving M-PESA payouts)
└──────────────┘

When member PAYS subscription:
  member → customer → cash_receipt → customer_ledger_entry → gl_entry

When club PAYS claim to member:
  claim → purchase_header (vendor=member) → vendor_payment → vendor_ledger_entry → gl_entry
```
