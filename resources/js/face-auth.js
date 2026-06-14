let faceApiInitialized = false;
let faceApiLoadingPromise = null;

const appBaseUrl = (() => {
    const buildScript = document.querySelector('script[src*="/build/assets/"]');

    if (buildScript?.src) {
        return buildScript.src.split('/build/assets/')[0];
    }

    return window.location.origin;
})();

const faceApiScriptUrl = `${appBaseUrl}/vendor/face-api/dist/face-api.js`;
const faceApiModelUrl = `${appBaseUrl}/vendor/face-api/model/`;

async function getFaceApi() {
    if (window.faceapi) {
        return window.faceapi;
    }

    await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = faceApiScriptUrl;
        script.onload = resolve;
        script.onerror = () => reject(new Error('Gagal memuat Face Recognition lokal. Pastikan folder public/vendor/face-api ikut ter-upload.'));
        document.head.appendChild(script);
    });

    return window.faceapi;
}

async function initFaceApi() {
    if (faceApiInitialized) {
        return;
    }

    if (faceApiLoadingPromise) {
        await faceApiLoadingPromise;
        return;
    }

    faceApiLoadingPromise = withTimeout((async () => {
        const faceapi = await getFaceApi();

        try {
            if (faceapi.tf?.setBackend) {
                try {
                    await faceapi.tf.setBackend('webgl');
                } catch (webglError) {
                    console.warn('WebGL Face Recognition tidak tersedia, fallback ke CPU:', webglError);
                    await faceapi.tf.setBackend('cpu');
                }
                await faceapi.tf.ready();
                await waitForBrowser();
            }

            await faceapi.nets.tinyFaceDetector.loadFromUri(faceApiModelUrl);
            await waitForBrowser();
            await faceapi.nets.faceLandmark68Net.loadFromUri(faceApiModelUrl);
            await waitForBrowser();
            await faceapi.nets.faceRecognitionNet.loadFromUri(faceApiModelUrl);
            faceApiInitialized = true;
        } catch (error) {
            console.error('Gagal memuat model Face Recognition:', error);
            throw new Error('Gagal memuat model Face Recognition lokal. Pastikan folder public/vendor/face-api/model ikut ter-upload.');
        }
    })(), 25000, 'Model Face Recognition terlalu lama dimuat. Refresh halaman, lalu coba lagi. Kalau tetap terjadi, cek koneksi lokal/server asset.');

    try {
        await faceApiLoadingPromise;
    } finally {
        faceApiLoadingPromise = null;
    }
}

function withTimeout(promise, milliseconds, message) {
    let timeoutId;

    const timeout = new Promise((_, reject) => {
        timeoutId = window.setTimeout(() => reject(new Error(message)), milliseconds);
    });

    return Promise.race([promise, timeout]).finally(() => window.clearTimeout(timeoutId));
}

function waitForBrowser() {
    return new Promise((resolve) => window.setTimeout(resolve, 0));
}

function cameraErrorMessage(error) {
    if (error?.name === 'NotAllowedError' || error?.name === 'PermissionDeniedError') {
        return 'Izin kamera ditolak. Klik ikon kamera di browser lalu izinkan akses kamera.';
    }

    if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
        return 'Kamera tidak ditemukan. Sambungkan kamera atau pilih kamera lain.';
    }

    if (error?.name === 'NotReadableError' || error?.name === 'TrackStartError') {
        return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi kamera lain lalu coba lagi.';
    }

    return error?.message || 'Kamera gagal dinyalakan.';
}

async function requestCameraStream() {
    try {
        return await withTimeout(
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 256, max: 320 },
                    height: { ideal: 192, max: 240 },
                    frameRate: { ideal: 12, max: 15 },
                },
                audio: false,
            }),
            8000,
            'Kamera terlalu lama merespon. Cek izin kamera browser atau tutup aplikasi kamera lain.'
        );
    } catch (error) {
        throw new Error(cameraErrorMessage(error));
    }
}

