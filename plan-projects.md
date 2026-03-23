# SOBA Alumni Portal — Fund & Project Management Module

## Implementation Plan for Codex (Step-by-Step)

**Agreement Ref:** PHL-SOBA-SDA-2026-001
**Stack:** Laravel 13, Livewire 3, Filament 3, MySQL 8.0+, M-PESA Daraja API
**Auth:** Filament Shield (Spatie Permission) with RBAC
**Queue:** Database driver (shared-hosting compatible)
**Modules:** SBF (Benevolent Fund) + Chakama Ranch

---

## Existing Finance Schema (Reference)

Your system already has a BC/NAV-style double-entry finance layer. The project module hooks into it — **not** beside it.

```
Existing tables the project module will reference:
├── number_series           → project document numbering
├── vendors                 → project suppliers/payees
├── purchase_headers        → purchase invoices linked to projects
├── purchase_lines          → line items on those invoices
├── vendor_ledger_entries   → vendor balances after posting
├── gl_entries              → all posted journal entries
├── gl_accounts             → chart of accounts
├── bank_accounts           → for payments
├── vendor_payments         → vendor payment documents
├── purchase_setups         → number series config for purchases
├── vendor_posting_groups   → GL mapping for vendors
```

**Key design principle:** Project expenses are NOT a separate financial table. An expense is either:
1. A **purchase invoice** (`purchase_headers` + `purchase_lines`) tagged to a project, OR
2. A **direct cost entry** (petty cash, cash withdrawal) recorded in a lightweight `project_direct_costs` table that gets posted to GL via the same posting engine.

This keeps your GL as the single source of truth for all money movement.

---

## Architecture Overview

```
app/
├── Models/
│   ├── Project.php
│   ├── ProjectMember.php
│   ├── ProjectStatusHistory.php
│   ├── ProjectDirectCost.php          ← lightweight: petty cash, M-PESA, non-PO costs
│   ├── ProjectMilestone.php
│   ├── ProjectAttachment.php
│   ├── ProjectComment.php
│   ├── ProjectBudgetLine.php          ← budget breakdown by GL account/category
│   │
│   │   ─── Existing models (already built or to be extended) ───
│   ├── PurchaseHeader.php             ← add project_id FK
│   ├── PurchaseLine.php
│   ├── Vendor.php
│   ├── GlEntry.php                    ← add project_id FK
│   ├── VendorLedgerEntry.php
│   └── NumberSeries.php
│
├── Filament/
│   └── Resources/
│       ├── ProjectResource.php
│       │   └── Pages/
│       │       ├── ListProjects.php
│       │       ├── CreateProject.php
│       │       ├── EditProject.php
│       │       └── ViewProject.php
│       └── RelationManagers/
│           ├── MilestonesRelationManager.php
│           ├── PurchaseOrdersRelationManager.php   ← shows purchase_headers for this project
│           ├── DirectCostsRelationManager.php
│           ├── MembersRelationManager.php
│           ├── BudgetLinesRelationManager.php
│           ├── AttachmentsRelationManager.php
│           └── CommentsRelationManager.php
│
├── Services/
│   ├── ProjectService.php
│   ├── ProjectCostService.php         ← handles direct costs + recalc from GL
│   └── NumberSeriesService.php        ← already exists — reuse
│
├── Enums/
│   ├── ProjectStatus.php
│   ├── ProjectPriority.php
│   ├── ProjectModule.php
│   ├── ProjectMemberRole.php
│   ├── DirectCostStatus.php
│   └── DirectCostType.php
│
├── Events/
├── Notifications/
├── Listeners/
├── Observers/
│   └── ProjectObserver.php
└── Policies/
```

---

## Phase 0 — Enums

### Step 0.1 — ProjectStatus Enum

**Codex prompt:**
> "Create a PHP string-backed enum `App\Enums\ProjectStatus` with cases: DRAFT = 'draft', PLANNING = 'planning', IN_PROGRESS = 'in_progress', ON_HOLD = 'on_hold', COMPLETED = 'completed', CANCELLED = 'cancelled'. Add a method `label(): string` that returns a human-friendly label (e.g. 'In Progress'). Add a method `color(): string` returning Filament color names: draft=gray, planning=info, in_progress=primary, on_hold=warning, completed=success, cancelled=danger. Add a static method `allowedTransitions(self $from): array` returning the valid next statuses:
> - draft → [planning, cancelled]
> - planning → [in_progress, on_hold, cancelled]
> - in_progress → [on_hold, completed, cancelled]
> - on_hold → [in_progress, cancelled]
> - completed → []
> - cancelled → []"

### Step 0.2 — ProjectPriority Enum

**Codex prompt:**
> "Create `App\Enums\ProjectPriority` string-backed enum. Cases: LOW = 'low', MEDIUM = 'medium', HIGH = 'high', CRITICAL = 'critical'. Add `label()` and `color()` methods (low=gray, medium=info, high=warning, critical=danger)."

### Step 0.3 — ProjectModule Enum

