@php
    $brandColor = $brandColor ?? \App\Support\Brand::primaryHex(\App\Enums\EntityDimension::Chakama);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chakama Member Billing Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1a1a1a; }

        .header { background: {{ $brandColor }}; color: white; padding: 14px 20px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: flex-end; }
        .header h1 { font-size: 16px; font-weight: bold; }
        .header p { font-size: 9px; opacity: 0.85; margin-top: 2px; }
        .header-right { text-align: right; font-size: 9px; opacity: 0.85; }

        table.report { width: calc(100% - 24px); margin: 0 12px; border-collapse: collapse; }
        table.report thead tr { background: {{ $brandColor }}; color: white; }
        table.report thead th { padding: 6px 6px; text-align: left; font-size: 9px; font-weight: 700; }
        table.report thead th.num { text-align: right; }
        table.report thead th.center { text-align: center; }

        table.report tbody tr:nth-child(even) { background: #F8FAFC; }
        table.report td { padding: 5px 6px; font-size: 9px; border-bottom: 1px solid #E5E7EB; vertical-align: top; }
        table.report td.num { text-align: right; font-family: 'Courier New', monospace; }
        table.report td.center { text-align: center; }
        table.report td.danger { color: #B91C1C; font-weight: 600; }
        table.report td.success { color: #15803D; }

        tr.totals { background: #E8F0FE !important; font-weight: bold; border-top: 2px solid {{ $brandColor }}; }
        tr.totals td { padding-top: 8px; padding-bottom: 8px; font-size: 10px; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-active { background: #DCFCE7; color: #166534; }
        .badge-lapsed, .badge-suspended { background: #FEE2E2; color: #991B1B; }
        .badge-default { background: #F3F4F6; color: #374151; }

        .footer { margin: 14px 12px 0; padding-top: 6px; border-top: 1px solid #E5E7EB; display: flex; justify-content: space-between; font-size: 8px; color: #9CA3AF; }
    </style>
</head>
<body>

    @php
        $period = match (true) {
            !empty($date_from) && !empty($date_to) => \Carbon\Carbon::parse($date_from)->format('d M Y').' – '.\Carbon\Carbon::parse($date_to)->format('d M Y'),
            !empty($date_from) => 'From '.\Carbon\Carbon::parse($date_from)->format('d M Y'),
            !empty($date_to) => 'Up to '.\Carbon\Carbon::parse($date_to)->format('d M Y'),
            default => 'All time',
        };
    @endphp

    <div class="header">
        <div>
            <h1>Chakama Member Billing Report</h1>
            <p>{{ $organization }}</p>
            <p style="font-size: 10px; opacity: 1; margin-top: 4px;"><strong>Period:</strong> {{ $period }}</p>
        </div>
        <div class="header-right">
            <p><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</p>
            <p><strong>Members:</strong> {{ count($rows) }}</p>
        </div>
    </div>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 8%;">Member No</th>
                <th style="width: 18%;">Name</th>
                <th style="width: 9%;">Phone</th>
                <th class="center" style="width: 6%;">Status</th>
                <th class="num" style="width: 10%;">Opening (KES)</th>
                <th class="num" style="width: 10%;">Charged (KES)</th>
                <th class="num" style="width: 10%;">Paid (KES)</th>
                <th class="num" style="width: 10%;">Closing (KES)</th>
                <th class="center" style="width: 7%;">Months Overdue</th>
                <th class="num" style="width: 5%;">Shares</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['no'] ?? '—' }}</td>
                    <td>{{ $row['name'] ?? '—' }}</td>
                    <td>{{ $row['phone'] ?? '—' }}</td>
                    <td class="center">
                        <span class="badge badge-{{ $row['member_status'] ?? 'default' }}">{{ ucfirst($row['member_status'] ?? '—') }}</span>
                    </td>
                    <td class="num {{ $row['opening_balance'] > 0 ? 'danger' : '' }}">{{ number_format($row['opening_balance'], 2) }}</td>
                    <td class="num danger">{{ number_format($row['movement_in'], 2) }}</td>
                    <td class="num success">{{ number_format($row['movement_out'], 2) }}</td>
                    <td class="num {{ $row['closing_balance'] > 0 ? 'danger' : 'success' }}">{{ number_format($row['closing_balance'], 2) }}</td>
                    <td class="center {{ $row['months_outstanding'] > 0 ? 'danger' : '' }}">{{ $row['months_outstanding'] }}</td>
                    <td class="num">{{ $row['share_count'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; color: #9CA3AF; padding: 20px;">No members found.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
            <tfoot>
                <tr class="totals">
                    <td colspan="4">Totals ({{ count($rows) }} member(s))</td>
                    <td class="num">{{ number_format($totals['opening_balance'], 2) }}</td>
                    <td class="num">{{ number_format($totals['movement_in'], 2) }}</td>
                    <td class="num">{{ number_format($totals['movement_out'], 2) }}</td>
                    <td class="num {{ $totals['closing_balance'] > 0 ? 'danger' : 'success' }}">{{ number_format($totals['closing_balance'], 2) }}</td>
                    <td></td>
                    <td class="num">{{ $totals['share_count'] }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <span>System-generated report. Balances are based on posted Customer Ledger Entries within the selected period.</span>
    </div>

</body>
</html>
