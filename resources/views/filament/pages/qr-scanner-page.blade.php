<x-filament::page>
    <div id="offline-scanner-app"></div>

    @push('scripts')
        <!-- Vue Scanner Entry Point -->
        @vite(['resources/js/scanner.js'])
        
        <!-- html5-qrcode CDN -->
        <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
    @endpush
</x-filament::page>
