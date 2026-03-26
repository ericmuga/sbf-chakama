# SOBA Alumni Portal — Chakama Ranch Share & Fund Management Module

## Implementation Plan for Codex (Step-by-Step)

**Scope:** Chakama Ranch module — members with `is_chakama = true`
**Stack:** Laravel 13, Livewire 3, Filament 3, MySQL 8.0+, M-PESA Daraja API
**Borrows from:** SBF finance schema, claims approval chain, member portal pattern
**Agreement Ref:** PHL-SOBA-SDA-2026-001, Workstream 3

---

## What Chakama Does (From Agreement)

1. **Share subscription:** 1 share = 10 acres. First share auto-tagged to registering member.
2. **Multi-person share allocation:** Members can buy shares for third parties (name, ID, contact).
3. **Automated billing:** Invoice generation based on share subscriptions, notifications via portal + email.
4. **M-PESA payment integration:** Collection via Daraja, auto-reconciliation against member ledgers.
5. **Fund & project management:** Track fund movements, share allocations, special project funding. Receipt uploads for all withdrawals.
6. **Admin reporting:** Share distribution, payment status, balances, fund allocations, project expenditure. All exportable.
7. **Historical data import:** Bulk import of existing members, share records, payment history.

---

## How It Maps to Your Finance Schema

```
MEMBER BUYS SHARES                          FUND WITHDRAWAL / PROJECT SPEND
       │                                            │
       ▼                                            ▼
┌──────────────────┐                       ┌────────────────────┐
│ share_subscriptions│ (new)               │ fund_withdrawals    │ (new)
│ member + nominees  │                     │ with receipt upload  │
│ generates invoices │                     │ approval chain       │
└──────┬───────────┘                       └────────┬───────────┘
       │                                            │
       ▼                                            ▼
┌──────────────────┐                       ┌──────────────────┐
│  sales_headers    │ ← existing           │ purchase_headers   │ ← existing
│  (invoice to      │   member is customer │ (vendor = payee)   │   fund disbursement
│   member for      │                      │  + purchase_lines   │
│   share payment)  │                      └──────┬────────────┘
│  + sales_lines    │                             │ posted
└──────┬───────────┘                              ▼
       │                                  ┌──────────────────┐
       ▼                                  │   gl_entries      │
┌──────────────────┐                      │   vendor_ledger   │
│  cash_receipts    │ ← member pays       └──────────────────┘
│  (M-PESA / bank)  │
└──────┬───────────┘
       ▼
┌──────────────────┐
│ customer_ledger   │
│ gl_entries        │ ← single source of truth
└──────────────────┘
```

---

## Architecture

```
app/
├── Models/
│   ├── ShareSubscription.php           ← new: the share record
│   ├── ShareNominee.php                ← new: third-party share holders
│   ├── ShareBillingSchedule.php        ← new: billing config per share type
│   ├── FundWithdrawal.php              ← new: fund disbursement (borrows claim approval)
│   ├── FundWithdrawalApproval.php      ← new: approval chain
│   ├── FundWithdrawalAttachment.php    ← new: receipt uploads
│   ├── FundAccount.php                 ← new: tracks named fund pools
│   ├── FundTransaction.php             ← new: fund ledger (in/out)
│   │
│   │   ─── Extended existing models ───
│   ├── Member.php                      ← extend: shares(), is_chakama
│   ├── SalesHeader.php                 ← extend: share_subscription_id FK
│   ├── PurchaseHeader.php              ← extend: fund_withdrawal_id FK
│   └── CashReceipt.php                ← extend: share_subscription_id FK
│
├── Filament/
│   ├── AdminPanel/Resources/
│   │   ├── ShareSubscriptionResource.php
│   │   ├── FundWithdrawalResource.php
│   │   ├── FundAccountResource.php
│   │   └── ShareBillingScheduleResource.php
│   │
│   └── MemberPanel/                    ← extend existing member portal
│       ├── Resources/
│       │   ├── MyShareResource.php
│       │   ├── MyChakamaBillingResource.php
│       │   └── MyFundWithdrawalResource.php (if member is also admin)
│       ├── Pages/
│       │   ├── ChakamaDashboard.php
│       │   └── MyShareStatement.php
│       └── Widgets/
│           ├── ShareSummaryWidget.php
│           ├── ChakamaBillingWidget.php
│           └── FundOverviewWidget.php
│
├── Services/
│   ├── ShareService.php
│   ├── ShareBillingService.php
│   ├── FundService.php
│   ├── FundWithdrawalService.php
│   └── ChakamaMemberService.php
│
├── Enums/
│   ├── ShareStatus.php
│   ├── FundWithdrawalStatus.php
│   ├── FundTransactionType.php
│   └── ShareBillingFrequency.php
│
├── Events/
├── Notifications/
├── Listeners/
└── Console/Commands/
    ├── GenerateShareInvoices.php
    └── CheckOverdueSharePayments.php
```

---

## Phase 0 — Enums

### Step 0.1 — ShareStatus

**Codex prompt:**
> "Create `App\Enums\ShareStatus` string-backed enum. Cases: ACTIVE = 'active', PENDING_PAYMENT = 'pending_payment', SUSPENDED = 'suspended', TRANSFERRED = 'transferred', CANCELLED = 'cancelled'. Add `label()` and `color()`: active=success, pending_payment=warning, suspended=danger, transferred=info, cancelled=gray."

### Step 0.2 — FundWithdrawalStatus

**Codex prompt:**
> "Create `App\Enums\FundWithdrawalStatus` string-backed enum. Cases: DRAFT = 'draft', SUBMITTED = 'submitted', UNDER_REVIEW = 'under_review', APPROVED = 'approved', REJECTED = 'rejected', PURCHASE_CREATED = 'purchase_created', PAID = 'paid', CANCELLED = 'cancelled'. Add `label()` and `color()`. This mirrors ClaimStatus from the SBF module."

### Step 0.3 — FundTransactionType

**Codex prompt:**
> "Create `App\Enums\FundTransactionType` string-backed enum. Cases: SHARE_PAYMENT = 'share_payment', CONTRIBUTION = 'contribution', WITHDRAWAL = 'withdrawal', PROJECT_ALLOCATION = 'project_allocation', INTEREST = 'interest', ADJUSTMENT = 'adjustment', REFUND = 'refund'. Add `label()` and `color()`."