**Codex prompt:**
> "Create `App\Enums\ProjectModule` string-backed enum. Cases: SBF = 'sbf', CHAKAMA = 'chakama'. Add `label()` returning 'SOBA Benevolent Fund' and 'Chakama Ranch' respectively. Add `color()`: sbf=primary, chakama=success."

### Step 0.4 — ProjectMemberRole Enum

**Codex prompt:**
> "Create `App\Enums\ProjectMemberRole` string-backed enum. Cases: OWNER = 'owner', MANAGER = 'manager', CONTRIBUTOR = 'contributor', VIEWER = 'viewer'. Add `label()` and `color()` methods."

### Step 0.5 — DirectCostStatus Enum

**Codex prompt:**
> "Create `App\Enums\DirectCostStatus` string-backed enum. Cases: PENDING = 'pending', APPROVED = 'approved', POSTED = 'posted', REJECTED = 'rejected', VOIDED = 'voided'. Add `label()` and `color()` methods (pending=warning, approved=info, posted=success, rejected=danger, voided=gray)."

### Step 0.6 — DirectCostType Enum

**Codex prompt:**
> "Create `App\Enums\DirectCostType` string-backed enum. Cases: PETTY_CASH = 'petty_cash', MPESA_PAYMENT = 'mpesa_payment', BANK_TRANSFER = 'bank_transfer', CASH_WITHDRAWAL = 'cash_withdrawal', OTHER = 'other'. Add `label()` and `color()` methods."

---

## Phase 1 — Database Migrations

> Run in order. Each step = one Codex prompt.

### Step 1.0 — Add project_id to existing finance tables

This is the critical integration point. We add a nullable `project_id` FK to `purchase_headers` and `gl_entries` so any purchase or GL posting can be tagged to a project.

**Codex prompt:**
> "Create a Laravel migration called `add_project_id_to_finance_tables`. Do the following:
>
> 1. Add to `purchase_headers`: `project_id` unsignedBigInteger nullable after 'status', index on project_id. Do NOT add a foreign key constraint yet (the projects table doesn't exist yet — we'll add it in a later migration).
>
> 2. Add to `gl_entries`: `project_id` unsignedBigInteger nullable after 'source_id', index on project_id. Same — no FK constraint yet.
>
> In the down() method, drop the project_id columns from both tables."

---

### Step 1.1 — Number Series Seed for Projects

**Codex prompt:**
> "Create a Laravel seeder `ProjectNumberSeriesSeeder`. Insert a row into the `number_series` table:
> - code: 'PROJ', description: 'Project Numbers', prefix: 'PROJ-', last_no: 0, length: 5, is_active: true, prevent_repeats: true, is_manual_allowed: false.
>
> Also insert:
> - code: 'DCOST', description: 'Direct Cost Entry Numbers', prefix: 'DC-', last_no: 0, length: 6, is_active: true, prevent_repeats: true, is_manual_allowed: false."

---

### Step 1.2 — Add project number series to purchase_setups

**Codex prompt:**
> "Create a Laravel migration `add_project_nos_to_purchase_setups`. Add to `purchase_setups` table:
> - `project_nos` string(50) nullable after 'vendor_nos'
> - `direct_cost_nos` string(50) nullable after 'project_nos'
> - Foreign key `project_nos` references `code` on `number_series` nullOnDelete
> - Foreign key `direct_cost_nos` references `code` on `number_series` nullOnDelete
>
> Down method drops both foreign keys and columns."

---

### Step 1.3 — Projects Table

```
Schema: projects
├── id                  BIGINT UNSIGNED PK AUTO_INCREMENT
├── no                  VARCHAR(50) UNIQUE NOT NULL              -- from number_series 'PROJ'
├── name                VARCHAR(255) NOT NULL
├── slug                VARCHAR(255) UNIQUE NOT NULL
├── description         TEXT NULLABLE
├── module              VARCHAR(20) NOT NULL                     -- enum: sbf, chakama
├── budget              DECIMAL(18,4) DEFAULT 0.0000             -- matches your finance precision
├── spent               DECIMAL(18,4) DEFAULT 0.0000             -- cached: sum from GL
├── status              VARCHAR(20) DEFAULT 'draft'
├── priority            VARCHAR(20) DEFAULT 'medium'
├── start_date          DATE NULLABLE
├── due_date            DATE NULLABLE
├── completed_at        TIMESTAMP NULLABLE
├── number_series_code  VARCHAR(50)                              -- FK to number_series
├── created_by          BIGINT UNSIGNED FK → users.id
├── updated_by          BIGINT UNSIGNED FK → users.id NULLABLE
├── created_at          TIMESTAMP
├── updated_at          TIMESTAMP
├── deleted_at          TIMESTAMP NULLABLE
```

**Codex prompt:**
> "Create a Laravel migration for a `projects` table. Columns: id bigIncrements, no string(50) unique, name string(255), slug string(255) unique, description text nullable, module string(20) not null, budget decimal(18,4) default 0, spent decimal(18,4) default 0, status string(20) default 'draft', priority string(20) default 'medium', start_date date nullable, due_date date nullable, completed_at timestamp nullable, number_series_code string(50), created_by foreignId constrained to users, updated_by foreignId nullable constrained to users, timestamps, softDeletes. Add foreign key on number_series_code referencing code on number_series. Add indexes on module, status, priority, created_by, start_date, due_date."

