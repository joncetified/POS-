const DESCRIPTOR_SIDE = 16;
const DESCRIPTOR_SIZE = DESCRIPTOR_SIDE * DESCRIPTOR_SIDE;

function stopStream(stream) {
    if (!stream) return;

    stream.getTracks().forEach((track) => track.stop());
}

async function openCamera(video) {
    if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('Browser ini belum mendukung akses kamera.');
    }

    const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 540 } },
        audio: false,
    });

    video.srcObject = stream;
    await video.play();

    return stream;
}

function centeredFaceBox(video) {
    const width = video.videoWidth;
    const height = video.videoHeight;
    const size = Math.min(width, height) * 0.72;

    return {
        x: (width - size) / 2,
        y: (height - size) / 2,
        width: size,
        height: size,
    };
}

function clampFaceBox(box, video) {
    const margin = Math.max(box.width, box.height) * 0.22;
    const size = Math.min(
        Math.max(box.width, box.height) + (margin * 2),
        video.videoWidth,
        video.videoHeight
    );
    const centerX = box.x + (box.width / 2);
    const centerY = box.y + (box.height / 2);

    return {
        x: Math.max(0, Math.min(video.videoWidth - size, centerX - (size / 2))),
        y: Math.max(0, Math.min(video.videoHeight - size, centerY - (size / 2))),
        width: size,
        height: size,
    };
}

async function detectedFaceBox(video) {
    if (!('FaceDetector' in window)) {
        return centeredFaceBox(video);
    }

    const detector = new FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
    const faces = await detector.detect(video);

    if (faces.length < 1) {
        throw new Error('Wajah belum terlihat jelas di kamera.');
    }

    const box = faces[0].boundingBox;

    return clampFaceBox({
        x: box.x,
        y: box.y,
        width: box.width,
        height: box.height,
    }, video);
}

async function captureDescriptor(video) {
    if (video.videoWidth === 0 || video.videoHeight === 0) {
        throw new Error('Kamera belum siap.');
    }

    const box = await detectedFaceBox(video);
    const canvas = document.createElement('canvas');
    canvas.width = DESCRIPTOR_SIDE;
    canvas.height = DESCRIPTOR_SIDE;
    const context = canvas.getContext('2d', { willReadFrequently: true });

    context.drawImage(
        video,
        box.x,
        box.y,
        box.width,
        box.height,
        0,
        0,
        DESCRIPTOR_SIDE,
        DESCRIPTOR_SIDE
    );

    const pixels = context.getImageData(0, 0, DESCRIPTOR_SIDE, DESCRIPTOR_SIDE).data;
    const values = [];

    for (let index = 0; index < pixels.length; index += 4) {
        values.push(((pixels[index] * 0.299) + (pixels[index + 1] * 0.587) + (pixels[index + 2] * 0.114)) / 255);
    }

    const mean = values.reduce((total, value) => total + value, 0) / DESCRIPTOR_SIZE;
    const variance = values.reduce((total, value) => total + ((value - mean) ** 2), 0) / DESCRIPTOR_SIZE;
    const deviation = Math.max(Math.sqrt(variance), 0.001);

    return values.map((value) => Number(((value - mean) / deviation).toFixed(6)));
}

function fillDescriptorInputs(container, descriptor) {
    container.innerHTML = '';

    descriptor.forEach((value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'face_descriptor[]';
        input.value = String(value);
        container.appendChild(input);
    });
}

window.CafeFaceRecognition = {
    captureDescriptor,
    fillDescriptorInputs,
    openCamera,
    stopStream,
};