### Step 0.4 — ShareBillingFrequency

**Codex prompt:**
> "Create `App\Enums\ShareBillingFrequency` string-backed enum. Cases: ONCE = 'once', MONTHLY = 'monthly', QUARTERLY = 'quarterly', ANNUALLY = 'annually'. Add `label()` and `periodInDays(): int` helper."

---

## Phase 1 — Database Migrations

### Step 1.0 — Number Series for Chakama

**Codex prompt:**
> "Create seeder `ChakamNumberSeriesSeeder`. Insert into number_series:
> - code: 'SHARE', description: 'Share Subscription Numbers', prefix: 'SHR-', last_no: 0, length: 6
> - code: 'FWITH', description: 'Fund Withdrawal Numbers', prefix: 'FW-', last_no: 0, length: 6
> - code: 'FUND', description: 'Fund Account Numbers', prefix: 'FUND-', last_no: 0, length: 4
>
> Create migration `add_chakama_nos_to_setups`. Add to `sales_setups`:
> - share_subscription_nos string(50) nullable, FK → number_series.code
> Add to `purchase_setups`:
> - fund_withdrawal_nos string(50) nullable, FK → number_series.code"

---

### Step 1.1 — Fund Accounts Table

Named pools of money (e.g. "Share Capital Fund", "Development Fund", "Emergency Fund").

```
Schema: fund_accounts
├── id                  BIGINT UNSIGNED PK
├── no                  VARCHAR(50) UNIQUE              -- from 'FUND' series
├── name                VARCHAR(255) NOT NULL
├── description         TEXT NULLABLE
├── gl_account_no       VARCHAR(50)                     -- FK gl_accounts.no
├── balance             DECIMAL(18,4) DEFAULT 0         -- cached
├── is_active           BOOLEAN DEFAULT true
├── number_series_code  VARCHAR(50)
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
```

**Codex prompt:**
> "Create migration for `fund_accounts`. Columns: id bigIncrements, no string(50) unique, name string(255), description text nullable, gl_account_no string(50), balance decimal(18,4) default 0, is_active boolean default true, number_series_code string(50), timestamps. FK gl_account_no → gl_accounts.no. FK number_series_code → number_series.code."

---

### Step 1.2 — Share Billing Schedules Table

Configures how shares are billed (price per share, frequency, etc.).

```
Schema: share_billing_schedules
├── id                  BIGINT UNSIGNED PK
├── name                VARCHAR(255)                    -- e.g. "Standard Share Plan"
├── price_per_share     DECIMAL(18,4) NOT NULL          -- KES per share
├── acres_per_share     INT UNSIGNED DEFAULT 10
├── billing_frequency   VARCHAR(20) DEFAULT 'once'      -- ShareBillingFrequency enum
├── is_default          BOOLEAN DEFAULT false
├── is_active           BOOLEAN DEFAULT true
├── fund_account_id     FK → fund_accounts.id           -- where payments go
├── service_id          FK → services.id NULLABLE       -- for invoice line item
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
```

**Codex prompt:**
> "Create migration for `share_billing_schedules`. Columns: id bigIncrements, name string(255), price_per_share decimal(18,4), acres_per_share unsignedInteger default 10, billing_frequency string(20) default 'once', is_default boolean default false, is_active boolean default true, fund_account_id foreignId constrained to fund_accounts, service_id foreignId nullable constrained to services, timestamps."

---

### Step 1.3 — Share Subscriptions Table

```
Schema: share_subscriptions
├── id                  BIGINT UNSIGNED PK
├── no                  VARCHAR(50) UNIQUE              -- from 'SHARE' series
├── member_id           FK → members.id                 -- the subscribing/paying member
├── billing_schedule_id FK → share_billing_schedules.id
├── number_of_shares    INT UNSIGNED NOT NULL DEFAULT 1
├── total_acres         INT UNSIGNED GENERATED           -- number_of_shares * acres_per_share
├── price_per_share     DECIMAL(18,4)                   -- snapshot at time of subscription
├── total_amount        DECIMAL(18,4)                   -- shares * price
├── amount_paid         DECIMAL(18,4) DEFAULT 0         -- cached from payments
├── amount_outstanding  DECIMAL(18,4) GENERATED          -- total_amount - amount_paid
├── status              VARCHAR(20) DEFAULT 'pending_payment'
├── is_first_share      BOOLEAN DEFAULT false            -- auto-tagged per agreement
│
│   ── Nominee (if shares are for a third party) ──
├── is_nominee          BOOLEAN DEFAULT false
├── nominee_id          FK → share_nominees.id NULLABLE
│
├── subscribed_at       DATE NOT NULL
├── next_billing_date   DATE NULLABLE                   -- for recurring billing
├── number_series_code  VARCHAR(50)
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
```

**Codex prompt:**
> "Create migration for `share_subscriptions`. Columns: id bigIncrements, no string(50) unique, member_id foreignId constrained to members, billing_schedule_id foreignId constrained to share_billing_schedules, number_of_shares unsignedInteger default 1, total_acres unsignedInteger storedAs('number_of_shares * 10') — or compute in model if stored generated columns cause issues, price_per_share decimal(18,4), total_amount decimal(18,4), amount_paid decimal(18,4) default 0, amount_outstanding decimal(18,4) storedAs('total_amount - amount_paid') — same caveat, status string(20) default 'pending_payment', is_first_share boolean default false, is_nominee boolean default false, nominee_id unsignedBigInteger nullable, subscribed_at date, next_billing_date date nullable, number_series_code string(50), timestamps, softDeletes. FK number_series_code → number_series.code. Indexes: member_id, status, subscribed_at, nominee_id. Note: if MySQL has issues with storedAs on decimal, use virtual columns or compute in model accessors instead."

---

### Step 1.4 — Share Nominees Table

Third parties who hold shares on behalf of a subscribing member.

```
Schema: share_nominees
├── id                  BIGINT UNSIGNED PK
├── member_id           FK → members.id                 -- the paying member
├── full_name           VARCHAR(255) NOT NULL
├── national_id         VARCHAR(50) NOT NULL
├── phone               VARCHAR(20) NULLABLE
├── email               VARCHAR(255) NULLABLE
├── relationship        VARCHAR(100) NULLABLE            -- e.g. spouse, child, sibling
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
UNIQUE(member_id, national_id)
```