---

### Step 1.4 — Add FK constraints for project_id on finance tables

Now that the projects table exists, add the deferred foreign keys.

**Codex prompt:**
> "Create a Laravel migration `add_project_fk_to_finance_tables`. Add foreign key constraints:
> 1. On `purchase_headers`: foreign key `project_id` references `id` on `projects`, nullOnDelete.
> 2. On `gl_entries`: foreign key `project_id` references `id` on `projects`, nullOnDelete.
>
> Down method drops both foreign keys (use dropForeign)."

---

### Step 1.5 — Project Members Table

**Codex prompt:**
> "Create a Laravel migration for `project_members`. Columns: id bigIncrements, project_id foreignId constrained to projects onDelete cascade, user_id foreignId constrained to users onDelete cascade, role string(20) default 'contributor', assigned_at timestamp useCurrent, assigned_by foreignId nullable constrained to users, timestamps. Add unique index on [project_id, user_id]."

---

### Step 1.6 — Project Status History Table

**Codex prompt:**
> "Create a Laravel migration for `project_status_history`. Columns: id bigIncrements, project_id foreignId constrained to projects onDelete cascade, from_status string(20) nullable, to_status string(20) not null, changed_by foreignId constrained to users, reason text nullable. Add only created_at timestamp (use `$table->timestamp('created_at')->useCurrent()`). No updated_at. Index on [project_id, created_at]."

---

### Step 1.7 — Project Budget Lines Table

**Codex prompt:**
> "Create a Laravel migration for `project_budget_lines`. Columns: id bigIncrements, project_id foreignId constrained to projects onDelete cascade, gl_account_no string(50), description string(255), budgeted_amount decimal(18,4), sort_order unsignedInteger default 0, timestamps. Add foreign key on gl_account_no referencing no on gl_accounts. Add unique index on [project_id, gl_account_no]."

---

### Step 1.8 — Project Direct Costs Table

**Codex prompt:**
> "Create a Laravel migration for `project_direct_costs`. Columns: id bigIncrements, no string(50) unique, project_id foreignId constrained to projects onDelete cascade, cost_type string(20) default 'other', description string(500), amount decimal(18,4), gl_account_no string(50), bank_account_id foreignId nullable constrained to bank_accounts, vendor_id foreignId nullable constrained to vendors, receipt_path string(500) nullable, receipt_number string(100) nullable, status string(20) default 'pending', posting_date date, posted_at timestamp nullable, posted_by foreignId nullable constrained to users, approved_by foreignId nullable constrained to users, approved_at timestamp nullable, rejection_reason text nullable, submitted_by foreignId constrained to users, number_series_code string(50), timestamps, softDeletes. Add foreign key on gl_account_no referencing no on gl_accounts. Add foreign key on number_series_code referencing code on number_series. Add indexes on project_id, status, cost_type, posting_date, submitted_by."

---

### Step 1.9 — Project Milestones Table

**Codex prompt:**
> "Create a Laravel migration for `project_milestones`. Columns: id bigIncrements, project_id foreignId constrained to projects onDelete cascade, title string(255), description text nullable, due_date date nullable, completed_at timestamp nullable, status string(20) default 'pending', sort_order unsignedInteger default 0, timestamps."

---

### Step 1.10 — Project Attachments Table

**Codex prompt:**
> "Create a Laravel migration for `project_attachments`. Columns: id bigIncrements, project_id foreignId constrained to projects onDelete cascade, uploaded_by foreignId constrained to users, file_name string(255), file_path string(500), file_size unsignedInteger, mime_type string(100). Add only `$table->timestamp('created_at')->useCurrent()` — no updated_at."

---

### Step 1.11 — Project Comments Table

**Codex prompt:**
> "Create a Laravel migration for `project_comments`. Columns: id bigIncrements, project_id foreignId constrained to projects onDelete cascade, user_id foreignId constrained to users onDelete cascade, body text, timestamps."

---

## Phase 2 — Eloquent Models & Relationships

### Step 2.1 — Project Model

