<template>
  <div v-if="showInstallPrompt" class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-80 bg-white rounded-lg shadow-lg border border-gray-200 p-4 z-50">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <h3 class="font-bold text-black text-base mb-1">Install IEEPIS App</h3>
        <p class="text-sm text-gray-600 mb-3">Install this app on your Android phone for quick access and offline use.</p>
        <div class="flex gap-2">
          <button @click="installApp" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
            Install
          </button>
          <button @click="dismissPrompt" class="px-4 py-2 bg-gray-200 text-black font-medium rounded-lg hover:bg-gray-300 transition">
            Not Now
          </button>
        </div>
      </div>
      <button @click="dismissPrompt" class="text-gray-400 hover:text-gray-600 ml-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const showInstallPrompt = ref(false);
let deferredPrompt = null;

onMounted(() => {
  // Check if already installed
  if (window.matchMedia('(display-mode: standalone)').matches) {
    return;
  }

  // Listen for the beforeinstallprompt event
  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
  
  // Check if app can be installed
  window.addEventListener('appinstalled', () => {
    showInstallPrompt.value = false;
    deferredPrompt = null;
  });
});

onUnmounted(() => {
  window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
});

const handleBeforeInstallPrompt = (e) => {
  // Prevent the mini-infobar from appearing on mobile
  e.preventDefault();
  // Stash the event so it can be triggered later
  deferredPrompt = e;
  // Show our custom install prompt
  showInstallPrompt.value = true;
};

const installApp = async () => {
  if (!deferredPrompt) return;
  
  // Show the install prompt
  deferredPrompt.prompt();
  
  // Wait for the user to respond to the prompt
  const { outcome } = await deferredPrompt.userChoice;
  
  // Clear the deferredPrompt
  deferredPrompt = null;
  showInstallPrompt.value = false;
  
  if (outcome === 'accepted') {
    console.log('User accepted the install prompt');
  } else {
    console.log('User dismissed the install prompt');
  }
};

const dismissPrompt = () => {
  showInstallPrompt.value = false;
  // Hide for this session
  sessionStorage.setItem('pwa-install-dismissed', 'true');
};
</script>
