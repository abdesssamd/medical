<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f5d4d">
    <title>Rechercher un rendez-vous — MedOffice</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
<div class="search-shell">
    <header class="search-header">
        <a href="<?php echo e(route('public.appointment.landing')); ?>" class="brand text-decoration-none">MedOffice</a>
        <a href="<?php echo e(route('login')); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-user"></i> Connexion
        </a>
    </header>

    <div class="search-container">
        <h1 class="search-title">Rechercher un rendez-vous</h1>
        <p class="search-subtitle">Remplissez vos informations et vos critères pour trouver le créneau idéal.</p>

        <form id="searchForm" class="search-form" method="POST" action="<?php echo e(route('public.appointment.request')); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

            <fieldset class="form-section">
                <legend><i class="ti ti-user-circle"></i> Informations patient</legend>
                <div class="row g-3">
                    <div class="col-lg-3">
                        <label class="form-label">NIN <span class="text-danger">*</span></label>
                        <input type="text" name="nin" class="form-control" placeholder="AB123456" required value="<?php echo e(old('nin')); ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" placeholder="Jean" required value="<?php echo e(old('first_name')); ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" placeholder="Dupont" required value="<?php echo e(old('last_name')); ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Date naissance <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control" required value="<?php echo e(old('date_of_birth')); ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" placeholder="06 XX XX XX XX" value="<?php echo e(old('phone')); ?>">
                    </div>
                    <div class="col-lg-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="jean@email.fr" value="<?php echo e(old('email')); ?>">
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Ordonnance (PDF/Image)</label>
                        <input type="file" name="prescription" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.webp">
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend><i class="ti ti-stethoscope"></i> Critères médicaux</legend>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">Service</label>
                        <select name="service_id" id="serviceSelect" class="form-select" value="<?php echo e(old('service_id')); ?>">
                            <option value="">Tous les services</option>
                            <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($service->id); ?>"><?php echo e($service->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Type d'acte</label>
                        <select name="appointment_type_id" id="acteSelect" class="form-select" value="<?php echo e(old('appointment_type_id')); ?>">
                            <option value="">Tous les actes</option>
                            <?php $__currentLoopData = $appointmentTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $at): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($at->id); ?>"><?php echo e($at->name); ?> (<?php echo e($at->duration_minutes); ?> min)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Spécialiste</label>
                        <select name="professional_id" id="proSelect" class="form-select" value="<?php echo e(old('professional_id')); ?>">
                            <option value="">Tous les spécialistes</option>
                            <?php $__currentLoopData = $professionals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($pro->id); ?>"><?php echo e($pro->display_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend><i class="ti ti-calendar-clock"></i> Préférences de rendez-vous</legend>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">À partir du</label>
                        <input type="date" name="preferred_date_from" class="form-control" value="<?php echo e(old('preferred_date_from', now()->addDay()->format('Y-m-d'))); ?>">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Jusqu'au</label>
                        <input type="date" name="preferred_date_to" class="form-control" value="<?php echo e(old('preferred_date_to', now()->addDays(14)->format('Y-m-d'))); ?>">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Moment</label>
                        <select name="time_preference" class="form-select">
                            <option value="any">Indifférent</option>
                            <option value="morning">Matin (8h-12h)</option>
                            <option value="afternoon">Après-midi (14h-18h)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes / Motif</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Décrivez brièvement le motif..."><?php echo e(old('notes')); ?></textarea>
                    </div>
                </div>
            </fieldset>

            <div id="slotsResults" class="slots-results" style="display:none;">
                <h3>Créneaux disponibles</h3>
                <div id="slotsList"></div>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn-cta-primary" style="font-size:1rem;">
                    <i class="ti ti-send"></i> Envoyer ma demande
                </button>
            </div>
        </form>
    </div>

    <footer class="landing-footer">
        <p>&copy; <?php echo e(date('Y')); ?> MedOffice — Gestion de cabinet médical</p>
    </footer>
</div>
</body>
</html>

