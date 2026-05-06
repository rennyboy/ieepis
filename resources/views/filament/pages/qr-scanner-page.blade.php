<x-filament::page>
    <div id="offline-scanner-app"></div>

    @push('scripts')
        <!-- Vue Scanner Entry Point -->
        @vite(['resources/js/scanner.js'])
        <!-- PWA Install Prompt Component -->
        <script type="module">
            import { createApp } from 'vue';
            import PwaInstallPrompt from '/resources/js/components/PwaInstallPrompt.vue';
            
            // Mount PWA install prompt if not already installed
            if (!window.matchMedia('(display-mode: standalone)').matches) {
                const installEl = document.createElement('div');
                installEl.id = 'pwa-install-container';
                document.body.appendChild(installEl);
                createApp(PwaInstallPrompt).mount('#pwa-install-container');
            }
        </script>
    @endpush
</x-filament::page>
