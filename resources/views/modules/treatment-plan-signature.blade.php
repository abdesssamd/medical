<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signature devis - {{ $plan->name }}</title>
    @vite(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-4">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Validation du devis phase</h3>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <p><strong>Patient:</strong> {{ $plan->patient?->full_name }} ({{ $plan->patient?->medical_record_number }})</p>
            <p><strong>Praticien:</strong> {{ $plan->practitioner?->name }}</p>
            <p><strong>Plan:</strong> {{ $plan->name }}</p>
            <p><strong>Montant estime:</strong> {{ number_format((float) $plan->total_estimated_cost, 2, ',', ' ') }} MAD</p>

            <div class="mb-3">
                <h4>Phases</h4>
                <ul>
                    @foreach(($plan->phases ?? []) as $phase)
                        <li>{{ $phase['name'] ?? '-' }}</li>
                    @endforeach
                </ul>
            </div>

            <form method="POST" action="{{ url()->full() }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nom du patient signataire</label>
                    <input class="form-control" name="patient_name" required value="{{ old('patient_name', $plan->signed_by_patient_name) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Signature tactile</label>
                    <canvas id="signaturePad" style="width:100%;max-width:700px;height:220px;border:1px dashed #cbd5e1;border-radius:8px;background:#fff"></canvas>
                    <input type="hidden" name="signature_data" id="signatureData" required>
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSign">Effacer</button>
                    </div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" name="accept_terms" id="acceptTerms" required>
                    <label class="form-check-label" for="acceptTerms">
                        Je confirme avoir lu et accepte le plan de traitement.
                    </label>
                </div>
                <button class="btn btn-primary">Signer et valider</button>
            </form>
        </div>
    </div>
</main>

<script>
(() => {
    const canvas = document.getElementById('signaturePad');
    if (!canvas) return;
    const clearBtn = document.getElementById('clearSign');
    const output = document.getElementById('signatureData');
    const ctx = canvas.getContext('2d');
    let drawing = false;

    function resize() {
        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';
    }

    function pos(e) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches && e.touches.length) {
            return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
        }
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    }

    function start(e) {
        drawing = true;
        const p = pos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        e.preventDefault();
    }

    function move(e) {
        if (!drawing) return;
        const p = pos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        output.value = canvas.toDataURL('image/png');
        e.preventDefault();
    }

    function end() {
        drawing = false;
        output.value = canvas.toDataURL('image/png');
    }

    resize();
    window.addEventListener('resize', resize);
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);
    clearBtn.addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        output.value = '';
    });
})();
</script>
</body>
</html>

