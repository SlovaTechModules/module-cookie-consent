/**
 * Slova_CookieConsent — store-ul Alpine pentru consimțământ.
 *
 * Extras din consent.phtml pe 29 iul 2026 (era inline pe fiecare pagină).
 * Inline rămâne doar configul generat de PHP: window.slovaConsentConfig
 * (cookieName / version / lifetimeDays din setările modulului).
 *
 * Se încarcă `defer`, deci ÎNAINTE de alpine3.min.js (ultimul script din
 * document) — `alpine:init` e prins la timp și store-ul există când
 * componentele îl citesc. API-ul global window.slovaConsent (folosit de
 * gating-ul de scripturi) se publică din init(), neschimbat.
 */
function initCookieConsentStore() {
    const cfg = window.slovaConsentConfig || {};
    return {
        cookieName: cfg.cookieName || 'slova_consent',
        version: cfg.version || 1,
        lifetimeDays: cfg.lifetimeDays || 365,
        ready: false,
        decided: false,
        open: false,
        prefs: { necessary: true, analytics: false, marketing: false },

        init() {
            const stored = this.read();
            if (stored && stored.v === this.version) {
                this.prefs.analytics = !! stored.analytics;
                this.prefs.marketing = !! stored.marketing;
                this.decided = true;
            }
            this.ready = true;

            // API global pentru gating scripturi (F2) și linkul „Setări cookie" din footer (F3)
            window.slovaConsent = {
                has: (cat) => this.prefs[cat] === true,
                get: () => Object.assign({}, this.prefs),
                open: () => { this.open = true; }
            };
            window.dispatchEvent(new CustomEvent('slova:consent-ready', { detail: Object.assign({}, this.prefs) }));
        },
        read() {
            const raw = hyva.getCookie(this.cookieName);
            if (! raw) return null;
            try { return JSON.parse(unescape(raw)); } catch (e) { return null; }
        },
        persist() {
            const payload = {
                v: this.version,
                necessary: true,
                analytics: !! this.prefs.analytics,
                marketing: !! this.prefs.marketing,
                ts: Math.floor(Date.now() / 1000)
            };
            hyva.setCookie(this.cookieName, JSON.stringify(payload), this.lifetimeDays);
            this.decided = true;
            this.open = false;
            window.dispatchEvent(new CustomEvent('slova:consent-updated', { detail: Object.assign({}, this.prefs) }));
        },
        acceptAll() { this.prefs.analytics = true; this.prefs.marketing = true; this.persist(); },
        rejectAll() { this.prefs.analytics = false; this.prefs.marketing = false; this.persist(); },
        savePrefs() { this.persist(); },
        openModal() { this.open = true; },
        closeModal() { this.open = false; }
    }
}
window.addEventListener('alpine:init', () => Alpine.store('cookieConsent', initCookieConsentStore()), { once: true });
