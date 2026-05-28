import '../css/app.css';
import { createApp } from 'vue';
import OfflineScanner from './components/OfflineScanner.vue';
import PwaInstallPrompt from './components/PwaInstallPrompt.vue';

const mount = () => {
    const el = document.getElementById('offline-scanner-app');
    if (el && !el.__vueMounted) {
        createApp(OfflineScanner).mount(el);
        el.__vueMounted = true;
    }

    if (!window.matchMedia('(display-mode: standalone)').matches
        && !document.getElementById('pwa-install-container')) {
        const installEl = document.createElement('div');
        installEl.id = 'pwa-install-container';
        document.body.appendChild(installEl);
        createApp(PwaInstallPrompt).mount(installEl);
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
