import './bootstrap';
import Alpine from 'alpinejs';
import QRCode from 'qrcode';
import '@tabler/core/dist/js/tabler.min.js';

window.Alpine = Alpine;
window.QRCodeLib = QRCode;
Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

const SPA_CONTENT_SELECTOR = '#content-area';
const DYNAMIC_HEAD_ATTR = 'data-spa-head';
const DYNAMIC_SCRIPT_ATTR = 'data-spa-script';
const PATIENT_CONTEXT_KEY = 'care.selectedPatientContext';

function isSpaNavigationEnabled() {
    return Boolean(document.querySelector(SPA_CONTENT_SELECTOR));
}

function injectSpaStyles() {
    if (document.getElementById('spa-runtime-style')) return;
    const style = document.createElement('style');
    style.id = 'spa-runtime-style';
    style.textContent = `
        #spa-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            box-shadow: 0 0 12px rgba(37, 99, 235, 0.5);
            z-index: 3000;
            opacity: 0;
            transition: width .22s ease, opacity .2s ease;
        }
        [data-spa-content] {
            opacity: 1;
            transition: opacity .17s ease;
        }
        [data-spa-content].is-loading {
            opacity: .18;
        }
        #spa-toast-zone {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 3100;
            display: grid;
            gap: 8px;
            max-width: min(90vw, 360px);
        }
        .spa-toast {
            color: #f8fafc;
            background: #0f172a;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 10px;
            padding: 9px 12px;
            box-shadow: 0 10px 24px rgba(2, 6, 23, .22);
            font-size: .86rem;
            transform: translateY(8px);
            opacity: 0;
            transition: transform .2s ease, opacity .2s ease;
        }
        .spa-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .spa-toast.success { border-color: rgba(34, 197, 94, .4); }
        .spa-toast.error { border-color: rgba(239, 68, 68, .5); }
    `;
    document.head.appendChild(style);
}

function ensureProgressBar() {
    let bar = document.getElementById('spa-progress');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'spa-progress';
        document.body.appendChild(bar);
    }
    return bar;
}

let progressTimer = null;
function startProgress() {
    const bar = ensureProgressBar();
    bar.style.opacity = '1';
    bar.style.width = '14%';
    if (progressTimer) window.clearInterval(progressTimer);
    progressTimer = window.setInterval(() => {
        const current = Number.parseFloat(bar.style.width) || 14;
        if (current < 86) bar.style.width = `${current + 6}%`;
    }, 180);
}

function endProgress() {
    const bar = ensureProgressBar();
    if (progressTimer) {
        window.clearInterval(progressTimer);
        progressTimer = null;
    }
    bar.style.width = '100%';
    window.setTimeout(() => {
        bar.style.opacity = '0';
        bar.style.width = '0';
    }, 170);
}

function getToastZone() {
    let zone = document.getElementById('spa-toast-zone');
    if (!zone) {
        zone = document.createElement('div');
        zone.id = 'spa-toast-zone';
        document.body.appendChild(zone);
    }
    return zone;
}

function showToast(message, type = 'success') {
    const zone = getToastZone();
    const toast = document.createElement('div');
    toast.className = `spa-toast ${type}`;
    toast.textContent = message;
    zone.appendChild(toast);
    window.requestAnimationFrame(() => toast.classList.add('show'));
    window.setTimeout(() => {
        toast.classList.remove('show');
        window.setTimeout(() => toast.remove(), 200);
    }, 2600);
}

function parseHtml(html) {
    const parser = new DOMParser();
    return parser.parseFromString(html, 'text/html');
}

function updateHeadFromDocument(doc) {
    document.querySelectorAll(`[${DYNAMIC_HEAD_ATTR}]`).forEach((el) => el.remove());
    const title = doc.querySelector('title');
    if (title) document.title = title.textContent || document.title;

    doc.head.querySelectorAll('style').forEach((styleEl) => {
        const clone = styleEl.cloneNode(true);
        clone.setAttribute(DYNAMIC_HEAD_ATTR, '1');
        document.head.appendChild(clone);
    });
}

function updateHeaderFromDocument(doc) {
    const nextPretitle = doc.querySelector('.page-pretitle')?.textContent;
    const nextTitle = doc.querySelector('.page-title')?.textContent;
    const pretitleEl = document.querySelector('.page-pretitle');
    const titleEl = document.querySelector('.page-title');
    if (pretitleEl && typeof nextPretitle === 'string') pretitleEl.textContent = nextPretitle;
    if (titleEl && typeof nextTitle === 'string') titleEl.textContent = nextTitle;
}

function updateSidebarActiveLink(pathname) {
    document.querySelectorAll('.navbar-nav .nav-link, .sidebar-nav .nav-item').forEach((link) => {
        const href = link.getAttribute('href');
        if (!href) return;
        const isActive = new URL(href, window.location.origin).pathname === pathname;
        link.classList.toggle('active', isActive);
    });
}

