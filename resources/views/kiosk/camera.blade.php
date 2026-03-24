<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Kiosk Clock With Camera</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --kiosk-bg: linear-gradient(180deg, #eef4ff 0%, #f8fafc 38%, #eef2f7 100%);
            --kiosk-card: rgba(255, 255, 255, 0.86);
            --kiosk-border: rgba(148, 163, 184, 0.22);
            --kiosk-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            --kiosk-shadow-soft: 0 10px 24px rgba(15, 23, 42, 0.06);
            --kiosk-accent: #2563eb;
            --kiosk-accent-2: #60a5fa;
            --kiosk-ink: #0f172a;
            --kiosk-muted: #64748b;
        }

        body {
            background: var(--kiosk-bg);
        }

        main > section {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.92));
            border: 1px solid var(--kiosk-border);
            box-shadow: var(--kiosk-shadow);
        }

        .kiosk-surface {
            background: var(--kiosk-card);
            border: 1px solid var(--kiosk-border);
            box-shadow: var(--kiosk-shadow-soft);
            backdrop-filter: blur(10px);
        }

        .kiosk-soft-surface {
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(241, 245, 249, 0.92));
            border: 1px solid rgba(203, 213, 225, 0.7);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        #staff_id,
        #pin {
            border-color: rgba(148, 163, 184, 0.35);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(241, 245, 249, 0.95));
            color: var(--kiosk-ink);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7), 0 8px 20px rgba(148, 163, 184, 0.08);
        }

        #staff_id:focus,
        #pin:focus {
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .key,
        #clear,
        #back,
        #identifyBtn,
        #confirmBtn,
        #startCameraBtn,
        #captureBtn,
        #uploadPhotoBtn {
            transition: transform 140ms ease, box-shadow 180ms ease, filter 180ms ease, background-color 180ms ease;
        }

        .key {
            background: linear-gradient(180deg, #24324b 0%, #18253b 100%) !important;
            border-color: #0f172a !important;
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .key:hover,
        .key:focus-visible,
        #identifyBtn:hover,
        #identifyBtn:focus-visible,
        #confirmBtn:hover,
        #confirmBtn:focus-visible,
        #startCameraBtn:hover,
        #captureBtn:hover,
        #uploadPhotoBtn:hover,
        #clear:hover,
        #back:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
        }

        .key:active,
        #identifyBtn:active,
        #confirmBtn:active,
        #startCameraBtn:active,
        #captureBtn:active,
        #uploadPhotoBtn:active,
        #clear:active,
        #back:active {
            transform: translateY(1px) scale(0.995);
        }

        #clear,
        #back {
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: 0 8px 16px rgba(148, 163, 184, 0.12);
        }

        #identifyBtn {
            background: linear-gradient(135deg, var(--kiosk-accent) 0%, var(--kiosk-accent-2) 100%) !important;
            border-color: #2563eb !important;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
        }

        #confirmBtn {
            box-shadow: 0 10px 22px rgba(148, 163, 184, 0.14);
        }

        #clockTime,
        #clockDate {
            letter-spacing: -0.02em;
        }

        #blinkToast {
            backdrop-filter: blur(10px);
        }

        #status.text-emerald-700,
        #status.text-red-600,
        #status.text-amber-700 {
            font-weight: 600;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-6xl px-4 py-8">
        <section class="rounded-2xl bg-white p-6 shadow">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                <div class="relative min-w-0 space-y-4 lg:w-[430px] lg:flex-none">
                    <div class="kiosk-surface rounded-2xl p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">KIOSK BUILD 2026-03-13-camera</p>
                        <div class="mt-3 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm text-slate-500">Location</p>
                                <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $location->name }}</h1>
                            </div>
                            <div class="min-w-[220px] rounded-2xl px-4 py-3 text-right shadow" style="background:linear-gradient(135deg, #0f172a 0%, #132445 100%);color:#ffffff;box-shadow:0 16px 28px rgba(15, 23, 42, 0.22);">
                                <p class="text-xs uppercase tracking-[0.18em]" style="color:#94a3b8;">Local Time</p>
                                <p id="clockTime" class="mt-1 text-2xl font-semibold leading-tight" style="color:#ffffff;">--:--:--</p>
                                <p id="clockDate" class="mt-1 text-sm" style="color:#cbd5e1;">--</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -left-[9999px] top-auto h-px w-px overflow-hidden opacity-0" aria-hidden="true">
                        <input type="text" name="username" autocomplete="username" tabindex="-1">
                        <input type="password" name="password" autocomplete="current-password" tabindex="-1">
                    </div>

                    <section class="kiosk-surface rounded-2xl p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Clock In/Out</p>
                                <p class="text-xs text-slate-500">Enter Staff ID and PIN, then identify.</p>
                            </div>
                            <div class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 shadow-sm">Camera-enabled kiosk</div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <input id="staff_id" name="kiosk_staff_id" type="text" inputmode="numeric" maxlength="12" pattern="[0-9]*" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" data-lpignore="true" data-form-type="other" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-center text-2xl shadow-inner outline-none transition focus:border-blue-500 focus:bg-white" placeholder="Staff ID">

                            <input id="pin" name="kiosk_pin" type="password" inputmode="numeric" maxlength="6" pattern="[0-9]*" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" data-lpignore="true" data-form-type="other" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-center text-2xl tracking-[0.5em] shadow-inner outline-none transition focus:border-blue-500 focus:bg-white" placeholder="PIN">
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="1">1</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="2">2</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="3">3</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="4">4</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="5">5</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="6">6</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="7">7</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="8">8</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="9">9</button>
                            <button type="button" id="clear" class="rounded-xl bg-slate-200 py-4 text-xl font-semibold text-slate-700 transition hover:bg-slate-300">Clear</button>
                            <button type="button" class="key rounded-xl bg-slate-800 py-4 text-2xl font-bold text-white shadow-sm transition hover:bg-slate-700" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="0">0</button>
                            <button type="button" id="back" class="rounded-xl bg-slate-200 py-4 text-xl font-semibold text-slate-700 transition hover:bg-slate-300">Back</button>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <button id="identifyBtn" class="rounded-xl bg-blue-600 py-3 text-lg font-semibold text-white shadow-sm transition hover:bg-blue-500" style="background:#2563eb;color:#fff;border:1px solid #1d4ed8;">Identify</button>
                            <button id="confirmBtn" class="rounded-xl border border-slate-300 bg-slate-200 py-3 text-lg font-semibold text-slate-500 opacity-60 shadow-sm" style="border-color:#cbd5e1;background:#e2e8f0;color:#64748b;">CLOCK</button>
                        </div>

                        <div class="kiosk-soft-surface mt-4 min-h-[96px] rounded-2xl px-4 py-3">
                            <p id="status" class="min-h-[48px] text-center text-base font-medium"></p>
                            <p id="hint" class="mt-1 min-h-[22px] text-center text-sm text-slate-500"></p>
                        </div>
                    </section>
                </div>

                <section class="kiosk-surface min-w-0 flex-1 rounded-2xl p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-h-[52px]">
                            <p class="text-sm font-semibold text-slate-900">Camera Verification</p>
                            <p id="cameraHint" class="mt-1 min-h-[40px] text-sm text-slate-500">After Identify succeeds, the camera starts automatically. Clock In/Out will capture the photo for the punch.</p>
                        </div>
                        <div class="lg:w-[220px] lg:flex-none lg:min-h-[126px]">
                            <div id="manualControls" class="invisible flex flex-col gap-2 opacity-0 transition-opacity">
                                <button id="startCameraBtn" type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white" style="background:#2563eb;color:#fff;border:1px solid #1d4ed8;">Use Webcam</button>
                                <button id="captureBtn" type="button" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;">Retake Photo</button>
                                <button id="uploadPhotoBtn" type="button" class="rounded-lg bg-slate-300 px-4 py-2 text-sm font-semibold text-slate-900">Upload Photo</button>
                            </div>
                            <p id="manualControlsHint" class="mt-2 min-h-[40px] invisible text-xs font-semibold text-amber-700 opacity-0 transition-opacity lg:text-right">Manual camera controls are available because automatic capture needs help.</p>
                        </div>
                        <input id="photoFallbackInput" type="file" accept="image/*" capture="user" class="hidden">
                    </div>

                    <div class="mt-5 grid gap-4 xl:grid-cols-2">
                        <div class="min-w-0 rounded-2xl border border-slate-200 bg-slate-950/95 p-3 shadow-inner">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold text-white">Live Camera</p>
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Auto verify</p>
                            </div>
                            <div class="h-[260px] overflow-hidden rounded-xl bg-slate-900 lg:h-[300px]">
                                <video id="cameraPreview" class="block h-full w-full object-cover" autoplay playsinline muted></video>
                            </div>
                        </div>

                        <div class="min-w-0 rounded-2xl border border-dashed border-slate-300 bg-white p-3 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900">Captured Photo</p>
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Punch evidence</p>
                            </div>
                            <div class="h-[260px] overflow-hidden rounded-xl border border-slate-200 bg-slate-50 lg:h-[300px]">
                                <img id="snapshotPreview" alt="Captured snapshot" class="hidden h-full w-full object-cover">
                                <div id="snapshotPlaceholder" class="flex h-full w-full items-center justify-center px-4 text-center text-sm text-slate-400">No photo captured yet.</div>
                            </div>
                        </div>
                    </div>

                    <div class="kiosk-soft-surface mt-4 rounded-2xl bg-white px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900">Verification Result</p>
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Live face status</p>
                        </div>
                        <p id="verificationSummary" class="mt-2 min-h-[48px] text-sm text-slate-600">No photo captured yet. Live-face verification has not started.</p>
                    </div>

                    <div class="kiosk-soft-surface mt-4 rounded-2xl bg-white px-4 py-3">
                        <div class="grid gap-2 text-sm text-slate-600 md:grid-cols-3">
                            <div class="rounded-xl bg-white/70 px-3 py-2 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 1</p>
                                <p class="mt-1">Identify the staff member.</p>
                            </div>
                            <div class="rounded-xl bg-white/70 px-3 py-2 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 2</p>
                                <p class="mt-1">Face the camera for automatic verification.</p>
                            </div>
                            <div class="rounded-xl bg-white/70 px-3 py-2 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Step 3</p>
                                <p class="mt-1">Clock in or out after the photo is ready.</p>
                            </div>
                        </div>
                    </div>

                </section>
            </div>
        </section>
    </main>

    <div id="blinkToast" class="pointer-events-none fixed bottom-6 right-6 z-50 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white opacity-0 shadow-xl shadow-slate-900/30 transition duration-200">
        Blink test ready
    </div>

