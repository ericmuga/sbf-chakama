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
                        <flux:text class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('National ID') }}</flux:text>
                        <flux:text class="mt-1">{{ $member->national_id ?? '—' }}</flux:text>
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
        </div>
    @endif
</div>
