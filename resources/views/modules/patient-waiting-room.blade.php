<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salle d'attente virtuelle</title>
    @vite(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="container py-4">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Salle d'attente virtuelle - suivi temps reel</h3></div>
        <div class="card-body">
            <p class="text-secondary mb-3">Patient: <strong>{{ $appointment->patient?->full_name ?? $appointment->patient_name }}</strong></p>
            <div class="row g-2">
                <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-secondary">Statut</div><div class="h4" id="status">...</div></div></div></div>
                <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-secondary">Position file</div><div class="h4" id="position">...</div></div></div></div>
                <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-secondary">Attente estimee</div><div class="h4"><span id="eta">...</span> min</div></div></div></div>
                <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body"><div class="text-secondary">Retard praticien</div><div class="h4"><span id="delay">...</span> min</div></div></div></div>
            </div>
            <div class="alert alert-info mt-3 mb-0" id="roomInfo">Salle assignee: {{ $appointment->room?->name ?? '-' }}</div>
            <div class="small text-secondary mt-2">Mise a jour: <span id="updatedAt">-</span></div>
        </div>
    </div>
</main>
<script>
(() => {
    const dataUrl = @json($dataUrl);
    async function refresh() {
        const res = await fetch(dataUrl, { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        document.getElementById('status').textContent = data.status || '-';
        document.getElementById('position').textContent = Number(data.queue_position || 0) + 1;
        document.getElementById('eta').textContent = data.estimated_wait_minutes ?? 0;
        document.getElementById('delay').textContent = data.doctor_delay_minutes ?? 0;
        document.getElementById('roomInfo').textContent = `Salle assignee: ${data.room || '-'}`;
        document.getElementById('updatedAt').textContent = data.updated_at || '-';
    }
    refresh();
    setInterval(refresh, 10000);
})();
</script>
</body>
</html>

