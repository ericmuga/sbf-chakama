@php
    $entries = $entries ?? collect();
@endphp

@if($entries->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">No entries found.</p>
@else
<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
    <table class="w-full text-sm text-left">
        <thead class="bg-gray-50 dark:bg-white/5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            <tr>
                <th class="px-3 py-2">Entry #</th>
                <th class="px-3 py-2">Document No</th>
                <th class="px-3 py-2">Type</th>
                <th class="px-3 py-2">Date</th>
                <th class="px-3 py-2 text-right">Amount (KES)</th>
                <th class="px-3 py-2">Description</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach($entries as $entry)
            @php
                $isCredit = (float) $entry->amount >= 0;
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                <td class="px-3 py-2 text-gray-600 dark:text-gray-300 font-mono text-xs">{{ $entry->entry_no }}</td>
                <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $entry->document_no ?? '—' }}</td>
                <td class="px-3 py-2">
                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium {{ $isCredit ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ ucfirst($entry->document_type) }}
                    </span>
                </td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                    {{ $entry->posting_date?->format('d M Y') }}
                </td>
                <td class="px-3 py-2 text-right font-mono {{ $isCredit ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                    {{ number_format(abs((float) $entry->amount), 2) }}
                </td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 text-xs max-w-xs truncate">{{ $entry->description ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/10">
            <tr>
                <td colspan="4" class="px-3 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Total</td>
                <td class="px-3 py-2 text-right font-mono font-semibold text-gray-800 dark:text-gray-100">
                    {{ number_format($entries->sum(fn($e) => abs((float) $e->amount)), 2) }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif
