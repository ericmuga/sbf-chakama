<?php

namespace Database\Seeders;

use App\Models\Issue;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    /**
     * Seed the issue tracker with the issues logged in SOBA_ISSUE_TRACKER.xlsx.
     */
    public function run(): void
    {
        foreach ($this->issues() as $issue) {
            Issue::updateOrCreate(
                ['title' => $issue['title'], 'details' => $issue['details']],
                $issue,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function issues(): array
    {
        $closed = ['issue_owner' => 'VW', 'category' => 'development', 'resource' => 'EM'];

        return [
            [
                'title' => 'Customer/ Vendor Creation', 'portal_type' => 'sbf',
                'details' => 'Unable to create a new record; check on the automatic numbering assignment',
                'date_assigned' => '2026-06-08', 'status' => 'closed', 'closure_date' => '2026-06-09',
                'qa_test_result' => 'Pass',
            ] + $closed,
            [
                'title' => 'Posting Setup Pages', 'portal_type' => 'sbf',
                'details' => 'Best to allow look up of the fields against the COA Page for relevant code selection',
                'date_assigned' => '2026-06-08', 'status' => 'closed', 'closure_date' => '2026-06-09',
                'qa_test_result' => 'Pass',
            ] + $closed,
            [
                'title' => 'Sales Invoice Page', 'portal_type' => 'sbf',
                'details' => "Grey out the status field; upon creation default to 'Open'",
                'date_assigned' => '2026-06-10', 'status' => 'closed', 'qa_test_result' => 'Pass',
            ] + $closed,
            [
                'title' => 'Saving a Draft Sales Doc', 'portal_type' => 'sbf',
                'details' => 'Allow save of draft doc - Once I exit the page, the inserted details on the page clears, necessitating fresh creation and insertion of details again.',
                'date_assigned' => '2026-06-10', 'status' => 'closed', 'qa_test_result' => 'Pass',
            ] + $closed,
            [
                'title' => 'Posted Sales Invoice', 'portal_type' => 'sbf',
                'details' => "modifying a posted invoices should be disabled; currently I'm able to change the status back to 'open' and post the posted doc again.",
                'date_assigned' => '2026-06-10', 'status' => 'closed', 'qa_test_result' => 'Pass',
            ] + $closed,
            [
                'title' => 'Sales Line', 'portal_type' => 'sbf',
                'details' => "Service Code, I've created a service (Meals & Accommodation) and checked the sellable boolean, however not showing in the look up list of services.",
                'date_assigned' => '2026-06-10', 'status' => 'closed', 'qa_test_result' => 'Pass',
            ] + $closed,
            [
                'title' => 'Posted Sales Invoice', 'portal_type' => 'sbf',
                'details' => 'Disable deletion of a posted sales invoice; upon multi-selection',
                'date_assigned' => '2026-06-14', 'status' => 'pending_testing',
                'comments' => 'Guard added in SalesHeadersTable bulk delete; posted documents cannot be deleted. Covered by tests.',
            ] + $closed,
            [
                'title' => 'Posted Sales Invoice', 'portal_type' => 'sbf',
                'details' => 'Introduce the sales amount on the Posted Sales Invoice Page',
                'date_assigned' => '2026-06-14', 'status' => 'pending_testing',
                'comments' => 'Amount column (sum of sales lines) added to the sales documents table.',
            ] + $closed,
            [
                'title' => 'Sales Credit Memo', 'portal_type' => 'sbf',
                'details' => 'Amt should post as -ve in the member ledger; currently goes in as +ve resulting to debit increase',
                'date_assigned' => '2026-06-14', 'status' => 'pending_testing',
                'comments' => 'Credit memos now post a negative customer ledger amount. Covered by SalesPostingServiceTest.',
            ] + $closed,
            [
                'title' => 'Share allocations', 'portal_type' => 'chakama',
                'details' => 'The total amt in the completed share allocation is debited to individual members in the CATEGORY; disregarding individual member amt billable',
                'date_assigned' => '2026-06-22', 'status' => 'open', 'date_actioned' => '2026-06-25',
                'comments' => 'Under investigation. Billing posts per-member amounts correctly (regression test ShareBillingPerMemberTest proves member A and B are billed only their own shares, not the category total). Both admin and member-portal balances are scoped per customer_id. Symptom likely stems from multiple members sharing a customer record (duplicate customer_no) in legacy data — needs a specific member + billing run to reproduce.',
            ] + $closed,
            [
                'title' => 'Bulk Member Upload', 'portal_type' => 'chakama',
                'details' => "Remove the Member No, Cust & Vend No field on from the member reg template' system to assign automatically",
                'date_assigned' => '2026-06-23', 'status' => 'pending_testing',
                'comments' => 'Removed from template and import; member/customer/vendor numbers auto-assigned from number series.',
            ] + $closed,
            [
                'title' => 'Bulk Member Upload', 'portal_type' => 'chakama',
                'details' => 'Problem with the Template_Date Field; encountering error while submitting',
                'date_assigned' => '2026-06-23', 'status' => 'pending_testing',
                'comments' => 'Flexible date parsing added (supports d/m/Y and others). Covered by MemberUploadManagementTest.',
            ] + $closed,
        ];
    }
}
