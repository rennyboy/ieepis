import { createApp } from 'vue';
import OfflineScanner from './components/OfflineScanner.vue';

// Initialize the Vue app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('offline-scanner-app');
    if (el) {
        createApp(OfflineScanner).mount(el);
    }
});
