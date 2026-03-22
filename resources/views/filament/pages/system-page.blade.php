<x-filament-panels::page>
    <div x-data="{ tab: 'logs' }" class="space-y-4">

        {{-- Tab bar --}}
        <div class="flex space-x-1 border-b border-gray-200 dark:border-gray-700">
            @foreach ([['key' => 'logs', 'label' => 'Application Logs'], ['key' => 'notifications', 'label' => 'Notifications'], ['key' => 'schedules', 'label' => 'Scheduled Tasks']] as $t)
            <button
                @click="tab = '{{ $t['key'] }}'"
                :class="tab === '{{ $t['key'] }}' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2 text-sm transition-colors"
            >
                {{ $t['label'] }}
            </button>
            @endforeach
        </div>

        {{-- LOGS TAB --}}
        <div x-show="tab === 'logs'">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm text-gray-500">Last 100 log entries (newest first)</span>
                <x-filament::button wire:click="clearLogs" color="danger" size="sm" icon="heroicon-o-trash">
                    Clear Log
                </x-filament::button>
            </div>
            <div class="space-y-1 max-h-[600px] overflow-y-auto font-mono text-xs">
                @forelse ($logLines as $line)
                    @php
                        $color = match($line['level']) {
                            'error', 'critical', 'alert', 'emergency' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                            'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                            'info', 'notice' => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
                            default => 'bg-gray-50 border-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        };
                    @endphp
                    <div class="flex gap-3 p-2 rounded border {{ $color }}">
                        <span class="shrink-0 text-gray-400">{{ $line['datetime'] }}</span>
                        <span class="shrink-0 uppercase font-bold w-16">{{ $line['level'] }}</span>
                        <span class="break-all">{{ $line['message'] }}</span>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-8">No log entries found.</p>
                @endforelse
            </div>
        </div>

        {{-- NOTIFICATIONS TAB --}}
        <div x-show="tab === 'notifications'">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">User ID</th>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3">Read</th>
                            <th class="px-4 py-3">Sent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($notifications as $n)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono text-xs text-primary-600">{{ $n['type'] }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $n['notifiable_id'] }}</td>
                            <td class="px-4 py-3 text-gray-500 max-w-xs"><pre class="text-xs whitespace-pre-wrap">{{ $n['data'] }}</pre></td>
                            <td class="px-4 py-3">
                                @if ($n['read_at'])
                                    <span class="text-green-600 text-xs">{{ $n['read_at'] }}</span>
                                @else
                                    <span class="text-yellow-500 text-xs">Unread</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">{{ $n['created_at'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No notifications.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SCHEDULES TAB --}}
        <div x-show="tab === 'schedules'">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Command</th>
                            <th class="px-4 py-3">Schedule</th>
                            <th class="px-4 py-3">Next Due</th>
                            <th class="px-4 py-3">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($schedules as $s)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $s['command'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $s['expression'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-primary-600">{{ $s['next_due_at'] ?? $s['next_due'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $s['description'] ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No scheduled tasks found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
