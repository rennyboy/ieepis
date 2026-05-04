<x-filament::page>
    <div id="offline-scanner-app"></div>

    @push('scripts')
        <!-- Vue Scanner Entry Point -->
        @vite(['resources/js/scanner.js'])
    @endpush
</x-filament::page>