**Codex prompt:**
> "Create migration for `share_nominees`. Columns: id bigIncrements, member_id foreignId constrained to members, full_name string(255), national_id string(50), phone string(20) nullable, email string(255) nullable, relationship string(100) nullable, timestamps. Unique on [member_id, national_id]."

---

### Step 1.5 — Fund Transactions Table (Fund Ledger)

Every money movement in/out of a fund account.

```
Schema: fund_transactions
├── id                  BIGINT UNSIGNED PK
├── fund_account_id     FK → fund_accounts.id
├── transaction_type    VARCHAR(30)                     -- FundTransactionType enum
├── description         VARCHAR(500)
├── amount              DECIMAL(18,4) NOT NULL          -- positive = in, negative = out
├── running_balance     DECIMAL(18,4)                   -- balance after this txn
├── reference_type      VARCHAR(50) NULLABLE            -- polymorphic: ShareSubscription, FundWithdrawal, Project
├── reference_id        BIGINT UNSIGNED NULLABLE
├── document_no         VARCHAR(50) NULLABLE            -- linked GL document_no
├── posting_date        DATE NOT NULL
├── created_by          FK → users.id
├── created_at          TIMESTAMP
```

**Codex prompt:**
> "Create migration for `fund_transactions`. Columns: id bigIncrements, fund_account_id foreignId constrained to fund_accounts, transaction_type string(30), description string(500), amount decimal(18,4), running_balance decimal(18,4), reference_type string(50) nullable, reference_id unsignedBigInteger nullable, document_no string(50) nullable, posting_date date, created_by foreignId constrained to users, timestamp created_at only (append-only). Indexes: fund_account_id, transaction_type, posting_date, [reference_type, reference_id]."

---

### Step 1.6 — Fund Withdrawals Table

Borrows from SBF claims — approval chain, converts to PO + payment.

```
Schema: fund_withdrawals
├── id                  BIGINT UNSIGNED PK
├── no                  VARCHAR(50) UNIQUE
├── fund_account_id     FK → fund_accounts.id
├── project_id          FK → projects.id NULLABLE        -- if withdrawal is for a project
├── description         VARCHAR(500) NOT NULL
├── amount              DECIMAL(18,4) NOT NULL
├── status              VARCHAR(20) DEFAULT 'draft'      -- FundWithdrawalStatus
├── approval_template_id FK → claim_approval_templates.id NULLABLE  -- REUSE SBF templates
├── current_step        INT UNSIGNED DEFAULT 0
│
│   ── Payee details ──
├── payee_name          VARCHAR(255) NOT NULL
├── payment_method      VARCHAR(20) NULLABLE
├── bank_name           VARCHAR(255) NULLABLE
├── bank_account_name   VARCHAR(255) NULLABLE
├── bank_account_no     VARCHAR(50) NULLABLE
├── bank_branch         VARCHAR(255) NULLABLE
├── mpesa_phone         VARCHAR(20) NULLABLE
├── vendor_id           FK → vendors.id NULLABLE
│
│   ── Finance links ──
├── purchase_header_id  FK → purchase_headers.id NULLABLE
├── vendor_payment_id   FK → vendor_payments.id NULLABLE
│
├── submitted_at        TIMESTAMP NULLABLE
├── approved_at         TIMESTAMP NULLABLE
├── rejected_at         TIMESTAMP NULLABLE
├── rejection_reason    TEXT NULLABLE
├── submitted_by        FK → users.id NULLABLE
├── number_series_code  VARCHAR(50)
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
```

**Codex prompt:**
> "Create migration for `fund_withdrawals`. Columns: id bigIncrements, no string(50) unique, fund_account_id foreignId constrained to fund_accounts, project_id foreignId nullable constrained to projects, description string(500), amount decimal(18,4), status string(20) default 'draft', approval_template_id foreignId nullable constrained to claim_approval_templates (reusing SBF approval templates), current_step unsignedInteger default 0, payee_name string(255), payment_method string(20) nullable, bank_name string(255) nullable, bank_account_name string(255) nullable, bank_account_no string(50) nullable, bank_branch string(255) nullable, mpesa_phone string(20) nullable, vendor_id foreignId nullable constrained to vendors, purchase_header_id foreignId nullable constrained to purchase_headers, vendor_payment_id foreignId nullable constrained to vendor_payments, submitted_at timestamp nullable, approved_at timestamp nullable, rejected_at timestamp nullable, rejection_reason text nullable, submitted_by foreignId nullable constrained to users, number_series_code string(50), timestamps, softDeletes. FK number_series_code → number_series.code. Indexes: fund_account_id, status, project_id, submitted_at."

---

### Step 1.7 — Fund Withdrawal Approvals Table

**Codex prompt:**
> "Create migration for `fund_withdrawal_approvals`. IDENTICAL structure to `claim_approvals` but references fund_withdrawals instead: id bigIncrements, fund_withdrawal_id foreignId constrained to fund_withdrawals onDelete cascade, step_order unsignedInteger, approver_user_id foreignId constrained to users, action string(20) default 'pending', comments text nullable, actioned_at timestamp nullable, due_by timestamp nullable, timestamps. Unique on [fund_withdrawal_id, step_order]."

---

### Step 1.8 — Fund Withdrawal Attachments Table

**Codex prompt:**
> "Create migration for `fund_withdrawal_attachments`. Columns: id bigIncrements, fund_withdrawal_id foreignId constrained to fund_withdrawals onDelete cascade, uploaded_by foreignId constrained to users, document_type string(50), file_name string(255), file_path string(500), file_size unsignedInteger, mime_type string(100), created_at timestamp only."

---

### Step 1.9 — Add FKs to existing finance tables

**Codex prompt:**
> "Create migration `add_chakama_fks_to_finance_tables`. Add:
> 1. `sales_headers`: share_subscription_id unsignedBigInteger nullable, index, FK → share_subscriptions.id nullOnDelete.
> 2. `cash_receipts`: share_subscription_id unsignedBigInteger nullable, index, FK → share_subscriptions.id nullOnDelete.
> 3. `purchase_headers`: fund_withdrawal_id unsignedBigInteger nullable, index, FK → fund_withdrawals.id nullOnDelete.
> 4. `vendor_payments`: fund_withdrawal_id unsignedBigInteger nullable, index, FK → fund_withdrawals.id nullOnDelete.
> Down: drop all."