<script>
const staffIdInput = document.getElementById('staff_id');
const pinInput = document.getElementById('pin');
const statusEl = document.getElementById('status');
const hintEl = document.getElementById('hint');
const identifyBtn = document.getElementById('identifyBtn');
const confirmBtn = document.getElementById('confirmBtn');
const startCameraBtn = document.getElementById('startCameraBtn');
const captureBtn = document.getElementById('captureBtn');
const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
const manualControls = document.getElementById('manualControls');
const manualControlsHint = document.getElementById('manualControlsHint');
const photoFallbackInput = document.getElementById('photoFallbackInput');
const cameraHintEl = document.getElementById('cameraHint');
const cameraPreview = document.getElementById('cameraPreview');
const snapshotPreview = document.getElementById('snapshotPreview');
const snapshotPlaceholder = document.getElementById('snapshotPlaceholder');
const verificationSummaryEl = document.getElementById('verificationSummary');
const blinkToast = document.getElementById('blinkToast');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const clockInRoute = @json($clockInRoute);
const clockOutRoute = @json($clockOutRoute);
const fallbackClockInRoute = @json($fallbackClockInRoute);
const fallbackClockOutRoute = @json($fallbackClockOutRoute);
const faceLandmarkerBundleUrl = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/vision_bundle.mjs';
const faceLandmarkerWasmRoot = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm';
const faceLandmarkerModelUrl = 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task';
const leftEyeIndices = [33, 160, 158, 133, 153, 144];
const rightEyeIndices = [362, 385, 387, 263, 373, 380];
let nextAction = null;
let activeField = 'staff_id';
let cameraStream = null;
let snapshotDataUrl = null;
let cameraReadyPromise = null;
let faceLandmarker = null;
let faceLandmarkerPromise = null;
let faceValidationFrame = null;
let faceValidationActive = false;
let lastValidatedVideoTime = -1;
let faceVerified = false;
let blinkClosedSeen = false;
let usingManualPhoto = false;
let autoCaptureInProgress = false;
let blinkClosedFrameCount = 0;
let blinkOpenFrameCount = 0;
let stableFaceFrameCount = 0;
let allowConfirmWithoutPhoto = false;
let blinkToastTimer = null;
let lastCameraErrorMessage = '';
let lastFaceValidationErrorMessage = '';
let cameraWarmupToken = 0;
let resetUiTimer = null;

