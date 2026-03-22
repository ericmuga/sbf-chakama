<x-filament-panels::page>
    <div class="mb-4 flex gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">From</label>
            <input type="date" wire:model.live="dateFrom"
                class="mt-1 block rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">To</label>
            <input type="date" wire:model.live="dateTo"
                class="mt-1 block rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50">
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Date</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Document No</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Description</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Debit</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Credit</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($this->getEntries() as $entry)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ optional($entry->posting_date)->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $entry->document_type ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $entry->document_no ?? '-' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $entry->description ?? '-' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                            {{ $entry->amount > 0 ? number_format($entry->amount, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                            {{ $entry->amount < 0 ? number_format(abs($entry->amount), 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                            {{ number_format($entry->remaining_amount ?? 0, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