**Codex prompt:**
> "Create an Eloquent model `App\Models\Project` for the `projects` table. Use SoftDeletes trait.
>
> **Casts:** status → `App\Enums\ProjectStatus`, priority → `App\Enums\ProjectPriority`, module → `App\Enums\ProjectModule`, budget → decimal:4, spent → decimal:4, start_date → date, due_date → date, completed_at → datetime.
>
> **Fillable:** no, name, slug, description, module, budget, status, priority, start_date, due_date, completed_at, number_series_code, created_by, updated_by.
>
> **Relationships:**
> - `creator()` belongsTo User via created_by
> - `updater()` belongsTo User via updated_by
> - `members()` belongsToMany User via project_members, withPivot(['role', 'assigned_at', 'assigned_by']), withTimestamps(), using(ProjectMember::class)
> - `purchaseOrders()` hasMany PurchaseHeader via project_id
> - `directCosts()` hasMany ProjectDirectCost
> - `budgetLines()` hasMany ProjectBudgetLine ordered by sort_order
> - `glEntries()` hasMany GlEntry via project_id
> - `milestones()` hasMany ProjectMilestone ordered by sort_order
> - `statusHistory()` hasMany ProjectStatusHistory ordered by created_at desc
> - `attachments()` hasMany ProjectAttachment
> - `comments()` hasMany ProjectComment ordered by created_at desc
> - `numberSeries()` belongsTo NumberSeries via number_series_code referencing code
>
> **Accessors (use Attribute):**
> - `budgetRemaining` → budget - spent
> - `budgetUtilisationPercent` → budget > 0 ? round((spent / budget) * 100, 2) : 0
> - `isOverdue` → due_date is past and status not completed/cancelled
> - `daysRemaining` → due_date ? now()->diffInDays(due_date, false) : null
>
> **Scopes:** scopeForModule, scopeByStatus, scopeOverdue, scopeActive (status in planning/in_progress)
>
> **Boot method:** auto-generate slug from name on creating event using Str::slug."

---

### Step 2.2 — Extend PurchaseHeader Model

**Codex prompt:**
> "In the existing `App\Models\PurchaseHeader` model, add:
> - To fillable: 'project_id'
> - New relationship: `project()` belongsTo Project, nullable
> - New scope: `scopeForProject($query, $projectId)` where project_id = $projectId
>
> This connects purchase invoices to projects."

---

### Step 2.3 — Extend GlEntry Model

**Codex prompt:**
> "In the existing `App\Models\GlEntry` model, add:
> - To fillable: 'project_id'
> - New relationship: `project()` belongsTo Project, nullable
> - New scope: `scopeForProject($query, $projectId)` where project_id = $projectId"

---

### Step 2.4 — ProjectMember Pivot Model

**Codex prompt:**
> "Create `App\Models\ProjectMember` extending Pivot. Set `$table = 'project_members'`, `$incrementing = true`. Cast role → ProjectMemberRole, assigned_at → datetime. Relationships: project() belongsTo Project, user() belongsTo User, assigner() belongsTo User via assigned_by."

---

### Step 2.5 — ProjectStatusHistory Model

**Codex prompt:**
> "Create `App\Models\ProjectStatusHistory`. Set `const UPDATED_AT = null`. Cast from_status → ProjectStatus nullable, to_status → ProjectStatus, created_at → datetime. Fillable: project_id, from_status, to_status, changed_by, reason. Relationships: project() belongsTo Project, changedBy() belongsTo User via changed_by."

---

### Step 2.6 — ProjectDirectCost Model

**Codex prompt:**
> "Create `App\Models\ProjectDirectCost` with SoftDeletes. Cast status → DirectCostStatus, cost_type → DirectCostType, amount → decimal:4, posting_date → date, posted_at → datetime, approved_at → datetime. Fillable: no, project_id, cost_type, description, amount, gl_account_no, bank_account_id, vendor_id, receipt_path, receipt_number, status, posting_date, posted_at, posted_by, approved_by, approved_at, rejection_reason, submitted_by, number_series_code.
>
> Relationships: project() belongsTo Project, submitter() belongsTo User via submitted_by, approver() belongsTo User via approved_by, poster() belongsTo User via posted_by, glAccount() belongsTo GlAccount via gl_account_no referencing no, bankAccount() belongsTo BankAccount nullable, vendor() belongsTo Vendor nullable, numberSeries() belongsTo NumberSeries via number_series_code referencing code.
>
> Scopes: scopePending, scopeApproved, scopePosted, scopeByType."

---

### Step 2.7 — ProjectBudgetLine Model

**Codex prompt:**
> "Create `App\Models\ProjectBudgetLine`. Fillable: project_id, gl_account_no, description, budgeted_amount, sort_order. Cast budgeted_amount → decimal:4. Relationships: project() belongsTo Project, glAccount() belongsTo GlAccount via gl_account_no referencing no."

---

### Step 2.8 — ProjectMilestone Model

**Codex prompt:**
> "Create `App\Models\ProjectMilestone`. Cast due_date → date, completed_at → datetime. Fillable: project_id, title, description, due_date, completed_at, status, sort_order. Relationship: project() belongsTo Project. Scope: scopeOverdue."

---

### Step 2.9 — ProjectAttachment Model

**Codex prompt:**
> "Create `App\Models\ProjectAttachment`. Set `const UPDATED_AT = null`. Fillable: project_id, uploaded_by, file_name, file_path, file_size, mime_type. Relationships: project() belongsTo Project, uploader() belongsTo User via uploaded_by. Accessor fileSizeHuman."

---

### Step 2.10 — ProjectComment Model

**Codex prompt:**
> "Create `App\Models\ProjectComment`. Fillable: project_id, user_id, body. Relationships: project() belongsTo Project, user() belongsTo User."

