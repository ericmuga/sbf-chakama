@php
    $subscriptions = $subscriptions ?? collect();
@endphp

@if($subscriptions->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">No subscriptions found.</p>
@else
<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 dark:bg-white/5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-3 py-2">Ref</th>
                <th class="px-3 py-2">Member</th>
                <th class="px-3 py-2 text-center">Shares</th>
                <th class="px-3 py-2 text-right">Total (KES)</th>
                <th class="px-3 py-2 text-right">Paid (KES)</th>
                <th class="px-3 py-2 text-right">Outstanding (KES)</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach($subscriptions as $sub)
            @php
                $outstanding = (float) $sub->total_amount - (float) $sub->amount_paid;
                $statusColor = match($sub->status?->value ?? $sub->status) {
                    'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'pending_payment' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'suspended' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    default => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
                };
                $statusLabel = is_string($sub->status) ? ucfirst($sub->status) : ($sub->status?->label() ?? ucfirst($sub->status?->value ?? ''));
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                <td class="px-3 py-2 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $sub->no }}</td>
                <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $sub->member?->name ?? '—' }}</td>
                <td class="px-3 py-2 text-center text-gray-700 dark:text-gray-300">{{ $sub->number_of_shares }}</td>
                <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">{{ number_format((float) $sub->total_amount, 2) }}</td>
                <td class="px-3 py-2 text-right font-mono text-green-700 dark:text-green-400">{{ number_format((float) $sub->amount_paid, 2) }}</td>
                <td class="px-3 py-2 text-right font-mono {{ $outstanding > 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-400' }}">{{ number_format($outstanding, 2) }}</td>
                <td class="px-3 py-2">
                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                        {{ $statusLabel }}
                    </span>
                </td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                    {{ $sub->subscribed_at?->format('d M Y') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/10 font-semibold text-gray-700 dark:text-gray-200">
            <tr>
                <td colspan="2" class="px-3 py-2 text-xs uppercase tracking-wide">
                    {{ $subscriptions->count() }} {{ Str::plural('subscription', $subscriptions->count()) }}
                </td>
                <td class="px-3 py-2 text-center">{{ $subscriptions->sum('number_of_shares') }}</td>
                <td class="px-3 py-2 text-right font-mono">{{ number_format($subscriptions->sum(fn($s) => (float) $s->total_amount), 2) }}</td>
                <td class="px-3 py-2 text-right font-mono text-green-700 dark:text-green-400">{{ number_format($subscriptions->sum(fn($s) => (float) $s->amount_paid), 2) }}</td>
                <td class="px-3 py-2 text-right font-mono text-red-700 dark:text-red-400">{{ number_format($subscriptions->sum(fn($s) => (float) $s->total_amount - (float) $s->amount_paid), 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif
