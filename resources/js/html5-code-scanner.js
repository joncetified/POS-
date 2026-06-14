import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

const supportedFormats = [
    Html5QrcodeSupportedFormats.QR_CODE,
    Html5QrcodeSupportedFormats.AZTEC,
    Html5QrcodeSupportedFormats.CODABAR,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.CODE_93,
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.DATA_MATRIX,
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.ITF,
    Html5QrcodeSupportedFormats.MAXICODE,
    Html5QrcodeSupportedFormats.PDF_417,
    Html5QrcodeSupportedFormats.RSS_14,
    Html5QrcodeSupportedFormats.RSS_EXPANDED,
    Html5QrcodeSupportedFormats.UPC_A,
    Html5QrcodeSupportedFormats.UPC_E,
    Html5QrcodeSupportedFormats.UPC_EAN_EXTENSION,
];

const scanners = new WeakMap();

function setStatus(root, message, type = 'info') {
    const status = root.querySelector('[data-code-scanner-status]');
    if (!status) return;

    status.textContent = message;
    status.dataset.status = type;
}

function chooseCamera(cameras) {
    return cameras.find((camera) => /back|rear|environment|belakang/i.test(camera.label || ''))
        || cameras[cameras.length - 1]
        || cameras[0];
}

function dispatchScan(root, code) {
    root.dispatchEvent(new CustomEvent('cafe:code-scanned', {
        bubbles: true,
        detail: { code },
    }));
}

async function stop(root) {
    const instance = scanners.get(root);
    if (!instance) return;

    try {
        if (instance.scanner.getState && instance.scanner.getState() === 2) {
            await instance.scanner.stop();
        } else {
            await instance.scanner.stop();
        }
    } catch (error) {
        // Scanner may already be stopped by the browser when permission changes.
    }

    try {
        instance.scanner.clear();
    } catch (error) {
        // Some mobile browsers throw when clearing an already detached video.
    }

    root.classList.remove('is-scanning');
    scanners.delete(root);
}

async function start(root) {
    const reader = root.querySelector('[data-code-scanner-reader]');
    const select = root.querySelector('[data-code-scanner-camera]');
    if (!reader) return;

    if (!window.isSecureContext && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
        setStatus(root, 'Kamera HP butuh HTTPS. Buka dari domain HTTPS atau localhost.', 'error');
        return;
    }

    await stop(root);

    if (!reader.id) {
        reader.id = `code-scanner-${Math.random().toString(36).slice(2)}`;
    }

    setStatus(root, 'Meminta izin kamera...', 'info');
    root.classList.add('is-scanning');

    const scanner = new Html5Qrcode(reader.id, {
        formatsToSupport: supportedFormats,
        verbose: false,
    });
    const config = {
        fps: 10,
        aspectRatio: 1.333334,
        rememberLastUsedCamera: true,
        formatsToSupport: supportedFormats,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
            const edge = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.72);
            return { width: Math.max(edge, 180), height: Math.max(edge, 180) };
        },
    };

    let lastCode = '';
    let lastAt = 0;
    const onSuccess = async (decodedText) => {
        const code = String(decodedText || '').trim();
        const now = Date.now();
        if (!code || (code === lastCode && now - lastAt < 1400)) return;

        lastCode = code;
        lastAt = now;
        setStatus(root, `Kode terbaca: ${code}`, 'success');
        dispatchScan(root, code);
        await stop(root);
    };

    try {
        const cameras = await Html5Qrcode.getCameras();
        if (select) {
            select.innerHTML = cameras.map((camera, index) => (
                `<option value="${camera.id}">${camera.label || `Kamera ${index + 1}`}</option>`
            )).join('');
            select.hidden = cameras.length < 2;
        }

        const selectedCamera = cameras.find((camera) => camera.id === select?.value) || chooseCamera(cameras);
        if (!selectedCamera) {
            throw new Error('Kamera tidak ditemukan.');
        }

        if (select && selectedCamera.id) {
            select.value = selectedCamera.id;
        }

        scanners.set(root, { scanner });
        await scanner.start(
            selectedCamera.id ? { deviceId: { exact: selectedCamera.id } } : { facingMode: 'environment' },
            config,
            onSuccess,
            () => {},
        );
        setStatus(root, 'Arahkan kamera ke QR Code atau barcode produk.', 'info');
    } catch (error) {
        try {
            scanners.set(root, { scanner });
            await scanner.start({ facingMode: { ideal: 'environment' } }, config, onSuccess, () => {});
            setStatus(root, 'Arahkan kamera ke QR Code atau barcode produk.', 'info');
        } catch (fallbackError) {
            await stop(root);
            setStatus(root, fallbackError?.message || error?.message || 'Kamera tidak bisa dibuka.', 'error');
        }
    }
}

function bind(root) {
    const open = root.querySelector('[data-code-scanner-open]');
    const close = root.querySelector('[data-code-scanner-close]');
    const select = root.querySelector('[data-code-scanner-camera]');

    open?.addEventListener('click', () => start(root));
    close?.addEventListener('click', () => stop(root));
    select?.addEventListener('change', async () => {
        const instance = scanners.get(root);
        if (!instance || !select.value) return;

        await stop(root);
        const reader = root.querySelector('[data-code-scanner-reader]');
        if (reader) {
            setStatus(root, 'Mengganti kamera...', 'info');
        }
        await start(root);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-html5-code-scanner]').forEach(bind);
});

window.CafeHtml5CodeScanner = { start, stop, bind, supportedFormats };