---

## Phase 2 — Models

### Step 2.1 — FundAccount Model

**Codex prompt:**
> "Create `App\Models\FundAccount`. Fillable: no, name, description, gl_account_no, balance, is_active, number_series_code. Cast: balance → decimal:4. Relationships: glAccount() belongsTo GlAccount via gl_account_no ref no, transactions() hasMany FundTransaction, withdrawals() hasMany FundWithdrawal, billingSchedules() hasMany ShareBillingSchedule. Scopes: scopeActive."

### Step 2.2 — ShareBillingSchedule Model

**Codex prompt:**
> "Create `App\Models\ShareBillingSchedule`. Fillable: name, price_per_share, acres_per_share, billing_frequency, is_default, is_active, fund_account_id, service_id. Cast: price_per_share → decimal:4, billing_frequency → ShareBillingFrequency. Relationships: fundAccount() belongsTo FundAccount, service() belongsTo Service nullable, subscriptions() hasMany ShareSubscription."

### Step 2.3 — ShareSubscription Model

**Codex prompt:**
> "Create `App\Models\ShareSubscription` with SoftDeletes. Cast: status → ShareStatus, price_per_share → decimal:4, total_amount → decimal:4, amount_paid → decimal:4, subscribed_at → date, next_billing_date → date. Fillable: no, member_id, billing_schedule_id, number_of_shares, price_per_share, total_amount, amount_paid, status, is_first_share, is_nominee, nominee_id, subscribed_at, next_billing_date, number_series_code.
>
> Relationships: member() belongsTo Member, billingSchedule() belongsTo ShareBillingSchedule, nominee() belongsTo ShareNominee nullable, invoices() hasMany SalesHeader via share_subscription_id, payments() hasMany CashReceipt via share_subscription_id.
>
> Accessors: amountOutstanding → total_amount - amount_paid, totalAcres → number_of_shares * (billingSchedule->acres_per_share ?? 10), isFullyPaid → amount_paid >= total_amount, holderName → is_nominee && nominee ? nominee.full_name : member.name.
>
> Scopes: scopeForMember, scopeByStatus, scopePendingPayment, scopeActive."

### Step 2.4 — ShareNominee Model

**Codex prompt:**
> "Create `App\Models\ShareNominee`. Fillable: member_id, full_name, national_id, phone, email, relationship. Relationships: member() belongsTo Member, shares() hasMany ShareSubscription via nominee_id."

### Step 2.5 — FundTransaction Model

**Codex prompt:**
> "Create `App\Models\FundTransaction`. Set UPDATED_AT = null. Cast: transaction_type → FundTransactionType, amount → decimal:4, running_balance → decimal:4, posting_date → date. Fillable: fund_account_id, transaction_type, description, amount, running_balance, reference_type, reference_id, document_no, posting_date, created_by. Relationships: fundAccount() belongsTo FundAccount, creator() belongsTo User via created_by. Polymorphic: reference() morphTo. Scopes: scopeByType, scopeInflow (amount > 0), scopeOutflow (amount < 0), scopeDateRange."

### Step 2.6 — FundWithdrawal Model

**Codex prompt:**
> "Create `App\Models\FundWithdrawal` with SoftDeletes. Cast: status → FundWithdrawalStatus, amount → decimal:4, payment_method → PaymentMethod nullable, submitted_at/approved_at/rejected_at → datetime. Fillable: all columns listed.
>
> Relationships: fundAccount() belongsTo FundAccount, project() belongsTo Project nullable, approvals() hasMany FundWithdrawalApproval ordered by step_order, attachments() hasMany FundWithdrawalAttachment, approvalTemplate() belongsTo ClaimApprovalTemplate nullable (REUSE), purchaseHeader() belongsTo PurchaseHeader nullable, vendorPayment() belongsTo VendorPayment nullable, vendor() belongsTo Vendor nullable, submitter() belongsTo User via submitted_by.
>
> Accessors: currentApproval, isFullyApproved, pendingApprover (same pattern as Claim model).
> Scopes: scopeByStatus, scopeForFund, scopePendingApproval."

### Step 2.7 — FundWithdrawalApproval & Attachment Models

**Codex prompt:**
> "Create `App\Models\FundWithdrawalApproval`. Identical structure to ClaimApproval but with fund_withdrawal_id FK. Cast action → ApprovalAction (REUSE enum from SBF). Relationships: fundWithdrawal() belongsTo FundWithdrawal, approver() belongsTo User.
>
> Create `App\Models\FundWithdrawalAttachment`. Identical to ClaimAttachment but fund_withdrawal_id FK."

### Step 2.8 — Extend Member Model for Chakama

**Codex prompt:**
> "In existing Member model, add relationships:
> - `shareSubscriptions()` hasMany ShareSubscription
> - `nominees()` hasMany ShareNominee
>
> Add accessors:
> - `totalShares` → sum of shareSubscriptions where status = active, number_of_shares
> - `totalAcres` → totalShares * 10
> - `isChakamaMember` → is_chakama === true
>
> Add scope: `scopeChakamMembers` where is_chakama = true."

### Step 2.9 — Extend SalesHeader, CashReceipt

**Codex prompt:**
> "In SalesHeader model, add to fillable: 'share_subscription_id'. Add: shareSubscription() belongsTo ShareSubscription nullable.
> In CashReceipt model, add to fillable: 'share_subscription_id'. Add: shareSubscription() belongsTo ShareSubscription nullable."

---

## Phase 3 — Services

### Step 3.1 — ShareService

