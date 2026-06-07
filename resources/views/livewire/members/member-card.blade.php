<div>
    @if ($member === null)
        <flux:card class="text-center py-12">
            <flux:icon name="user-circle" class="mx-auto size-16 text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">{{ __('Member profile not set up') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('Your member profile is not yet set up. Please contact the administrator.') }}</flux:text>
        </flux:card>
    @else
        <div class="space-y-6">
            {{-- Section 1: Member Details --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Member Details') }}</flux:heading>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Member Number') }}</flux:text>
                        <flux:text class="mt-1 font-semibold">{{ $member->no ?? '—' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Full Name') }}</flux:text>
                        <flux:text class="mt-1 font-semibold">{{ $member->user?->name ?? $member->name ?? '—' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Identity Type') }}</flux:text>
                        <flux:text class="mt-1">{{ match($member->identity_type) {
                            'national_id' => 'National ID',
                            'passport_no' => 'Passport No',
                            'birth_cert_no' => 'Birth Certificate No',
                            'driving_licence_no' => 'Driving Licence No',
                            'pin_no' => 'PIN No',
                            default => '—',
                        } }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Identity Number') }}</flux:text>
                        <flux:text class="mt-1">{{ $member->identity_no ?? '—' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Phone') }}</flux:text>
                        <flux:text class="mt-1">{{ $member->phone ?? '—' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Status') }}</flux:text>
                        <div class="mt-1">
                            @if ($member->member_status === 'active')
                                <flux:badge color="green">{{ __('Active') }}</flux:badge>
                            @elseif ($member->member_status === 'lapsed')
                                <flux:badge color="yellow">{{ __('Lapsed') }}</flux:badge>
                            @elseif ($member->member_status === 'suspended')
                                <flux:badge color="red">{{ __('Suspended') }}</flux:badge>
                            @else
                                <flux:badge color="zinc">{{ __('Unknown') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Membership Type') }}</flux:text>
                        <div class="mt-1 flex gap-2 flex-wrap">
                            @if ($member->is_chakama)
                                <flux:badge color="blue">{{ __('Chakama') }}</flux:badge>
                            @endif
                            @if ($member->is_sbf)
                                <flux:badge color="purple">{{ __('SBF') }}</flux:badge>
                            @endif
                            @if (!$member->is_chakama && !$member->is_sbf)
                                <flux:text class="text-zinc-500 text-sm">{{ __('None') }}</flux:text>
                            @endif
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Section 2: Next of Kin --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Next of Kin') }}</flux:heading>
                @if ($member->nextOfKin->isEmpty())
                    <flux:text class="text-zinc-500 italic">{{ __('No next of kin on record.') }}</flux:text>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Name') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Relationship') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Phone') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Email') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Contact Preference') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($member->nextOfKin as $kin)
                                    <tr>
                                        <td class="py-3 font-medium">{{ $kin->name }}</td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $kin->relationship }}</td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $kin->phone ?? '—' }}</td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $kin->email ?? '—' }}</td>
                                        <td class="py-3">
                                            @if ($kin->contact_preference)
                                                <flux:badge color="zinc" size="sm">{{ ucfirst($kin->contact_preference) }}</flux:badge>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </flux:card>

            {{-- Section 3: Dependants --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">{{ __('Dependants') }}</flux:heading>
                @if ($member->dependants->isEmpty())
                    <flux:text class="text-zinc-500 italic">{{ __('No dependants on record.') }}</flux:text>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Name') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Relationship') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Phone') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Date of Birth') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($member->dependants as $dependant)
                                    <tr>
                                        <td class="py-3 font-medium">{{ $dependant->name }}</td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $dependant->relationship }}</td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">{{ $dependant->phone ?? '—' }}</td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                            {{ $dependant->date_of_birth?->format('d M Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </flux:card>

            {{-- Section 4: Account Statement --}}
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">{{ __('Account Statement') }}</flux:heading>
                    @if (count($ledgerEntries) > 0)
                        <span class="text-sm font-semibold
                            {{ $closingBalance > 0 ? 'text-red-600 dark:text-red-400' : ($closingBalance < 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-500') }}">
                            Balance:
                            KES {{ number_format(abs($closingBalance), 2) }}
                            {{ $closingBalance > 0 ? 'DR' : ($closingBalance < 0 ? 'CR' : 'NIL') }}
                        </span>
                    @endif
                </div>

                @if (count($ledgerEntries) === 0)
                    <flux:text class="text-zinc-500 italic">{{ __('No ledger entries on record.') }}</flux:text>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Date') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Type') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Document No') }}</th>
                                    <th class="pb-3 text-left font-medium text-zinc-500">{{ __('Due Date') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500">{{ __('Debit') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500">{{ __('Credit') }}</th>
                                    <th class="pb-3 text-right font-medium text-zinc-500">{{ __('Balance') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($ledgerEntries as $entry)
                                    <tr>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $entry['posting_date'] }}</td>
                                        <td class="py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                {{ $entry['document_type'] === 'Invoice' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                                                {{ $entry['document_type'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                            @if ($entry['pdf_url'])
                                                <a href="{{ $entry['pdf_url'] }}"
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300">
                                                    {{ $entry['document_no'] }}
                                                    <flux:icon name="arrow-top-right-on-square" class="size-3" />
                                                </a>
                                            @else
                                                {{ $entry['document_no'] }}
                                            @endif
                                        </td>
                                        <td class="py-3 text-zinc-500 dark:text-zinc-400 whitespace-nowrap text-xs">{{ $entry['due_date'] }}</td>
                                        <td class="py-3 text-right tabular-nums {{ $entry['debit'] > 0 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">
                                            {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '—' }}
                                        </td>
                                        <td class="py-3 text-right tabular-nums {{ $entry['credit'] > 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-zinc-300 dark:text-zinc-600' }}">
                                            {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '—' }}
                                        </td>
                                        <td class="py-3 text-right tabular-nums font-semibold whitespace-nowrap
                                            {{ $entry['running_balance'] > 0 ? 'text-red-600 dark:text-red-400' : ($entry['running_balance'] < 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-400') }}">
                                            {{ number_format(abs($entry['running_balance']), 2) }}
                                            <span class="text-xs font-normal">
                                                {{ $entry['running_balance'] > 0 ? 'DR' : ($entry['running_balance'] < 0 ? 'CR' : '') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-zinc-300 dark:border-zinc-600">
                                    <td colspan="4" class="pt-3 font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Closing Balance') }}</td>
                                    <td class="pt-3 text-right tabular-nums font-semibold text-amber-600 dark:text-amber-400">
                                        {{ number_format(collect($ledgerEntries)->sum('debit'), 2) }}
                                    </td>
                                    <td class="pt-3 text-right tabular-nums font-semibold text-green-600 dark:text-green-400">
                                        {{ number_format(collect($ledgerEntries)->sum('credit'), 2) }}
                                    </td>
                                    <td class="pt-3 text-right tabular-nums font-bold whitespace-nowrap
                                        {{ $closingBalance > 0 ? 'text-red-600 dark:text-red-400' : ($closingBalance < 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-400') }}">
                                        {{ number_format(abs($closingBalance), 2) }}
                                        <span class="text-xs font-normal">
                                            {{ $closingBalance > 0 ? 'DR' : ($closingBalance < 0 ? 'CR' : 'NIL') }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </flux:card>
        </div>
    @endif
</div>
