import { createApp } from 'vue';
import OfflineEquipment from './components/OfflineEquipment.vue';

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('offline-equipment-app');
    if (el) {
        createApp(OfflineEquipment).mount(el);
    }
});
