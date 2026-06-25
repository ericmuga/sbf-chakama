@php
    $brandColor = $brandColor ?? \App\Support\Brand::primaryHex();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice — {{ $invoice->no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; }

        .header { background: {{ $brandColor }}; color: white; padding: 18px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .header-left h1 { font-size: 18px; font-weight: bold; letter-spacing: 0.5px; }
        .header-left p { font-size: 9px; opacity: 0.85; margin-top: 2px; }
        .header-right { text-align: right; }
        .header-right .doc-label { font-size: 10px; opacity: 0.75; text-transform: uppercase; letter-spacing: 1px; }
        .header-right .doc-no { font-size: 20px; font-weight: bold; letter-spacing: 1px; }

        .body { padding: 0 24px; }

        .meta-grid { display: flex; gap: 0; margin-bottom: 20px; border: 1px solid #E5E7EB; border-radius: 6px; overflow: hidden; }
        .meta-cell { flex: 1; padding: 10px 14px; border-right: 1px solid #E5E7EB; }
        .meta-cell:last-child { border-right: none; }
        .meta-cell .label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px; color: #6B7280; margin-bottom: 3px; }
        .meta-cell .value { font-size: 11px; font-weight: bold; color: #111827; }

        .section { margin-bottom: 16px; }
        .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; color: #6B7280; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px; margin-bottom: 10px; }

        .lines { width: 100%; border-collapse: collapse; }
        .lines thead tr { background: #F3F4F6; }
        .lines th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: 700; color: #374151; }
        .lines th.num { text-align: right; }
        .lines td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #F3F4F6; }
        .lines td.num { text-align: right; font-family: 'Courier New', monospace; }

        .totals { width: 50%; margin-left: 50%; margin-top: 12px; }
        .totals td { padding: 6px 8px; font-size: 11px; }
        .totals td:first-child { color: #6B7280; }
        .totals td:last-child { text-align: right; font-family: 'Courier New', monospace; font-weight: 600; }
        .totals tr.grand td { font-size: 14px; font-weight: bold; color: #111827; border-top: 2px solid {{ $brandColor }}; padding-top: 8px; }

        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-open { background: #FEF3C7; color: #92400E; }
        .status-posted { background: #DCFCE7; color: #166534; }
        .status-paid { background: #DBEAFE; color: #1E40AF; }

        .footer { margin: 24px 24px 0; padding-top: 10px; border-top: 1px solid #E5E7EB; display: flex; justify-content: space-between; font-size: 8px; color: #9CA3AF; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <h1>{{ $organization }}</h1>
            <p>Tax Invoice</p>
        </div>
        <div class="header-right">
            <div class="doc-label">Invoice No</div>
            <div class="doc-no">{{ $invoice->no }}</div>
        </div>
    </div>

    <div class="body">

        <div class="meta-grid">
            <div class="meta-cell">
                <div class="label">Billed To</div>
                <div class="value">{{ $invoice->customer->name ?? '—' }}</div>
            </div>
            <div class="meta-cell">
                <div class="label">Invoice Date</div>
                <div class="value">{{ $invoice->posting_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="meta-cell">
                <div class="label">Due Date</div>
                <div class="value">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="meta-cell">
                <div class="label">Status</div>
                <div class="value">
                    <span class="status-badge status-{{ strtolower($invoice->status ?? 'open') }}">{{ ucfirst($invoice->status ?? 'open') }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Line Items</div>
            <table class="lines">
                <thead>
                    <tr>
                        <th style="width: 8%;">#</th>
                        <th>Description</th>
                        <th class="num" style="width: 12%;">Qty</th>
                        <th class="num" style="width: 18%;">Unit Price (KES)</th>
                        <th class="num" style="width: 18%;">Line Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->salesLines as $i => $line)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $line->description ?? $line->service?->description ?? '—' }}</td>
                            <td class="num">{{ number_format((float) $line->quantity, 2) }}</td>
                            <td class="num">{{ number_format((float) $line->unit_price, 2) }}</td>
                            <td class="num">{{ number_format((float) $line->line_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <table class="totals">
            <tr class="grand">
                <td>Total Due</td>
                <td>KES {{ number_format($invoiceTotal, 2) }}</td>
            </tr>
        </table>

        @if($invoice->shareSubscription ?? false)
            <div class="section" style="margin-top: 20px;">
                <div class="section-title">Reference</div>
                <table class="lines">
                    <tr>
                        <td style="width: 40%; color: #6B7280;">Share Subscription</td>
                        <td>{{ $invoice->shareSubscription->no }} — {{ $invoice->shareSubscription->number_of_shares ?? 0 }} share(s)</td>
                    </tr>
                </table>
            </div>
        @endif

    </div>

    <div class="footer">
        <span>Generated: {{ now()->format('d M Y H:i') }}</span>
        <span>This is a system-generated invoice. Payment instructions provided separately.</span>
    </div>

</body>
</html>
