<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f5d4d">
    <link rel="manifest" href="/manifest.webmanifest">
    <title><?php echo e(__('queue.login')); ?> — MedOffice</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/app.scss', 'resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="login-page">
<div class="login-wrap">
    <div class="login-brand-panel">
        <div class="brand-content">
            <div class="brand-icon">
                <svg viewBox="0 0 40 40" fill="none" width="48" height="48">
                    <rect width="40" height="40" rx="10" fill="rgba(255,255,255,0.15)"/>
                    <path d="M20 8v24M8 20h24" stroke="#fff" stroke-width="3.5" stroke-linecap="round"/>
                    <circle cx="20" cy="20" r="14" stroke="rgba(255,255,255,0.3)" stroke-width="1.5" fill="none"/>
                </svg>
            </div>
            <h1 class="brand-name">MedOffice</h1>
            <p class="brand-tagline">Gestion de cabinet médical<br>simplifiée et intelligente</p>
            <div class="brand-features">
                <div class="feature-item">
                    <i class="ti ti-calendar-check"></i>
                    <span>Planning intelligent</span>
                </div>
                <div class="feature-item">
                    <i class="ti ti-users"></i>
                    <span>File d'attente temps réel</span>
                </div>
                <div class="feature-item">
                    <i class="ti ti-file-medical"></i>
                    <span>Dossier patient numérique</span>
                </div>
            </div>
            <div class="brand-footer">
                <a href="<?php echo e(route('public.appointment.landing')); ?>" class="brand-link">
                    <i class="ti ti-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </div>
        <div class="brand-decor">
            <div class="decor-circle c1"></div>
            <div class="decor-circle c2"></div>
            <div class="decor-circle c3"></div>
        </div>
    </div>

    <div class="login-form-panel">
        <div class="form-container">
            <div class="form-header">
                <h2 class="form-title"><?php echo e(__('queue.login')); ?></h2>
                <p class="form-subtitle"><?php echo e(__('queue.login_hint')); ?></p>
            </div>

            <form method="POST" action="<?php echo e(route('login.attempt')); ?>" autocomplete="off" novalidate>
                <?php echo csrf_field(); ?>

                <div class="field-group">
                    <label class="field-label" for="email">Email</label>
                    <div class="field-input-wrap">
                        <i class="ti ti-mail field-icon"></i>
                        <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" required
                               class="field-input" placeholder="exemple@cabinet.fr">
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label" for="password"><?php echo e(__('queue.password')); ?></label>
                    <div class="field-input-wrap">
                        <i class="ti ti-lock field-icon"></i>
                        <input id="password" name="password" type="password" required
                               class="field-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                        <button type="button" class="field-toggle" onclick="togglePassword()" tabindex="-1">
                            <i class="ti ti-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <label class="check-line">
                    <input type="checkbox" name="remember">
                    <span><?php echo e(__('queue.remember_me')); ?></span>
                </label>

                <button type="submit" class="submit-btn">
                    <span><?php echo e(__('queue.login')); ?></span>
                    <i class="ti ti-arrow-right"></i>
                </button>
            </form>

            <?php if(isset($errors) && $errors->any()): ?>
                <div class="error-msg">
                    <i class="ti ti-alert-circle"></i>
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <div class="demo-creds">
                <div class="creds-header">
                    <i class="ti ti-info-circle"></i>
                    <span>Identifiants de démonstration</span>
                </div>
                <div class="creds-grid">
                    <span><strong>Admin</strong> admin@queue.local / password</span>
                    <span><strong>Agent</strong> agent1@CITY-001.local / password</span>
                    <span><strong>Pro</strong> pro@rdv.local / password</span>
                    <span><strong>Secrétaire</strong> secretary@rdv.local / password</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        pwd.type = 'password';
        icon.className = 'ti ti-eye';
    }
}
</script>

<style>
.login-page {
    margin: 0;
    min-height: 100vh;
    background: #f8f6f2;
    font-family: 'Manrope', 'Segoe UI', sans-serif;
    color: #171411;
    line-height: 1.45;
}
.login-page * { box-sizing: border-box; }

.login-wrap {
    display: flex;
    min-height: 100vh;
}

