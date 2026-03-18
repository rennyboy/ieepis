<div class="flex items-center gap-4 px-4">
    <!-- User Info Display -->
    <div class="hidden sm:flex flex-col items-end text-sm">
        <div class="font-semibold text-gray-900 dark:text-white">{{ $userName }}</div>
        <div class="text-xs text-gray-600 dark:text-gray-400">
            {{ ucfirst(str_replace('-', ' ', $userRole)) }}
        </div>
        @if($schoolName)
            <div class="text-xs text-gray-500 dark:text-gray-500">{{ $schoolName }}</div>
        @endif
    </div>
</div>