function armInputsAgainstAutofill() {
    staffIdInput.readOnly = true;
    pinInput.readOnly = true;
}

function unlockInput(input) {
    input.readOnly = false;
}

function extractErrorMessage(data, fallback) {
    if (data?.errors) {
        const firstErrorGroup = Object.values(data.errors)[0];
        if (Array.isArray(firstErrorGroup) && firstErrorGroup.length > 0) {
            return firstErrorGroup[0];
        }
    }
    return data?.message || fallback;
}

function setStatus(text, cls = 'text-slate-700') {
    statusEl.className = `mt-4 min-h-6 text-center text-base ${cls}`;
    statusEl.textContent = text;
}

function setCaptureButtonState(isBusy, label = 'Retake Photo') {
    captureBtn.disabled = isBusy;
    captureBtn.textContent = label;
}

function stampTime() {
    return new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function setVerificationSummary(message, tone = 'neutral') {
    if (!verificationSummaryEl) {
        return;
    }

    const toneClasses = {
        neutral: 'mt-2 min-h-[48px] text-sm text-slate-600',
        info: 'mt-2 min-h-[48px] text-sm text-blue-700',
        success: 'mt-2 min-h-[48px] text-sm font-medium text-emerald-700',
        warning: 'mt-2 min-h-[48px] text-sm font-medium text-amber-700',
    };

    verificationSummaryEl.className = toneClasses[tone] || toneClasses.neutral;
    verificationSummaryEl.textContent = message;
}

function hideBlinkToast() {
    if (!blinkToast) {
        return;
    }

    blinkToast.classList.add('opacity-0');
}

function showBlinkToast(message, tone = 'info') {
    if (!blinkToast) {
        return;
    }

    const toneClasses = {
        info: 'bg-slate-900 text-white',
        success: 'bg-emerald-600 text-white',
        warning: 'bg-amber-500 text-slate-950',
    };

    blinkToast.className = `pointer-events-none fixed bottom-6 right-6 z-50 rounded-2xl px-5 py-3 text-sm font-semibold opacity-100 shadow-xl transition duration-200 ${toneClasses[tone] || toneClasses.info}`;
    blinkToast.textContent = message;

    if (blinkToastTimer) {
        window.clearTimeout(blinkToastTimer);
    }

    blinkToastTimer = window.setTimeout(() => {
        hideBlinkToast();
    }, 1400);
}

function showManualControls(message = 'Manual camera controls are available because automatic capture needs help.') {
    manualControls.classList.remove('invisible', 'opacity-0');
    manualControlsHint.textContent = message;
    manualControlsHint.classList.remove('invisible', 'opacity-0');
}

function hideManualControls() {
    manualControls.classList.add('invisible', 'opacity-0');
    manualControlsHint.classList.add('invisible', 'opacity-0');
}

function enableConfirmWithoutPhoto(message = 'Camera is unavailable. You can continue without a photo for this punch.', options = {}) {
    allowConfirmWithoutPhoto = true;
    showManualControls(options.manualMessage || 'Manual camera controls are available, or you can continue without a photo.');
    cameraHintEl.textContent = message;
    setVerificationSummary(
        options.summary || 'No live camera photo was captured. This punch can continue without live-face verification.',
        options.summaryTone || 'warning'
    );
    setConfirmAvailability();
}

function disableConfirmWithoutPhoto() {
    allowConfirmWithoutPhoto = false;
    setConfirmAvailability();
}

function setConfirmAvailability() {
    const canConfirm = Boolean(nextAction) && (faceVerified || Boolean(snapshotDataUrl) || allowConfirmWithoutPhoto);

    confirmBtn.disabled = !canConfirm;
    confirmBtn.classList.toggle('opacity-60', !canConfirm);
    confirmBtn.classList.toggle('cursor-not-allowed', !canConfirm);
    confirmBtn.classList.toggle('ring-2', !canConfirm && Boolean(nextAction));
    confirmBtn.classList.toggle('ring-amber-300', !canConfirm && Boolean(nextAction));
}

function resetSnapshot() {
    snapshotDataUrl = null;
    usingManualPhoto = false;
    autoCaptureInProgress = false;
    allowConfirmWithoutPhoto = false;
    snapshotPreview.src = '';
    snapshotPreview.classList.add('hidden');
    snapshotPlaceholder.classList.remove('hidden');
    faceVerified = false;
    blinkClosedSeen = false;
    blinkClosedFrameCount = 0;
    blinkOpenFrameCount = 0;
    stableFaceFrameCount = 0;
    setVerificationSummary('No photo captured yet. Live-face verification has not started.', 'neutral');
    setConfirmAvailability();
}

function cameraStreamIsLive(stream = cameraStream) {
    return Boolean(stream?.getVideoTracks?.().some((track) => track.readyState === 'live'));
}

function cameraPreviewHasRenderableFrame() {
    return cameraPreview.srcObject === cameraStream
        && cameraStreamIsLive()
        && cameraPreview.readyState >= 2
        && !cameraPreview.paused
        && !cameraPreview.ended
        && Boolean(cameraPreview.videoWidth && cameraPreview.videoHeight);
}

function cameraPreviewIsHealthy() {
    return cameraPreviewHasRenderableFrame();
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach((track) => track.stop());
    }

    cameraStream = null;
    cameraReadyPromise = null;
    cameraPreview.pause();
    cameraPreview.srcObject = null;
    cameraPreview.removeAttribute('src');
    cameraPreview.load();
}

function stopFaceValidation() {
    faceValidationActive = false;
    lastValidatedVideoTime = -1;
    if (faceValidationFrame) {
        cancelAnimationFrame(faceValidationFrame);
    }
    faceValidationFrame = null;
}

function resetConfirmButton() {
    confirmBtn.textContent = 'CLOCK';
    confirmBtn.className = 'rounded-xl border border-slate-300 bg-slate-200 py-3 text-lg font-semibold text-slate-500 opacity-60 shadow-sm';
    confirmBtn.style.background = '#e2e8f0';
    confirmBtn.style.color = '#64748b';
    confirmBtn.style.borderColor = '#cbd5e1';
    confirmBtn.disabled = false;
}

function clearPendingUiReset() {
    if (!resetUiTimer) {
        return;
    }

    window.clearTimeout(resetUiTimer);
    resetUiTimer = null;
}

