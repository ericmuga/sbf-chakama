 ---                                                                                                                                                                         Member Registration — Testing Guide                                                                                                                                                                                                                                                                                                                     Prerequisites                                                                                                                                                                                                                                                                                                                                           - Logged in as an admin user
  - Application running at http://localhost

  ---
  Step 1 — Create a New Member (Admin)

  URL: http://localhost/admin/members/create

  Fill in the registration form:

  ┌────────────┬──────────────────┬────────────────────┐
  │  Section   │      Field       │     Test Value     │
  ├────────────┼──────────────────┼────────────────────┤
  │ Identity   │ Identity Type    │ National ID        │
  ├────────────┼──────────────────┼────────────────────┤
  │            │ Identity Number  │ e.g. 12345678      │
  ├────────────┼──────────────────┼────────────────────┤
  │ Personal   │ Full Name        │ e.g. Jane Chakama  │
  ├────────────┼──────────────────┼────────────────────┤
  │            │ Phone            │ e.g. 0712345678    │
  ├────────────┼──────────────────┼────────────────────┤
  │            │ Email            │ e.g. jane@test.com │
  ├────────────┼──────────────────┼────────────────────┤
  │            │ Date of Birth    │ any valid date     │
  ├────────────┼──────────────────┼────────────────────┤
  │ Membership │ Is Chakama       │ ✅ checked         │
  ├────────────┼──────────────────┼────────────────────┤
  │            │ Is SBF           │ optional           │
  ├────────────┼──────────────────┼────────────────────┤
  │ Payment    │ Preferred Method │ M-Pesa             │
  ├────────────┼──────────────────┼────────────────────┤
  │            │ M-Pesa Phone     │ e.g. 0712345678    │
  └────────────┴──────────────────┴────────────────────┘

  Click Create. Verify:
  - Member is saved and appears in the list at http://localhost/admin/members
  - The member number (no) was auto-generated
  - A linked User account was created automatically (check Users list)

  ---
  Step 2 — Upload Identity Documents (Admin)

  Open the newly created member's Edit page.

  Scroll to the Documents relation manager tab and click Add Document:

  ┌─────────────────┬─────────────────────────────────┐
  │      Field      │           Test Value            │
  ├─────────────────┼─────────────────────────────────┤
  │ Document Type   │ National ID                     │
  ├─────────────────┼─────────────────────────────────┤
  │ Document Number │ 12345678                        │
  ├─────────────────┼─────────────────────────────────┤
  │ File            │ Upload any PDF/image (max 5 MB) │
  └─────────────────┴─────────────────────────────────┘

  Repeat for a PIN document. Verify both uploads appear in the Documents table with correct type labels.

  ---
  Step 3 — Log In as the Member (Portal)

  The member's credentials are auto-generated:
  - Email: the email entered in Step 1
  - Password: password (default)

  Log in via http://localhost/chakama-portal.

  Verify:
  - Access is granted (member has is_chakama = true)
  - The dashboard loads with the Share Summary Widget

  ---
  Step 4 — Verify the Member Profile

  Navigate to My Profile in the portal sidebar.

  Verify the following sections are visible and pre-populated:
  - Personal Details — name, email, phone, DOB
  - Identity — identity type and number
  - Payment Details — M-Pesa phone, preferred method

  Try editing the phone number and saving. Confirm changes persist.

  ---
  Step 5 — Verify Shares Owned

  Navigate to My Shares (http://localhost/chakama-portal/my-shares).

  At this point, shares may be empty if none have been subscribed yet. Verify:
  - The page loads without errors
  - Column headers are visible: Subscription No., Shares, Acres, Total Amount, Amount Paid, Status

  To test with data, go back to the admin panel and create a Share Subscription linked to this member, then return to the portal to confirm it appears.

  ---
  Step 6 — Verify Subscription History & Statement

  Navigate to My Statement in the portal sidebar.

  Verify:
  - Opening balance, debits, credits, and closing balance are shown
  - Date range filter works (apply a date range and confirm the table updates)
  - Running balance column calculates correctly

  ---
  Step 7 — Verify Payment Records

  Navigate to Make Payment in the portal sidebar.

  Verify:
  - Outstanding invoices are listed (if any exist)
  - The M-Pesa phone is pre-filled from the member's profile
  - The STK push flow initiates correctly (requires M-Pesa sandbox/live credentials)

  ---
  Step 8 — Edge Cases to Test

  ┌───────────────────────────────────────────────┬───────────────────────────────────────┐
  │                   Scenario                    │               Expected                │
  ├───────────────────────────────────────────────┼───────────────────────────────────────┤
  │ Register with a duplicate Identity Number     │ Validation error — "already taken"    │
  ├───────────────────────────────────────────────┼───────────────────────────────────────┤
  │ Log in as a member with is_chakama = false    │ Access denied to Chakama portal       │
  ├───────────────────────────────────────────────┼───────────────────────────────────────┤
  │ Upload a document larger than 5 MB            │ Validation error on file size         │
  ├───────────────────────────────────────────────┼───────────────────────────────────────┤
  │ Leave Identity Number blank                   │ Required field validation fires       │
  ├───────────────────────────────────────────────┼───────────────────────────────────────┤
  │ Edit profile name → check admin member record │ Name should sync to the member record │
  └───────────────────────────────────────────────┴───────────────────────────────────────┘

  ---
  Key File References (for debugging)

  - Member form: app/Filament/Resources/Members/Schemas/MemberForm.php
  - Document uploads: app/Filament/Resources/Members/RelationManagers/DocumentsRelationManager.php
  - Portal dashboard: app/Filament/Member/Pages/MemberDashboard.php
  - Shares page: app/Filament/Member/Resources/Shares/MyShareResource.php
  - Statement: app/Filament/Member/Pages/MyStatement.php
