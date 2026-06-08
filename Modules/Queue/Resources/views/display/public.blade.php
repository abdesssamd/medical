<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ?? false ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('queue.public_display') }} - {{ $organization->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="tv-body {{ ($tvTemplate ?? 'classic') === 'split' ? 'tv-body-split' : '' }}" x-data="publicDisplay({ code: '{{ $screenCode }}', organizationId: {{ $organization->id }}, initialVideo: @js($videoUrl), initialTemplate: '{{ $tvTemplate ?? 'classic' }}', initialLogo: @js($tvLogoUrl ?? ''), initialInfoMessages: @js($tvInfoMessages ?? []) })" x-init="init()" x-bind:style="`--tv-primary:${tvPrimary};--tv-secondary:${tvSecondary};`">
    <header class="tv-header {{ ($tvTemplate ?? 'classic') === 'split' ? 'tv-header-split' : '' }}">
        <div class="tv-head-left">
            <template x-if="logoUrl">
                <img class="tv-logo" :src="logoUrl" alt="logo">
            </template>
            <div style="font-size:clamp(1.1rem,2vw,1.8rem);font-weight:900;">{{ $organization->name }}</div>
            <div style="opacity:.9;">{{ __('queue.public_display') }} <span x-show="screenCode">- <strong x-text="screenCode"></strong></span></div>
        </div>
        <div style="text-align:end;">
            <div style="font-size:1.35rem;font-weight:900;" x-text="clock"></div>
            <div style="font-size:.9rem;opacity:.9;" x-text="clockDate"></div>
            <div style="font-size:.9rem;opacity:.85;">{{ __('queue.last_update') }}: <span x-text="serverTime"></span></div>
        </div>
    </header>
    @if(($tvTemplate ?? 'classic') === 'split')
    <div class="tv-mode-badge">MODE SPLIT</div>
    @endif

    <div class="tv-info-rotator" x-show="currentInfoMessage">
        <span x-text="currentInfoMessage"></span>
    </div>

    @if(($tvTemplate ?? 'classic') === 'classic')
    <div class="tv-chip-row">
        <template x-for="s in serviceNames" :key="s">
            <span class="tv-chip" x-text="s"></span>
        </template>
    </div>

    <main class="tv-main">
        <section class="tv-card">
            <h2 class="tv-panel-title">{{ __('queue.active_calls') }}</h2>
            <template x-for="call in calls" :key="call.id">
                <div class="tv-call">
                    <div>
                        <div class="tv-ticket" x-text="call.ticket?.ticket_number"></div>
                        <div style="font-size:.85rem;opacity:.8" x-text="call.service?.name"></div>
                    </div>
                    <div class="tv-counter" x-text="call.counter?.name"></div>
                </div>
            </template>
        </section>

        <section class="tv-now">
            <div class="tv-now-main">
                <div style="opacity:.95;font-size:1.08rem;font-weight:800;letter-spacing:.8px;">{{ __('queue.current_call') }}</div>
                <div class="tv-ticket-big" x-text="currentCall.ticket?.ticket_number ?? '---'"></div>
                <div class="tv-counter-big" x-text="currentCall.counter?.name ?? '---'"></div>
                <div style="opacity:.85" x-text="currentCall.service?.name ?? ''"></div>
            </div>

            <div class="tv-media">
                <template x-if="videoUrl">
                    <video x-bind:src="videoUrl" autoplay muted loop playsinline></video>
                </template>
                <template x-if="!videoUrl">
                    <div class="tv-media-placeholder">{{ __('queue.no_media') }}</div>
                </template>
            </div>
        </section>

        <section class="tv-card">
            <h2 class="tv-panel-title">{{ __('queue.waiting_by_service') }}</h2>
            <template x-for="item in waiting" :key="item.service">
                <div class="tv-call">
                    <div style="font-weight:700;" x-text="item.service"></div>
                    <div style="font-size:1.4rem;font-weight:900;" x-text="item.count"></div>
                </div>
            </template>

            <div class="tv-stat-box" style="margin-top:.8rem;">
                <div class="tv-stat"><div style="opacity:.8">{{ __('queue.today_waiting') }}</div><b x-text="stats.waiting"></b></div>
                <div class="tv-stat"><div style="opacity:.8">{{ __('queue.active_calls') }}</div><b x-text="stats.calls"></b></div>
            </div>
        </section>
    </main>
    @else

    <main class="tv-split">
        <section class="tv-split-left-col">
            <div class="tv-split-board">
                <div class="tv-split-board-title">{{ __('queue.active_calls') }}</div>
                <template x-for="call in calls.slice(0, 8)" :key="call.id">
                    <div class="tv-split-board-row">
                        <div class="tv-split-board-ticket" x-text="call.ticket?.ticket_number ?? '--'"></div>
                        <div class="tv-split-board-counter" x-text="call.counter?.name ?? '--'"></div>
                    </div>
                </template>
            </div>
            <div class="tv-split-stats-grid">
                <div class="tv-split-kpi">
                    <div>{{ __('queue.today_waiting') }}</div>
                    <b x-text="stats.waiting"></b>
                </div>
                <div class="tv-split-kpi">
                    <div>{{ __('queue.active_calls') }}</div>
                    <b x-text="stats.calls"></b>
                </div>
            </div>
        </section>

        <section class="tv-split-right-col">
            <div class="tv-split-media-box">
                <template x-if="videoUrl">
                    <video x-bind:src="videoUrl" autoplay muted loop playsinline></video>
                </template>
                <template x-if="!videoUrl">
                    <div class="tv-media-placeholder">{{ __('queue.no_media') }}</div>
                </template>
            </div>

            <div class="tv-split-hero">
                <div class="tv-split-hero-head">{{ __('queue.current_call') }}</div>
                <div class="tv-split-hero-ticket" x-text="currentCall.ticket?.ticket_number ?? '---'"></div>
                <div class="tv-split-hero-counter" x-text="currentCall.counter?.name ?? '---'"></div>
                <div class="tv-split-hero-service" x-text="currentCall.service?.name ?? ''"></div>
            </div>

            <div class="tv-split-service-ticker">
                <span x-text="serviceNames.join('  |  ') || '{{ __('queue.waiting_by_service') }}'"></span>
            </div>
        </section>
    </main>
    @endif

    <div class="tv-footer-msg">
        <template x-if="adhkarEnabled && adhkarText">
            <div class="tv-adhkar-live">
                <span class="tv-adhkar-track" x-text="adhkarText + ' | ' + adhkarText + ' | ' + adhkarText"></span>
            </div>
        </template>
        <template x-if="!adhkarEnabled || !adhkarText">
            <span>{{ __('queue.tv_footer_message') }}</span>
        </template>
    </div>

<script>
function publicDisplay(config) {
    return {
        organizationId: config.organizationId,
        screenCode: config.code || '',
        calls: [],
        waiting: [],
        currentCall: {},
        serviceNames: [],
        stats: { waiting: 0, calls: 0 },
        audioEnabled: true,
        audioOrder: 'fr_ar',
        audioRepeat: 1,
        adhkarEnabled: false,
        adhkarText: '',
        serverTime: '',
        clock: '',
        clockDate: '',
        lastCallId: null,
        videoUrl: config.initialVideo || '',
        logoUrl: config.initialLogo || '',
        infoMessages: config.initialInfoMessages || [],
        infoIndex: 0,
        currentInfoMessage: '',
        tvPrimary: '#1D4ED8',
        tvSecondary: '#0F172A',
        tvTemplate: config.initialTemplate || 'classic',
        init() {
            this.tickClock();
            setInterval(() => this.tickClock(), 1000);
            this.rotateInfoMessage();
            setInterval(() => this.rotateInfoMessage(), 5000);
            this.refresh();
            setInterval(() => this.refresh(), 3000);
        },
        tickClock() {
            const d = new Date();
            this.clock = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            this.clockDate = d.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        },
        rotateInfoMessage() {
            if (!this.infoMessages.length) {
                this.currentInfoMessage = '';
                return;
            }
            this.currentInfoMessage = this.infoMessages[this.infoIndex % this.infoMessages.length];
            this.infoIndex = (this.infoIndex + 1) % this.infoMessages.length;
        },
        async refresh() {
            const endpoint = this.screenCode
                ? `/display/code/${this.screenCode}/status`
                : `/display/${this.organizationId}/status`;

            const res = await fetch(endpoint);
            const data = await res.json();
            this.calls = data.active_calls || [];
            this.waiting = data.waiting_by_service || [];
            this.serverTime = data.server_time || '';
            if (data.video_url) this.videoUrl = data.video_url;
            this.audioEnabled = data.audio_enabled ?? true;
            this.audioOrder = data.audio_order || 'fr_ar';
            this.audioRepeat = Number(data.audio_repeat || 1);
            this.adhkarEnabled = data.adhkar_enabled ?? false;
            this.adhkarText = data.adhkar_text || '';
            this.tvPrimary = data.tv_primary_color || '#1D4ED8';
            this.tvSecondary = data.tv_secondary_color || '#0F172A';
            this.tvTemplate = data.tv_template || this.tvTemplate;
            this.logoUrl = data.tv_logo_url || this.logoUrl;
            this.infoMessages = data.tv_info_messages || this.infoMessages;

            this.currentCall = this.calls[0] || {};
            this.stats.waiting = this.waiting.reduce((sum, i) => sum + Number(i.count || 0), 0);
            this.stats.calls = this.calls.length;
            this.serviceNames = this.waiting.map(i => i.service).filter(Boolean).slice(0, 8);

            const newest = this.calls[0];
            if (newest && this.lastCallId !== newest.id) {
                this.lastCallId = newest.id;
                this.speakCall(newest.voice_payload);
            }
        },
        speakCall(payload) {
            if (!payload || !this.audioEnabled) return;
            const frText = payload.fr || '';
            const arText = payload.ar || '';

            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const sequence = [];
                if (this.audioOrder === 'fr_only') sequence.push({ text: frText, lang: 'fr-FR' });
                if (this.audioOrder === 'ar_only') sequence.push({ text: arText, lang: 'ar-MA' });
                if (this.audioOrder === 'fr_ar') sequence.push({ text: frText, lang: 'fr-FR' }, { text: arText, lang: 'ar-MA' });
                if (this.audioOrder === 'ar_fr') sequence.push({ text: arText, lang: 'ar-MA' }, { text: frText, lang: 'fr-FR' });

                let delay = 0;
                for (let i = 0; i < this.audioRepeat; i++) {
                    sequence.forEach((item) => {
                        setTimeout(() => {
                            if (!item.text) return;
                            const utt = new SpeechSynthesisUtterance(item.text);
                            utt.lang = item.lang;
                            utt.rate = 0.97;
                            window.speechSynthesis.speak(utt);
                        }, delay);
                        delay += 1500;
                    });
                }
                return;
            }

            const fallbackFr = new Audio('/audio/fr/announcement.mp3');
            const fallbackAr = new Audio('/audio/ar/announcement.mp3');
            fallbackFr.play().then(() => setTimeout(() => fallbackAr.play(), 1200)).catch(() => {});
        }
    };
}
</script>
</body>
</html>


