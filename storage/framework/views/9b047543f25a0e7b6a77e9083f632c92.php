<?php $__env->startSection('title', __('queue.ticket_kiosk')); ?>

<?php $__env->startSection('content'); ?>
<div class="page-stack">
    <section class="card">
        <h1 class="page-title"><?php echo e(__('queue.ticket_kiosk')); ?></h1>
        <p class="muted" style="margin-bottom:.8rem;"><?php echo e(__('queue.touch_hint')); ?></p>

        <form method="GET" class="kiosk-org-form">
            <label class="label"><?php echo e(__('queue.select_organization')); ?></label>
            <select class="select kiosk-select" name="organization_id" onchange="this.form.submit()">
                <?php $__currentLoopData = $organizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($org->id); ?>" <?php if($organization?->id === $org->id): echo 'selected'; endif; ?>><?php echo e($org->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </form>

        <form id="ticketForm" method="POST" action="<?php echo e(route('tickets.store')); ?>" style="display:none;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="organization_id" value="<?php echo e($organization?->id); ?>">
            <input type="hidden" name="service_id" id="serviceIdInput">
            <input type="hidden" name="direct_print" value="1">
        </form>

        <div class="kiosk-service-grid">
            <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button" class="btn btn-primary kiosk-btn kiosk-service-btn" onclick="createTicket('<?php echo e($service->id); ?>', null)">
                    <span class="kiosk-service-name"><?php echo e($service->name); ?></span>
                    <span class="kiosk-service-line"><?php echo e(__('queue.ticket_code')); ?>: <?php echo e($service->prefix); ?></span>
                    <span class="kiosk-service-line">
                        <?php echo e(__('queue.realtime_eta')); ?>:
                        <?php echo e($realtimeEtaByService[$service->id]['eta_for_new_ticket'] ?? ($service->average_service_minutes)); ?>

                        <?php echo e(__('queue.minutes')); ?>

                    </span>
                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title"><?php echo e(__('queue.appointment_booking')); ?></h2>
        <p class="muted" style="margin-bottom:.8rem;"><?php echo e(__('queue.appointment_hint')); ?></p>
        <form id="appointmentForm" onsubmit="return submitAppointment(event)" class="kiosk-service-grid">
            <div>
                <label class="label"><?php echo e(__('queue.select_service')); ?></label>
                <select class="select" id="appointmentService" required>
                    <option value=""><?php echo e(__('queue.select_service')); ?></option>
                    <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($service->id); ?>"><?php echo e($service->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="label"><?php echo e(__('queue.appointment_time')); ?></label>
                <input class="input" type="datetime-local" id="appointmentAt" required>
            </div>
            <div>
                <button class="btn btn-accent touch-btn" type="submit"><?php echo e(__('queue.create_appointment_ticket')); ?></button>
            </div>
        </form>
    </section>

    <section class="card toolbar">
        <div class="display-open-box">
            <h2 class="section-title"><?php echo e(__('queue.public_display')); ?></h2>
            <p class="muted"><?php echo e(__('queue.open_display')); ?></p>
            <div class="split-actions">
                <input id="tvCodeInput" class="input tv-input" placeholder="<?php echo e(__('queue.tv_code')); ?> : TV-HOSP-01">
                <button type="button" class="btn btn-soft" onclick="openTvByCode()"><?php echo e(__('queue.open_by_code')); ?></button>
            </div>
            <?php if(isset($screens) && $screens->count()): ?>
                <div class="split-actions">
                    <select id="tvCodeSelect" class="select tv-select">
                        <option value=""><?php echo e(__('queue.select_tv')); ?></option>
                        <?php $__currentLoopData = $screens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $screen): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($screen->code); ?>"><?php echo e($screen->name); ?> (<?php echo e($screen->code); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="button" class="btn btn-soft" onclick="openTvFromSelect()"><?php echo e(__('queue.open_selected_tv')); ?></button>
                </div>
            <?php endif; ?>
        </div>
        <a class="btn btn-accent touch-btn" href="<?php echo e(route('display.open')); ?>" target="_blank"><?php echo e(__('queue.public_display')); ?></a>
    </section>
</div>

<script>
async function createTicket(serviceId, appointmentAt) {
    const form = document.getElementById('ticketForm');
    document.getElementById('serviceIdInput').value = serviceId;
    const data = new FormData(form);
    if (appointmentAt) {
        data.set('appointment_at', appointmentAt);
    } else {
        data.delete('appointment_at');
    }

    const res = await fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: data
    });

    if (!res.ok) {
        alert('Erreur lors de la creation du ticket');
        return;
    }

    const data = await res.json();
    if (!data.print_url) return;

    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = data.print_url;
    document.body.appendChild(iframe);
    setTimeout(() => iframe.remove(), 15000);
    setTimeout(() => window.location.reload(), 900);
}

function submitAppointment(event) {
    event.preventDefault();
    const serviceId = document.getElementById('appointmentService').value;
    const appointmentAt = document.getElementById('appointmentAt').value;
    if (!serviceId || !appointmentAt) return false;
    createTicket(serviceId, appointmentAt);
    return false;
}

function openTvByCode() {
    const code = (document.getElementById('tvCodeInput').value || '').trim().toUpperCase();
    if (!code) return;
    window.open(`/display/code/${encodeURIComponent(code)}`, '_blank');
}

function openTvFromSelect() {
    const code = document.getElementById('tvCodeSelect')?.value;
    if (!code) return;
    window.open(`/display/code/${encodeURIComponent(code)}`, '_blank');
}
</script>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\Modules\Queue\Resources\views/tickets/create.blade.php ENDPATH**/ ?>