---

## Phase 3 — Service Layer & Business Logic

### Step 3.1 — ProjectService

**Codex prompt:**
> "Create `App\Services\ProjectService`. Inject `App\Services\NumberSeriesService` (already exists — generates next number from number_series table).
>
> Methods:
>
> 1. `createProject(array $data, User $creator): Project` — DB::transaction. Get next number from NumberSeriesService using 'PROJ' series. Create project with no = generated number, created_by = creator, number_series_code = 'PROJ'. Attach creator as OWNER in project_members. Log initial status history (from null → draft). Dispatch `ProjectCreated` event.
>
> 2. `updateProject(Project $project, array $data, User $editor): Project` — set updated_by. If status changed call changeStatus(). Save and return.
>
> 3. `changeStatus(Project $project, ProjectStatus $newStatus, User $user, ?string $reason = null): void` — validate using ProjectStatus::allowedTransitions(). Throw InvalidStatusTransitionException if invalid. DB::transaction: record old status, update project, if completed set completed_at, create ProjectStatusHistory, dispatch ProjectStatusChanged event.
>
> 4. `addMember(Project $project, User $user, ProjectMemberRole $role, User $assigner): void` — attach to pivot. Dispatch MemberAddedToProject event.
>
> 5. `removeMember(Project $project, User $user): void` — cannot remove last OWNER. Detach.
>
> 6. `recalculateSpent(Project $project): void` — sum debit_amount from gl_entries WHERE project_id = project.id (expense postings are debits to expense accounts). Subtract any credit amounts on the same accounts if reversals exist. Update project.spent.
>
> 7. `getBudgetVsActual(Project $project): Collection` — for each project_budget_line, compute actual = sum (debit_amount - credit_amount) from gl_entries where project_id and account_no = budget_line.gl_account_no. Return collection of [gl_account_no, description, budgeted, actual, variance, variance_percent]."

---

### Step 3.2 — ProjectCostService (Direct Costs)

**Codex prompt:**
> "Create `App\Services\ProjectCostService`. Inject NumberSeriesService.
>
> Methods:
>
> 1. `submitDirectCost(Project $project, array $data, User $submitter): ProjectDirectCost` — DB::transaction. Generate no from 'DCOST' series. Create with status pending, submitted_by. Handle receipt upload to `project-receipts/{project.no}/`. Dispatch DirectCostSubmitted event.
>
> 2. `approveDirectCost(ProjectDirectCost $cost, User $approver): void` — set status approved, approved_by, approved_at. Dispatch DirectCostApproved.
>
> 3. `postDirectCost(ProjectDirectCost $cost, User $poster): void` — DB::transaction. Validate status is approved. Create GL entries:
>    - Debit: cost.gl_account_no for cost.amount, with project_id = cost.project_id, document_no = cost.no, posting_date = cost.posting_date, source_type = 'ProjectDirectCost', source_id = cost.id
>    - Credit: if bank_account_id set, credit the bank's GL account (lookup via bank_account → bank_posting_group → bank_account_gl_no). If no bank, credit a default cash-in-hand account.
>    - If vendor_id set, create vendor_ledger_entry with document_type = 'Direct Cost', document_no = cost.no, amount (negative = payable).
>    Set status = posted, posted_at, posted_by. Call ProjectService::recalculateSpent. Dispatch DirectCostPosted.
>
> 4. `rejectDirectCost(ProjectDirectCost $cost, User $approver, string $reason): void` — set status rejected, rejection_reason. Dispatch DirectCostRejected.
>
> 5. `voidDirectCost(ProjectDirectCost $cost, User $user): void` — only if posted. DB::transaction. Create reversing GL entries (swap debit/credit). If vendor_ledger_entry exists, create reversal entry. Set status voided. Recalculate spent."

---

### Step 3.3 — InvalidStatusTransitionException

**Codex prompt:**
> "Create `App\Exceptions\InvalidStatusTransitionException` extending RuntimeException. Constructor takes ProjectStatus $from and ProjectStatus $to. Message: 'Cannot transition project from {from->label()} to {to->label()}'."

---

## Phase 4 — Events & Notifications

### Step 4.1 — Events

**Codex prompt:**
> "Create these events in `App\Events`. All use SerializesModels.
> 1. `ProjectCreated` — public: Project $project
> 2. `ProjectStatusChanged` — public: Project $project, ?ProjectStatus $fromStatus, ProjectStatus $toStatus, User $changedBy, ?string $reason
> 3. `MemberAddedToProject` — public: Project $project, User $member, ProjectMemberRole $role, User $assigner
> 4. `DirectCostSubmitted` — public: ProjectDirectCost $cost
> 5. `DirectCostApproved` — public: ProjectDirectCost $cost
> 6. `DirectCostPosted` — public: ProjectDirectCost $cost
> 7. `DirectCostRejected` — public: ProjectDirectCost $cost
> 8. `ProjectBudgetThresholdReached` — public: Project $project, float $utilisationPercent"

---

