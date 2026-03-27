<div class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-700 dark:bg-primary-950">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <x-filament::icon
                icon="heroicon-o-arrows-right-left"
                class="h-5 w-5 text-primary-600 dark:text-primary-400"
            />
            <div>
                <p class="text-sm font-semibold text-primary-900 dark:text-primary-100">
                    You also have access to the <span class="font-bold">{{ $this->getOtherPortalLabel() }}</span>.
                </p>
                <p class="text-xs text-primary-700 dark:text-primary-300">
                    Currently viewing: {{ $this->getCurrentPortalLabel() }}
                </p>
            </div>
        </div>
        <a
            href="{{ $this->getOtherPortalUrl() }}"
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
            Switch Portal
            <x-filament::icon icon="heroicon-o-arrow-right" class="h-3.5 w-3.5" />
        </a>
    </div>
</div>
