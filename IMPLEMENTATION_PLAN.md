# IEEPIS Implementation Plan — Laravel 12 + Nuxt PWA

**Last Updated:** 2026-04-29  
**Status:** Phase 1 Complete | Phase 2 In Progress

---

## Completed ✅

### Phase 1: Laravel 11 → 12 Upgrade

| Task | Date | Status |
|------|------|--------|
| composer.json: Laravel 12, Sanctum 4, DomPDF 3 | 2026-04-29 | ✅ |
| Run `composer update` | 2026-04-29 | ✅ |
| Verify Laravel 12 boots | 2026-04-29 | ✅ |
| Publish Sanctum config | 2026-04-29 | ✅ |
| Fix PostgreSQL GET_LOCK → pg_advisory_xact_lock | 2026-04-29 | ✅ |
| Verify Filament loads at /admin | 2026-04-29 | ✅ |

### Versions After Upgrade

| Package | Before | After |
|---------|--------|-------|
| laravel/framework | 11.48.0 | 12.58.0 |
| laravel/sanctum | (none) | 4.3.1 |
| barryvdh/laravel-dompdf | 2.2.0 | 3.1.2 |
| filament/filament | 3.3.49 | 3.3.49 (unchanged) |
| PHP | 8.4 | 8.4 |

---

## Plan: Phase 2 — API Foundation

**Timeline:** Week 2-3  
**Goal:** API-first architecture for Nuxt PWA

### Tasks

#### 2.1 Sanctum Configuration

```bash
# Execute:
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Verify config exists at config/sanctum.php
```

#### 2.2 API Routes (routes/api.php)

```php
Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('equipment')->group(base_path('routes/api/equipment.php'));
Route::prefix('assignments')->group(base_path('routes/api/assignments.php'));
Route::prefix('tickets')->group(base_route('routes/api/tickets.php'));
Route::prefix('documents')->group(base_path('routes/api/documents.php'));
```

#### 2.3 API Controllers

| Controller | Path | Endpoints |
|------------|------|----------|
| AuthController | `app/Http/Controllers/Api/AuthController.php` | POST /login, /logout, /me |
| EquipmentController | `app/Http/Controllers/Api/EquipmentController.php` | GET /, /{id}, /qr/{code} |
| AssignmentController | `app/Http/Controllers/Api/AssignmentController.php` | POST /, /transfer, /return |
| TicketController | `app/Http/Controllers/Api/TicketController.php` | GET /, POST / |
| DocumentController | `app/Http/Controllers/Api/DocumentController.php` | POST /upload, GET /{id} |

#### 2.4 API Resources

| Resource | Path |
|----------|------|
| EquipmentResource | `app/Http/Resources/EquipmentResource.php` |
| TicketResource | `app/Http/Resources/TicketResource.php` |
| AssignmentResource | `app/Http/Resources/AssignmentResource.php` |

### Files to Create

```
app/Http/Controllers/Api/
├── AuthController.php
├── EquipmentController.php
├── AssignmentController.php
├── TicketController.php
└── DocumentController.php

app/Http/Resources/
├── EquipmentResource.php
├── TicketResource.php
└── AssignmentResource.php

routes/api/
├── auth.php
├── equipment.php
├── assignments.php
├── tickets.php
└── documents.php
```

### Key Design Rules

1. **All write operations MUST go through AssignmentService** — same as Filament
2. **API controllers use Sanctum token auth** — SPA auth guard
3. **SchoolScope applies** — global scope on all queries
4. **Return API Resources** — consistent JSON response format

---

## Plan: Phase 3 — Nuxt PWA Scaffold

**Timeline:** Week 3-4  
**Goal:** Mobile PWA with offline capability

### Tasks

#### 3.1 Initialize Nuxt Project

```bash
npx nuxi@latest init ieepis-pwa --packageManager npm --no-install
cd ieepis-pwa
npm install
npm install @vite-pwa/nuxt dexie html5-qrcode
```

#### 3.2 Nuxt Configuration

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  modules: ['@vite-pwa/nuxt'],
  pwa: {
    registerTypes: 'autoUpdate',
    manifest: {
      name: 'IEEPIS',
      short_name: 'IEEPIS',
      theme_color: '#2563eb',
      background_color: '#ffffff',
      display: 'standalone',
      orientation: 'portrait',
    },
    workbox: {
      navigateFallback: '/',
      globPatterns: ['**/*.{js,css,html,png,svg,ico}'],
    },
  },
})
```

#### 3.3 Pages Structure

```
pages/
├── index.vue              # Redirect to /login or /dashboard
├── login.vue             # Sanctum login
├── dashboard.vue        # Overview
├── equipment/
│   ├── index.vue        # Equipment list
│   └── [id].vue         # Equipment detail
├── audit/
│   ├── index.vue        # Audit list
│   └── create.vue       # New audit
└── tickets/
    ├── index.vue       # Ticket list
    └── create.vue     # Create ticket
