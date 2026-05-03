<template>
  <div class="p-4 md:p-6 bg-white rounded-xl shadow-sm border border-gray-100 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-bold text-gray-800">Offline Equipment</h2>
      <div :class="['px-3 py-1 text-xs font-semibold rounded-full', isOnline ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700']">
        {{ isOnline ? 'Online' : 'Offline Mode' }}
      </div>
    </div>

    <!-- Cache & Queue Status -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
      <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
        <div class="text-xs text-gray-500 uppercase">Cached records</div>
        <div class="font-bold text-gray-800">{{ cache.length }}</div>
        <div class="text-xs text-gray-400" v-if="cacheFetchedAt">Updated {{ formatDate(cacheFetchedAt) }}</div>
      </div>
      <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="text-xs text-blue-700 uppercase">Pending uploads</div>
        <div class="font-bold text-blue-900">{{ pending.length }}</div>
        <div class="text-xs text-blue-400" v-if="failed.length">{{ failed.length }} failed — see below</div>
      </div>
      <div class="flex flex-col gap-2">
        <button
          @click="refreshCache"
          :disabled="!isOnline || refreshing"
          class="px-3 py-2 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
          {{ refreshing ? 'Refreshing…' : 'Refresh cache' }}
        </button>
        <button
          v-if="pending.length"
          @click="syncPending"
          :disabled="!isOnline || syncing"
          class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
          {{ syncing ? 'Syncing…' : 'Sync now' }}
        </button>
      </div>
    </div>

    <!-- Search -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2" for="eq-search">Search cached equipment</label>
      <input
        id="eq-search"
        type="search"
        v-model="searchTerm"
        placeholder="Search by property no, serial, brand, or model…"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition" />

      <div class="mt-3 max-h-72 overflow-y-auto border border-gray-100 rounded-lg divide-y">
        <div v-if="!filteredResults.length" class="p-4 text-sm text-gray-500 text-center">
          {{ searchTerm ? 'No matches in offline cache.' : 'Type to search the cached records.' }}
        </div>
        <div v-for="item in filteredResults" :key="item.id" class="p-3 text-sm flex justify-between items-start gap-3">
          <div>
            <div class="font-medium text-gray-800">{{ item.property_no }}</div>
            <div class="text-xs text-gray-500">
              {{ [item.brand, item.model].filter(Boolean).join(' ') || item.equipment_type || '—' }}
              <span v-if="item.serial_number"> · SN {{ item.serial_number }}</span>
            </div>
          </div>
          <div class="text-right">
            <span :class="['inline-block px-2 py-0.5 text-xs rounded-full', statusClass(item.accountability_status)]">
              {{ item.accountability_status || 'unknown' }}
            </span>
            <div class="text-xs text-gray-400 mt-1">{{ item.equipment_location || '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Form -->
    <div class="border-t border-gray-100 pt-6">
      <div class="flex justify-between items-center mb-3">
        <h3 class="text-lg font-semibold text-gray-800">Add equipment</h3>
        <button
          v-if="!showForm"
          @click="showForm = true"
          class="px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
          + New entry
        </button>
        <button
          v-else
          @click="resetForm"
          class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
          Cancel
        </button>
      </div>

      <form v-if="showForm" @submit.prevent="addEntry" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Property No <span class="text-red-500">*</span></label>
          <input v-model="form.property_no" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Serial Number</label>
          <input v-model="form.serial_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Type</label>
          <input v-model="form.equipment_type" placeholder="Laptop, Desktop, Printer…" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Brand</label>
          <input v-model="form.brand" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Model</label>
          <input v-model="form.model" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Condition</label>
          <select v-model="form.condition" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500 bg-white">
            <option value="Good">Good</option>
            <option value="Fair">Fair</option>
            <option value="Poor">Poor</option>
            <option value="Unserviceable">Unserviceable</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
          <select v-model="form.category" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500 bg-white">
            <option value="High-Value">High-Value</option>
            <option value="Low-Value">Low-Value</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Location</label>
          <input v-model="form.equipment_location" placeholder="Room / Office" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500" />
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Remarks</label>
          <textarea v-model="form.remarks" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-green-500"></textarea>
        </div>
        <div class="sm:col-span-2 flex justify-end">
          <button type="submit" class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition">
            Queue entry
          </button>
        </div>
      </form>
    </div>

    <!-- Pending list -->
    <div v-if="pending.length" class="border-t border-gray-100 pt-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-3">Pending uploads</h3>
      <ul class="divide-y border border-gray-100 rounded-lg">
        <li v-for="item in pending" :key="item.client_id" class="p-3 text-sm flex justify-between items-center">
          <div>
            <div class="font-medium text-gray-800">{{ item.property_no }}</div>
            <div class="text-xs text-gray-500">
              {{ [item.brand, item.model].filter(Boolean).join(' ') || item.equipment_type || '—' }}
              · queued {{ formatDate(item.queued_at) }}
            </div>
          </div>
          <button @click="removePending(item.client_id)" class="text-xs text-red-600 hover:underline">Remove</button>
        </li>
      </ul>
    </div>

    <!-- Failed list -->
    <div v-if="failed.length" class="border-t border-gray-100 pt-6">
      <h3 class="text-lg font-semibold text-red-700 mb-3">Failed uploads</h3>
      <ul class="divide-y border border-red-100 rounded-lg bg-red-50">
        <li v-for="item in failed" :key="item.client_id" class="p-3 text-sm">
          <div class="font-medium text-gray-800">{{ item.property_no || '(no property no)' }}</div>
          <div class="text-xs text-red-700">{{ formatErrors(item.errors) }}</div>
          <button @click="removeFailed(item.client_id)" class="mt-1 text-xs text-red-600 hover:underline">Dismiss</button>
        </li>
      </ul>
    </div>

    <!-- Toast -->
    <div v-if="message.text" :class="['p-3 rounded-lg text-sm', message.type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200']">
      {{ message.text }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import localforage from 'localforage';
import axios from 'axios';

const CACHE_KEY = 'ieepis_offline_equipment_cache';
const CACHE_META_KEY = 'ieepis_offline_equipment_cache_meta';
const QUEUE_KEY = 'ieepis_offline_equipment_queue';
const FAILED_KEY = 'ieepis_offline_equipment_failed';

const isOnline = ref(navigator.onLine);
const cache = ref([]);
const cacheFetchedAt = ref(null);
const pending = ref([]);
const failed = ref([]);
const searchTerm = ref('');
const refreshing = ref(false);
const syncing = ref(false);
const showForm = ref(false);
const message = ref({ text: '', type: '' });

const blankForm = () => ({
  property_no: '',
  serial_number: '',
  equipment_type: '',
  brand: '',
  model: '',
  condition: 'Good',
  category: 'High-Value',
  equipment_location: '',
  remarks: '',
});

const form = ref(blankForm());

onMounted(async () => {
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);

  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
  const csrf = document.querySelector('meta[name="csrf-token"]');
  if (csrf) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');
  }

  await Promise.all([loadCache(), loadQueues()]);

  if (isOnline.value && cache.value.length === 0) {
    refreshCache();
  }
});

onUnmounted(() => {
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
});

const handleOnline = () => {
  isOnline.value = true;
  if (pending.value.length) syncPending();
};

const handleOffline = () => {
  isOnline.value = false;
};

const loadCache = async () => {
  cache.value = (await localforage.getItem(CACHE_KEY)) || [];
  const meta = await localforage.getItem(CACHE_META_KEY);
  cacheFetchedAt.value = meta?.fetched_at || null;
};

const loadQueues = async () => {
  pending.value = (await localforage.getItem(QUEUE_KEY)) || [];
  failed.value = (await localforage.getItem(FAILED_KEY)) || [];
};

const refreshCache = async () => {
  if (!isOnline.value || refreshing.value) return;
  refreshing.value = true;
  try {
    const { data } = await axios.get('/equipment/offline/cache');
    cache.value = data.equipment || [];
    cacheFetchedAt.value = data.fetched_at;
    await localforage.setItem(CACHE_KEY, cache.value);
    await localforage.setItem(CACHE_META_KEY, { fetched_at: data.fetched_at });
    showMessage(`Cached ${cache.value.length} equipment records.`, 'success');
  } catch (err) {
    showMessage('Could not refresh cache. ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    refreshing.value = false;
  }
};

const addEntry = async () => {
  if (!form.value.property_no.trim()) {
    showMessage('Property number is required.', 'error');
    return;
  }

  const entry = {
    ...form.value,
    client_id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    queued_at: new Date().toISOString(),
  };

  pending.value = [...pending.value, entry];
  await localforage.setItem(QUEUE_KEY, pending.value);
  resetForm();
  showMessage('Entry queued for sync.', 'success');

  if (isOnline.value) syncPending();
};

const syncPending = async () => {
  if (!isOnline.value || syncing.value || pending.value.length === 0) return;
  syncing.value = true;
  try {
    const { data } = await axios.post('/equipment/offline/sync', { entries: pending.value });
    const syncedIds = new Set((data.synced || []).map((s) => s.client_id));
    const failedIds = new Map((data.failed || []).map((f) => [f.client_id, f]));

    pending.value = pending.value.filter((p) => !syncedIds.has(p.client_id) && !failedIds.has(p.client_id));
    await localforage.setItem(QUEUE_KEY, pending.value);

    if (failedIds.size) {
      const newFailed = [...failed.value, ...failedIds.values()];
      failed.value = newFailed;
      await localforage.setItem(FAILED_KEY, newFailed);
    }

    showMessage(
      `Synced ${data.synced_count}/${data.synced_count + data.failed_count}. ` +
      (data.failed_count ? `${data.failed_count} failed — see list below.` : ''),
      data.failed_count ? 'error' : 'success'
    );

    if (data.synced_count > 0) refreshCache();
  } catch (err) {
    showMessage('Sync failed: ' + (err.response?.data?.message || err.message), 'error');
  } finally {
    syncing.value = false;
  }
};

const removePending = async (clientId) => {
  pending.value = pending.value.filter((p) => p.client_id !== clientId);
  await localforage.setItem(QUEUE_KEY, pending.value);
};

const removeFailed = async (clientId) => {
  failed.value = failed.value.filter((p) => p.client_id !== clientId);
  await localforage.setItem(FAILED_KEY, failed.value);
};

const resetForm = () => {
  form.value = blankForm();
  showForm.value = false;
};

const showMessage = (text, type = 'success', duration = 4000) => {
  message.value = { text, type };
  if (duration > 0) {
    setTimeout(() => { message.value = { text: '', type: '' }; }, duration);
  }
};

const filteredResults = computed(() => {
  const term = searchTerm.value.trim().toLowerCase();
  if (!term) return [];
  return cache.value
    .filter((item) => {
      const haystack = [item.property_no, item.old_property_no, item.serial_number, item.brand, item.model, item.equipment_type]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      return haystack.includes(term);
    })
    .slice(0, 50);
});

const statusClass = (status) => {
  if (status === 'assigned') return 'bg-green-100 text-green-700';
  if (status === 'unassigned') return 'bg-gray-100 text-gray-700';
  if (status === 'For Disposal') return 'bg-red-100 text-red-700';
  return 'bg-yellow-100 text-yellow-700';
};

const formatDate = (iso) => {
  if (!iso) return '';
  try {
    return new Date(iso).toLocaleString();
  } catch (e) {
    return iso;
  }
};

const formatErrors = (errors) => {
  if (!errors) return 'Unknown error.';
  return Object.entries(errors)
    .map(([field, msgs]) => `${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
    .join(' · ');
};
</script>
