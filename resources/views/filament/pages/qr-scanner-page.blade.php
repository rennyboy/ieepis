<x-filament::page>
    <div id="offline-scanner-app"></div>

    @push('scripts')
        @vite(['resources/js/scanner.js'])
    @endpush
</x-filament::page>
