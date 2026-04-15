<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Statement — {{ $data['member']->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1a1a1a; }

        .header { background: #1E3A5F; color: white; padding: 16px 20px; margin-bottom: 16px; }
        .header h1 { font-size: 16px; font-weight: bold; }
        .header p { font-size: 10px; opacity: 0.85; margin-top: 2px; }

        .meta { display: flex; justify-content: space-between; margin: 0 20px 12px; }
        .meta-block p { font-size: 9px; color: #555; line-height: 1.6; }
        .meta-block strong { color: #1a1a1a; }

        table { width: calc(100% - 40px); margin: 0 20px; border-collapse: collapse; }
        thead tr { background: #1E3A5F; color: white; }
        thead th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: bold; }
        thead th.num { text-align: right; }

        tbody tr:nth-child(even) { background: #F8FAFC; }
        tbody tr.opening { background: #EFF6FF; }
        tbody tr.totals { background: #E8F0FE; font-weight: bold; border-top: 2px solid #1E3A5F; }

        td { padding: 5px 8px; font-size: 9px; border-bottom: 1px solid #E5E7EB; }
        td.num { text-align: right; font-family: 'Courier New', monospace; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
        .badge-dr { background: #FEE2E2; color: #991B1B; }
        .badge-cr { background: #DCFCE7; color: #166534; }
        .badge-nil { background: #F3F4F6; color: #374151; }

        .footer { margin: 16px 20px 0; font-size: 8px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 8px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Member Statement</h1>
        <p>{{ $data['member']->name }} &mdash; {{ $data['member']->no }}</p>
    </div>

    <div class="meta">
        <div class="meta-block">
            <p><strong>Member No:</strong> {{ $data['member']->no }}</p>
            <p><strong>Name:</strong> {{ $data['member']->name }}</p>
            @if($data['member']->phone)
                <p><strong>Phone:</strong> {{ $data['member']->phone }}</p>
            @endif
        </div>
        <div class="meta-block" style="text-align: right;">
            <p><strong>Period:</strong>
                @if($data['date_from'] && $data['date_to'])
                    {{ \Carbon\Carbon::parse($data['date_from'])->format('d M Y') }} – {{ \Carbon\Carbon::parse($data['date_to'])->format('d M Y') }}
                @elseif($data['date_from'])
                    From {{ \Carbon\Carbon::parse($data['date_from'])->format('d M Y') }}
                @elseif($data['date_to'])
                    Up to {{ \Carbon\Carbon::parse($data['date_to'])->format('d M Y') }}
                @else
                    All Dates
                @endif
            </p>
            <p><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Document No</th>
                <th class="num">Debit (KES)</th>
                <th class="num">Credit (KES)</th>
                <th class="num">Running Balance (KES)</th>
            </tr>
        </thead>
        <tbody>
            @if($data['date_from'] && $data['opening'] != 0)
                <tr class="opening">
                    <td>{{ \Carbon\Carbon::parse($data['date_from'])->subDay()->format('d M Y') }}</td>
                    <td>Opening Balance</td>
                    <td>—</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num">{{ number_format(abs($data['opening']), 2) }}
                        <span class="badge {{ $data['opening'] > 0 ? 'badge-dr' : ($data['opening'] < 0 ? 'badge-cr' : 'badge-nil') }}">
                            {{ $data['opening'] > 0 ? 'DR' : ($data['opening'] < 0 ? 'CR' : 'NIL') }}
                        </span>
                    </td>
                </tr>
            @endif

            @foreach($data['entries'] as $entry)
                @php $amount = (float) $entry->amount; @endphp
                <tr>
                    <td>
                        {{ $entry->posting_date instanceof \Carbon\Carbon
                            ? $entry->posting_date->format('d M Y')
                            : \Carbon\Carbon::parse($entry->posting_date)->format('d M Y') }}
                    </td>
                    <td>{{ $entry->document_type ?? '' }}</td>
                    <td>{{ $entry->document_no ?? '' }}</td>
                    <td class="num">{{ $amount > 0 ? number_format($amount, 2) : '' }}</td>
                    <td class="num">{{ $amount < 0 ? number_format(abs($amount), 2) : '' }}</td>
                    <td class="num">
                        {{ number_format(abs((float) $entry->running_balance), 2) }}
                        @php $rb = (float) $entry->running_balance; @endphp
                        <span class="badge {{ $rb > 0 ? 'badge-dr' : ($rb < 0 ? 'badge-cr' : 'badge-nil') }}">
                            {{ $rb > 0 ? 'DR' : ($rb < 0 ? 'CR' : 'NIL') }}
                        </span>
                    </td>
                </tr>
            @endforeach

            <tr class="totals">
                <td colspan="3">TOTALS</td>
                <td class="num">{{ number_format($data['total_debits'], 2) }}</td>
                <td class="num">{{ number_format($data['total_credits'], 2) }}</td>
                <td class="num">
                    {{ number_format(abs($data['closing']), 2) }}
                    <span class="badge {{ $data['closing'] > 0 ? 'badge-dr' : ($data['closing'] < 0 ? 'badge-cr' : 'badge-nil') }}">
                        {{ $data['closing'] > 0 ? 'DR' : ($data['closing'] < 0 ? 'CR' : 'NIL') }}
                    </span>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>This statement was generated automatically. For queries, contact the administrator.</p>
    </div>

</body>
</html>