### Step 4.2 — Notification Classes

**Codex prompt:**
> "Create these Notification classes in `App\Notifications`. All implement ShouldQueue, deliver via ['mail', 'database'].
>
> 1. `ProjectStatusChangedNotification` — constructor: Project, fromStatus, toStatus, changedBy, reason. Mail subject: 'Project {no} "{name}" → {toStatus label}'.
> 2. `AddedToProjectNotification` — constructor: Project, role, assigner. Subject: 'Added to project {no}'.
> 3. `DirectCostActionNotification` — constructor: ProjectDirectCost, string $action. Subject: 'Direct cost {no} {action}'.
> 4. `ProjectOverdueNotification` — constructor: Project. Subject: 'Project {no} is overdue'.
> 5. `BudgetThresholdNotification` — constructor: Project, float $percent. Subject: 'Budget Alert: Project {no} at {percent}%'."

---

### Step 4.3 — Event Subscriber

**Codex prompt:**
> "Create `App\Listeners\ProjectEventSubscriber`. Subscribe to:
> - ProjectStatusChanged → notify all members (except changer)
> - MemberAddedToProject → notify new member
> - DirectCostApproved → notify submitter
> - DirectCostRejected → notify submitter
> - DirectCostPosted → notify submitter
> - ProjectBudgetThresholdReached → notify OWNER/MANAGER members
>
> Register in EventServiceProvider."

---

### Step 4.4 — Budget Threshold Observer

**Codex prompt:**
> "Create `App\Observers\ProjectObserver`. On `updated`, if `spent` changed, check if utilisation crossed 80% or 100%. If so dispatch ProjectBudgetThresholdReached. Register in AppServiceProvider."

---

### Step 4.5 — Overdue Check Command

**Codex prompt:**
> "Create `App\Console\Commands\CheckOverdueProjects`. Signature: `projects:check-overdue`. Find projects where due_date < today and status in [in_progress, on_hold, planning]. Notify all members. Schedule daily 8 AM."

---

## Phase 5 — Filament Resources

### Step 5.1 — ProjectResource

**Codex prompt:**
> "Create Filament resource `App\Filament\Resources\ProjectResource`.
>
> Navigation: icon heroicon-o-briefcase, group 'Project Management', sort 1.
>
> Form: Section 'Project Details' (name required, description textarea, module select from enum required, priority select from enum default medium). Section 'Budget & Timeline' (budget numeric prefix KES required, start_date DatePicker, due_date DatePicker afterOrEqual start_date). Note: 'no' is auto-generated — not on form.
>
> Table columns: no (searchable sortable), name (searchable limit 40), module badge, status badge, priority badge, budget money KES sortable, spent money KES sortable, utilisation (custom view: progress bar with color), members_count, due_date date sortable, creator.name.
>
> Filters: module, status, priority, overdue (TernaryFilter).
>
> Actions: View, Edit, Delete (only if draft).
>
> Pages: ListProjects, CreateProject, EditProject, ViewProject.
>
> Relation managers: MilestonesRelationManager, PurchaseOrdersRelationManager, DirectCostsRelationManager, BudgetLinesRelationManager, MembersRelationManager, AttachmentsRelationManager."

---

### Step 5.2 — CreateProject Page

**Codex prompt:**
> "Override CreateProject page. In handleRecordCreation, call `app(ProjectService::class)->createProject($data, auth()->user())`. This auto-generates project number, adds creator as owner, logs status history."

---

### Step 5.3 — ViewProject Page (Dashboard)

**Codex prompt:**
> "Create custom ViewProject page extending ViewRecord.
>
> Header: project no + name. Status badge. Actions: 'change_status' (modal: Select from allowedTransitions, Textarea reason, calls ProjectService::changeStatus), 'generate_report' (link to report page).
>
> Header widgets: ProjectStatsWidget (Budget, Spent, Remaining, Utilisation — 4 stat cards).
>
> Infolist: Section details (description, module, priority, dates, creator, number series). Tabs: Milestones, Purchase Orders, Direct Costs, Budget vs Actual, Members, Activity, Attachments, Comments. Each tab renders its relation manager or widget."

---

### Step 5.4 — EditProject Page

**Codex prompt:**
> "Override EditProject. In handleRecordUpdate, call `app(ProjectService::class)->updateProject($record, $data, auth()->user())`."

---

### Step 5.5 — Milestones Relation Manager

**Codex prompt:**
> "Create MilestonesRelationManager. Table: title, status badge, due_date, completed_at, sort_order. Actions: Edit (modal), toggle_complete, Delete. Header: Create (modal). Reorderable by sort_order."

---

### Step 5.6 — Purchase Orders Relation Manager

**Codex prompt:**
> "Create PurchaseOrdersRelationManager for purchaseOrders (purchase_headers where project_id = this project).
>
> Table: no (PO No), vendor.name, posting_date, due_date, status badge, computed total_amount (sum purchase_lines.line_amount) money KES.
>
> Actions: View (link to PurchaseHeader page if exists), 'unlink' (sets project_id null).
>
> Header: Action 'link_purchase' — modal Select searching purchase_headers where project_id IS NULL and status = 'Open', sets project_id. Action 'create_purchase' — redirects to purchase create page with project_id prefilled.
>
> Note: Actual purchase creation/posting uses existing engine. Project module only tags and reads."

