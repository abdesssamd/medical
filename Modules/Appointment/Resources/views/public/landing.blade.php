<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f5d4d">
    <title>MedOffice — Prenez rendez-vous en ligne</title>
    @vite(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="landing-shell">
    <header class="landing-nav">
        <span class="brand">{{ __('queue.app_name') }}</span>
        <nav class="landing-links">
            <a href="{{ route('public.appointment.search') }}" class="btn btn-primary">
                <i class="ti ti-calendar-search"></i> Rechercher un rendez-vous
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                <i class="ti ti-user"></i> Connexion
            </a>
        </nav>
    </header>

    <section class="landing-hero">
        <div class="landing-hero-content">
            <h1>Votre santé, <br><span class="text-accent">simplifiée.</span></h1>
            <p>Prenez rendez-vous avec nos spécialistes en quelques clics.<br>
               Consultation, suivi, urgence — nous sommes là pour vous.</p>
            <div class="landing-cta">
                <a href="{{ route('public.appointment.search') }}" class="btn-cta-primary">
                    <i class="ti ti-calendar-plus"></i> Prendre rendez-vous
                </a>
                <a href="{{ route('login') }}" class="btn-cta-secondary">
                    Espace professionnel
                </a>
            </div>
        </div>
        <div class="landing-hero-visual">
            <div class="hero-illustration">
                <i class="ti ti-heart-rate-monitor"></i>
            </div>
        </div>
    </section>

    <section class="landing-services">
        <h2>Nos services</h2>
        <div class="services-grid">
            @forelse($services as $service)
                <div class="service-card">
                    <div class="service-icon"><i class="ti ti-stethoscope"></i></div>
                    <h3>{{ $service->name }}</h3>
                    <p class="text-muted">{{ $service->name_ar ?? 'Consultation spécialisée' }}</p>
                </div>
            @empty
                <p class="text-muted">Aucun service disponible pour le moment.</p>
            @endforelse
        </div>
    </section>

    <footer class="landing-footer">
        <p>&copy; {{ date('Y') }} MedOffice — Gestion de cabinet médical</p>
    </footer>
</div>
</body>
</html>

<style>
.landing-shell {
    min-height: 100vh;
    display: grid;
    grid-template-rows: auto 1fr auto auto;
    background: var(--bg);
}
.landing-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 2rem;
    border-bottom: 1px solid var(--line);
    background: var(--surface);
}
.landing-links { display: flex; gap: 0.75rem; }
.landing-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    padding: 4rem 2rem;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}
.landing-hero-content h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 3rem;
    font-weight: 700;
    line-height: 1.15;
    color: var(--text);
    margin-bottom: 1rem;
}
.text-accent { color: var(--primary); }
.landing-hero-content p {
    font-size: 1.1rem;
    color: var(--muted);
    margin-bottom: 2rem;
    line-height: 1.7;
}
.landing-cta { display: flex; gap: 1rem; flex-wrap: wrap; }
.btn-cta-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--primary);
    color: #fff;
    padding: 0.85rem 1.8rem;
    border-radius: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba(15, 93, 77, 0.3);
}
.btn-cta-primary:hover {
    background: var(--primary-strong);
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(15, 93, 77, 0.4);
}
.btn-cta-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface);
    color: var(--text);
    padding: 0.85rem 1.8rem;
    border-radius: 14px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid var(--line);
    transition: all 0.3s;
}
.btn-cta-secondary:hover { background: var(--surface-soft); }
.landing-hero-visual { display: flex; justify-content: center; align-items: center; }
.hero-illustration {
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #0a493c);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 40px 80px rgba(15, 93, 77, 0.25);
}
.hero-illustration i { font-size: 8rem; color: rgba(255,255,255,0.9); }
.landing-services {
    padding: 3rem 2rem;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}
.landing-services h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
    color: var(--text);
}
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.service-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 20px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s;
}
.service-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow);
}
.service-icon i { font-size: 2rem; color: var(--primary); margin-bottom: 0.75rem; }
.service-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 0.25rem; }
.landing-footer {
    text-align: center;
    padding: 1.5rem;
    color: var(--muted);
    font-size: 0.9rem;
    border-top: 1px solid var(--line);
}
@media (max-width: 768px) {
    .landing-hero { grid-template-columns: 1fr; text-align: center; padding: 2rem 1rem; }
    .landing-hero-content h1 { font-size: 2rem; }
    .landing-cta { justify-content: center; }
    .hero-illustration { width: 200px; height: 200px; }
    .hero-illustration i { font-size: 5rem; }
}
</style>