/* ─── LEFT BRAND PANEL ─── */
.login-brand-panel {
    flex: 0 0 480px;
    background: linear-gradient(160deg, #0f5d4d 0%, #0a493c 40%, #083d32 100%);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    overflow: hidden;
}

.brand-content {
    position: relative;
    z-index: 2;
    max-width: 340px;
}

.brand-icon { margin-bottom: 1.5rem; }

.brand-name {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 2.2rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 0.5rem;
    letter-spacing: -0.5px;
}

.brand-tagline {
    font-size: 1rem;
    color: rgba(255,255,255,0.7);
    line-height: 1.6;
    margin: 0 0 2.5rem;
}

.brand-features {
    display: grid;
    gap: 0.85rem;
    margin-bottom: 2.5rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: rgba(255,255,255,0.85);
    font-size: 0.9rem;
}

.feature-item i {
    font-size: 1.2rem;
    color: rgba(255,255,255,0.5);
}

.brand-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.brand-link:hover { color: #fff; }

/* decorative circles */
.brand-decor { position: absolute; inset: 0; pointer-events: none; z-index: 1; }
.decor-circle {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.06);
}
.c1 { width: 500px; height: 500px; top: -150px; right: -200px; }
.c2 { width: 300px; height: 300px; bottom: -80px; left: -100px; }
.c3 { width: 180px; height: 180px; top: 50%; left: -60px; }

/* ─── RIGHT FORM PANEL ─── */
.login-form-panel {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.form-container {
    width: 100%;
    max-width: 400px;
    animation: loginFadeIn 0.6s ease;
}

.form-header { margin-bottom: 2rem; }

.form-title {
    font-family: 'Space Grotesk', 'Manrope', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #171411;
    margin: 0 0 0.35rem;
}

.form-subtitle {
    color: #6b6258;
    font-size: 0.95rem;
    margin: 0;
}

/* form fields */
.field-group { margin-bottom: 1.25rem; }

.field-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #171411;
    margin-bottom: 0.4rem;
}

.field-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.field-icon {
    position: absolute;
    left: 1rem;
    font-size: 1.15rem;
    color: #9ca3af;
    pointer-events: none;
    z-index: 1;
}

.field-input {
    width: 100%;
    padding: 0.8rem 1rem 0.8rem 2.8rem;
    border: 1.5px solid #e5e2dc;
    border-radius: 12px;
    font-size: 0.9rem;
    font-family: inherit;
    color: #171411;
    background: #fff;
    transition: all 0.25s;
    outline: none;
}

.field-input:focus {
    border-color: #0f5d4d;
    box-shadow: 0 0 0 4px rgba(15, 93, 77, 0.1);
}

.field-input::placeholder { color: #b0a89e; }

.field-toggle {
    position: absolute;
    right: 0.75rem;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    font-size: 1.1rem;
    z-index: 1;
}

.field-toggle:hover { color: #6b6258; }

/* checkbox */
.check-line {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.88rem;
    color: #6b6258;
    cursor: pointer;
    margin-bottom: 1.5rem;
}

.check-line input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: #0f5d4d;
    border-radius: 4px;
}

/* submit button */
.submit-btn {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem 1.5rem;
    background: linear-gradient(135deg, #0f5d4d, #0a493c);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 16px rgba(15, 93, 77, 0.25);
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(15, 93, 77, 0.35);
}

.submit-btn:active { transform: translateY(0); }

.submit-btn i { font-size: 1.1rem; transition: transform 0.3s; }
.submit-btn:hover i { transform: translateX(4px); }

/* error */
.error-msg {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    color: #b91c1c;
    font-size: 0.85rem;
}

.error-msg i { font-size: 1.1rem; flex-shrink: 0; }

/* demo credentials */
.demo-creds {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f8f6f2;
    border-radius: 12px;
    border: 1px solid #e5e2dc;
}

.creds-header {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b6258;
    margin-bottom: 0.5rem;
}

.creds-header i { font-size: 0.9rem; }

.creds-grid {
    display: grid;
    gap: 0.3rem;
    font-size: 0.78rem;
    color: #6b6258;
}

.creds-grid strong { color: #171411; }

/* animation */
@keyframes loginFadeIn {
    0% { opacity: 0; transform: translateY(12px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* responsive */
@media (max-width: 900px) {
    .login-wrap { flex-direction: column; }
    .login-brand-panel {
        flex: none;
        padding: 2rem 1.5rem;
        min-height: auto;
    }
    .brand-content { max-width: 100%; }
    .brand-name { font-size: 1.8rem; }
    .brand-features { display: none; }
    .login-form-panel { padding: 1.5rem; }
}

@media (max-width: 480px) {
    .login-brand-panel { padding: 1.5rem 1rem; }
    .brand-name { font-size: 1.5rem; }
    .brand-tagline { font-size: 0.9rem; margin-bottom: 0; }
    .login-form-panel { padding: 1rem; }
    .form-title { font-size: 1.3rem; }
}
</style>
</body>
</html>
<?php /**PATH E:\xamp8.1\htdocs\medical\Modules\Queue\Resources\views/auth/login.blade.php ENDPATH**/ ?>