<style>
.search-shell { min-height: 100vh; display: grid; grid-template-rows: auto 1fr auto; background: var(--bg); }
.search-header { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 2rem; border-bottom: 1px solid var(--line); background: var(--surface); }
.search-container { max-width: 860px; margin: 0 auto; padding: 2rem 1rem; width: 100%; }
.search-title { font-family: 'Space Grotesk', sans-serif; font-size: 1.8rem; color: var(--text); margin-bottom: 0.25rem; }
.search-subtitle { color: var(--muted); margin-bottom: 2rem; }
.search-form { display: grid; gap: 1.5rem; }
.form-section { background: var(--surface); border: 1px solid var(--line); border-radius: 20px; padding: 1.5rem; }
.form-section legend { font-family: 'Space Grotesk', sans-serif; font-weight: 600; font-size: 1.05rem; color: var(--primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.slots-results { background: var(--surface); border: 2px solid var(--primary); border-radius: 20px; padding: 1.5rem; }
.slots-results h3 { font-family: 'Space Grotesk', sans-serif; color: var(--primary); margin-bottom: 1rem; }
.slot-day { margin-bottom: 1rem; }
.slot-day h4 { font-size: 0.95rem; color: var(--text); margin-bottom: 0.5rem; }
.slot-badge { display: inline-block; background: var(--primary); color: #fff; padding: 0.35rem 0.75rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; margin: 0.2rem; cursor: pointer; transition: all 0.2s; }
.slot-badge:hover { background: var(--primary-strong); transform: scale(1.05); }
.form-actions { display: flex; justify-content: flex-end; padding-top: 0.5rem; }
.landing-footer { text-align: center; padding: 1.5rem; color: var(--muted); font-size: 0.9rem; border-top: 1px solid var(--line); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const serviceEl = document.getElementById('serviceSelect');
    const acteEl = document.getElementById('acteSelect');
    const proEl = document.getElementById('proSelect');
    const dateFrom = document.querySelector('[name="preferred_date_from"]');
    const dateTo = document.querySelector('[name="preferred_date_to"]');

    function triggerSearch() {
        const fromVal = dateFrom.value;
        const toVal = dateTo.value;
        if (!fromVal) return;

        const params = new URLSearchParams({
            date_from: fromVal,
            date_to: toVal || '',
            service_id: serviceEl.value || '',
            appointment_type_id: acteEl.value || '',
            professional_id: proEl.value || '',
        });

        fetch('<?php echo e(route("public.appointment.slots.json")); ?>?' + params.toString())
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('slotsResults');
                const list = document.getElementById('slotsList');
                if (!data.available) {
                    container.style.display = 'block';
                    list.innerHTML = '<p class="text-muted">Aucun créneau disponible. Essayez d\'élargir vos dates.</p>';
                    return;
                }
                container.style.display = 'block';
                let html = '';
                data.dates.forEach(day => {
                    html += `<div class="slot-day"><h4>${day.formatted}</h4>`;
                    const slots = Array.isArray(day.slots) ? day.slots : (day.slots ? Object.values(day.slots) : []);
                    slots.forEach(slot => {
                        html += `<span class="slot-badge" data-pro="${slot.professional_id || ''}" data-time="${slot.first_slot?.start_time?.substring(0,5) || ''}">${slot.professional_name || 'Spécialiste'} — ${slot.first_slot?.start_time?.substring(0,5) || 'N/A'}</span>`;
                    });
                    html += '</div>';
                });
                list.innerHTML = html;
            })
            .catch(() => {});
    }

    serviceEl?.addEventListener('change', triggerSearch);
    acteEl?.addEventListener('change', triggerSearch);
    proEl?.addEventListener('change', triggerSearch);
    dateFrom?.addEventListener('change', triggerSearch);
    dateTo?.addEventListener('change', triggerSearch);

    setTimeout(triggerSearch, 500);
});
</script>
<?php /**PATH E:\xamp8.1\htdocs\medical\Modules\Appointment\Providers/../Resources/views/public/search.blade.php ENDPATH**/ ?>