async function startCamera(video, onStatus = null) {
    if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('Browser belum mendukung akses kamera. Gunakan Chrome atau Edge terbaru.');
    }

    stopCamera(video);
    onStatus?.('Menyiapkan Face Recognition...');
    await initFaceApi();

    onStatus?.('Meminta izin kamera...');

    const stream = await requestCameraStream();

    video.srcObject = stream;
    video.muted = true;
    video.playsInline = true;
    video.dataset.ready = '1';

    try {
        onStatus?.('Menyalakan preview kamera...');
        await video.play();

        await Promise.race([
            new Promise((resolve) => {
                if (video.readyState >= 2 && video.videoWidth) {
                    resolve();
                    return;
                }

                video.addEventListener('loadedmetadata', resolve, { once: true });
                video.addEventListener('canplay', resolve, { once: true });
            }),
            new Promise((_, reject) => {
                window.setTimeout(() => reject(new Error('Kamera tidak merespon. Tutup aplikasi kamera lain lalu coba lagi.')), 5000);
            }),
        ]);

        await waitForVideoFrame(video);
        onStatus?.('Kamera siap. Arahkan wajah ke frame.');
    } catch (error) {
        stopCamera(video);
        throw error;
    }

    return stream;
}

async function waitForVideoFrame(video) {
    const startedAt = Date.now();

    while (Date.now() - startedAt < 3500) {
        if (video.readyState >= 2 && video.videoWidth > 0 && video.videoHeight > 0) {
            return;
        }

        await new Promise((resolve) => window.setTimeout(resolve, 120));
    }

    throw new Error('Preview kamera belum siap. Coba refresh halaman atau pilih kamera lain.');
}

function stopCamera(video) {
    const stream = video?.srcObject;

    if (stream instanceof MediaStream) {
        stream.getTracks().forEach((track) => track.stop());
    }

    if (video) {
        video.srcObject = null;
    }
}

async function readFaceDescriptor(video) {
    if (!video || video.readyState < 2 || !video.videoWidth || !video.videoHeight) {
        throw new Error('Kamera belum siap. Coba ulangi.');
    }

    const faceapi = await getFaceApi();
    if (!faceApiInitialized) {
        await initFaceApi();
    }
    const startedAt = Date.now();

    while (Date.now() - startedAt < 6000) {
        await waitForBrowser();

        const detections = await faceapi.detectAllFaces(
            video,
            new faceapi.TinyFaceDetectorOptions({ inputSize: 128, scoreThreshold: 0.42 })
        ).withFaceLandmarks().withFaceDescriptors();

        if (detections.length === 1) {
            const detection = detections[0];
            const box = detection.detection.box;
            const minSize = Math.min(video.videoWidth, video.videoHeight) * 0.16;

            if (box.width >= minSize && box.height >= minSize) {
                return detection;
            }

            throw new Error('Wajah terlalu jauh dari kamera. Dekatkan wajah ke frame.');
        }

        if (detections.length > 1) {
            throw new Error('Terdeteksi lebih dari satu wajah. Pastikan hanya ada satu wajah di kamera.');
        }

        await new Promise((resolve) => window.setTimeout(resolve, 180));
    }

    throw new Error('Data wajah belum bisa dibaca. Pastikan wajah masuk frame dan pencahayaan cukup.');
}

function captureDescriptor(detection) {
    if (!detection?.descriptor) {
        throw new Error('Gagal membaca data wajah.');
    }

    return Array.from(detection.descriptor).map((value) => Number(value.toFixed(6)));
}

async function captureStableDescriptor(video, samples = 1) {
    return withTimeout((async () => {
        const descriptors = [];

        for (let index = 0; index < samples; index += 1) {
            const detection = await readFaceDescriptor(video);
            descriptors.push(captureDescriptor(detection));
            await new Promise((resolve) => window.setTimeout(resolve, 80));
        }

        return descriptors[0].map((_, descriptorIndex) => {
            const total = descriptors.reduce((sum, descriptor) => sum + descriptor[descriptorIndex], 0);
            return Number((total / descriptors.length).toFixed(6));
        });
    })(), 15000, 'Pembacaan wajah terlalu lama. Dekatkan wajah, pastikan cahaya cukup, lalu coba lagi.');
}

function descriptorPayload(descriptor) {
    return JSON.stringify(descriptor);
}

window.CafeFaceAuth = {
    captureStableDescriptor,
    descriptorPayload,
    readFaceDescriptor,
    startCamera,
    stopCamera,
};
