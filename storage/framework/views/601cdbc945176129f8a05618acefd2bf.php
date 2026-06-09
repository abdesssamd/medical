<?php $__env->startSection('title', 'Admin Queue'); ?>
<?php $__env->startSection('page-title', 'Administration Queue'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-stack">
    
    <div class="welcome-card">
        <div class="welcome-text">
            <h2>🏥 Administration Queue</h2>
            <p><?php echo e($organization->name ?? 'Sélectionnez une organisation'); ?></p>
        </div>
        <div class="welcome-date">
            <div class="date-day"><?php echo e(now()->locale(app()->getLocale())->isoFormat('dddd D MMMM YYYY')); ?></div>
            <div class="date-time"><?php echo e(now()->format('H:i')); ?></div>
        </div>
    </div>

    
    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Organisation</label>
                <select class="select" name="organization_id" onchange="this.form.submit()">
                    <?php $__currentLoopData = $organizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($org->id); ?>" <?php if(($organization->id ?? null) === $org->id): echo 'selected'; endif; ?>><?php echo e($org->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">OK</button>
        </form>
    </section>

    <?php if($organization): ?>
    
    <div class="grid-stats">
        <div class="card stat-card stat-blue">
            <div class="stat-icon">🎫</div>
            <div class="stat-info">
                <div class="stat-label">Total aujourd'hui</div>
                <div class="stat-value"><?php echo e($stats['today_total'] ?? 0); ?></div>
            </div>
        </div>
        <div class="card stat-card stat-orange">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <div class="stat-label">En attente</div>
                <div class="stat-value"><?php echo e($stats['today_waiting'] ?? 0); ?></div>
            </div>
        </div>
        <div class="card stat-card stat-green">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-label">Servis</div>
                <div class="stat-value"><?php echo e($stats['today_served'] ?? 0); ?></div>
            </div>
        </div>
        <div class="card stat-card stat-red">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <div class="stat-label">Absents</div>
                <div class="stat-value"><?php echo e($stats['today_absent'] ?? 0); ?></div>
            </div>
        </div>
        <div class="card stat-card stat-purple">
            <div class="stat-icon">⏱️</div>
            <div class="stat-info">
                <div class="stat-label">Attente moyenne</div>
                <div class="stat-value"><?php echo e($stats['avg_wait_minutes'] ?? 0); ?> min</div>
            </div>
        </div>
    </div>

    
    <?php if(isset($serviceStats) && $serviceStats->count() > 0): ?>
    <section class="card">
        <h3 class="card-title">📊 Statistiques par service</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Total</th>
                        <th>Servis</th>
                        <th>Taux absent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $serviceStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><strong><?php echo e($row['service_name']); ?></strong></td>
                        <td><?php echo e($row['total']); ?></td>
                        <td><?php echo e($row['served']); ?></td>
                        <td><?php echo e($row['absent_rate']); ?>%</td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    
    <?php if(isset($recentTickets) && $recentTickets->count() > 0): ?>
    <section class="card">
        <h3 class="card-title">🎫 Tickets récents</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Service</th>
                        <th>Statut</th>
                        <th>Guichet</th>
                        <th>Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $recentTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><code><?php echo e($ticket->ticket_number); ?></code></td>
                        <td><?php echo e($ticket->service?->name ?? '-'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo e($ticket->status === 'served' ? 'success' : ($ticket->status === 'waiting' ? 'warning' : ($ticket->status === 'absent' ? 'danger' : 'info'))); ?>">
                                <?php echo e($ticket->status); ?>

                            </span>
                        </td>
                        <td><?php echo e($ticket->counter?->name ?? '-'); ?></td>
                        <td><?php echo e($ticket->agent?->name ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    
    <section class="card">
        <h3 class="card-title">⚡ Actions rapides</h3>
        <div class="quick-cards">
            <a href="<?php echo e(route('admin.statistics', ['organization_id' => $organization->id])); ?>" class="quick-card quick-blue">
                <span class="quick-icon">📊</span>
                <span class="quick-label">Statistiques</span>
                <span class="quick-desc">Rapports détaillés</span>
            </a>
            <a href="<?php echo e(route('admin.history', ['organization_id' => $organization->id])); ?>" class="quick-card quick-green">
                <span class="quick-icon">📜</span>
                <span class="quick-label">Historique</span>
                <span class="quick-desc">Tous les tickets</span>
            </a>
            <a href="<?php echo e(route('admin.users')); ?>" class="quick-card quick-orange">
                <span class="quick-icon">👥</span>
                <span class="quick-label">Utilisateurs</span>
                <span class="quick-desc">Gestion des comptes</span>
            </a>
            <a href="<?php echo e(route('admin.counters')); ?>" class="quick-card quick-purple">
                <span class="quick-icon">🏢</span>
                <span class="quick-label">Guichets</span>
                <span class="quick-desc">Configuration</span>
            </a>
            <a href="<?php echo e(route('admin.kiosks')); ?>" class="quick-card quick-blue">
                <span class="quick-icon">🖥️</span>
                <span class="quick-label">Bornes</span>
                <span class="quick-desc">Gestion des kiosques</span>
            </a>
            <a href="<?php echo e(route('admin.screens')); ?>" class="quick-card quick-green">
                <span class="quick-icon">📺</span>
                <span class="quick-label">Écrans TV</span>
                <span class="quick-desc">Affichage public</span>
            </a>
            <a href="<?php echo e(route('admin.settings.questionnaires')); ?>" class="quick-card quick-orange">
                <span class="quick-icon">📝</span>
                <span class="quick-label">Questionnaires</span>
                <span class="quick-desc">Administration des paramètres</span>
            </a>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\Modules\Queue\Providers/../Resources/views/admin/dashboard-new.blade.php ENDPATH**/ ?>