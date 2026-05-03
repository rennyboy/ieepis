<x-filament::page>
    <h2 class="text-xl font-semibold mb-4">QR Code Scanner</h2>
    @livewire('qr-scanner')

    @push('scripts')
        <!-- html5-qrcode CDN (fallback if not compiled locally) -->
        <script src="https://unpkg.com/html5-qrcode@2.3.7/minified/html5-qrcode.min.js"></script>
    @endpush
</x-filament::page>
