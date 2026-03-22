<x-filament-panels::page>
    <div class="space-y-4">
        @forelse($this->getNotifications() as $notification)
            <div @class([
                'rounded-xl border p-4',
                'bg-white dark:bg-gray-800' => $notification->read_at,
                'bg-primary-50 dark:bg-primary-900/20 border-primary-200' => !$notification->read_at,
            ])>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $notification->data['title'] ?? 'Notification' }}
                        </p>
                        @if(!empty($notification->data['body']))
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $notification->data['body'] }}
                            </p>
                        @endif
                        <p class="mt-2 text-xs text-gray-400">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$notification->read_at)
                        <span class="inline-flex h-2 w-2 flex-shrink-0 rounded-full bg-primary-500 mt-1.5"></span>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border bg-white p-8 text-center dark:bg-gray-800">
                <p class="text-gray-500">No notifications yet.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