**Codex prompt:**
> "Create `App\Services\ShareService`. Inject NumberSeriesService.
>
> 1. `subscribe(Member $member, array $data): ShareSubscription` — DB::transaction. Generate no from 'SHARE' series. Get billing schedule, snapshot price_per_share. Calculate total_amount = shares * price. If member has no existing shares, set is_first_share = true. If nominee data provided, create ShareNominee first, set is_nominee = true, nominee_id. Create subscription with status pending_payment. Generate first invoice via ShareBillingService. Record fund transaction (share_payment inflow to fund_account). Return subscription.
>
> 2. `allocateToNominee(Member $member, ShareSubscription $subscription, array $nomineeData): void` — validate subscription belongs to member. Create/update ShareNominee. Link subscription. Requires admin approval (create approval chain).
>
> 3. `activateSubscription(ShareSubscription $subscription): void` — called when fully paid. Set status = active. Notify member.
>
> 4. `suspendSubscription(ShareSubscription $subscription, string $reason): void` — set status suspended. Notify.
>
> 5. `transferSubscription(ShareSubscription $sub, Member $newMember): void` — admin only. DB::transaction. Set old sub status = transferred. Create new sub for new member with same details. Audit trail.
>
> 6. `getMemberShareSummary(Member $member): array` — returns: total_shares, total_acres, shares_by_status, nominees with their shares, total_paid, total_outstanding."

### Step 3.2 — ShareBillingService

**Codex prompt:**
> "Create `App\Services\ShareBillingService`. Inject NumberSeriesService.
>
> 1. `generateInvoice(ShareSubscription $subscription): SalesHeader` — DB::transaction. Ensure member has customer record (create if null, like SBF). Create sales_header: customer_id = member.customer_id, document_type = 'Invoice', posting_date = today, share_subscription_id. Create sales_line: service_id from billing schedule, description = 'Share subscription {no} — {shares} shares', quantity = number_of_shares, unit_price = price_per_share, line_amount = total_amount. Return invoice.
>
> 2. `recordPayment(ShareSubscription $sub, CashReceipt $receipt): void` — update sub.amount_paid += receipt.amount. If fully paid, call ShareService::activateSubscription. Record FundTransaction inflow.
>
> 3. `generateRecurringInvoices(): int` — find all active subs with billing_frequency != once and next_billing_date <= today. Generate invoice for each. Update next_billing_date. Return count generated. Used by scheduled command."

### Step 3.3 — FundService

**Codex prompt:**
> "Create `App\Services\FundService`. Inject NumberSeriesService.
>
> 1. `createFundAccount(array $data): FundAccount` — generate no from 'FUND' series. Create.
>
> 2. `recordTransaction(FundAccount $fund, FundTransactionType $type, decimal $amount, string $description, ?Model $reference = null, ?string $documentNo = null, ?User $user = null): FundTransaction` — DB::transaction. Calculate running_balance = fund.balance + amount. Create FundTransaction. Update fund.balance = running_balance.
>
> 3. `getFundStatement(FundAccount $fund, ?Date $from, ?Date $to): Collection` — fund_transactions filtered by date. With running balance.
>
> 4. `recalculateBalance(FundAccount $fund): void` — sum all fund_transactions.amount. Update fund.balance. Safety net."

### Step 3.4 — FundWithdrawalService

Mirrors ClaimService almost exactly.

**Codex prompt:**
> "Create `App\Services\FundWithdrawalService`. Inject NumberSeriesService. This mirrors ClaimService from SBF.
>
> 1. `createWithdrawal(FundAccount $fund, array $data, User $submitter): FundWithdrawal` — generate no from 'FWITH'. Create with status draft, submitted_by.
>
> 2. `submitWithdrawal(FundWithdrawal $withdrawal): void` — find approval template (reuse claim_approval_templates — match by a 'fund_withdrawal' type or use default). Generate FundWithdrawalApproval rows. Set submitted, current_step = 1. Dispatch FundWithdrawalSubmitted.
>
> 3. `approveStep(FundWithdrawalApproval $approval, User $approver, ?string $comments): void` — mirror ClaimApprovalService::approve. If fully approved, set withdrawal approved.
>
> 4. `rejectStep(FundWithdrawalApproval $approval, User $approver, string $reason): void` — mirror reject.
>
> 5. `convertToPurchase(FundWithdrawal $withdrawal): PurchaseHeader` — create PO for the payee (create vendor if needed). Set fund_withdrawal_id on PO. Record FundTransaction outflow.
>
> 6. `cancelWithdrawal(FundWithdrawal $w, User $user, string $reason): void`."

---

## Phase 4 — Events & Notifications

**Codex prompt:**
> "Create these events:
> 1. `ShareSubscribed` — ShareSubscription
> 2. `SharePaymentReceived` — ShareSubscription, CashReceipt
> 3. `ShareActivated` — ShareSubscription
> 4. `FundWithdrawalSubmitted` — FundWithdrawal
> 5. `FundWithdrawalApproved` — FundWithdrawal
> 6. `FundWithdrawalRejected` — FundWithdrawal
> 7. `FundWithdrawalPaid` — FundWithdrawal
>
> Create notifications:
> 1. `ShareInvoiceNotification` — to member: invoice generated for share subscription
> 2. `SharePaymentConfirmation` — to member: payment received, X shares now active
> 3. `SharePaymentOverdueNotification` — to member: outstanding balance reminder
> 4. `FundWithdrawalApprovalRequest` — to approver (reuse pattern from SBF)
> 5. `FundWithdrawalApprovedNotification` — to submitter
> 6. `FundWithdrawalRejectedNotification` — to submitter
>
> Create subscriber `ChakamEventSubscriber`:
> - ShareSubscribed → generate invoice notification
> - SharePaymentReceived → payment confirmation
> - ShareActivated → activation confirmation
> - FundWithdrawalSubmitted → notify first approver
> - FundWithdrawalApproved → notify submitter
> - FundWithdrawalRejected → notify submitter"

---

## Phase 5 — Admin Panel Resources

### Step 5.1 — ShareBillingScheduleResource

**Codex prompt:**
> "Create Filament resource for ShareBillingSchedule. Navigation: group 'Chakama Settings'. Form: name, price_per_share numeric prefix KES, acres_per_share integer default 10, billing_frequency select, fund_account_id select, service_id select, is_default toggle, is_active toggle. Table: name, price, acres, frequency, fund account name, is_default, is_active."

### Step 5.2 — FundAccountResource

