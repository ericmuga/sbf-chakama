<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt — {{ $receipt->no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a1a; }

        .header { background: #1E3A5F; color: white; padding: 18px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .header-left h1 { font-size: 18px; font-weight: bold; letter-spacing: 0.5px; }
        .header-left p { font-size: 9px; opacity: 0.8; margin-top: 2px; }
        .header-right { text-align: right; }
        .header-right .receipt-label { font-size: 10px; opacity: 0.75; text-transform: uppercase; letter-spacing: 1px; }
        .header-right .receipt-no { font-size: 20px; font-weight: bold; letter-spacing: 1px; }

        .body { padding: 0 24px; }

        .meta-grid { display: flex; gap: 0; margin-bottom: 20px; border: 1px solid #E5E7EB; border-radius: 6px; overflow: hidden; }
        .meta-cell { flex: 1; padding: 10px 14px; border-right: 1px solid #E5E7EB; }
        .meta-cell:last-child { border-right: none; }
        .meta-cell .label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.6px; color: #6B7280; margin-bottom: 3px; }
        .meta-cell .value { font-size: 11px; font-weight: bold; color: #111827; }

        .section { margin-bottom: 16px; }
        .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; color: #6B7280; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px; margin-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; }
        tr td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #F3F4F6; }
        tr td:first-child { color: #6B7280; width: 45%; }
        tr td:last-child { font-weight: 500; color: #111827; }

        .amount-box { background: #F0FDF4; border: 1px solid #86EFAC; border-radius: 6px; padding: 12px 16px; margin: 16px 0; display: flex; justify-content: space-between; align-items: center; }
        .amount-box .amount-label { font-size: 11px; color: #166534; font-weight: 600; }
        .amount-box .amount-value { font-size: 22px; font-weight: bold; color: #15803D; font-family: 'Courier New', monospace; }

        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-posted { background: #DCFCE7; color: #166534; }

        .footer { margin: 24px 24px 0; padding-top: 10px; border-top: 1px solid #E5E7EB; display: flex; justify-content: space-between; font-size: 8px; color: #9CA3AF; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <h1>{{ $organization }}</h1>
            <p>Official Payment Receipt</p>
        </div>
        <div class="header-right">
            <div class="receipt-label">Receipt No</div>
            <div class="receipt-no">{{ $receipt->no }}</div>
        </div>
    </div>

    <div class="body">

        <div class="meta-grid">
            <div class="meta-cell">
                <div class="label">Received From</div>
                <div class="value">{{ $receipt->customer->name ?? '—' }}</div>
            </div>
            <div class="meta-cell">
                <div class="label">Date</div>
                <div class="value">{{ $receipt->posting_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="meta-cell">
                <div class="label">Payment Method</div>
                <div class="value">{{ $receipt->paymentMethod?->description ?? '—' }}</div>
            </div>
            <div class="meta-cell">
                <div class="label">Status</div>
                <div class="value">
                    <span class="status-badge status-posted">{{ ucfirst($receipt->status) }}</span>
                </div>
            </div>
        </div>

        <div class="amount-box">
            <div class="amount-label">Amount Received (KES)</div>
            <div class="amount-value">{{ number_format((float) $receipt->amount, 2) }}</div>
        </div>

        @if($receipt->description)
        <div class="section">
            <div class="section-title">Description</div>
            <table>
                <tr>
                    <td>Narration</td>
                    <td>{{ $receipt->description }}</td>
                </tr>
            </table>
        </div>
        @endif

        <div class="section">
            <div class="section-title">Payment Details</div>
            <table>
                <tr>
                    <td>Bank Account</td>
                    <td>{{ $receipt->bankAccount?->name ?? '—' }}</td>
                </tr>
                @if($receipt->mpesa_receipt_no)
                <tr>
                    <td>M-Pesa Receipt No</td>
                    <td>{{ $receipt->mpesa_receipt_no }}</td>
                </tr>
                @endif
                @if($receipt->mpesa_phone)
                <tr>
                    <td>M-Pesa Phone</td>
                    <td>{{ $receipt->mpesa_phone }}</td>
                </tr>
                @endif
                @if($receipt->shareSubscription)
                <tr>
                    <td>Share Subscription Ref</td>
                    <td>{{ $receipt->shareSubscription->no }}</td>
                </tr>
                @endif
                <tr>
                    <td>Received At</td>
                    <td>{{ $receipt->created_at?->format('d M Y H:i') ?? '—' }}</td>
                </tr>
            </table>
        </div>

    </div>

    <div class="footer">
        <span>Generated: {{ now()->format('d M Y H:i') }}</span>
        <span>This is a system-generated receipt. No signature required.</span>
    </div>

</body>
</html>
