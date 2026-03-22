<x-filament-panels::page>

    {{-- ─── STEP 1: Payment Form ─────────────────────────────────────── --}}
    @if ($step === 'form')
        <div class="max-w-lg mx-auto">
            <x-filament::section>
                <x-slot name="heading">Pay with M-Pesa</x-slot>
                <x-slot name="description">Enter your M-Pesa number and the amount. You will receive a prompt on your phone.</x-slot>

                <div class="space-y-4">
                    {{-- Phone --}}
                    <div>
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            M-Pesa Phone Number
                            <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-2">
                            <input
                                wire:model="phone"
                                type="tel"
                                placeholder="07XXXXXXXX"
                                class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:border-white/20 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:placeholder:text-gray-500"
                            />
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                            Amount (KES)
                            <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500">KES</span>
                            <input
                                wire:model="amount"
                                type="number"
                                min="1"
                                step="1"
                                placeholder="0"
                                class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:border-white/20 dark:bg-white/5 dark:text-white dark:ring-white/20"
                            />
                        </div>
                        @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="flex justify-end gap-3 pt-2">
                        <x-filament::button
                            wire:click="initiateSTKPush"
                            wire:loading.attr="disabled"
                            color="success"
                            icon="heroicon-o-device-phone-mobile"
                        >
                            <span wire:loading.remove wire:target="initiateSTKPush">Send STK Push</span>
                            <span wire:loading wire:target="initiateSTKPush">Sending…</span>
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- ─── STEP 2: Pending — polling for M-Pesa callback ──────────────────────── --}}
    @if ($step === 'pending')
        <div
            class="max-w-lg mx-auto"
            wire:poll.3000ms="pollForTransaction"
        >
            <x-filament::section>
                <x-slot name="heading">Waiting for M-Pesa…</x-slot>

                <div class="flex flex-col items-center gap-6 py-8 text-center">
                    {{-- Spinner --}}
                    <svg class="h-14 w-14 animate-spin text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>

                    <div>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">Check your phone</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            An M-Pesa prompt has been sent to <strong>{{ $phone }}</strong>.<br>
                            Enter your PIN to complete the payment of <strong>KES {{ number_format((float) $amount, 2) }}</strong>.
                        </p>
                    </div>

                    <x-filament::button color="gray" wire:click="cancelPayment" size="sm">
                        Cancel
                    </x-filament::button>

                    @if ($isLocalMode)
                        <x-filament::button
                            wire:click="simulateLocalPayment"
                            color="warning"
                            size="sm"
                            icon="heroicon-o-beaker"
                        >
                            Simulate Payment (Test)
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- ─── STEP 3: Confirmed — show receipt + invoice allocation ─────────────────── --}}
    @if ($step === 'confirmed')
        <div class="max-w-2xl mx-auto space-y-6">

            {{-- Confirmation badge --}}
            <x-filament::section>
                <x-slot name="heading">Payment Confirmed</x-slot>

                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-success-100 dark:bg-success-500/20">
                        <x-heroicon-o-check-circle class="h-7 w-7 text-success-600 dark:text-success-400" />
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt class="font-medium text-gray-500 dark:text-gray-400">M-Pesa Code</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white">{{ $mpesaReceiptNo }}</dd>

                        <dt class="font-medium text-gray-500 dark:text-gray-400">Amount</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white">KES {{ number_format((float) $confirmedAmount, 2) }}</dd>

                        <dt class="font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $confirmedPhone }}</dd>

                        <dt class="font-medium text-gray-500 dark:text-gray-400">Date & Time</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $confirmedAt }}</dd>
                    </dl>
                </div>
            </x-filament::section>

            {{-- Invoice allocation --}}
            @if (count($openInvoices) > 0)
                <x-filament::section>
                    <x-slot name="heading">Apply to Pending Bills</x-slot>
                    <x-slot name="description">Optionally tag this payment against your open invoices. Leave blank to leave unallocated.</x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-white/10 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    <th class="pb-2 pr-4">Invoice No</th>
                                    <th class="pb-2 pr-4">Due Date</th>
                                    <th class="pb-2 pr-4 text-right">Outstanding (KES)</th>
                                    <th class="pb-2 text-right">Apply Amount (KES)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($openInvoices as $index => $invoice)
                                    <tr>
                                        <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $invoice['document_no'] }}</td>
                                        <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $invoice['due_date'] }}</td>
                                        <td class="py-2 pr-4 text-right text-gray-900 dark:text-white">{{ number_format($invoice['remaining_amount'], 2) }}</td>
                                        <td class="py-2 text-right">
                                            <input
                                                wire:model="openInvoices.{{ $index }}.amount_applied"
                                                type="number"
                                                min="0"
                                                max="{{ $invoice['remaining_amount'] }}"
                                                step="0.01"
                                                placeholder="0.00"
                                                class="w-32 rounded-lg border border-gray-300 bg-white px-2 py-1 text-right text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:border-white/20 dark:bg-white/5 dark:text-white"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- Post action --}}
            <div class="flex justify-end gap-3">
                <x-filament::button color="gray" wire:click="cancelPayment">
                    Start Over
                </x-filament::button>

                <x-filament::button
                    wire:click="postPayment"
                    wire:loading.attr="disabled"
                    color="success"
                    icon="heroicon-o-check-circle"
                >
                    <span wire:loading.remove wire:target="postPayment">Post Receipt</span>
                    <span wire:loading wire:target="postPayment">Posting…</span>
                </x-filament::button>
            </div>
        </div>
    @endif

</x-filament-panels::page>