function removeDynamicScripts() {
    document.querySelectorAll(`script[${DYNAMIC_SCRIPT_ATTR}]`).forEach((s) => s.remove());
}

async function runScriptsFromDocument(doc) {
    removeDynamicScripts();
    const scripts = Array.from(doc.body.querySelectorAll('script'));
    for (const sourceScript of scripts) {
        const script = document.createElement('script');
        script.setAttribute(DYNAMIC_SCRIPT_ATTR, '1');
        if (sourceScript.src) {
            script.src = sourceScript.src;
            script.async = false;
            await new Promise((resolve) => {
                script.onload = () => resolve();
                script.onerror = () => resolve();
                document.body.appendChild(script);
            });
            continue;
        }
        script.textContent = sourceScript.textContent || '';
        document.body.appendChild(script);
    }
}

function shouldHandleLink(anchor) {
    if (!anchor) return false;
    const href = anchor.getAttribute('href');
    if (!href || href.startsWith('#')) return false;
    if (anchor.target === '_blank' || anchor.hasAttribute('download')) return false;
    if (anchor.dataset.noSpa === 'true') return false;
    const url = new URL(href, window.location.origin);
    if (url.origin !== window.location.origin) return false;
    if (!url.pathname.startsWith('/care/module-') && !url.pathname.startsWith('/admin/') && !url.pathname.startsWith('/clinical/')) return false;
    return true;
}

function shouldHandleForm(form) {
    if (!form) return false;
    if (form.dataset.noSpa === 'true') return false;
    const action = form.getAttribute('action') || window.location.href;
    const url = new URL(action, window.location.origin);
    if (url.origin !== window.location.origin) return false;
    if (!url.pathname.startsWith('/care/module-') && !url.pathname.startsWith('/admin/') && !url.pathname.startsWith('/clinical/')) return false;
    if (form.target === '_blank') return false;
    return true;
}

function setLoadingState(isLoading) {
    const content = document.querySelector(SPA_CONTENT_SELECTOR);
    if (!content) return;
    content.classList.toggle('is-loading', isLoading);
}

async function fetchPage(url, init = {}) {
    const response = await fetch(url, {
        ...init,
        headers: {
            Accept: 'text/html,application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(init.headers || {}),
        },
    });
    return response;
}

async function swapContentFromHtml(html, finalUrl) {
    const doc = parseHtml(html);
    const incoming = doc.querySelector(SPA_CONTENT_SELECTOR);
    const current = document.querySelector(SPA_CONTENT_SELECTOR);
    if (!incoming || !current) return false;

    updateHeadFromDocument(doc);
    updateHeaderFromDocument(doc);
    current.innerHTML = incoming.innerHTML;
    await runScriptsFromDocument(doc);
    refreshPatientContext();

    if (finalUrl) {
        const nextPath = new URL(finalUrl, window.location.origin).pathname;
        updateSidebarActiveLink(nextPath);
    }

    return true;
}

function setPatientContext(context) {
    if (!context || !context.patientId) return;
    window.localStorage.setItem(PATIENT_CONTEXT_KEY, JSON.stringify(context));
    renderPatientContext(context);
}

function clearPatientContext() {
    const current = getPatientContext();
    window.localStorage.removeItem(PATIENT_CONTEXT_KEY);
    renderPatientContext(null);

    const releaseUrl = current?.releaseUrl;
    if (!releaseUrl) return;
    const shouldRefreshRis = window.location.pathname.startsWith('/ris/');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    fetchPage(releaseUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            Accept: 'text/html,application/json',
        },
    }).finally(() => {
        if (shouldRefreshRis) {
            window.location.assign('/ris/examens');
        }
    });
}

