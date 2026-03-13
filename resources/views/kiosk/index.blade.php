<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kiosk Clock</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-xl px-4 py-8">
        <section class="rounded-2xl bg-white p-6 shadow">
            <p class="text-xs text-gray-500">KIOSK BUILD: 2026-03-13-basic</p>
            <p class="text-sm text-slate-500">Location</p>
            <h1 class="text-2xl font-bold">{{ $location->name }}</h1>
            <p id="clock" class="mt-1 text-lg text-slate-600"></p>

            <p class="mt-6 text-sm font-semibold text-slate-600">Enter Staff ID and PIN to Clock In/Out</p>

            <div class="absolute -left-[9999px] top-auto h-px w-px overflow-hidden opacity-0" aria-hidden="true">
                <input type="text" name="username" autocomplete="username" tabindex="-1">
                <input type="password" name="password" autocomplete="current-password" tabindex="-1">
            </div>

            <div class="mt-3">
                <input
                    id="staff_id"
                    name="kiosk_staff_id"
                    type="text"
                    inputmode="numeric"
                    maxlength="12"
                    pattern="[0-9]*"
                    autocomplete="off"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    data-lpignore="true"
                    data-form-type="other"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-2xl"
                    placeholder="Staff ID"
                >
            </div>

            <div class="mt-3">
                <input
                    id="pin"
                    name="kiosk_pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]*"
                    autocomplete="new-password"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    data-lpignore="true"
                    data-form-type="other"
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-2xl tracking-[0.5em]"
                    placeholder="PIN"
                >
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3">
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="1">1</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="2">2</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="3">3</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="4">4</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="5">5</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="6">6</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="7">7</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="8">8</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="9">9</button>
                <button type="button" id="clear" class="rounded-lg bg-slate-300 py-4 text-xl font-semibold">Clear</button>
                <button type="button" class="key rounded-lg bg-slate-800 py-4 text-2xl font-bold text-white" style="background:#1e293b;color:#fff;border:1px solid #0f172a;" data-key="0">0</button>
                <button type="button" id="back" class="rounded-lg bg-slate-300 py-4 text-xl font-semibold">Back</button>
            </div>

            <div class="mt-4 flex gap-3">
                <button id="identifyBtn" class="flex-1 rounded-lg bg-blue-600 py-3 text-lg font-semibold text-white" style="background:#2563eb;color:#fff;border:1px solid #1d4ed8;">Identify</button>
                <button id="confirmBtn" class="hidden flex-1 rounded-lg py-3 text-lg font-semibold text-white" style="border:1px solid transparent;"></button>
            </div>

            <p id="status" class="mt-4 min-h-6 text-center text-base"></p>
            <p id="hint" class="mt-1 min-h-5 text-center text-sm text-slate-500"></p>
            <p class="mt-6 text-center text-xs text-slate-400">Camera test version: <a class="text-blue-600 underline" href="{{ route('kiosk.camera.home') }}">open kiosk-camera</a></p>
        </section>
    </main>

<script>
const staffIdInput = document.getElementById('staff_id');
const pinInput = document.getElementById('pin');
const statusEl = document.getElementById('status');
const hintEl = document.getElementById('hint');
const identifyBtn = document.getElementById('identifyBtn');
const confirmBtn = document.getElementById('confirmBtn');
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const clockInRoute = @json($clockInRoute);
const clockOutRoute = @json($clockOutRoute);
let nextAction = null;
let activeField = 'staff_id';

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

function resetUI() {
    staffIdInput.value = '';
    pinInput.value = '';
    armInputsAgainstAutofill();
    activeField = 'staff_id';
    nextAction = null;
    confirmBtn.classList.add('hidden');
    confirmBtn.textContent = '';
    confirmBtn.className = 'hidden flex-1 rounded-lg py-3 text-lg font-semibold text-white';
    identifyBtn.disabled = false;
    confirmBtn.disabled = false;
    hintEl.textContent = '';
    setStatus('');
    setActiveField(activeField);
}

function setActiveField(field) {
    activeField = field;
    staffIdInput.classList.toggle('ring-2', field === 'staff_id');
    staffIdInput.classList.toggle('ring-blue-500', field === 'staff_id');
    pinInput.classList.toggle('ring-2', field === 'pin');
    pinInput.classList.toggle('ring-blue-500', field === 'pin');
}