function scheduleUiReset(delayMs = 2500) {
    clearPendingUiReset();
    resetUiTimer = window.setTimeout(() => {
        resetUiTimer = null;
        resetUI();
    }, delayMs);
}

function clearReadyState() {
    clearPendingUiReset();
    cameraWarmupToken += 1;
    nextAction = null;
    stopFaceValidation();
    stopCamera();
    resetSnapshot();
    resetConfirmButton();
    identifyBtn.disabled = false;
    confirmBtn.disabled = false;
    captureBtn.disabled = false;
    startCameraBtn.disabled = false;
    uploadPhotoBtn.disabled = false;
    hintEl.textContent = '';
    cameraHintEl.textContent = 'After Identify succeeds, the camera starts automatically. Clock In/Out will capture the photo for the punch.';
    hideManualControls();
    hideBlinkToast();
}

function resetUI() {
    clearPendingUiReset();
    cameraWarmupToken += 1;
    stopFaceValidation();
    stopCamera();
    staffIdInput.value = '';
    pinInput.value = '';
    armInputsAgainstAutofill();
    activeField = 'staff_id';
    nextAction = null;
    resetConfirmButton();
    identifyBtn.disabled = false;
    confirmBtn.disabled = false;
    captureBtn.disabled = false;
    startCameraBtn.disabled = false;
    uploadPhotoBtn.disabled = false;
    hintEl.textContent = '';
    resetSnapshot();
    hideManualControls();
    hideBlinkToast();
    lastCameraErrorMessage = '';
    lastFaceValidationErrorMessage = '';
    cameraHintEl.textContent = 'After Identify succeeds, the camera starts automatically. Clock In/Out will capture the photo for the punch.';
    setStatus('');
    setActiveField(activeField);
    setCaptureButtonState(false);
    setConfirmAvailability();
}

function setActiveField(field) {
    activeField = field;
    staffIdInput.classList.toggle('ring-2', field === 'staff_id');
    staffIdInput.classList.toggle('ring-blue-500', field === 'staff_id');
    pinInput.classList.toggle('ring-2', field === 'pin');
    pinInput.classList.toggle('ring-blue-500', field === 'pin');
}

