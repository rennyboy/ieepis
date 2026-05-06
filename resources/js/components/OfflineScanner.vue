<template>
  <div class="p-4 md:p-6 bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold text-gray-800">QR Scanner</h2>
      <div :class="['px-3 py-1 text-xs font-semibold rounded-full', isOnline ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700']">
        {{ isOnline ? 'Online' : 'Offline Mode' }}
      </div>
    </div>

    <!-- Offline Queue Status -->
    <div v-if="offlineQueue.length > 0" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800 flex justify-between items-center">
      <span>{{ offlineQueue.length }} scan(s) waiting to sync...</span>
      <button v-if="isOnline" @click="syncOfflineScans" class="px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs transition">Sync Now</button>
    </div>

    <!-- Scanner Area -->
    <div v-show="scanning" id="qr-reader-vue" class="w-full mt-4 overflow-hidden rounded-lg border border-gray-200"></div>

    <!-- Action Buttons -->
    <div class="mt-6 flex flex-col sm:flex-row gap-3">
      <button 
        v-if="!scanning" 
        @click="startScanning" 
        class="flex-1 px-4 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition flex justify-center items-center gap-2 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        Open Camera
      </button>
      <button 
        v-if="scanning" 
        @click="stopScanning" 
        class="flex-1 px-4 py-3 bg-red-500 text-white font-medium rounded-lg hover:bg-red-600 transition flex justify-center items-center shadow-sm">
        Stop Camera
      </button>
    </div>

    <!-- Manual Entry Fallback -->
    <div class="mt-6 pt-6 border-t border-gray-100">
      <label class="block text-sm font-medium text-gray-700 mb-2" for="manual-code">Enter QR code manually</label>
      <div class="flex gap-2">
        <input 
          type="text" 
          id="manual-code" 
          v-model="manualCode" 
          @keyup.enter="submitManual"
          class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition" 
          placeholder="e.g. EQ-123 or Property Number" />
        <button 
          @click="submitManual" 
          class="px-4 py-2 bg-gray-800 text-white font-medium rounded-lg hover:bg-gray-700 transition">
          Submit
        </button>
      </div>
    </div>

    <!-- Messages -->
    <div v-if="message.text" :class="['mt-4 p-3 rounded-lg text-sm', message.type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200']">
      {{ message.text }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import localforage from 'localforage';
import axios from 'axios';
import { Html5Qrcode } from 'html5-qrcode';

// State
const scanning = ref(false);
const isOnline = ref(navigator.onLine);
const manualCode = ref('');
const message = ref({ text: '', type: '' });
const offlineQueue = ref([]);
let html5QrCode = null;

// Initialize
onMounted(async () => {
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
  await loadOfflineQueue();
});

onUnmounted(() => {
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
  stopScanning();
});

const handleOnline = () => {
  isOnline.value = true;
  syncOfflineScans();
};

const handleOffline = () => {
  isOnline.value = false;
};

const showMessage = (text, type = 'error', duration = 5000) => {
  message.value = { text, type };
  if (duration > 0) {
    setTimeout(() => { message.value = { text: '', type: '' }; }, duration);
  }
};

const loadOfflineQueue = async () => {
  try {
    const queue = await localforage.getItem('ieepis_offline_scans');
    if (queue && Array.isArray(queue)) {
      offlineQueue.value = queue;
    } else {
      offlineQueue.value = [];
    }
  } catch (err) {
    console.error("Error loading offline queue", err);
  }
};

const saveToOfflineQueue = async (code) => {
  const newScan = {
    code,
    timestamp: new Date().toISOString(),
    id: Date.now().toString()
  };
  
  const updatedQueue = [...offlineQueue.value, newScan];
  await localforage.setItem('ieepis_offline_scans', updatedQueue);
  offlineQueue.value = updatedQueue;
  showMessage(`Saved offline: ${code}. Will sync when connected.`, 'success', 0);
};

const processScannedCode = async (code) => {
  stopScanning();
  
  if (!code) {
    showMessage('Invalid QR code');
    return;
  }

  // If online, send to server
  if (isOnline.value) {
    try {
      showMessage('Processing...', 'success', 0);
      const response = await axios.post('/scanner/resolve', { code });
      
      if (response.data.redirect) {
        window.location.href = response.data.redirect;
      } else {
        showMessage(response.data.message || 'Scanned successfully', 'success');
      }
    } catch (err) {
      if (err.response && err.response.data && err.response.data.message) {
        showMessage(err.response.data.message);
      } else {
        showMessage('Error reaching server. Saving offline...', 'error');
        saveToOfflineQueue(code);
      }
    }
  } else {
    // If offline, save locally
    saveToOfflineQueue(code);
  }
};

const submitManual = () => {
  const code = manualCode.value.trim();
  if (code) {
    processScannedCode(code);
    manualCode.value = '';
  }
};

const syncOfflineScans = async () => {
  if (!isOnline.value || offlineQueue.value.length === 0) return;
  
  try {
    showMessage(`Syncing ${offlineQueue.value.length} offline scans...`, 'success', 0);
    
    // In a real app, you might want to sync them one by one or in batch.
    // For this implementation, we will send them in batch.
    const response = await axios.post('/scanner/sync', { 
      scans: offlineQueue.value 
    });
    
    // Clear queue on success
    await localforage.setItem('ieepis_offline_scans', []);
    offlineQueue.value = [];
    
    showMessage(`Successfully synced ${response.data.synced_count || 'all'} offline scans!`, 'success');
  } catch (err) {
    console.error("Sync error", err);
    showMessage('Failed to sync. Will try again later.', 'error');
  }
};

// Camera Logic
const isInAppBrowser = () => /(FBAN|FBAV|FB_IAB|Instagram|Line|MicroMessenger|Twitter|TikTok)/i.test(navigator.userAgent || '');

const cameraPreflight = () => {
  if (!window.isSecureContext) {
    return 'Camera requires HTTPS. This page is being served over an insecure (http://) connection — mobile browsers block the camera unless the URL starts with https://.';
  }
  if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
    if (isInAppBrowser()) {
      return 'Camera streaming is blocked inside this in-app browser (Facebook / Instagram / Line / etc.). Tap the menu and choose "Open in Chrome" or "Open in Safari".';
    }
    return 'Camera streaming is not supported in this browser. Update Chrome / Safari, or open the site in a different browser.';
  }
  return null;
};