function getPatientContext() {
    try {
        const raw = window.localStorage.getItem(PATIENT_CONTEXT_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function renderPatientContext(context) {
    const chip = document.getElementById('header-patient-context');
    if (!chip) return;

    const nameEl = document.getElementById('header-patient-name');
    const mrnEl = document.getElementById('header-patient-mrn');
    const ageEl = document.getElementById('header-patient-age');
    const phoneEl = document.getElementById('header-patient-phone');

    if (!context || !context.patientId) {
        chip.classList.add('d-none');
        if (nameEl) nameEl.textContent = '-';
        if (mrnEl) mrnEl.textContent = 'MRN -';
        if (ageEl) ageEl.textContent = 'Age -';
        if (phoneEl) phoneEl.textContent = 'Tel -';
        return;
    }

    chip.classList.remove('d-none');
    if (nameEl) nameEl.textContent = context.name || '-';
    if (mrnEl) mrnEl.textContent = `MRN ${context.mrn || '-'}`;
    if (ageEl) ageEl.textContent = `Age ${context.age || '-'} ans`;
    if (phoneEl) phoneEl.textContent = `Tel ${context.phone || '-'}`;
}

function refreshPatientContext() {
    const source = document.getElementById('module3-patient-context');
    if (source) {
        setPatientContext({
            patientId: source.dataset.patientId,
            name: source.dataset.patientName,
            mrn: source.dataset.patientMrn,
            age: source.dataset.patientAge,
            phone: source.dataset.patientPhone,
            releaseUrl: source.dataset.patientReleaseUrl,
        });
        return;
    }
    renderPatientContext(getPatientContext());
}

function notifyDataUpdated() {
    const now = Date.now();
    window.localStorage.setItem('care.lastDataUpdateAt', String(now));
    window.dispatchEvent(new CustomEvent('care:data-updated', { detail: { at: now } }));
}

async function refreshModule2ConsultationsCounter() {
    const badge = document.getElementById('module2-consultations-badge');
    if (!badge) return;
    try {
        const today = new Date().toISOString().slice(0, 10);
        const response = await fetch(`/care/module-2/board-data?date=${today}`, {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) return;
        const payload = await response.json();
        const items = Array.isArray(payload.items) ? payload.items : [];
        const consultations = items.filter((row) => ['in_care', 'awaiting_payment', 'completed'].includes(String(row.flow_status || ''))).length;
        badge.textContent = String(consultations);
        badge.classList.remove('d-none');
    } catch {
        // Silent failure to avoid interrupting user flow.
    }
}

async function navigateSpa(url, options = { push: true }) {
    if (!isSpaNavigationEnabled()) {
        window.location.assign(url);
        return;
    }
    startProgress();
    setLoadingState(true);
    try {
        const response = await fetchPage(url);
        const finalUrl = response.url || url;
        const html = await response.text();
        const swapped = await swapContentFromHtml(html, finalUrl);
        if (!swapped) {
            window.location.assign(finalUrl);
            return;
        }
        if (options.push) {
            window.history.pushState({ spa: true }, '', finalUrl);
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch {
        window.location.assign(url);
    } finally {
        setLoadingState(false);
        endProgress();
    }
}

async function submitFormSpa(form) {
    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    const action = form.getAttribute('action') || window.location.href;
    const url = new URL(action, window.location.origin);
    const hasFile = form.enctype === 'multipart/form-data' || form.querySelector('input[type="file"]');

    if (method === 'GET') {
        const query = new URLSearchParams(new FormData(form));
        const target = `${url.pathname}?${query.toString()}`;
        await navigateSpa(target, { push: true });
        return;
    }

    startProgress();
    setLoadingState(true);

    try {
        let response;
        if (hasFile) {
            response = await fetchPage(url.toString(), {
                method,
                body: new FormData(form),
            });
        } else {
            const formData = new FormData(form);
            response = await fetchPage(url.toString(), {
                method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: formData,
            });
        }

        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const payload = await response.json();
            showToast(payload.message || 'Enregistrement reussi', response.ok ? 'success' : 'error');
            if (response.ok) {
                notifyDataUpdated();
                window.dispatchEvent(new CustomEvent('care:toast', { detail: payload }));
            }
            return;
        }

        const html = await response.text();
        const finalUrl = response.url || window.location.href;
        const swapped = await swapContentFromHtml(html, finalUrl);
        if (!swapped) {
            window.location.assign(finalUrl);
            return;
        }

        window.history.pushState({ spa: true }, '', finalUrl);
        if (response.ok) {
            showToast('Enregistrement reussi', 'success');
            notifyDataUpdated();
        } else {
            showToast('Erreur pendant lenregistrement', 'error');
        }
    } catch {
        showToast('Erreur reseau', 'error');
    } finally {
        setLoadingState(false);
        endProgress();
    }
}

function bootSpaRuntime() {
    if (!isSpaNavigationEnabled()) return;

    injectSpaStyles();
    refreshPatientContext();

    document.addEventListener('click', (event) => {
        const releaseButton = event.target instanceof Element ? event.target.closest('#header-patient-release') : null;
        if (releaseButton) {
            event.preventDefault();
            clearPatientContext();
            return;
        }

        const anchor = event.target instanceof Element ? event.target.closest('a') : null;
        if (!shouldHandleLink(anchor)) return;
        event.preventDefault();
        navigateSpa(anchor.href, { push: true });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!shouldHandleForm(form)) return;
        event.preventDefault();
        submitFormSpa(form);
    });

    window.addEventListener('popstate', () => {
        navigateSpa(window.location.href, { push: false });
    });

    window.addEventListener('storage', (event) => {
        if (event.key === PATIENT_CONTEXT_KEY) {
            renderPatientContext(getPatientContext());
        }
    });

    window.addEventListener('care:data-updated', () => {
        refreshModule2ConsultationsCounter();
    });

    refreshModule2ConsultationsCounter();
    window.setInterval(refreshModule2ConsultationsCounter, 30000);
}

bootSpaRuntime();
