# Finance DATABASE STRUCTURE (BC-STYLE ERP)

## ⚠️ RULES FOR CLAUDE CODE

1. Follow migration order EXACTLY
2. Use foreign keys AFTER parent tables exist
3. Use unsignedBigInteger for FK fields
4. Add indexes for:
   - document_no
   - entry_no
   - foreign keys
5. Use snake_case table names
6. Use singular model names
7. All monetary fields: decimal(18,4)
8. All codes: varchar(50)
9. All document numbers must be UNIQUE

---

# 1. NUMBER SERIES

## table: number_series
- id (bigint, pk)
- code (string, unique)
- description (string)
- prefix (string, nullable)
- last_no (bigint, default 0)
- last_date_used (date, nullable)
- length (integer, default 6)
- is_manual_allowed (boolean, default false)
- prevent_repeats (boolean, default true)
- is_active (boolean, default true)
- created_at
- updated_at

---

# 2. POSTING GROUPS

## table: customer_posting_groups
- id
- code (unique)
- description
- receivables_account_no
- service_charge_account_no (nullable)
- created_at
- updated_at

---

## table: vendor_posting_groups
- id
- code (unique)
- description
- payables_account_no
- created_at
- updated_at

---

## table: service_posting_groups
- id
- code (unique)
- description
- revenue_account_no
- created_at
- updated_at

---

## table: bank_posting_groups
- id
- code (unique)
- description
- bank_account_gl_no
- created_at
- updated_at

---

# 3. GENERAL POSTING SETUP

## table: general_posting_setups
- id
- customer_posting_group_id (fk)
- service_posting_group_id (fk)
- sales_account_no
- created_at
- updated_at

UNIQUE:
(customer_posting_group_id, service_posting_group_id)

---

# 4. MASTER DATA

## table: customers
- id
- no (unique)
- name
- customer_posting_group_id (fk)
- payment_terms_code (nullable)
- created_at
- updated_at

---

## table: vendors
- id
- no (unique)
- name
- vendor_posting_group_id (fk)
- payment_terms_code (nullable)
- created_at
- updated_at

---

## table: services
- id
- code (unique)
- description
- unit_price (decimal)
- service_posting_group_id (fk)
- created_at
- updated_at

---

## table: bank_accounts
- id
- code (unique)
- name
- bank_account_no
- bank_posting_group_id (fk)
- currency_code (nullable)
- created_at
- updated_at

---

## table: payment_terms
- code (pk)
- description
- due_days (integer)
- created_at
- updated_at

---

# 5. SETUPS (SINGLETONS)

## table: sales_setup
- id
- invoice_nos (fk -> number_series.code)
- posted_invoice_nos (fk)
- created_at
- updated_at

---

## table: purchase_setup
- id
- invoice_nos (fk)
- posted_invoice_nos (fk)
- created_at
- updated_at

---

# 6. SALES DOCUMENTS

## table: sales_headers
- id
- no (unique)
- customer_id (fk)
- document_type
- posting_date
- due_date
- customer_posting_group_id (fk)
- number_series_code (fk)
- status
- created_at
- updated_at

---

## table: sales_lines
- id
- sales_header_id (fk)
- line_no (integer)
- service_id (fk)
- description
- quantity
- unit_price
- line_amount
- customer_posting_group_id (fk)
- service_posting_group_id (fk)
- general_posting_setup_id (fk)
- created_at
- updated_at

UNIQUE:
(sales_header_id, line_no)

---

# 7. PURCHASE DOCUMENTS

## table: purchase_headers
- id
- no (unique)
- vendor_id (fk)
- posting_date
- due_date
- vendor_posting_group_id (fk)
- number_series_code (fk)
- status
- created_at
- updated_at

---

## table: purchase_lines
- id
- purchase_header_id (fk)
- line_no
- service_id (fk)
- description
- quantity
- unit_price
- line_amount
- created_at
- updated_at

---

# 8. GENERAL LEDGER

## table: gl_accounts
- id
- no (unique)
- name
- account_type
- created_at
- updated_at

---

## table: gl_entries
- id
- posting_date
- document_no
- account_no
- debit_amount
- credit_amount
- source_type
- source_id
- created_at

---