---

### Step 5.7 — Direct Costs Relation Manager

**Codex prompt:**
> "Create DirectCostsRelationManager.
>
> Table: no, description limit 40, cost_type badge, amount money KES, gl_account_no, posting_date, status badge, submitter.name.
>
> Filters: status, cost_type.
>
> Actions: View, 'approve' (if pending, calls CostService::approve, success color), 'post' (if approved, confirmation, calls CostService::post, primary color), 'reject' (if pending, modal reason, calls CostService::reject, danger), 'void' (if posted, confirmation, calls CostService::void), 'download_receipt' (if receipt_path).
>
> Header: Create modal: description, amount, cost_type select, gl_account_no select (searchable from gl_accounts where account_type=Posting), bank_account_id select nullable, vendor_id select nullable searchable, receipt_number, posting_date default today, receipt FileUpload max 5MB. On create call CostService::submitDirectCost."

---

### Step 5.8 — Budget Lines Relation Manager

**Codex prompt:**
> "Create BudgetLinesRelationManager.
>
> Table: gl_account_no, description, budgeted_amount money KES, computed actual_amount (sum GL entries for this project + account, debit-credit) money KES, computed variance (budgeted-actual) money KES with color, computed variance_percent.
>
> Summary row: totals.
>
> Header: Create (gl_account_no select searchable from gl_accounts, description auto-fill, budgeted_amount, sort_order). Edit, Delete. Reorderable."

---

### Step 5.9 — Members Relation Manager

**Codex prompt:**
> "Create MembersRelationManager for members belongsToMany.
>
> Table: name, email, pivot.role badge, pivot.assigned_at.
>
> Actions: change_role (modal Select), Detach 'Remove' (disabled if only OWNER).
>
> Header: Attach — searchable users not in project, Select role default contributor. Override attach to use ProjectService::addMember."

---

### Step 5.10 — Attachments Relation Manager

**Codex prompt:**
> "Create AttachmentsRelationManager. Table: file_name, mime_type, file_size_human, uploader.name, created_at. Actions: download, delete. Header: Create with FileUpload, store to `project-attachments/{project.no}/`."

---

### Step 5.11 — Activity Tab (Status History)

**Codex prompt:**
> "In ViewProject 'Activity' tab, render statusHistory as RepeatableEntry. Each: from_status badge → to_status badge, changedBy.name, reason, created_at relative. Timeline style with Tailwind."

---

### Step 5.12 — Comments Livewire Component

**Codex prompt:**
> "Create Livewire component `App\Livewire\ProjectComments`. Mount projectId. Property $body. submit() creates comment. render() loads comments paginated desc. Blade: comment list with avatar initials, name, time, body. Textarea + submit at top. Tailwind matching Filament."

---

## Phase 6 — Reporting & Charts

### Step 6.1 — Project Stats Widget

**Codex prompt:**
> "Create `App\Filament\Widgets\ProjectStatsWidget` extending StatsOverviewWidget. Accept Project $record. Four cards: Budget KES, Spent KES, Remaining KES (danger if negative), Utilisation % (color thresholds). Sparklines from last 6 months GL entry sums."

---

### Step 6.2 — Budget vs Actual Bar Chart

**Codex prompt:**
> "Create `BudgetVsActualChart` extending ChartWidget. Type bar. For each budget line: blue bar = budgeted, orange bar = actual (from GL). X-axis: descriptions. Y-axis: KES. Display on ViewProject."

---

### Step 6.3 — Monthly Spend Trend

**Codex prompt:**
> "Create `MonthlySpendTrend` ChartWidget. Type line. GL entries grouped by month for this project. Cumulative spend line + budget ceiling line. Last 12 months."

---

### Step 6.4 — Cost Source Breakdown

**Codex prompt:**
> "Create `CostBreakdownChart` ChartWidget. Type doughnut. Slices: posted direct costs by type + one 'Purchase Orders' slice (sum of PO line amounts). Display on ViewProject."

---

### Step 6.5 — Dashboard-Level Widgets

**Codex prompt:**
> "Create for main dashboard:
> 1. `AllProjectsStatsOverview` — total projects, active, total budget, total spent, overdue count.
> 2. `ProjectStatusDistribution` — horizontal bar, count per status.
> 3. `ModuleSpendComparison` — bar chart, SBF vs Chakama budget and spent."

---

### Step 6.6 — Printable Project Report

**Codex prompt:**
> "Create custom page `ProjectReport` at `{record}/report`. Load all relationships. Print-friendly Blade: header with no/name/module/status/dates/budget. Budget vs actual table. Purchase orders table. Direct costs table. Milestones. Members. Status history. Print button + @media print CSS. Link from ViewProject header action."

---

## Phase 7 — Policies & Permissions

