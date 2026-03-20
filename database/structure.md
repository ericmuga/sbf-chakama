# SOBA Alumni Portal - Database Schema (Business Central Pattern)

## 1. Setup & Master Data (bus_)
Tables for core entities and system configuration.

### bus_no_series
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- code: VARCHAR(20) UNIQUE (e.g., 'S-INV', 'CLAIM', 'PROJ')
- description: VARCHAR(255)
- prefix: VARCHAR(10)
- last_no_used: INT
- increment_by: INT DEFAULT 1

### bus_members
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- no: VARCHAR(20) UNIQUE (From No. Series)
- user_id: FOREIGN_KEY (users.id)
- national_id: VARCHAR(50) UNIQUE
- phone: VARCHAR(20)
- member_status: ENUM('active', 'lapsed', 'suspended')
- customer_no: VARCHAR(20) (Links to ent_member_ledger)
- vendor_no: VARCHAR(20) (Links to ent_vendor_ledger)

### bus_vendors
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- no: VARCHAR(20) UNIQUE
- name: VARCHAR(255)
- vendor_type: ENUM('External', 'Member')
- member_id: FOREIGN_KEY (bus_members.id) NULLABLE
- payment_terms: VARCHAR(20)

### bus_projects
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- no: VARCHAR(20) UNIQUE
- title: VARCHAR(255)
- budget_lcy: DECIMAL(18,2)
- total_actual_cost: DECIMAL(18,2) DEFAULT 0.00
- status: ENUM('Planning', 'Active', 'Completed', 'Closed')

---

## 2. Sales & Billing (doc_ / pst_)
Member-facing receivables (Subscriptions/Dues).

### doc_sales_headers (Unposted)
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- no: VARCHAR(20) UNIQUE
- member_no: FOREIGN_KEY (bus_members.no)
- posting_date: DATE
- due_date: DATE
- total_amount: DECIMAL(18,2)

### doc_sales_lines
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- header_id: FOREIGN_KEY (doc_sales_headers.id)
- description: VARCHAR(255)
- amount: DECIMAL(18,2)
- gl_account_no: VARCHAR(20)

### pst_sales_headers (Posted - Immutable)
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- no: VARCHAR(20) UNIQUE
- member_no: VARCHAR(20)
- posting_date: DATE
- external_doc_no: VARCHAR(100) (e.g., M-Pesa Ref)
- total_amount: DECIMAL(18,2)

### pst_sales_lines
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- header_no: FOREIGN_KEY (pst_sales_headers.no)
- description: VARCHAR(255)
- amount: DECIMAL(18,2)

---

## 3. Purchases & Claims (doc_ / pst_)
Payables to external vendors and member claims.

### doc_purchase_headers
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- no: VARCHAR(20) UNIQUE
- vendor_no: FOREIGN_KEY (bus_vendors.no)
- doc_type: ENUM('Claim', 'Invoice')
- project_id: FOREIGN_KEY (bus_projects.id) NULLABLE
- status: ENUM('Draft', 'Pending Approval', 'Approved', 'Rejected')

### doc_purchase_lines
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- header_id: FOREIGN_KEY (doc_purchase_headers.id)
- description: VARCHAR(255)
- amount: DECIMAL(18,2)
- project_id: BIGINT NULLABLE

### pst_purchase_headers
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- no: VARCHAR(20) UNIQUE
- vendor_no: VARCHAR(20)
- posting_date: DATE
- project_id: BIGINT NULLABLE
- total_amount: DECIMAL(18,2)

---

## 4. Ledger Entries (ent_)
The immutable financial "Source of Truth".

### ent_member_ledger (Receivables)
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- entry_no: INT UNIQUE
- member_no: FOREIGN_KEY (bus_members.no)
- posting_date: DATE
- document_type: ENUM('Invoice', 'Payment', 'Refund')
- document_no: VARCHAR(20)
- amount: DECIMAL(18,2)
- remaining_amount: DECIMAL(18,2)
- open: BOOLEAN DEFAULT TRUE

### ent_vendor_ledger (Payables)
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- entry_no: INT UNIQUE
- vendor_no: FOREIGN_KEY (bus_vendors.no)
- posting_date: DATE
- document_type: ENUM('Claim', 'Invoice', 'Payment')
- document_no: VARCHAR(20)
- amount: DECIMAL(18,2) (Negative for liabilities)
- remaining_amount: DECIMAL(18,2)
- open: BOOLEAN DEFAULT TRUE

### ent_detailed_ledger (Applications)
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- ledger_entry_no: INT (Refers to Member or Vendor Ledger)
- ledger_type: ENUM('Member', 'Vendor')
- entry_type: ENUM('Initial', 'Application', 'Correction', 'Unrealized Loss/Gain')
- posting_date: DATE
- amount: DECIMAL(18,2)

### ent_project_ledger
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- project_no: FOREIGN_KEY (bus_projects.no)
- posting_date: DATE
- document_no: VARCHAR(20)
- entry_type: ENUM('Budget', 'Usage')
- amount: DECIMAL(18,2)

---

## 5. Communication & Logs
### bus_notifications
- id: BIGINT PRIMARY KEY AUTO_INCREMENT
- member_no: FOREIGN_KEY (bus_members.no)
- title: VARCHAR(255)
- body: TEXT
- status: ENUM('Draft', 'Scheduled', 'Sent', 'Failed')
- scheduled_at: TIMESTAMP NULLABLE
- sent_at: TIMESTAMP NULLABLE
- error_log: TEXT NULLABLE