**Codex prompt:**
> "Create Filament resource for FundAccount. Navigation: group 'Chakama Funds', icon heroicon-o-building-library. Form: name, description, gl_account_no select searchable from gl_accounts. Table: no, name, gl_account_no, balance money KES, is_active. View page: show fund details + transactions list (relation manager) + charts.
>
> Transactions relation manager: table showing fund_transactions ordered by created_at desc. Columns: posting_date, type badge, description, amount (green if positive, red if negative) money KES, running_balance money KES, reference link, created_by name. Filters: transaction_type, date range. Read-only — transactions are created by services only."

### Step 5.3 — ShareSubscriptionResource

**Codex prompt:**
> "Create Filament resource for ShareSubscription. Navigation: group 'Chakama Shares', icon heroicon-o-map. 
>
> Table: no, member.name searchable, number_of_shares, total_acres, holder_name (accessor), price_per_share money, total_amount money, amount_paid money, amount_outstanding money, status badge, is_first_share icon, subscribed_at date. Filters: status, is_nominee, is_first_share.
>
> Form (admin create): member_id select searchable, billing_schedule_id select, number_of_shares integer min 1, nominee toggle → conditional nominee fields (full_name, national_id, phone, email, relationship). Price and totals auto-calculated from billing schedule.
>
> View page: all details + invoices (sales_headers where share_subscription_id) + payments (cash_receipts where share_subscription_id) + nominee details.
>
> Actions: 'activate' (if pending + fully paid), 'suspend', 'transfer' (modal: select new member)."

### Step 5.4 — FundWithdrawalResource

**Codex prompt:**
> "Create Filament resource for FundWithdrawal. Navigation: group 'Chakama Funds'. Mirrors ClaimResource from SBF:
>
> Table: no, fund_account.name, description limit 40, amount money KES, status badge, approval progress, submitted_at. Filters: status, fund_account.
>
> Actions: approve/reject/return (same pattern as claims), convert_to_po, view_purchase, view_payment.
>
> Create form: fund_account_id select, description, amount, payee details, project_id select nullable (link to project module). Attachments repeater.
>
> View page: details, approval chain timeline, attachments, finance trail."

---

## Phase 6 — Member Portal (Conditional Chakama Views)

### Step 6.0 — Amend MemberPanelProvider for Dual-Module Support

**Codex prompt:**
> "Modify `App\Providers\Filament\MemberPanelProvider` (created in the SBF plan). The panel now supports BOTH SBF and Chakama members. Change the `EnsureMemberAccess` middleware to check: user has a member record AND (member.is_sbf = true OR member.is_chakama = true). At least one must be true.
>
> In the panel's `navigation()` or `pages()` / `resources()` registration, conditionally register:
> - SBF resources (MyClaimResource, etc.) → only if member.is_sbf = true
> - Chakama resources (MyShareResource, etc.) → only if member.is_chakama = true
> - Shared resources (MyPaymentResource, MyProjectResource, Notifications) → always
>
> Use Filament's `shouldRegisterNavigation()` on each resource/page to hide/show based on the member's booleans."

---

### Step 6.1 — Conditional Dashboard

**Codex prompt:**
> "Modify `MemberDashboard` page. The dashboard now renders widgets conditionally:
>
> ```php
> public function getHeaderWidgets(): array
> {
>     $member = auth()->user()->member;
>     $widgets = [];
>
>     if ($member->is_sbf) {
>         $widgets[] = SbfStatsOverview::class;      // existing from SBF plan
>         $widgets[] = RecentClaimsWidget::class;
>         $widgets[] = UpcomingDuesWidget::class;
>     }
>
>     if ($member->is_chakama) {
>         $widgets[] = ShareSummaryWidget::class;     // new
>         $widgets[] = ChakamaBillingWidget::class;   // new
>         $widgets[] = FundOverviewWidget::class;     // new (if admin role)
>     }
>
>     // Always
>     $widgets[] = RecentPaymentsWidget::class;
>
>     return $widgets;
> }
> ```
>
> If member has BOTH flags, they see both sets of widgets."

---

### Step 6.2 — ShareSummaryWidget

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Widgets\ShareSummaryWidget` extending StatsOverviewWidget. Scoped to auth member.
>
> Stats: 'My Shares' (total active shares count), 'My Acres' (total_shares * 10), 'Amount Paid' (sum of amount_paid across active subs, KES), 'Outstanding' (sum of amount_outstanding, KES, danger if > 0)."

### Step 6.3 — ChakamaBillingWidget

**Codex prompt:**
> "Create `ChakamaBillingWidget` extending TableWidget. Shows member's outstanding share invoices. Query: sales_headers where customer_id = member.customer_id AND share_subscription_id IS NOT NULL, joined with customer_ledger_entries where is_open = true. Columns: invoice no, posting_date, amount, remaining_amount, due_date, days overdue. Action: 'Pay Now' → triggers M-PESA STK push or payment modal."

### Step 6.4 — MyShareResource

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Resources\MyShareResource` for ShareSubscription.
>
> Scoping: `getEloquentQuery()` → filter by member_id = auth()->user()->member->id.
>
> Navigation: icon heroicon-o-map, label 'My Shares'. Only registered if member.is_chakama.
>
> Table: no, number_of_shares, total_acres, holder_name (self or nominee name), total_amount money, amount_paid money, amount_outstanding money, status badge, subscribed_at.
>
> View page: share details, nominee info if applicable, linked invoices table, linked payments table, payment history timeline.
>
> Header action: 'Buy More Shares' — opens wizard: billing_schedule select, number_of_shares, nominee toggle → fields. Calls ShareService::subscribe.
>
> **Members can subscribe to new shares but CANNOT edit/delete existing ones.**"

### Step 6.5 — MyShareStatement Page

**Codex prompt:**
> "Create `App\Filament\MemberPanel\Pages\MyShareStatement` extending Page. Only visible if is_chakama.
>
> Shows: member share portfolio summary at top (total shares, acres, paid, outstanding). Below: a combined statement from customer_ledger_entries for this member filtered to share-related transactions. Columns: date, document_type, document_no, description, debit, credit, balance.
>
> Filters: date range. Actions: Download PDF, Print."

### Step 6.6 — FundOverviewWidget (Optional — Admin-Role Chakama Members)

**Codex prompt:**
> "Create `FundOverviewWidget`. Only visible to Chakama members who also have an admin or manager role. Shows simplified fund balances and recent transactions. Regular members do NOT see fund details."