### Step 7.1 — ProjectPolicy

**Codex prompt:**
> "Create ProjectPolicy. viewAny: admin. view: admin OR member. create: admin. update: admin OR owner/manager. delete: admin AND draft/cancelled. changeStatus: admin OR owner/manager. manageMembers: admin OR owner. manageCosts: admin OR owner/manager."

---

### Step 7.2 — ProjectDirectCostPolicy

**Codex prompt:**
> "Create ProjectDirectCostPolicy. viewAny/view: admin or member. create: admin or owner/manager/contributor. approve/reject: admin or owner/manager (NOT submitter). post: admin only. void: admin or owner. delete: admin AND pending."

---

## Phase 8 — Scheduled Tasks

### Step 8.1 — Overdue Milestones

**Codex prompt:**
> "Create command `milestones:check-overdue`. Update milestones past due_date and status pending/in_progress → overdue. Daily 6 AM."

---

### Step 8.2 — Recalculate Spent

**Codex prompt:**
> "Create command `projects:recalculate-spent`. For each active project, recalculate from GL entries. Weekly Sunday midnight."

---

## Phase 9 — Tests

### Step 9.1 — ProjectService Tests

**Codex prompt:**
> "Test: creates number from series, sets creator as owner, logs status history, valid transitions succeed, invalid throw, complete sets completed_at, cannot remove last owner, recalculate from GL."

### Step 9.2 — ProjectCostService Tests

**Codex prompt:**
> "Test: submit creates pending with number, approve sets approver, post creates GL entries (debit + credit), post updates spent, reject sets reason, void creates reversals, cannot post unapproved."

### Step 9.3 — Filament Feature Tests

**Codex prompt:**
> "Test: admin can list/create projects, number auto-generated, filters work, status change validates transitions, direct cost approve→post flow, purchase order link, budget vs actual computation."

---

## Execution Order

| Order | Phase | What | Depends On |
|-------|-------|------|------------|
| 1 | Phase 0 | Enums | Nothing |
| 2 | Phase 1.0 | Add project_id to finance tables | Nothing |
| 3 | Phase 1.1–1.2 | Number series + setup columns | Phase 1.0 |
| 4 | Phase 1.3–1.4 | Projects table + FK constraints | Phase 1.0 |
| 5 | Phase 1.5–1.11 | Remaining project tables | Phase 1.3 |
| 6 | Phase 2 | Models | Phase 1 |
| 7 | Phase 3 | Services | Phase 2 |
| 8 | Phase 4 | Events & Notifications | Phase 2, 3 |
| 9 | Phase 5 | Filament Resources & UI | Phase 2, 3, 4 |
| 10 | Phase 6 | Charts & Reports | Phase 5 |
| 11 | Phase 7 | Policies | Phase 2 |
| 12 | Phase 8 | Scheduled Commands | Phase 2, 3 |
| 13 | Phase 9 | Tests | All above |

---

## Finance Integration Diagram

```
┌─────────────────────┐
│      PROJECT        │
│  no: PROJ-00001     │
│  budget: 500,000    │
│  spent: 125,000     │  ← cached, recalculated from GL
└────────┬────────────┘
         │
         │ has many
         ▼
┌──────────────────────────────────────────────────┐
│                  COSTS (two paths)               │
│                                                  │
│  Path A: Purchase Invoices                       │
│  ┌──────────────────┐                            │
│  │ purchase_headers  │  ← project_id FK          │
│  │ no: PI-000045     │                           │
│  │ vendor: ABC Ltd   │                           │
│  │ ► purchase_lines  │ → posted → gl_entries     │
│  └──────────────────┘     (with project_id)      │
│                                                  │
│  Path B: Direct Costs                            │
│  ┌──────────────────────┐                        │
│  │ project_direct_costs  │                       │
│  │ no: DC-000012         │                       │
│  │ type: petty_cash      │                       │
│  │ ► approved → posted   │ → gl_entries          │
│  └──────────────────────┘     (with project_id)  │
└──────────────────────────────────────────────────┘
         │
         ▼
┌──────────────────────┐
│     gl_entries       │  ← project_id FK
│  Single source of    │
│  truth for actuals   │
│  Budget vs Actual    │
│  computed from here  │
└──────────────────────┘
```

---

## Key Design Decisions

1. **DECIMAL(18,4)** — matches your existing finance tables, not 14,2.
2. **Number series** — projects and direct costs use your existing `number_series` table and `NumberSeriesService`.
3. **GL is the single source of truth** — `project.spent` is a cache derived from `gl_entries WHERE project_id`.
4. **Purchase invoices are tagged, not duplicated** — `purchase_headers.project_id` links existing POs to projects.
5. **Direct costs bridge the gap** — for non-PO expenses (petty cash, M-PESA), with an approve → post flow that creates proper GL entries.
6. **Budget lines map to GL accounts** — enables account-level budget vs actual, not just lump sum.
7. **Existing posting engine untouched** — the purchase posting codeunit just needs to carry `project_id` through to `gl_entries` when the purchase header has one.