const friendlyCameraError = (err) => {
  const name = err?.name || '';
  const msg = err?.message || (typeof err === 'string' ? err : '');

  if (name === 'NotAllowedError' || /denied|permission/i.test(msg)) {
    return 'Camera permission was denied. Open browser settings and allow camera access for this site, then try again.';
  }
  if (name === 'NotFoundError' || /not.*found|no.*camera/i.test(msg)) {
    return 'No camera was found on this device.';
  }
  if (name === 'NotReadableError' || /in use|busy/i.test(msg)) {
    return 'Camera is already in use by another app. Close apps using the camera and try again.';
  }
  if (name === 'OverconstrainedError') {
    return 'No camera matches the requested settings. Use the manual entry below.';
  }
  if (/secure|https/i.test(msg)) {
    return 'Camera requires a secure (https://) connection.';
  }
  return msg || 'Unknown camera error. Use the manual entry below.';
};

const startScanning = () => {
  message.value = { text: '', type: '' };

  const failure = cameraPreflight();
  if (failure) {
    showMessage(failure, 'error', 0);
    return;
  }

  scanning.value = true;

  // Need to wait for next tick for the DOM element to be visible
  setTimeout(() => {
    html5QrCode = new Html5Qrcode('qr-reader-vue');

    html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      (decodedText) => {
        processScannedCode(decodedText);
      },
    ).catch(err => {
      console.error('Camera start failed:', err);
      showMessage(friendlyCameraError(err), 'error', 0);
      stopScanning();
    });
  }, 100);
};

const stopScanning = () => {
  if (html5QrCode && scanning.value) {
    try {
      html5QrCode.stop().then(() => {
        html5QrCode.clear();
      }).catch(err => console.error(err));
    } catch(e) {}
  }
  scanning.value = false;
};
</script>
