<?php $__env->startSection('title', __('queue.general_settings')); ?>

<?php $__env->startSection('content'); ?>
<div class="settings-shell">
    <section class="card settings-hero">
        <div>
            <h1 class="page-title"><?php echo e(__('queue.general_settings')); ?></h1>
            <p class="text-secondary mb-0">Parametrage du cabinet, des protocoles et de l'affichage TV sur une seule vue.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-secondary" href="<?php echo e(route('admin.settings.questionnaires')); ?>">Administration questionnaires</a>
            <a class="btn btn-outline-primary" href="<?php echo e(route('admin.planning.settings')); ?>">Paramétrage des plannings</a>
            <a class="btn btn-outline-primary" href="<?php echo e(route('care.module1.index')); ?>">Retour module 1</a>
        </div>
    </section>

    <form method="POST" action="<?php echo e(route('admin.settings.update')); ?>" enctype="multipart/form-data" class="settings-form">
        <?php echo csrf_field(); ?>

        <?php if (isset($component)) { $__componentOriginal5ade941a940bdd0f874fd07e8e3383d1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-card','data' => ['title' => 'En-tete document et TV']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'En-tete document et TV']); ?>
            <div class="row g-3">
                <div class="col-lg-6">
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'cabinet_dsp','label' => 'DSP du cabinet','icon' => 'file-text','value' => $cabinetDsp,'placeholder' => 'DSP-2026-MEDIOFFICE']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cabinet_dsp','label' => 'DSP du cabinet','icon' => 'file-text','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cabinetDsp),'placeholder' => 'DSP-2026-MEDIOFFICE']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'cabinet_address','label' => 'Adresse du cabinet','icon' => 'map-pin','value' => $cabinetAddress,'placeholder' => 'Adresse complete']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cabinet_address','label' => 'Adresse du cabinet','icon' => 'map-pin','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cabinetAddress),'placeholder' => 'Adresse complete']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'cabinet_logo_url','label' => 'Logo cabinet (URL)','icon' => 'photo','value' => $cabinetLogoUrl,'placeholder' => '/storage/logos/logo.png']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cabinet_logo_url','label' => 'Logo cabinet (URL)','icon' => 'photo','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cabinetLogoUrl),'placeholder' => '/storage/logos/logo.png']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Logo cabinet (fichier)</label>
                    <input class="form-control" type="file" name="cabinet_logo_file" accept=".png,.jpg,.jpeg,.webp,.svg">
                </div>
                <div class="col-lg-4">
                    <?php if (isset($component)) { $__componentOriginal89ea77aa8c2562594fba6dc2be507ff9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-select','data' => ['name' => 'tv_display_template','label' => 'Template TV','icon' => 'screen-share']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tv_display_template','label' => 'Template TV','icon' => 'screen-share']); ?>
                        <option value="classic" <?php if($defaultTvTemplate === 'classic'): echo 'selected'; endif; ?>>Classique</option>
                        <option value="split" <?php if($defaultTvTemplate === 'split'): echo 'selected'; endif; ?>>Split</option>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9)): ?>
<?php $attributes = $__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9; ?>
<?php unset($__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal89ea77aa8c2562594fba6dc2be507ff9)): ?>
<?php $component = $__componentOriginal89ea77aa8c2562594fba6dc2be507ff9; ?>
<?php unset($__componentOriginal89ea77aa8c2562594fba6dc2be507ff9); ?>
<?php endif; ?>
                </div>
                <div class="col-lg-8">
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'tv_logo_url','label' => 'Logo TV (URL)','icon' => 'device-tv','value' => $tvLogoUrl,'placeholder' => '/logos/logo.png']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tv_logo_url','label' => 'Logo TV (URL)','icon' => 'device-tv','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tvLogoUrl),'placeholder' => '/logos/logo.png']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                </div>
                <div class="col-12">
                    <?php if (isset($component)) { $__componentOriginal2c57230b8fafbe020fa6a0851fe82739 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2c57230b8fafbe020fa6a0851fe82739 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-textarea','data' => ['name' => 'tv_info_messages','label' => 'Messages TV','icon' => 'message-circle','rows' => '4','value' => $tvInfoMessages,'placeholder' => 'Message 1&#10;Message 2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tv_info_messages','label' => 'Messages TV','icon' => 'message-circle','rows' => '4','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tvInfoMessages),'placeholder' => 'Message 1&#10;Message 2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2c57230b8fafbe020fa6a0851fe82739)): ?>
<?php $attributes = $__attributesOriginal2c57230b8fafbe020fa6a0851fe82739; ?>
<?php unset($__attributesOriginal2c57230b8fafbe020fa6a0851fe82739); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2c57230b8fafbe020fa6a0851fe82739)): ?>
<?php $component = $__componentOriginal2c57230b8fafbe020fa6a0851fe82739; ?>
<?php unset($__componentOriginal2c57230b8fafbe020fa6a0851fe82739); ?>
<?php endif; ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1)): ?>
<?php $attributes = $__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1; ?>
<?php unset($__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5ade941a940bdd0f874fd07e8e3383d1)): ?>
<?php $component = $__componentOriginal5ade941a940bdd0f874fd07e8e3383d1; ?>
<?php unset($__componentOriginal5ade941a940bdd0f874fd07e8e3383d1); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal5ade941a940bdd0f874fd07e8e3383d1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-card','data' => ['title' => 'Fauteuils et utilisateurs','class' => 'mt-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Fauteuils et utilisateurs','class' => 'mt-3']); ?>
            <div class="row g-3">
                <div class="col-lg-4">
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'cabinet_chair_count','label' => 'Nombre de fauteuils','icon' => 'chair','type' => 'number','value' => $cabinetChairCount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cabinet_chair_count','label' => 'Nombre de fauteuils','icon' => 'chair','type' => 'number','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cabinetChairCount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                </div>
                <div class="col-lg-8">
                    <x-tabler-textarea name="cabinet_chairs" label="Libelles fauteuils" icon="list" rows="4" :value="implode("\n", $cabinetChairs)" placeholder="Fauteuil 1&#10;Fauteuil 2" />
                </div>
                <div class="col-12">
                    <?php if (isset($component)) { $__componentOriginal89ea77aa8c2562594fba6dc2be507ff9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-select','data' => ['name' => 'cabinet_user_ids[]','label' => 'Utilisateurs du cabinet','icon' => 'users','multiple' => true,'size' => '6','class' => 'form-select']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cabinet_user_ids[]','label' => 'Utilisateurs du cabinet','icon' => 'users','multiple' => true,'size' => '6','class' => 'form-select']); ?>
                        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($user->id); ?>" <?php if(in_array($user->id, $cabinetUserIds, true)): echo 'selected'; endif; ?>><?php echo e($user->name); ?> (<?php echo e($user->role); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9)): ?>
