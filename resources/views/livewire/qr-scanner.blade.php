<div>
    <!-- Mobile detection using Alpine -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('mobile', {
                isMobile: window.innerWidth <= 768,
                update() {
                    this.isMobile = window.innerWidth <= 768;
                }
            });
            window.addEventListener('resize', () => {
                Alpine.store('mobile').update();
            });
        });
    </script>

<div x-data="{}" class="p-4 md:hidden">
        <h2 class="text-lg font-bold mb-2">QR Scanner</h2>
        <button wire:click="startScanning" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500 transition">
            Scan QR
        </button>
        <div id="qr-reader" class="mt-4"></div>
    </div>

    <!-- Fallback manual entry always visible -->
    <div class="mt-4 p-4 border rounded bg-gray-50">
        <label class="block text-sm font-medium mb-1" for="manual-code">Enter QR code manually</label>
        <input type="text" id="manual-code" wire:model.defer="manualCode" class="w-full border rounded px-2 py-1" placeholder="EQ-123" />
        <button wire:click="submitManual" class="mt-2 px-3 py-1 bg-gray-800 text-white rounded hover:bg-gray-700 transition">
            Submit
        </button>
        <template x-if="errorMessage">
            <p class="mt-2 text-red-600" x-text="errorMessage"></p>
        </template>
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
        if (window.Html5Qrcode) {
            Livewire.on('qr-start', () => {
                const html5QrCode = new Html5Qrcode('qr-reader');
                
                Html5Qrcode.getCameras().then(devices => {
                    if (devices && devices.length) {
                        // Prefer back camera if available
                        let cameraId = devices[0].id;
                        devices.forEach(device => {
                            if (device.label.toLowerCase().includes('back') || device.label.toLowerCase().includes('environment')) {
                                cameraId = device.id;
                            }
                        });

                        html5QrCode.start(
                            cameraId,
                            { fps: 10, qrbox: 250 },
                            (decodedText) => {
                                html5QrCode.stop();
                                Livewire.dispatch('qr-scanned', { value: decodedText });
                            }
                        ).catch(err => console.error(err));
                    } else {
                        // Fallback to environment facing mode
                        html5QrCode.start(
                            { facingMode: 'environment' }, 
                            { fps: 10, qrbox: 250 }, 
                            (decodedText) => {
                                html5QrCode.stop();
                                Livewire.dispatch('qr-scanned', { value: decodedText });
                            }
                        ).catch(err => console.error(err));
                    }
                }).catch(err => {
                    console.error("Camera error:", err);
                });
            });
        }
    });
</script>
@endpush
