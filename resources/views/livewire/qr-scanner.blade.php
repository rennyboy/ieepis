<div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('mobile', {
                isMobile: window.innerWidth <= 768,
                update() { this.isMobile = window.innerWidth <= 768; },
            });
            window.addEventListener('resize', () => Alpine.store('mobile').update());
        });
    </script>

    <div x-data="{}" class="p-4 md:hidden">
        <h2 class="text-lg font-bold mb-2">QR Scanner</h2>

        <button wire:click="startScanning"
                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 transition">
            Scan QR
        </button>

        <div id="qr-reader" class="mt-4"></div>

        <p id="qr-scanner-status"
           class="hidden mt-3 text-sm rounded p-2 border
                  data-[type=error]:text-red-700 data-[type=error]:bg-red-50 data-[type=error]:border-red-200
                  data-[type=info]:text-gray-700 data-[type=info]:bg-gray-50 data-[type=info]:border-gray-200"
           role="status" aria-live="polite"></p>
    </div>

    <div class="mt-4 p-4 border rounded bg-gray-50">
        <label class="block text-sm font-medium mb-1" for="manual-code">Enter QR code manually</label>
        <input type="text" id="manual-code" wire:model.defer="manualCode"
               class="w-full border rounded px-2 py-1" placeholder="EQ-123" />
        <button wire:click="submitManual"
                class="mt-2 px-3 py-1 bg-gray-800 text-white rounded hover:bg-gray-700 transition">
            Submit
        </button>

        @if ($errorMessage)
            <p class="mt-2 text-red-600">{{ $errorMessage }}</p>
        @endif
    </div>

    @push('scripts')
        @vite('resources/js/livewire-scanner.js')
    @endpush
</div>