---

## Phase 7 — Admin Panel Toggle (SBF vs Chakama)

### Step 7.1 — Admin Module Switcher

**Codex prompt:**
> "In the admin Filament panel, add a global 'Module Switcher' in the top bar / sidebar. This is a simple Alpine.js dropdown or Filament's built-in tenant switcher pattern. Options: 'SBF', 'Chakama', 'All'.
>
> When selected, it sets a session variable `active_module`. All admin resources that are module-specific should check this:
> - ClaimResource → visible when module = SBF or All
> - ShareSubscriptionResource → visible when module = Chakama or All
> - FundAccountResource / FundWithdrawalResource → visible when module = Chakama or All
> - ProjectResource → always visible (projects span both)
>
> Implement via a custom `ModuleSwitcher` Livewire component in the admin panel's renderHook for the sidebar or top navigation."

---

## Phase 8 — Scheduled Commands

**Codex prompt:**
> "Create these commands:
>
> 1. `App\Console\Commands\GenerateShareInvoices` — signature: `chakama:generate-invoices`. Calls ShareBillingService::generateRecurringInvoices(). Schedule: daily 6 AM.
>
> 2. `App\Console\Commands\CheckOverdueSharePayments` — signature: `chakama:check-overdue`. Find share_subscriptions with status pending_payment where subscribed_at > 30 days ago and amount_outstanding > 0. Notify members with SharePaymentOverdueNotification. If overdue > 90 days, auto-suspend via ShareService::suspendSubscription. Schedule: weekly Monday 8 AM.
>
> 3. `App\Console\Commands\RecalculateFundBalances` — signature: `funds:recalculate`. For each fund_account, recalculate balance from sum of fund_transactions. Safety net. Schedule: weekly Sunday midnight.
>
> 4. `App\Console\Commands\RemindWithdrawalApprovers` — signature: `withdrawals:remind-approvers`. Same pattern as claims:remind-approvers. Schedule: daily 9 AM."

---

## Phase 9 — Policies

### Step 9.1 — ShareSubscriptionPolicy

**Codex prompt:**
> "Create ShareSubscriptionPolicy. viewAny: admin or chakama member (own). view: admin or subscription.member.user_id = auth. create: admin or chakama member. update: admin only. delete: admin only and status pending_payment. activate/suspend/transfer: admin only."

### Step 9.2 — FundWithdrawalPolicy

**Codex prompt:**
> "Create FundWithdrawalPolicy. Mirrors ClaimPolicy: viewAny admin. create: admin. submit: admin. approve/reject: current step approver. convertToPurchase: admin and approved."

### Step 9.3 — FundAccountPolicy

**Codex prompt:**
> "Create FundAccountPolicy. All actions: admin only."

---

## Phase 10 — Tests

### Step 10.1 — ShareService Unit Tests

**Codex prompt:**
> "Create `Tests\Unit\Services\ShareServiceTest`. Use RefreshDatabase. Test cases:
>
> 1. `test_subscribe_generates_number_from_series` — assert no starts with 'SHR-'
> 2. `test_first_share_is_auto_tagged` — first subscription for a member has is_first_share = true
> 3. `test_second_share_is_not_first` — second subscription has is_first_share = false
> 4. `test_subscribe_with_nominee_creates_nominee_record` — assert ShareNominee created with correct national_id
> 5. `test_subscribe_calculates_total_amount` — 3 shares at 50,000 = 150,000
> 6. `test_activate_sets_status_active` — after full payment, status changes
> 7. `test_suspend_sets_status_and_notifies` — status = suspended
> 8. `test_transfer_creates_new_sub_and_marks_old_transferred` — old sub transferred, new sub active for new member
> 9. `test_member_share_summary_returns_correct_totals` — multiple subs, correct aggregate"

### Step 10.2 — ShareBillingService Unit Tests

**Codex prompt:**
> "Create `Tests\Unit\Services\ShareBillingServiceTest`. Test cases:
>
> 1. `test_generate_invoice_creates_sales_header_and_line` — assert sales_header with share_subscription_id, sales_line with correct amount
> 2. `test_generate_invoice_creates_customer_if_missing` — member with no customer_id gets one
> 3. `test_record_payment_updates_amount_paid` — amount_paid increases by receipt amount
> 4. `test_record_payment_activates_when_fully_paid` — status changes to active
> 5. `test_record_payment_does_not_activate_when_partial` — status stays pending
> 6. `test_generate_recurring_invoices_finds_due_subs` — subs with next_billing_date <= today get invoiced
> 7. `test_generate_recurring_skips_once_frequency` — frequency 'once' is never re-billed"

### Step 10.3 — FundService Unit Tests

**Codex prompt:**
> "Create `Tests\Unit\Services\FundServiceTest`. Test cases:
>
> 1. `test_record_transaction_updates_balance` — fund balance changes by amount
> 2. `test_inflow_increases_balance` — positive amount increases
> 3. `test_outflow_decreases_balance` — negative amount decreases
> 4. `test_running_balance_is_sequential` — each transaction's running_balance is correct
> 5. `test_recalculate_balance_matches_sum` — after manual changes, recalculate fixes it
> 6. `test_fund_statement_filters_by_date` — only transactions in range returned"

### Step 10.4 — FundWithdrawalService Unit Tests

**Codex prompt:**
> "Create `Tests\Unit\Services\FundWithdrawalServiceTest`. Test cases:
>
> 1. `test_create_generates_number` — no starts with 'FW-'
> 2. `test_submit_creates_approval_chain` — reuses claim_approval_templates
> 3. `test_approve_step_advances_current_step` — current_step increments
> 4. `test_final_approval_sets_status_approved` — after last step
> 5. `test_reject_sets_status_and_reason` — status = rejected
> 6. `test_convert_to_purchase_creates_po` — purchase_header with fund_withdrawal_id
> 7. `test_convert_records_fund_outflow` — fund_transaction with negative amount
> 8. `test_wrong_approver_throws` — user not assigned to step cannot approve"

### Step 10.5 — Member Portal Scoping Tests