function currentTime() {
    document.getElementById('clock').textContent = new Date().toLocaleString();
}

setInterval(currentTime, 1000);
currentTime();
resetUI();

window.addEventListener('pageshow', () => {
    resetUI();
});

staffIdInput.addEventListener('click', () => setActiveField('staff_id'));
staffIdInput.addEventListener('pointerdown', () => unlockInput(staffIdInput));
staffIdInput.addEventListener('focus', () => setActiveField('staff_id'));
staffIdInput.addEventListener('focus', () => unlockInput(staffIdInput));
pinInput.addEventListener('click', () => setActiveField('pin'));
pinInput.addEventListener('pointerdown', () => unlockInput(pinInput));
pinInput.addEventListener('focus', () => setActiveField('pin'));
pinInput.addEventListener('focus', () => unlockInput(pinInput));

document.querySelectorAll('.key').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetInput = activeField === 'staff_id' ? staffIdInput : pinInput;
        const maxLength = activeField === 'staff_id' ? 12 : 6;
        if (targetInput.value.length < maxLength) {
            targetInput.value += btn.dataset.key;
        }
    });
});

staffIdInput.addEventListener('input', () => {
    staffIdInput.value = staffIdInput.value.replace(/\D/g, '').slice(0, 12);
    setStatus('');
    hintEl.textContent = '';
});

pinInput.addEventListener('input', () => {
    pinInput.value = pinInput.value.replace(/\D/g, '').slice(0, 6);
    setStatus('');
    hintEl.textContent = '';
});

document.getElementById('clear').addEventListener('click', () => {
    if (activeField === 'staff_id') {
        staffIdInput.value = '';
        return;
    }

    pinInput.value = '';
});

document.getElementById('back').addEventListener('click', () => {
    if (activeField === 'staff_id') {
        staffIdInput.value = staffIdInput.value.slice(0, -1);
        return;
    }

    pinInput.value = pinInput.value.slice(0, -1);
});

identifyBtn.addEventListener('click', async () => {
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

    const res = await fetch('{{ route('kiosk.identify') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ staff_id, pin })
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        return setStatus(extractErrorMessage(data, 'Staff ID / PIN not recognized.'), 'text-red-600');
    }

    nextAction = data.next_action;
    const clockInAllowed = data.clock_in_allowed !== false;

    if (nextAction === 'clock-in' && !clockInAllowed) {
        confirmBtn.classList.add('hidden');
        confirmBtn.textContent = '';
        setStatus(data.clock_in_block_reason || 'Clock in is not allowed yet.', 'text-red-600');
        hintEl.textContent = data.schedule_hint || '';
        identifyBtn.disabled = false;
        return;
    }

    confirmBtn.classList.remove('hidden');

    if (nextAction === 'clock-in') {
        confirmBtn.textContent = `CLOCK IN (${data.name})`;
        confirmBtn.style.background = '#059669';
        confirmBtn.style.color = '#ffffff';
        confirmBtn.style.borderColor = '#047857';
    } else {
        confirmBtn.textContent = `CLOCK OUT (${data.name})`;
        confirmBtn.style.background = '#d97706';
        confirmBtn.style.color = '#ffffff';
        confirmBtn.style.borderColor = '#b45309';
    }

    setStatus(`Ready: ${data.name}`, 'text-slate-700');
    hintEl.textContent = data.schedule_hint || '';
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

    identifyBtn.disabled = true;
    confirmBtn.disabled = true;

    const route = nextAction === 'clock-in' ? clockInRoute : clockOutRoute;

    const res = await fetch(route, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ staff_id: staffIdInput.value, pin: pinInput.value })
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        setStatus(extractErrorMessage(data, 'Action failed.'), 'text-red-600');
        hintEl.textContent = data.note || '';
        identifyBtn.disabled = false;
        confirmBtn.disabled = false;
        return;
    }

    setStatus(data.message || 'Success.', 'text-emerald-700');
    hintEl.textContent = data.note || '';
    setTimeout(resetUI, 2500);
});
</script>
</body>
</html>