# 9. CUSTOMER SUBLEDGER (AR)

## table: customer_ledger_entries
- id
- entry_no (bigint, index)
- customer_id (fk)
- document_type
- document_no
- posting_date
- due_date
- amount
- remaining_amount
- is_open (boolean)
- created_at

---

# 10. VENDOR SUBLEDGER (AP)

## table: vendor_ledger_entries
- id
- entry_no
- vendor_id (fk)
- document_type
- document_no
- posting_date
- due_date
- amount
- remaining_amount
- is_open
- created_at

---

# 11. DETAILED LEDGER (APPLICATION ENGINE)

## table: detailed_customer_ledger_entries
- id
- customer_ledger_entry_id (fk)
- applied_entry_id (fk)
- document_no
- posting_date
- amount
- entry_type
- created_at

---

## table: detailed_vendor_ledger_entries
- id
- vendor_ledger_entry_id (fk)
- applied_entry_id (fk)
- document_no
- posting_date
- amount
- entry_type
- created_at

---

# 12. PAYMENTS & RECEIPTS

## table: cash_receipts
- id
- no (unique)
- customer_id (fk)
- bank_account_id (fk)
- posting_date
- amount
- status
- created_at

---

## table: vendor_payments
- id
- no (unique)
- vendor_id (fk)
- bank_account_id (fk)
- posting_date
- amount
- status
- created_at

---

# 13. APPLICATION (UI LEVEL)

## table: customer_applications
- id
- payment_entry_id (fk)
- invoice_entry_id (fk)
- amount_applied

---

## table: vendor_applications
- id
- payment_entry_id (fk)
- invoice_entry_id (fk)
- amount_applied

---

# 14. RELATIONSHIPS SUMMARY

Customer:
- belongsTo customer_posting_group
- hasMany sales_headers
- hasMany customer_ledger_entries

Vendor:
- belongsTo vendor_posting_group
- hasMany purchase_headers
- hasMany vendor_ledger_entries

Service:
- belongsTo service_posting_group

SalesHeader:
- belongsTo customer
- hasMany sales_lines
- belongsTo number_series

SalesLine:
- belongsTo sales_header
- belongsTo service
- belongsTo general_posting_setup

CustomerLedgerEntry:
- belongsTo customer
- hasMany detailed entries

DetailedCustomerLedgerEntry:
- links payment ↔ invoice

---

# 15. MIGRATION ORDER (MANDATORY)

1. number_series
2. customer_posting_groups
3. vendor_posting_groups
4. service_posting_groups
5. bank_posting_groups
6. general_posting_setups
7. payment_terms
8. customers
9. vendors
10. services
11. bank_accounts
12. sales_setup
13. purchase_setup
14. sales_headers
15. sales_lines
16. purchase_headers
17. purchase_lines
18. gl_accounts
19. gl_entries
20. customer_ledger_entries
21. vendor_ledger_entries
22. detailed_customer_ledger_entries
23. detailed_vendor_ledger_entries
24. cash_receipts
25. vendor_payments
26. customer_applications
27. vendor_applications

---

# 16. LIVEWIRE / FILAMENT COMPONENTS

CREATE RESOURCES FOR:

- NumberSeries
- CustomerPostingGroup
- VendorPostingGroup
- ServicePostingGroup
- BankPostingGroup
- GeneralPostingSetup
- Customer
- Vendor
- Service
- BankAccount
- SalesInvoice
- PurchaseInvoice
- CashReceipt
- VendorPayment

CREATE SINGLETON PAGES:

- SalesSetup
- PurchaseSetup

---

# 17. CRITICAL IMPLEMENTATION RULES

- NEVER post directly to GL
- ALWAYS post to subledger first
- ALWAYS use posting groups
- ALWAYS store due_date
- ALWAYS update remaining_amount via detailed entries
- ALWAYS keep ledger entries immutable (no updates except remaining_amount)

---

# 18. CORE BUSINESS FLOW

Sales Invoice:
→ Sales Header + Lines  
→ Customer Ledger Entry  
→ GL Entry  

Payment:
→ Customer Ledger Entry  
→ Bank Entry  
→ GL Entry  

Application:
→ Detailed Ledger Entry  
→ reduces remaining_amount  

---
