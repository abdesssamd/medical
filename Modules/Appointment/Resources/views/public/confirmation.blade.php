<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f5d4d">
    <title>Demande envoyée — MedOffice</title>
    @vite(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="search-shell">
    <header class="search-header">
        <a href="{{ route('public.appointment.landing') }}" class="brand text-decoration-none">MedOffice</a>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-user"></i> Connexion
        </a>
    </header>

    <div class="search-container" style="display:flex;align-items:center;justify-content:center;min-height:50vh;">
        <div class="text-center" style="max-width:500px;">
            <div style="font-size:4rem;color:var(--success);margin-bottom:1rem;">
                <i class="ti ti-circle-check-filled"></i>
            </div>
            <h1 style="font-family:'Space Grotesk',sans-serif;color:var(--text);margin-bottom:0.5rem;">
                Demande envoyée !
            </h1>
            <p style="color:var(--muted);line-height:1.7;font-size:1.05rem;">
                Votre demande de rendez-vous a bien été reçue.<br>
                Notre équipe va l'examiner et vous contactera dans les plus brefs délais
                pour confirmer le créneau.
            </p>
            <div style="margin-top:2rem;">
                <a href="{{ route('public.appointment.landing') }}" class="btn-cta-primary">
                    <i class="ti ti-home"></i> Retour à l'accueil
                </a>
            </div>
        </div>
    </div>

    <footer class="landing-footer">
        <p>&copy; {{ date('Y') }} MedOffice — Gestion de cabinet médical</p>
    </footer>
</div>
</body>
</html>

<style>
.search-shell { min-height: 100vh; display: grid; grid-template-rows: auto 1fr auto; background: var(--bg); }
.search-header { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 2rem; border-bottom: 1px solid var(--line); background: var(--surface); }
.btn-cta-primary { display: inline-flex; align-items: center; gap: 0.5rem; background: var(--primary); color: #fff; padding: 0.85rem 1.8rem; border-radius: 14px; font-weight: 600; text-decoration: none; transition: all 0.3s; box-shadow: 0 8px 24px rgba(15,93,77,0.3); }
.btn-cta-primary:hover { background: var(--primary-strong); transform: translateY(-2px); box-shadow: 0 12px 32px rgba(15,93,77,0.4); }
.landing-footer { text-align: center; padding: 1.5rem; color: var(--muted); font-size: 0.9rem; border-top: 1px solid var(--line); }
</style>