**Codex prompt:**
> "Create `Tests\Feature\MemberPortal\ChakamaScopingTest`. Test cases:
>
> 1. `test_chakama_member_sees_share_resources` — is_chakama=true, MyShareResource is accessible
> 2. `test_sbf_only_member_does_not_see_share_resources` — is_sbf=true, is_chakama=false, MyShareResource returns 403
> 3. `test_dual_member_sees_both_modules` — is_sbf=true, is_chakama=true, sees claims AND shares
> 4. `test_chakama_member_only_sees_own_shares` — member A cannot see member B's subscriptions
> 5. `test_non_member_cannot_access_portal` — user with no member record gets redirected
> 6. `test_admin_can_toggle_module_view` — session variable changes visible resources"

### Step 10.6 — Dashboard Conditional Widget Tests

**Codex prompt:**
> "Create `Tests\Feature\MemberPortal\DashboardWidgetTest`. Test cases:
>
> 1. `test_sbf_only_member_sees_sbf_widgets_not_chakama` — SBF stats visible, share summary not
> 2. `test_chakama_only_member_sees_chakama_widgets_not_sbf` — share summary visible, SBF stats not
> 3. `test_dual_member_sees_all_widgets` — both sets visible
> 4. `test_share_summary_shows_correct_totals` — widget data matches DB"

### Step 10.7 — Filament Admin Feature Tests

**Codex prompt:**
> "Create `Tests\Feature\Filament\ChakamAdminTest`. Test cases:
>
> 1. `test_admin_can_list_share_subscriptions` — page loads, data shown
> 2. `test_admin_can_create_share_subscription` — number generated, nominee created if provided
> 3. `test_admin_can_activate_fully_paid_subscription` — action works
> 4. `test_admin_cannot_activate_unpaid_subscription` — action hidden/fails
> 5. `test_admin_can_create_fund_account` — number generated
> 6. `test_admin_can_create_fund_withdrawal` — submit, approve chain, convert to PO
> 7. `test_fund_transaction_recorded_on_share_payment` — inflow appears
> 8. `test_fund_transaction_recorded_on_withdrawal_payment` — outflow appears
> 9. `test_billing_schedule_filter_works` — subscriptions filtered by schedule
> 10. `test_module_switcher_hides_sbf_resources_in_chakama_mode`"

### Step 10.8 — Integration / End-to-End Tests

**Codex prompt:**
> "Create `Tests\Feature\ChakamE2ETest`. Full lifecycle tests:
>
> 1. `test_full_share_subscription_lifecycle`:
>    - Member subscribes to 2 shares via portal
>    - Invoice generated (sales_header exists)
>    - Member makes payment (cash_receipt created)
>    - Partial payment → status still pending
>    - Second payment → fully paid → status active
>    - Fund transaction inflow recorded for each payment
>    - Member statement shows all entries
>
> 2. `test_full_fund_withdrawal_lifecycle`:
>    - Admin creates fund withdrawal
>    - Submits for approval
>    - Approver 1 approves
>    - Approver 2 approves → fully approved
>    - Convert to PO → purchase_header created with fund_withdrawal_id
>    - Fund transaction outflow recorded
>    - Fund account balance decreased
>
> 3. `test_nominee_share_allocation`:
>    - Member buys shares for nominee (third party)
>    - Nominee record created with ID and details
>    - Share subscription linked to nominee
>    - holder_name accessor returns nominee name, not member name
>    - Member's share summary includes nominee shares"

---

## Execution Order

| Order | Phase | What | Depends On |
|-------|-------|------|------------|
| 1 | Phase 0 | Enums | Nothing |
| 2 | Phase 1.0 | Number series seed | Nothing |
| 3 | Phase 1.1–1.2 | Fund accounts + billing schedules | Phase 1.0 |
| 4 | Phase 1.3–1.4 | Shares + nominees | Phase 1.1 |
| 5 | Phase 1.5–1.8 | Fund transactions + withdrawals | Phase 1.1 |
| 6 | Phase 1.9 | Finance table FKs | Phase 1.3, 1.6 |
| 7 | Phase 2 | Models | Phase 1 |
| 8 | Phase 3 | Services | Phase 2 |
| 9 | Phase 4 | Events & Notifications | Phase 2, 3 |
| 10 | Phase 5 | Admin panel resources | Phase 2, 3, 4 |
| 11 | Phase 6.0 | Amend member panel for dual-module | Phase 2 |
| 12 | Phase 6.1–6.6 | Chakama member portal views | Phase 6.0, 3 |
| 13 | Phase 7 | Admin module switcher | Phase 5 |
| 14 | Phase 8 | Scheduled commands | Phase 3 |
| 15 | Phase 9 | Policies | Phase 2 |
| 16 | Phase 10 | Tests | All above |

---

## Member Portal Conditional View Summary

```
┌─────────────────────────────────────────────────────────┐
│              MEMBER PORTAL (/portal)                    │
│                                                         │
│  ┌─── IF is_sbf = true ───────────────────────────┐    │
│  │  Dashboard: SBF stats, claims, dues             │    │
│  │  My Claims                                       │    │
│  │  My SBF Statement                                │    │
│  └─────────────────────────────────────────────────┘    │
│                                                         │
│  ┌─── IF is_chakama = true ───────────────────────┐    │
│  │  Dashboard: Share summary, billing, fund stats   │    │
│  │  My Shares (buy + view)                          │    │
│  │  My Chakama Statement                            │    │
│  └─────────────────────────────────────────────────┘    │
│                                                         │
│  ┌─── ALWAYS (if either flag) ────────────────────┐    │
│  │  My Payments (cash receipts)                     │    │
│  │  My Projects (if tagged)                         │    │
│  │  Notifications                                   │    │
│  └─────────────────────────────────────────────────┘    │
│                                                         │
│  ┌─── IF is_sbf AND is_chakama ───────────────────┐    │
│  │  Sees EVERYTHING above — both module sections    │    │
│  └─────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│              ADMIN PANEL (/admin)                        │
│                                                         │
│  Module Switcher: [SBF] [Chakama] [All]                 │
│                                                         │
│  SBF mode:    Claims, SBF members, SBF billing          │
│  Chakama mode: Shares, Funds, Withdrawals, Chakama mbrs │
│  All mode:    Everything visible                        │
│  Always:      Projects, Finance, Settings               │
└─────────────────────────────────────────────────────────┘
```