<?php $attributes = $__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9; ?>
<?php unset($__attributesOriginal89ea77aa8c2562594fba6dc2be507ff9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal89ea77aa8c2562594fba6dc2be507ff9)): ?>
<?php $component = $__componentOriginal89ea77aa8c2562594fba6dc2be507ff9; ?>
<?php unset($__componentOriginal89ea77aa8c2562594fba6dc2be507ff9); ?>
<?php endif; ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1)): ?>
<?php $attributes = $__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1; ?>
<?php unset($__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5ade941a940bdd0f874fd07e8e3383d1)): ?>
<?php $component = $__componentOriginal5ade941a940bdd0f874fd07e8e3383d1; ?>
<?php unset($__componentOriginal5ade941a940bdd0f874fd07e8e3383d1); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal5ade941a940bdd0f874fd07e8e3383d1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-card','data' => ['title' => 'Protocoles et alertes métier','class' => 'mt-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Protocoles et alertes métier','class' => 'mt-3']); ?>
            <div class="row g-3">
                <div class="col-lg-6">
                    <x-tabler-textarea name="favorite_protocols" label="Protocoles favoris" icon="pill" rows="5" :value="implode("\n", $favoriteProtocols)" placeholder="Protocole prophylaxie&#10;Protocole chirurgie" />
                </div>
                <div class="col-lg-6">
                    <x-tabler-textarea name="consultation_motifs" label="Motifs de consultation personnalisés" icon="stethoscope" rows="5" :value="implode("\n", $consultationMotifs)" placeholder="Douleur&#10;Détartrage&#10;Urgence" />
                </div>
                <div class="col-lg-4">
                    <?php if (isset($component)) { $__componentOriginalb2e58112aec649fe6720fd8615da95b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb2e58112aec649fe6720fd8615da95b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tabler-input','data' => ['name' => 'stock_alert_threshold','label' => 'Seuil stock critique','icon' => 'alert-triangle','type' => 'number','value' => $stockAlertThreshold]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tabler-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'stock_alert_threshold','label' => 'Seuil stock critique','icon' => 'alert-triangle','type' => 'number','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stockAlertThreshold)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $attributes = $__attributesOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__attributesOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb2e58112aec649fe6720fd8615da95b3)): ?>
<?php $component = $__componentOriginalb2e58112aec649fe6720fd8615da95b3; ?>
<?php unset($__componentOriginalb2e58112aec649fe6720fd8615da95b3); ?>
<?php endif; ?>
                </div>
                <div class="col-lg-8">
                    <x-tabler-textarea name="stock_alert_items" label="Articles sous surveillance" icon="package" rows="4" :value="implode("\n", $stockAlertItems)" placeholder="Amoxicilline&#10;Anesthesique&#10;Gants" />
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1)): ?>
<?php $attributes = $__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1; ?>
<?php unset($__attributesOriginal5ade941a940bdd0f874fd07e8e3383d1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5ade941a940bdd0f874fd07e8e3383d1)): ?>
<?php $component = $__componentOriginal5ade941a940bdd0f874fd07e8e3383d1; ?>
<?php unset($__componentOriginal5ade941a940bdd0f874fd07e8e3383d1); ?>
<?php endif; ?>

        <div class="settings-actions mt-3">
            <button class="btn btn-primary" type="submit"><?php echo e(__('queue.save')); ?></button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('head'); ?>
<style>
.settings-shell{display:grid;gap:14px}
.settings-hero{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px}
.settings-form{display:grid;gap:0}
.settings-actions{display:flex;justify-content:flex-end}
</style>
<?php $__env->stopPush(); ?>



<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xamp8.1\htdocs\medical\Modules\Queue\Resources\views/admin/settings.blade.php ENDPATH**/ ?>