```

#### 3.4 Composables

```
composables/
├── useAuth.ts           # Laravel Sanctum integration
├── useOffline.ts        # IndexedDB + sync queue
├── useEquipment.ts      # Equipment API calls
└── usePwaInstall.ts    # PWA install prompt
```

### Offline Strategy

1. **IndexedDB** via Dexie.js for local data
2. **Sync Queue** for pending actions
3. **Conflict Resolution** — server wins, notify user on conflict
4. **Background Sync** — when connection restores

---

## Plan: Phase 4 — Offline Capability

**Timeline:** Week 4-5  
**Goal:** Field audits work offline

### Tasks

#### 4.1 IndexedDB Setup

```typescript
// lib/db.ts
import Dexie from 'dexie'

export const db = new Dexie('IEEPISOffline')

db.version(1).stores({
  equipment: '++id, property_no, school_id, accountability_status',
  assignments: '++id, equipment_id, employee_id',
  tickets: '++id, ticket_number, status',
  syncQueue: '++id, endpoint, payload, status',
})
```

#### 4.2 Sync Queue

```typescript
// composables/useSync.ts
export function useSync() {
  async function queueAction(endpoint: string, payload: object) {
    await db.syncQueue.add({
      endpoint,
      payload,
      timestamp: Date.now(),
      status: 'pending',
    })
  }

  async function syncPending() {
    const pending = await db.syncQueue.where('status').equals('pending').toArray()
    for (const item of pending) {
      try {
        await $fetch(`/api/${item.endpoint}`, {
          method: 'POST',
          body: item.payload,
          headers: { Authorization: `Bearer ${token}` },
        })
        await db.syncQueue.update(item.id!, { status: 'synced' })
      } catch (e) {
        await db.syncQueue.update(item.id!, { status: 'failed' })
      }
    }
  }

  return { queueAction, syncPending }
}
```

---

## Plan: Phase 5 — Field Audit Features

**Timeline:** Week 5-6  
**Goal:** Production-ready mobile audits

### Tasks

#### 5.1 QR Scanner

```bash
npm install html5-qrcode
```

```vue
<script setup>
import { Html5QrcodeScanner } from 'html5-qrcode'

const scanner = new Html5QrcodeScanner('reader', { fps: 10, qrbox: 250 })
scanner.render(onScanSuccess)

function onScanSuccess(decodedText) {
  const [prefix, propertyNo, serial] = decodedText.split('|')
  navigateTo(`/equipment?q=${propertyNo}`)
}
</script>

<template>
  <div id="reader"></div>
</template>
```

#### 5.2 Photo Compression

```bash
npm install compressorjs
```

```typescript
import Compressor from 'compressorjs'

function compressImage(file: File): Promise<Blob> {
  return new Promise((resolve) => {
    new Compressor(file, {
      quality: 0.7,
      success(result) {
        resolve(result as Blob)
      },
    })
  })
}
```

#### 5.3 Background Sync (Progressive Enhancement)

```typescript
if ('sync' in ServiceWorkerRegistration.prototype) {
  await registration.sync.register('sync-audit')
}
```

---

## Deferred: Filament Upgrade

**Timeline:** After PWA stable  
**Goal:** Upgrade to Filament 4/5

### Pre-requisites

1. All PWA tests passing
2. API layer stable
3. No blocking issues

### Upgrade Path

1. Filament 3 → 4: `composer require filament/filament:^4.0`
2. Test all Filament pages
3. Fix breaking changes
4. (Optional) Filament 4 → 5

---

## Risk Register

| Risk | Phase | Impact | Mitigation |
|------|-------|--------|------------|
| API design change | 2 | Medium | Review before Phase 3 |
| Offline conflict resolution | 4 | High | Careful testing, server-wins |
| Filament 3→4 upgrade | Deferred | High | Defer until PWA stable |
| Performance under load | All | Medium | Monitor, optimize queries |

---

## Implementation Checklist

- [ ] Phase 2: API Foundation
  - [ ] 2.1 Sanctum config verified
  - [ ] 2.2 API routes
  - [ ] 2.3 API controllers
  - [ ] 2.4 API resources
- [ ] Phase 3: Nuxt PWA Scaffold
  - [ ] 3.1 Initialize Nuxt project
  - [ ] 3.2 PWA config
  - [ ] 3.3 Pages
  - [ ] 3.4 Composables
- [ ] Phase 4: Offline Capability
  - [ ] 4.1 IndexedDB
  - [ ] 4.2 Sync queue
- [ ] Phase 5: Field Audit Features
  - [ ] 5.1 QR scanner
  - [ ] 5.2 Photo compression
  - [ ] 5.3 Background sync
- [ ] Deferred: Filament Upgrade
  - [ ] Filament 3 → 4
  - [ ] Filament 4 → 5