@extends('layouts.admin')

@section('title', __('queue.tv_management'))

@section('content')
<div x-data="{ createOpen: false, editId: null }" class="page-stack">
    <section class="card" style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
        <div>
            <h1 class="page-title">{{ __('queue.tv_management') }}</h1>
            <p style="color:var(--muted);margin-top:.2rem;">{{ __('queue.tv_management_hint') }}</p>
        </div>
        <button type="button" class="btn btn-primary" @click="createOpen = true">{{ __('queue.create_tv') }}</button>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>{{ __('queue.name') }}</th>
                    <th>{{ __('queue.tv_code') }}</th>
                    <th>{{ __('queue.select_organization') }}</th>
                    <th>{{ __('queue.services') }}</th>
                    <th>{{ __('queue.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($screens as $screen)
                    <tr>
                        <td>{{ $screen->name }}</td>
                        <td>
                            <strong>{{ $screen->code }}</strong><br>
                            <a href="{{ route('display.public.code', $screen->code) }}" target="_blank">{{ route('display.public.code', $screen->code) }}</a>
                        </td>
                        <td>{{ $screen->organization?->name }}</td>
                        <td>{{ $screen->services->pluck('name')->join(', ') ?: __('queue.all_services') }}</td>
                        <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
                            <button type="button" class="btn btn-soft" @click="editId = {{ $screen->id }}">{{ __('queue.edit') }}</button>
                            <form method="POST" action="{{ route('admin.screens.destroy', $screen) }}" onsubmit="return confirm('{{ __('queue.confirm_delete') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">{{ __('queue.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div x-show="createOpen" x-cloak style="position:fixed;inset:0;background:#02061799;display:grid;place-items:center;z-index:60;padding:1rem;" @click.self="createOpen = false">
        <div class="card" style="width:min(980px,96vw);max-height:92vh;overflow:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                <h2 class="section-title">{{ __('queue.create_tv') }}</h2>
                <button type="button" class="btn btn-soft" @click="createOpen = false">X</button>
            </div>
            <form method="POST" action="{{ route('admin.screens.store') }}" class="grid-two" enctype="multipart/form-data" style="margin-top:.8rem;">
                @csrf
                <div>
                    <label class="label">{{ __('queue.select_organization') }}</label>
                    <select class="select" name="organization_id" required>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="label">{{ __('queue.name') }}</label><input class="input" name="name" required></div>
                <div><label class="label">{{ __('queue.tv_code') }}</label><input class="input" name="code" placeholder="TV-HALL-01" required></div>
                <div><label class="label">{{ __('queue.location') }}</label><input class="input" name="location"></div>
                <div style="grid-column:1/-1;"><label class="label">{{ __('queue.video_url') }}</label><input class="input" name="video_url" placeholder="/videos/welcome.mp4"></div>
                <div style="grid-column:1/-1;"><label class="label">{{ __('queue.video_upload') }}</label><input class="input" type="file" name="video_file" accept=".mp4,.webm,.mov"></div>
                <div>
                    <label class="label">{{ __('queue.audio_order') }}</label>
                    <select class="select" name="audio_order">
                        <option value="fr_ar">FR puis AR</option>
                        <option value="ar_fr">AR puis FR</option>
                        <option value="fr_only">FR seulement</option>
                        <option value="ar_only">AR seulement</option>
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('queue.audio_repeat') }}</label>
                    <select class="select" name="audio_repeat">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
                <div>
                    <label class="label">{{ __('queue.tv_primary_color') }}</label>
                    <input class="input" type="color" name="tv_primary_color" value="#1D4ED8" style="width:90px;padding:.35rem;height:44px;">
                </div>
                <div>
                    <label class="label">{{ __('queue.tv_secondary_color') }}</label>
                    <input class="input" type="color" name="tv_secondary_color" value="#0F172A" style="width:90px;padding:.35rem;height:44px;">
                </div>
                <div style="grid-column:1/-1;display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;">
                    <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="audio_enabled" value="1" checked> {{ __('queue.audio_enabled') }}</label>
                    <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="adhkar_enabled" value="1"> {{ __('queue.adhkar_enabled') }}</label>
                    <button type="button" class="btn btn-soft" onclick="testAudioAnnouncement()">{{ __('queue.test_audio') }}</button>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="label">{{ __('queue.adhkar_text') }}</label>
                    <textarea class="textarea" name="adhkar_text" rows="3" placeholder="Subhan Allah | Alhamdulillah | Allahu Akbar"></textarea>
                </div>
                <div style="grid-column:1/-1;">
                    <label class="label">{{ __('queue.services') }} (multi)</label>
                    <select class="select" multiple size="6" name="service_ids[]">
                        @foreach($organizations as $org)
                            <optgroup label="{{ $org->name }}">
                                @foreach($org->services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:1/-1;display:flex;gap:.6rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-soft" @click="createOpen = false">{{ __('queue.cancel') }}</button>
                    <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($screens as $screen)
        <div x-show="editId === {{ $screen->id }}" x-cloak style="position:fixed;inset:0;background:#02061799;display:grid;place-items:center;z-index:60;padding:1rem;" @click.self="editId = null">
            <div class="card" style="width:min(980px,96vw);max-height:92vh;overflow:auto;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                    <h2 class="section-title">{{ __('queue.edit') }}: {{ $screen->name }}</h2>
                    <button type="button" class="btn btn-soft" @click="editId = null">X</button>
                </div>
                <form method="POST" action="{{ route('admin.screens.update', $screen) }}" class="grid-two" enctype="multipart/form-data" style="margin-top:.8rem;">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="label">{{ __('queue.select_organization') }}</label>
                        <select class="select" name="organization_id" required>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" @selected($screen->organization_id === $org->id)>{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label">{{ __('queue.name') }}</label><input class="input" name="name" value="{{ $screen->name }}" required></div>
                    <div><label class="label">{{ __('queue.tv_code') }}</label><input class="input" name="code" value="{{ $screen->code }}" required></div>
                    <div><label class="label">{{ __('queue.location') }}</label><input class="input" name="location" value="{{ $screen->location }}"></div>
                    <div style="grid-column:1/-1;"><label class="label">{{ __('queue.video_url') }}</label><input class="input" name="video_url" value="{{ $screen->video_url }}"></div>
                    <div style="grid-column:1/-1;"><label class="label">{{ __('queue.video_upload') }}</label><input class="input" type="file" name="video_file" accept=".mp4,.webm,.mov"></div>
                    <div>
                        <label class="label">{{ __('queue.audio_order') }}</label>
                        <select class="select" name="audio_order">
                            <option value="fr_ar" @selected($screen->audio_order === 'fr_ar')>FR puis AR</option>
                            <option value="ar_fr" @selected($screen->audio_order === 'ar_fr')>AR puis FR</option>
                            <option value="fr_only" @selected($screen->audio_order === 'fr_only')>FR seulement</option>
                            <option value="ar_only" @selected($screen->audio_order === 'ar_only')>AR seulement</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('queue.audio_repeat') }}</label>
                        <select class="select" name="audio_repeat">
                            <option value="1" @selected($screen->audio_repeat === 1)>1</option>
                            <option value="2" @selected($screen->audio_repeat === 2)>2</option>
                            <option value="3" @selected($screen->audio_repeat === 3)>3</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('queue.tv_primary_color') }}</label>
                        <input class="input" type="color" name="tv_primary_color" value="{{ $screen->tv_primary_color ?? '#1D4ED8' }}" style="width:90px;padding:.35rem;height:44px;">
                    </div>
                    <div>
                        <label class="label">{{ __('queue.tv_secondary_color') }}</label>
                        <input class="input" type="color" name="tv_secondary_color" value="{{ $screen->tv_secondary_color ?? '#0F172A' }}" style="width:90px;padding:.35rem;height:44px;">
                    </div>
                    <div style="grid-column:1/-1;display:flex;gap:.7rem;align-items:center;flex-wrap:wrap;">
                        <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="audio_enabled" value="1" @checked($screen->audio_enabled)> {{ __('queue.audio_enabled') }}</label>
                        <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="adhkar_enabled" value="1" @checked($screen->adhkar_enabled)> {{ __('queue.adhkar_enabled') }}</label>
                        <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="is_active" value="1" @checked($screen->is_active)> {{ __('queue.active') }}</label>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label class="label">{{ __('queue.adhkar_text') }}</label>
                        <textarea class="textarea" name="adhkar_text" rows="3">{{ $screen->adhkar_text }}</textarea>
                    </div>
                    <div style="grid-column:1/-1;">
                        <label class="label">{{ __('queue.services') }} (multi)</label>
                        <select class="select" multiple size="6" name="service_ids[]">
                            @foreach($screen->organization->services as $service)
                                <option value="{{ $service->id }}" @selected($screen->services->contains('id', $service->id))>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="grid-column:1/-1;display:flex;gap:.6rem;justify-content:flex-end;">
                        <button type="button" class="btn btn-soft" @click="editId = null">{{ __('queue.cancel') }}</button>
                        <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>

<script>
function testAudioAnnouncement() {
    if (!('speechSynthesis' in window)) return;
    const fr = new SpeechSynthesisUtterance('Numero A 12, guichet 3');
    fr.lang = 'fr-FR';
    const ar = new SpeechSynthesisUtterance('Arqam A 12, chobbak 3');
    ar.lang = 'ar-MA';
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(fr);
    setTimeout(() => window.speechSynthesis.speak(ar), 1500);
}
</script>
@endsection



