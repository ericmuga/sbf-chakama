<div class="space-y-4">
    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-zinc-500 uppercase tracking-wide">From</label>
            <input
                type="date"
                wire:model="dateFrom"
                class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-zinc-500 uppercase tracking-wide">To</label>
            <input
                type="date"
                wire:model="dateTo"
                class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
        </div>
        <button
            type="button"
            wire:click="refresh"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white text-sm font-medium px-4 py-1.5 transition"
        >
            <span wire:loading.remove wire:target="refresh">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
            </span>
            <span wire:loading wire:target="refresh">
                <svg class="size-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
            </span>
            Refresh
        </button>
    </div>

    {{-- Statement body --}}
    @if ($loaded)
        {{-- Summary row --}}
        <div class="grid grid-cols-3 gap-3 text-sm">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-center">
                <div class="text-xs text-zinc-500 mb-1">Opening Balance</div>
                <div class="font-semibold {{ $opening >= 0 ? 'text-warning-600' : 'text-success-600' }}">
                    KES {{ number_format(abs($opening), 2) }} {{ $opening > 0 ? 'DR' : ($opening < 0 ? 'CR' : 'NIL') }}
                </div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-center">
                <div class="text-xs text-zinc-500 mb-1">Period Activity</div>
                <div class="text-xs">
                    <span class="text-warning-600">DR {{ number_format($totalDebits, 2) }}</span>
                    &nbsp;/&nbsp;
                    <span class="text-success-600">CR {{ number_format($totalCredits, 2) }}</span>
                </div>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 text-center">
                <div class="text-xs text-zinc-500 mb-1">Closing Balance</div>
                <div class="font-semibold {{ $closing > 0 ? 'text-danger-600' : 'text-success-600' }}">
                    KES {{ number_format(abs($closing), 2) }} {{ $closing > 0 ? 'DR' : ($closing < 0 ? 'CR' : 'NIL') }}
                </div>
            </div>
        </div>

        {{-- Entries table --}}
        @if (count($entries) > 0)
            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Date</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Document No</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase tracking-wide">Description</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase tracking-wide">Debit</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase tracking-wide">Credit</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase tracking-wide">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($entries as $entry)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-3 py-2 whitespace-nowrap text-zinc-600 dark:text-zinc-400">{{ $entry['posting_date'] }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $entry['document_type'] === 'Invoice' ? 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' : 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' }}">
                                        {{ $entry['document_type'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $entry['document_no'] }}</td>
                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400 max-w-[160px] truncate">{{ $entry['description'] ?? '—' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums {{ $entry['debit'] > 0 ? 'text-warning-600 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">
                                    {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums {{ $entry['credit'] > 0 ? 'text-success-600 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">
                                    {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums font-medium {{ $entry['running_balance'] > 0 ? 'text-danger-600' : ($entry['running_balance'] < 0 ? 'text-success-600' : 'text-zinc-500') }}">
                                    {{ number_format(abs($entry['running_balance']), 2) }}
                                    {{ $entry['running_balance'] > 0 ? 'DR' : ($entry['running_balance'] < 0 ? 'CR' : '') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-zinc-400 text-sm">No entries found for the selected period.</div>
        @endif

        {{-- Download buttons --}}
        <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
            <a
                href="{{ $this->getDownloadUrlAttribute('excel') }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Excel
            </a>
            <a
                href="{{ $this->getDownloadUrlAttribute('pdf') }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 rounded-lg bg-danger-600 hover:bg-danger-700 px-3 py-1.5 text-sm font-medium text-white transition"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                PDF
            </a>
        </div>
    @else
        <div class="text-center py-10 text-zinc-400 text-sm">
            Set the date range above (or leave blank for all entries) and click <strong>Refresh</strong> to load the statement.
        </div>
    @endif
</div>
