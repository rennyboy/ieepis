// Livewire QR scanner entry — bundled (no CDN), with visible diagnostic errors.
// Loaded by resources/views/livewire/qr-scanner.blade.php via @vite(...).

import { Html5Qrcode } from 'html5-qrcode';

const READER_ID = 'qr-reader';
const STATUS_ID = 'qr-scanner-status';

let scannerInstance = null;

const setStatus = (text, type = 'info') => {
    const el = document.getElementById(STATUS_ID);
    if (!el) return;
    el.textContent = text || '';
    el.dataset.type = type;
    el.classList.toggle('hidden', !text);
};

const isInAppBrowser = () => {
    const ua = navigator.userAgent || '';
    return /(FBAN|FBAV|FB_IAB|Instagram|Line|MicroMessenger|Twitter|TikTok)/i.test(ua);
};

const preflightCheck = () => {
    if (!window.isSecureContext) {
        return 'Camera requires HTTPS. Open this page over a secure (https://) URL — http:// is blocked by mobile browsers.';
    }
    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
        if (isInAppBrowser()) {
            return 'Camera streaming is blocked inside this in-app browser (Facebook / Instagram / Line / etc.). Tap the menu and choose "Open in Chrome / Safari".';
        }
        return 'Camera streaming is not supported in this browser. Update Chrome or Safari, or open the site in a different browser.';
    }
    return null;
};

const friendlyError = (err) => {
    const name = err?.name || '';
    const msg = err?.message || (typeof err === 'string' ? err : '');

    if (name === 'NotAllowedError' || /denied|permission/i.test(msg)) {
        return 'Camera permission was denied. Open your browser settings and allow camera access for this site.';
    }
    if (name === 'NotFoundError' || /not.*found|no.*camera/i.test(msg)) {
        return 'No camera was found on this device.';
    }
    if (name === 'NotReadableError' || /in use|busy/i.test(msg)) {
        return 'Camera is already in use by another app. Close other apps using the camera and try again.';
    }
    if (name === 'OverconstrainedError') {
        return 'No camera matches the requested settings. Try the manual entry below.';
    }
    if (/secure|https/i.test(msg)) {
        return 'Camera requires a secure (https://) connection.';
    }
    return msg || 'Unknown camera error. Use the manual entry below.';
};

const startScanner = async () => {
    setStatus('', 'info');

    const reader = document.getElementById(READER_ID);
    if (!reader) {
        setStatus('Scanner UI is not available on this page.', 'error');
        return;
    }

    const failure = preflightCheck();
    if (failure) {
        setStatus(failure, 'error');
        return;
    }

    if (scannerInstance) {
        try { await scannerInstance.stop(); } catch (_) {}
        try { scannerInstance.clear(); } catch (_) {}
        scannerInstance = null;
    }

    setStatus('Opening camera…', 'info');
    scannerInstance = new Html5Qrcode(READER_ID);

    const onDecode = async (decodedText) => {
        try { await scannerInstance.stop(); } catch (_) {}
        try { scannerInstance.clear(); } catch (_) {}
        scannerInstance = null;
        setStatus('', 'info');
        window.Livewire?.dispatch('qr-scanned', { value: decodedText });
    };

    const config = { fps: 10, qrbox: 250 };

    try {
        const cameras = await Html5Qrcode.getCameras();
        if (cameras && cameras.length) {
            // Prefer a back / environment-facing camera
            const back = cameras.find((d) => /back|environment|rear/i.test(d.label || ''));
            const cameraId = (back || cameras[cameras.length - 1] || cameras[0]).id;
            await scannerInstance.start(cameraId, config, onDecode);
        } else {
            await scannerInstance.start({ facingMode: 'environment' }, config, onDecode);
        }
        setStatus('', 'info');
    } catch (err) {
        scannerInstance = null;
        setStatus(friendlyError(err), 'error');
    }
};

document.addEventListener('livewire:init', () => {
    window.Livewire.on('qr-start', startScanner);
});