function currentTime() {
    const now = new Date();
    const timeEl = document.getElementById('clockTime');
    const dateEl = document.getElementById('clockDate');

    if (timeEl) {
        timeEl.textContent = now.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    }

    if (dateEl) {
        dateEl.textContent = now.toLocaleDateString([], {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }
}

function isSecureCameraContext() {
    return window.isSecureContext || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
}

function describeCameraError(error) {
    const name = error?.name || 'UnknownError';

    switch (name) {
        case 'NotAllowedError':
        case 'PermissionDeniedError':
            return 'Browser camera permission was denied. Allow camera access for this site and retry.';
        case 'NotFoundError':
        case 'DevicesNotFoundError':
            return 'No camera was detected on this device.';
        case 'NotReadableError':
        case 'TrackStartError':
            return 'The camera is already in use by another app or browser tab.';
        case 'OverconstrainedError':
        case 'ConstraintNotSatisfiedError':
            return 'The preferred camera settings are not supported on this device.';
        case 'SecurityError':
            return 'The browser blocked camera access because the page is not in a trusted secure context.';
        case 'AbortError':
            return error?.message || 'Camera startup was interrupted before the device became ready.';
        case 'TimeoutError':
            return error?.message || 'The browser did not finish opening the camera in time.';
        default:
            return 'The browser could not start the camera.';
    }
}

function describeFaceValidationError(error) {
    const rawMessage = String(error?.message || error || '').trim();

    if (rawMessage.includes('Failed to fetch dynamically imported module')) {
        return 'Live-face verification could not download its browser library.';
    }

    if (rawMessage.includes('ERR_BLOCKED_BY_CLIENT')) {
        return 'A browser extension blocked the live-face verification library.';
    }

    if (rawMessage.includes('ERR_CERT') || rawMessage.includes('ERR_SSL')) {
        return 'A certificate error blocked the live-face verification library.';
    }

    if (rawMessage.includes('storage.googleapis.com')) {
        return 'The face-verification model could not be downloaded.';
    }

    if (error?.name === 'TimeoutError') {
        return rawMessage || 'Live-face verification did not finish loading in time.';
    }

    return rawMessage !== '' ? `Live-face verification failed: ${rawMessage}` : 'Live-face verification could not start.';
}

function createNamedError(name, message) {
    const error = new Error(message);
    error.name = name;

    return error;
}

async function withTimeout(promise, timeoutMs, timeoutError) {
    let timedOut = false;

    const guardedPromise = Promise.resolve(promise).then((value) => {
        if (timedOut && value?.getTracks) {
            value.getTracks().forEach((track) => track.stop());
        }

        return value;
    });

    return Promise.race([
        guardedPromise,
        wait(timeoutMs).then(() => {
            timedOut = true;
            throw timeoutError;
        }),
    ]);
}

async function requestCameraStream() {
    const attempts = [
        {
            video: {
                facingMode: { ideal: 'user' },
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        },
        {
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        },
        {
            video: true,
            audio: false,
        },
    ];

    let lastError = null;

    for (const constraints of attempts) {
        try {
            return await withTimeout(
                navigator.mediaDevices.getUserMedia(constraints),
                8000,
                createNamedError('TimeoutError', 'The browser did not finish opening the camera.')
            );
        } catch (error) {
            lastError = error;

            if (['NotAllowedError', 'PermissionDeniedError', 'NotReadableError', 'TrackStartError', 'SecurityError'].includes(error?.name)) {
                break;
            }
        }
    }

    throw lastError;
}

function wait(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function beginCameraWarmup() {
    const token = ++cameraWarmupToken;

    if (cameraPreviewIsHealthy()) {
        return Promise.resolve(true);
    }

    if (!isSecureCameraContext() || !navigator.mediaDevices?.getUserMedia) {
        return Promise.resolve(false);
    }

    return ensureCamera(token);
}

async function attachCameraPreview(stream) {
    cameraReadyPromise = null;
    cameraPreview.pause();
    cameraPreview.srcObject = null;
    cameraPreview.removeAttribute('src');
    cameraPreview.load();
    await wait(80);
    cameraPreview.srcObject = stream;
    const previewStarted = await startCameraPreview();
    if (!previewStarted) {
        throw createNamedError('AbortError', 'The browser granted camera access, but the live preview did not start.');
    }

    return true;
}

async function startCameraPreview() {
    cameraPreview.autoplay = true;
    cameraPreview.muted = true;
    cameraPreview.playsInline = true;
    cameraPreview.setAttribute('autoplay', 'autoplay');
    cameraPreview.setAttribute('muted', 'muted');
    cameraPreview.setAttribute('playsinline', 'playsinline');

    for (let attempt = 0; attempt < 3; attempt += 1) {
        try {
            await withTimeout(
                cameraPreview.play(),
                3500,
                createNamedError('TimeoutError', 'The browser did not start the live preview.')
            );
        } catch (error) {
            if (attempt === 2) {
                throw error;
            }
        }

        const ready = await waitForVideoReady();
        const freshFrame = ready ? await waitForFreshPreviewFrame() : false;
        if (ready && freshFrame && cameraPreview.videoWidth && cameraPreview.videoHeight && !cameraPreview.paused) {
            return true;
        }

        await wait(250 * (attempt + 1));
    }

    return false;
}

function waitForVideoReady() {
    if (cameraPreviewHasRenderableFrame()) {
        return Promise.resolve(true);
    }

    if (!cameraReadyPromise) {
        cameraReadyPromise = new Promise((resolve) => {
            let settled = false;
            const finish = (result) => {
                if (settled) {
                    return;
                }
                settled = true;
                cameraPreview.removeEventListener('loadedmetadata', onReady);
                cameraPreview.removeEventListener('canplay', onReady);
                cameraPreview.removeEventListener('playing', onReady);
                resolve(result);
            };
            const onReady = () => finish(cameraPreviewHasRenderableFrame());
            cameraPreview.addEventListener('loadedmetadata', onReady, { once: true });
            cameraPreview.addEventListener('canplay', onReady, { once: true });
            cameraPreview.addEventListener('playing', onReady, { once: true });
            window.setTimeout(() => finish(cameraPreviewHasRenderableFrame()), 3000);
        }).finally(() => {
            cameraReadyPromise = null;
        });
    }

    return cameraReadyPromise;
}

function waitForFreshPreviewFrame(timeoutMs = 2500) {
    const initialTime = Number.isFinite(cameraPreview.currentTime) ? cameraPreview.currentTime : 0;
    const startedAt = performance.now();

    return new Promise((resolve) => {
        const check = () => {
            if (cameraPreview.srcObject !== cameraStream || !cameraStreamIsLive()) {
                resolve(false);
                return;
            }

            const hasFreshFrame = Number.isFinite(cameraPreview.currentTime)
                && cameraPreview.currentTime > initialTime + 0.01;

            if (cameraPreviewHasRenderableFrame() && hasFreshFrame) {
                resolve(true);
                return;
            }

            if (performance.now() - startedAt >= timeoutMs) {
                resolve(false);
                return;
            }

            window.setTimeout(check, 100);
        };

        check();
    });
}

function updateSnapshot(dataUrl) {
    snapshotDataUrl = dataUrl;
    allowConfirmWithoutPhoto = false;
    snapshotPreview.src = dataUrl;
    snapshotPreview.classList.remove('hidden');
    snapshotPlaceholder.classList.add('hidden');
    cameraHintEl.textContent = 'Photo captured. You can retake it before confirming.';
    setVerificationSummary(`Photo captured from the live webcam at ${stampTime()}. Live-face verification is not confirmed yet.`, 'info');
    setConfirmAvailability();
}

function openPhotoFallback() {
    photoFallbackInput.value = '';
    photoFallbackInput.click();
}

async function ensureFaceLandmarker() {
    if (faceLandmarker) {
        return true;
    }

    if (!faceLandmarkerPromise) {
        faceLandmarkerPromise = withTimeout((async () => {
            const visionModule = await import(faceLandmarkerBundleUrl);
            const vision = await visionModule.FilesetResolver.forVisionTasks(faceLandmarkerWasmRoot);

            return visionModule.FaceLandmarker.createFromOptions(vision, {
                baseOptions: {
                    modelAssetPath: faceLandmarkerModelUrl,
                },
                runningMode: 'VIDEO',
                numFaces: 1,
                minFaceDetectionConfidence: 0.6,
                minFacePresenceConfidence: 0.6,
                minTrackingConfidence: 0.6,
                outputFaceBlendshapes: true,
            });
        })(), 12000, createNamedError('TimeoutError', 'Live-face verification did not finish loading.'))
            .then((instance) => {
                faceLandmarker = instance;
                lastFaceValidationErrorMessage = '';
                return true;
            })
            .catch((error) => {
                console.error('Face validation failed to load.', error);
                lastFaceValidationErrorMessage = describeFaceValidationError(error);
                enableConfirmWithoutPhoto('Automatic face verification is unavailable. You can continue without a photo or use manual camera controls.', {
                    manualMessage: 'Manual camera controls are available because live-face verification could not load.',
                    summary: 'Webcam can still be used without live-face verification. Capture manually or continue without a photo.',
                });
                setStatus(`${lastFaceValidationErrorMessage} You can continue without a photo or use Upload Photo.`, 'text-amber-700');
                return false;
            })
            .finally(() => {
                faceLandmarkerPromise = null;
            });
    }

    return faceLandmarkerPromise;
}

function distanceBetween(pointA, pointB) {
    return Math.hypot(pointA.x - pointB.x, pointA.y - pointB.y);
}

function eyeAspectRatio(landmarks, indices) {
    const points = indices.map((index) => landmarks[index]).filter(Boolean);
    if (points.length !== indices.length) {
        return null;
    }

    const horizontal = distanceBetween(points[0], points[3]);
    if (!horizontal) {
        return null;
    }

    const vertical = distanceBetween(points[1], points[5]) + distanceBetween(points[2], points[4]);

    return vertical / (2 * horizontal);
}

function getBlendshapeScore(result, categoryName) {
    const blendshapeGroup = result?.faceBlendshapes?.[0] ?? result?.face_blendshapes?.[0];
    const categories = blendshapeGroup?.categories ?? [];
    const category = categories.find((item) => item.categoryName === categoryName);

    return category?.score ?? null;
}

function getBlinkState(result, landmarks) {
    const leftBlink = getBlendshapeScore(result, 'eyeBlinkLeft');
    const rightBlink = getBlendshapeScore(result, 'eyeBlinkRight');

    if (leftBlink !== null || rightBlink !== null) {
        const scores = [leftBlink, rightBlink].filter((value) => value !== null);
        const averageScore = scores.reduce((sum, value) => sum + value, 0) / scores.length;
        const maxScore = Math.max(...scores);

        return {
            closed: averageScore > 0.3 || maxScore > 0.45,
            open: averageScore < 0.14 && maxScore < 0.22,
        };
    }

    const leftEar = eyeAspectRatio(landmarks, leftEyeIndices);
    const rightEar = eyeAspectRatio(landmarks, rightEyeIndices);
    if (leftEar === null || rightEar === null) {
        return { closed: false, open: false };
    }

    const averageEar = (leftEar + rightEar) / 2;

    return {
        closed: averageEar < 0.22,
        open: averageEar > 0.25,
    };
}

function updateFaceValidation(result) {
    const landmarksList = result?.faceLandmarks ?? result?.face_landmarks ?? [];

    faceVerified = false;
    setConfirmAvailability();

    if (usingManualPhoto) {
        cameraHintEl.textContent = 'Manual photo loaded. Blink verification is skipped for this punch.';
        setConfirmAvailability();
        return;
    }

    if (landmarksList.length === 0) {
        blinkClosedSeen = false;
        blinkClosedFrameCount = 0;
        blinkOpenFrameCount = 0;
        stableFaceFrameCount = 0;
        cameraHintEl.textContent = 'Position your face in the frame.';
        return;
    }

    if (landmarksList.length > 1) {
        blinkClosedSeen = false;
        blinkClosedFrameCount = 0;
        blinkOpenFrameCount = 0;
        stableFaceFrameCount = 0;
        cameraHintEl.textContent = 'Only one face can be in frame.';
        return;
    }

    const landmarks = landmarksList[0];
    const xValues = landmarks.map((point) => point.x);
    const yValues = landmarks.map((point) => point.y);
    const minX = Math.min(...xValues);
    const maxX = Math.max(...xValues);
    const minY = Math.min(...yValues);
    const maxY = Math.max(...yValues);
    const faceWidth = maxX - minX;
    const faceHeight = maxY - minY;
    const centerX = minX + faceWidth / 2;
    const centerY = minY + faceHeight / 2;

    if (faceWidth < 0.22 || faceHeight < 0.22) {
        blinkClosedSeen = false;
        blinkClosedFrameCount = 0;
        blinkOpenFrameCount = 0;
        stableFaceFrameCount = 0;
        cameraHintEl.textContent = 'Move closer so your face fills more of the frame.';
        return;
    }

    if (Math.abs(centerX - 0.5) > 0.16 || Math.abs(centerY - 0.5) > 0.18) {
        blinkClosedSeen = false;
        blinkClosedFrameCount = 0;
        blinkOpenFrameCount = 0;
        stableFaceFrameCount = 0;
        cameraHintEl.textContent = 'Center your face in the camera preview.';
        return;
    }

    stableFaceFrameCount += 1;

    const blinkState = getBlinkState(result, landmarks);
    if (!blinkClosedSeen) {
        if (blinkState.closed) {
            blinkClosedFrameCount += 1;
            if (blinkClosedFrameCount >= 1) {
                blinkClosedSeen = true;
                blinkOpenFrameCount = 0;
                cameraHintEl.textContent = 'Blink detected. Open your eyes to complete verification.';
                showBlinkToast('Blink detected. Open your eyes.', 'success');
            }
            return;
        }

        blinkClosedFrameCount = 0;
        if (stableFaceFrameCount >= 8) {
            cameraHintEl.textContent = 'Face detected. Blink once to verify your face.';
            return;
        }

        cameraHintEl.textContent = 'Blink once to verify your face.';
        return;
    }

    if (!blinkState.open) {
        blinkOpenFrameCount = 0;
        cameraHintEl.textContent = 'Open your eyes to complete verification.';
        return;
    }

    blinkOpenFrameCount += 1;

    cameraHintEl.textContent = 'Face verified. Capturing photo automatically...';
    showBlinkToast('Blink confirmed. Capturing photo.', 'success');
    setConfirmAvailability();
    void autoCaptureVerifiedFace();
}

function processFaceValidation() {
    if (!faceValidationActive) {
        return;
    }

    faceValidationFrame = requestAnimationFrame(processFaceValidation);

    if (!faceLandmarker || !cameraStream || cameraPreview.readyState < 2) {
        return;
    }

    if (cameraPreview.currentTime === lastValidatedVideoTime) {
        return;
    }

    lastValidatedVideoTime = cameraPreview.currentTime;

    try {
        const result = faceLandmarker.detectForVideo(cameraPreview, performance.now());
        updateFaceValidation(result);
    } catch (error) {
        console.error('Face validation failed during detection.', error);
        stopFaceValidation();
        enableConfirmWithoutPhoto('Face verification stopped unexpectedly. You can continue without a photo or use manual camera controls.');
        setStatus('Face verification failed during capture. You can continue without a photo.', 'text-amber-700');
    }
}

async function startFaceValidation() {
    usingManualPhoto = false;
    faceVerified = false;
    disableConfirmWithoutPhoto();
    blinkClosedSeen = false;
    setVerificationSummary('Waiting for one blink to verify a live face from the webcam.', 'info');
    setConfirmAvailability();

    const landmarkerReady = await ensureFaceLandmarker();
    if (!landmarkerReady) {
        return false;
    }

    stopFaceValidation();
    faceValidationActive = true;
    processFaceValidation();

    return true;
}

async function ensureCamera(expectedWarmupToken = null, options = {}) {
    const { forceRestart = false } = options;

    if (forceRestart) {
        stopCamera();
    }

    if (cameraStream && cameraStreamIsLive()) {
        try {
            if (!cameraPreviewIsHealthy()) {
                await attachCameraPreview(cameraStream);
            }

            lastCameraErrorMessage = '';
            disableConfirmWithoutPhoto();
            cameraHintEl.textContent = 'Camera ready. Clock In/Out will capture the photo automatically. Use Retake Photo only if needed.';
            return true;
        } catch (error) {
            console.warn('Existing camera stream could not resume. Requesting a new stream.', error);
            stopCamera();
        }
    } else if (cameraStream) {
        stopCamera();
    }

    if (!isSecureCameraContext()) {
        lastCameraErrorMessage = 'Live camera needs HTTPS or localhost.';
        enableConfirmWithoutPhoto('Live camera needs HTTPS or localhost. You can continue without a photo or use manual camera controls.');
        return false;
    }
    if (!navigator.mediaDevices?.getUserMedia) {
        lastCameraErrorMessage = 'This browser does not support live camera access.';
        enableConfirmWithoutPhoto('This browser does not support live camera access. You can continue without a photo or use manual camera controls.');
        return false;
    }

    let startupError = null;

    for (let attempt = 0; attempt < 2; attempt += 1) {
        try {
            cameraStream = await requestCameraStream();
            if (expectedWarmupToken !== null && expectedWarmupToken !== cameraWarmupToken) {
                stopCamera();
                return false;
            }

            await attachCameraPreview(cameraStream);
            if (expectedWarmupToken !== null && expectedWarmupToken !== cameraWarmupToken) {
                stopCamera();
                return false;
            }

            lastCameraErrorMessage = '';
            disableConfirmWithoutPhoto();
            cameraHintEl.textContent = 'Camera ready. Clock In/Out will capture the photo automatically. Use Retake Photo only if needed.';
            return true;
        } catch (error) {
            startupError = error;
            console.warn(`Live camera start attempt ${attempt + 1} failed.`, error);
            stopCamera();

            if (attempt === 0) {
                await wait(300);
            }
        }
    }

    const errorMessage = describeCameraError(startupError);
    lastCameraErrorMessage = errorMessage;
    enableConfirmWithoutPhoto('Camera unavailable. You can continue without a photo or allow camera access and use manual controls.');
    setStatus(`${errorMessage} You can continue without a photo.`, 'text-amber-700');
    return false;
}

async function captureSnapshot(loadingLabel = 'Capturing...') {
    setCaptureButtonState(true, loadingLabel);
    const liveCameraAvailable = await ensureCamera();
    if (!liveCameraAvailable) {
        setCaptureButtonState(false);
        setStatus('Webcam is not available. You can continue without a photo, retry the webcam, or use Upload Photo.', 'text-red-600');
        return false;
    }

    const ready = await waitForVideoReady();
    if (!ready || !cameraPreview.videoWidth || !cameraPreview.videoHeight) {
        setCaptureButtonState(false);
        enableConfirmWithoutPhoto('The camera is still starting. You can continue without a photo or use manual camera controls.');
        setStatus('Camera is still starting. Try again in a moment.', 'text-red-600');
        return false;
    }

    const canvas = document.createElement('canvas');
    canvas.width = cameraPreview.videoWidth;
    canvas.height = cameraPreview.videoHeight;
    canvas.getContext('2d').drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
    updateSnapshot(canvas.toDataURL('image/jpeg', 0.85));
    setStatus('Photo captured for this punch.', 'text-slate-700');
    setCaptureButtonState(false);
    return true;
}

async function autoCaptureVerifiedFace() {
    if (autoCaptureInProgress || snapshotDataUrl || usingManualPhoto) {
        return;
    }

    autoCaptureInProgress = true;
    const captured = await captureSnapshot('Capturing...');
    autoCaptureInProgress = false;

    if (!captured) {
        enableConfirmWithoutPhoto('Automatic capture could not complete. You can continue without a photo or use manual camera controls.');
        setStatus('Face verified, but automatic capture failed. Keep your face in frame, retry, or continue without a photo.', 'text-amber-700');
        return;
    }

    stopFaceValidation();
    faceVerified = true;
    cameraHintEl.textContent = `Face verified and photo captured. Press ${nextAction === 'clock-in' ? 'CLOCK IN' : 'CLOCK OUT'} to continue.`;
    setVerificationSummary(`Live face verified by blink at ${stampTime()}. Photo captured from the live webcam and ready for this punch.`, 'success');
    setStatus('Photo captured automatically. Press the clock button to continue.', 'text-slate-700');
    setConfirmAvailability();
}

setInterval(currentTime, 1000);
currentTime();
resetUI();
window.addEventListener('pageshow', resetUI);
window.addEventListener('pagehide', () => {
    cameraWarmupToken += 1;
    stopFaceValidation();
    stopCamera();
});
staffIdInput.addEventListener('click', () => setActiveField('staff_id'));
staffIdInput.addEventListener('pointerdown', () => unlockInput(staffIdInput));
staffIdInput.addEventListener('focus', () => setActiveField('staff_id'));
staffIdInput.addEventListener('focus', () => unlockInput(staffIdInput));
pinInput.addEventListener('click', () => setActiveField('pin'));
pinInput.addEventListener('pointerdown', () => unlockInput(pinInput));
pinInput.addEventListener('focus', () => setActiveField('pin'));
pinInput.addEventListener('focus', () => unlockInput(pinInput));

document.querySelectorAll('.key').forEach((btn) => {
    btn.addEventListener('click', () => {
        const targetInput = activeField === 'staff_id' ? staffIdInput : pinInput;
        const maxLength = activeField === 'staff_id' ? 12 : 6;
        if (targetInput.value.length < maxLength) {
            targetInput.value += btn.dataset.key;
        }
    });
});

staffIdInput.addEventListener('input', () => {
    if (nextAction) {
        clearReadyState();
    }
    staffIdInput.value = staffIdInput.value.replace(/\D/g, '').slice(0, 12);
    setStatus('');
    hintEl.textContent = '';
});

pinInput.addEventListener('input', () => {
    if (nextAction) {
        clearReadyState();
    }
    pinInput.value = pinInput.value.replace(/\D/g, '').slice(0, 6);
    setStatus('');
    hintEl.textContent = '';
});

document.getElementById('clear').addEventListener('click', () => {
    if (nextAction) {
        clearReadyState();
    }
    if (activeField === 'staff_id') {
        staffIdInput.value = '';
        return;
    }
    pinInput.value = '';
});

document.getElementById('back').addEventListener('click', () => {
    if (nextAction) {
        clearReadyState();
    }
    if (activeField === 'staff_id') {
        staffIdInput.value = staffIdInput.value.slice(0, -1);
        return;
    }
    pinInput.value = pinInput.value.slice(0, -1);
});

startCameraBtn.addEventListener('click', async () => {
    showManualControls('Manual camera controls are available while you recover the automatic flow.');
    startCameraBtn.disabled = true;
    startCameraBtn.textContent = 'Starting...';
    const started = await ensureCamera(null, { forceRestart: true });
    if (started && nextAction) {
        await startFaceValidation();
    }
    startCameraBtn.disabled = false;
    startCameraBtn.textContent = 'Use Webcam';
    setStatus(started ? 'Webcam ready. The clock button will capture the photo automatically.' : 'Webcam could not start. Check HTTPS certificate trust and browser camera permission.', started ? 'text-slate-700' : 'text-red-600');
});

captureBtn.addEventListener('click', async () => {
    showManualControls('Manual camera controls are active for this punch.');
    await captureSnapshot();
});

uploadPhotoBtn.addEventListener('click', () => {
    showManualControls('Manual photo upload is active for this punch.');
    openPhotoFallback();
});

photoFallbackInput.addEventListener('change', () => {
    const [file] = photoFallbackInput.files || [];
    if (!file) {
        setCaptureButtonState(false);
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        stopFaceValidation();
        usingManualPhoto = true;
        disableConfirmWithoutPhoto();
        showManualControls('A manual photo is loaded for this punch.');
        updateSnapshot(String(reader.result || ''));
        cameraHintEl.textContent = 'Manual photo ready. Blink verification is skipped for this punch.';
        setVerificationSummary(`Manual photo loaded at ${stampTime()}. This image was not verified as a live face.`, 'warning');
        setStatus('Photo ready.', 'text-slate-700');
        setCaptureButtonState(false);
    };
    reader.onerror = () => {
        setStatus('Selected photo could not be read.', 'text-red-600');
        setCaptureButtonState(false);
    };
    reader.readAsDataURL(file);
});

identifyBtn.addEventListener('click', async () => {
    clearReadyState();
    setStatus('');
    hintEl.textContent = '';
    const staff_id = staffIdInput.value;
    const pin = pinInput.value;

    if (!staff_id) {
        setActiveField('staff_id');
        return setStatus('Enter Staff ID.', 'text-red-600');
    }

    if (!pin) {
        setActiveField('pin');
        return setStatus('Enter PIN.', 'text-red-600');
    }

    const prewarmCameraPromise = beginCameraWarmup();

    const res = await fetch('{{ route('kiosk.identify') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ staff_id, pin })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        cameraWarmupToken += 1;
        stopCamera();
        return setStatus(extractErrorMessage(data, 'Staff ID / PIN not recognized.'), 'text-red-600');
    }

    nextAction = data.next_action;
    const clockInAllowed = data.clock_in_allowed !== false;

    if (nextAction === 'clock-in' && !clockInAllowed) {
        cameraWarmupToken += 1;
        stopCamera();
        resetConfirmButton();
        setStatus(data.clock_in_block_reason || 'Clock in is not allowed yet.', 'text-red-600');
        hintEl.textContent = data.schedule_hint || '';
        return;
    }

    resetSnapshot();
    if (nextAction === 'clock-in') {
        confirmBtn.textContent = 'CLOCK IN';
        confirmBtn.style.background = '#059669';
        confirmBtn.style.color = '#ffffff';
        confirmBtn.style.borderColor = '#047857';
    } else {
        confirmBtn.textContent = 'CLOCK OUT';
        confirmBtn.style.background = '#d97706';
        confirmBtn.style.color = '#ffffff';
        confirmBtn.style.borderColor = '#b45309';
    }

    setStatus(`Ready: ${data.name}. Starting camera...`, 'text-slate-700');
    hintEl.textContent = data.schedule_hint || '';
    setVerificationSummary(`Staff identified as ${data.name}. Starting live-face verification.`, 'info');

    const warmedCamera = await prewarmCameraPromise;
    const cameraStarted = warmedCamera || await ensureCamera();
    const faceValidationStarted = cameraStarted ? await startFaceValidation() : false;
    if (cameraStarted && faceValidationStarted) {
        setStatus(`Ready: ${data.name}. Blink once, then press ${nextAction === 'clock-in' ? 'CLOCK IN' : 'CLOCK OUT'}.`, 'text-slate-700');
        return;
    }

    if (cameraStarted) {
        enableConfirmWithoutPhoto(
            `Webcam ready, but live-face verification could not start. You can use Retake Photo or continue without a photo before ${nextAction === 'clock-in' ? 'clocking in' : 'clocking out'}.`,
            {
                manualMessage: 'Webcam is ready. Use Retake Photo to capture manually, or continue without a photo.',
                summary: 'Webcam started, but live-face verification is unavailable. You can still capture a manual photo for this punch.',
            }
        );
        setStatus(
            `Ready: ${data.name}. Webcam started, but live-face verification could not start. ${lastFaceValidationErrorMessage || 'Use Retake Photo or continue without a photo.'}`,
            'text-amber-700'
        );
        return;
    }

    enableConfirmWithoutPhoto(`Camera or face verification could not start. You can continue without a photo, or use manual camera controls before ${nextAction === 'clock-in' ? 'clocking in' : 'clocking out'}.`);
    setStatus(`Ready: ${data.name}. ${lastCameraErrorMessage || 'Camera or face verification could not start.'} You can continue without a photo.`, 'text-amber-700');
});

function handleEnter(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        identifyBtn.click();
    }
}

staffIdInput.addEventListener('keydown', handleEnter);
pinInput.addEventListener('keydown', handleEnter);

confirmBtn.addEventListener('click', async () => {
    if (!nextAction) {
        return;
    }

    if (!snapshotDataUrl && !allowConfirmWithoutPhoto) {
        const shouldUseLiveCapture = Boolean(cameraStream) && !usingManualPhoto;
        const captured = shouldUseLiveCapture ? await captureSnapshot('Capturing...') : false;
        if (!captured && !snapshotDataUrl) {
            return;
        }
    }

    identifyBtn.disabled = true;
    confirmBtn.disabled = true;
    startCameraBtn.disabled = true;
    captureBtn.disabled = true;
    uploadPhotoBtn.disabled = true;

    const usePhotoRoute = Boolean(snapshotDataUrl);
    const route = nextAction === 'clock-in'
        ? (usePhotoRoute ? clockInRoute : fallbackClockInRoute)
        : (usePhotoRoute ? clockOutRoute : fallbackClockOutRoute);
    const res = await fetch(route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ staff_id: staffIdInput.value, pin: pinInput.value, photo: snapshotDataUrl })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        setStatus(extractErrorMessage(data, 'Action failed.'), 'text-red-600');
        hintEl.textContent = data.note || '';
        identifyBtn.disabled = false;
        confirmBtn.disabled = false;
        startCameraBtn.disabled = false;
        captureBtn.disabled = false;
        uploadPhotoBtn.disabled = false;
        return;
    }

    setStatus(data.message || 'Success.', 'text-emerald-700');
    hintEl.textContent = usePhotoRoute
        ? (data.note || '')
        : (data.note || 'Clock completed without a camera photo because camera fallback was used.');
    if (!usePhotoRoute) {
        setVerificationSummary('Punch submitted without a camera photo. No live-face verification was saved for this record.', 'warning');
    }
    scheduleUiReset();
});
</script>
</body>
</html>
