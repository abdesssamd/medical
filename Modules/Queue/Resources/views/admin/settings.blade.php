@extends('layouts.admin')

@section('title', __('queue.general_settings'))

@section('content')
<div class="settings-shell">
    <section class="card settings-hero">
        <div>
            <h1 class="page-title">{{ __('queue.general_settings') }}</h1>
            <p class="text-secondary mb-0">Parametrage du cabinet, des protocoles et de l'affichage TV sur une seule vue.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-secondary" href="{{ route('admin.settings.questionnaires') }}">Administration questionnaires</a>
            <a class="btn btn-outline-primary" href="{{ route('care.module1.index') }}">Retour module 1</a>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="settings-form">
        @csrf

        <x-tabler-card title="En-tete document et TV">
            <div class="row g-3">
                <div class="col-lg-6">
                    <x-tabler-input name="cabinet_dsp" label="DSP du cabinet" icon="file-text" :value="$cabinetDsp" placeholder="DSP-2026-MEDIOFFICE" />
                </div>
                <div class="col-lg-6">
                    <x-tabler-input name="cabinet_address" label="Adresse du cabinet" icon="map-pin" :value="$cabinetAddress" placeholder="Adresse complete" />
                </div>
                <div class="col-lg-6">
                    <x-tabler-input name="cabinet_logo_url" label="Logo cabinet (URL)" icon="photo" :value="$cabinetLogoUrl" placeholder="/storage/logos/logo.png" />
                </div>
                <div class="col-lg-6">
                    <label class="form-label">Logo cabinet (fichier)</label>
                    <input class="form-control" type="file" name="cabinet_logo_file" accept=".png,.jpg,.jpeg,.webp,.svg">
                </div>
                <div class="col-lg-4">
                    <x-tabler-select name="tv_display_template" label="Template TV" icon="screen-share">
                        <option value="classic" @selected($defaultTvTemplate === 'classic')>Classique</option>
                        <option value="split" @selected($defaultTvTemplate === 'split')>Split</option>
                    </x-tabler-select>
                </div>
                <div class="col-lg-8">
                    <x-tabler-input name="tv_logo_url" label="Logo TV (URL)" icon="device-tv" :value="$tvLogoUrl" placeholder="/logos/logo.png" />
                </div>
                <div class="col-12">
                    <x-tabler-textarea name="tv_info_messages" label="Messages TV" icon="message-circle" rows="4" :value="$tvInfoMessages" placeholder="Message 1&#10;Message 2" />
                </div>
            </div>
        </x-tabler-card>

        <x-tabler-card title="Fauteuils et utilisateurs" class="mt-3">
            <div class="row g-3">
                <div class="col-lg-4">
                    <x-tabler-input name="cabinet_chair_count" label="Nombre de fauteuils" icon="chair" type="number" :value="$cabinetChairCount" />
                </div>
                <div class="col-lg-8">
                    <x-tabler-textarea name="cabinet_chairs" label="Libelles fauteuils" icon="list" rows="4" :value="implode("\n", $cabinetChairs)" placeholder="Fauteuil 1&#10;Fauteuil 2" />
                </div>
                <div class="col-12">
                    <x-tabler-select name="cabinet_user_ids[]" label="Utilisateurs du cabinet" icon="users" multiple size="6" class="form-select">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(in_array($user->id, $cabinetUserIds, true))>{{ $user->name }} ({{ $user->role }})</option>
                        @endforeach
                    </x-tabler-select>
                </div>
            </div>
        </x-tabler-card>

        <x-tabler-card title="Protocoles et alertes métier" class="mt-3">
            <div class="row g-3">
                <div class="col-lg-6">
                    <x-tabler-textarea name="favorite_protocols" label="Protocoles favoris" icon="pill" rows="5" :value="implode("\n", $favoriteProtocols)" placeholder="Protocole prophylaxie&#10;Protocole chirurgie" />
                </div>
                <div class="col-lg-6">
                    <x-tabler-textarea name="consultation_motifs" label="Motifs de consultation personnalisés" icon="stethoscope" rows="5" :value="implode("\n", $consultationMotifs)" placeholder="Douleur&#10;Détartrage&#10;Urgence" />
                </div>
                <div class="col-lg-4">
                    <x-tabler-input name="stock_alert_threshold" label="Seuil stock critique" icon="alert-triangle" type="number" :value="$stockAlertThreshold" />
                </div>
                <div class="col-lg-8">
                    <x-tabler-textarea name="stock_alert_items" label="Articles sous surveillance" icon="package" rows="4" :value="implode("\n", $stockAlertItems)" placeholder="Amoxicilline&#10;Anesthesique&#10;Gants" />
                </div>
            </div>
        </x-tabler-card>

        <div class="settings-actions mt-3">
            <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
        </div>
    </form>
</div>
@endsection

@push('head')
<style>
.settings-shell{display:grid;gap:14px}
.settings-hero{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px}
.settings-form{display:grid;gap:0}
.settings-actions{display:flex;justify-content:flex-end}
</style>
@endpush


