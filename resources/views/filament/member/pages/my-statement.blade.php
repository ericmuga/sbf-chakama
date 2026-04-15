<x-filament-panels::page>

    {{-- ── Date filters ─────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
            <input type="date" wire:model.live="dateFrom"
                class="block rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:border-primary-500 focus:ring-primary-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
            <input type="date" wire:model.live="dateTo"
                class="block rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:border-primary-500 focus:ring-primary-500" />
        </div>
        @if($dateFrom || $dateTo)
            <button wire:click="$set('dateFrom', null); $set('dateTo', null)"
                class="text-sm text-gray-500 hover:text-danger-600 dark:text-gray-400 underline self-end pb-1">
                Clear filters
            </button>
        @endif
    </div>

    @php
        $entries = $this->getEntries();
        $totals  = $this->getTotals();
    @endphp

    {{-- ── Table ───────────────────────────────────────────────────────────── --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">

            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/60">
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Type</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Document No</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Debit (KES)</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Credit (KES)</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Running Balance</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50 bg-white dark:bg-gray-900">

                {{-- Opening balance row (only when a date filter is active) --}}
                @if($dateFrom && $totals['opening'] != 0)
                    <tr class="bg-blue-50/60 dark:bg-blue-900/10 italic text-gray-500 dark:text-gray-400">
                        <td class="px-4 py-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($dateFrom)->subDay()->format('d M Y') }}</td>
                        <td class="px-4 py-2" colspan="2">Opening Balance</td>
                        <td class="px-4 py-2 text-right">—</td>
                        <td class="px-4 py-2 text-right">—</td>
                        <td class="px-4 py-2 text-right font-medium
                            {{ $totals['opening'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                            {{ number_format(abs($totals['opening']), 2) }}
                            <span class="text-xs font-normal">{{ $totals['opening'] > 0 ? 'DR' : 'CR' }}</span>
                        </td>
                    </tr>
                @endif

                @forelse($entries as $entry)
                    @php
                        $isDebit  = (float) $entry->amount > 0;
                        $isCredit = (float) $entry->amount < 0;
                        $balance  = (float) $entry->running_balance;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $entry->posting_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $isDebit
                                    ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                                {{ ucfirst($entry->document_type ?? '—') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                            {{ $entry->document_no ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">
                            {{ $isDebit ? number_format((float) $entry->amount, 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">
                            {{ $isCredit ? number_format(abs((float) $entry->amount), 2) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold whitespace-nowrap
                            {{ $balance > 0 ? 'text-danger-600 dark:text-danger-400' : ($balance < 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-500') }}">
                            {{ number_format(abs($balance), 2) }}
                            @if($balance != 0)
                                <span class="text-xs font-normal ml-0.5">{{ $balance > 0 ? 'DR' : 'CR' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <x-heroicon-o-document-text class="w-8 h-8 opacity-40" />
                                <span>No entries found for the selected period.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse

            </tbody>

            {{-- ── Totals footer ──────────────────────────────────────────────── --}}
            @if($entries->isNotEmpty())
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60 font-semibold">
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200" colspan="3">Totals</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">
                            {{ number_format($totals['total_debits'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">
                            {{ number_format($totals['total_credits'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right
                            {{ $totals['closing'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                            {{ number_format(abs($totals['closing']), 2) }}
                            @if($totals['closing'] != 0)
                                <span class="text-xs font-normal ml-0.5">{{ $totals['closing'] > 0 ? 'DR' : 'CR' }}</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            @endif

        </table>
    </div>

</x-filament-panels::page>
