/**
 * Slova Cookie Consent — the visitor's choice, the public API and the banner component.
 *
 * Settings come from the JSON in #slova-cookie-consent[data-slova-cookie-consent]:
 *   cookieName, version, lifetimeDays, categories [{code, required}], accent.
 *
 * The choice lives in one first-party cookie, {"v": version, "ts": unix time, "<code>": bool, ...}.
 * A cookie from another version counts as no choice, so raising the version asks everyone again.
 *
 * Public API (available as soon as this deferred script has run, before Alpine starts):
 *   window.slovaConsent.has(code)      true when the category is allowed (required ones always are)
 *   window.slovaConsent.get()          {code: bool, ...}
 *   window.slovaConsent.isDecided()    whether the visitor has answered
 *   window.slovaConsent.open()         opens the preferences
 *   window.slovaConsent.acceptAll() / rejectAll() / save({code: bool, ...})
 * Events on window, detail = {code: bool, ...}:
 *   slova:consent-ready     once, when the stored choice (or its absence) is known
 *   slova:consent-updated   after every choice
 * mirrored into dataLayer as slova_consent_ready / slova_consent_updated for Google Tag Manager.
 *
 * Blocking until consent: <script type="text/plain" data-consent="analytics"> (and
 * <iframe data-consent="marketing" data-src="...">) are activated once every listed category
 * is allowed. Withdrawing a category reloads the page, so scripts that already ran stop.
 *
 * The Alpine component `slovaCookieConsent` is compatible with the Alpine CSP build: the
 * template references only properties and methods, never expressions.
 */
(function () {
    'use strict';

    var root = document.getElementById('slova-cookie-consent');
    if (!root || window.slovaConsent) {
        return;
    }

    var cfg;
    try {
        cfg = JSON.parse(root.getAttribute('data-slova-cookie-consent') || '{}');
    } catch (e) {
        return;
    }
    var categories = cfg.categories || [];
    var cookieName = cfg.cookieName || 'slova_consent';

    function copy(source) {
        var target = {};
        Object.keys(source).forEach(function (key) { target[key] = source[key]; });
        return target;
    }

    // Required categories on, every other one set to `value`.
    function uniform(value) {
        var result = {};
        categories.forEach(function (category) {
            result[category.code] = category.required ? true : value;
        });
        return result;
    }

    function normalize(input) {
        var result = uniform(false);
        categories.forEach(function (category) {
            if (!category.required) {
                result[category.code] = !!(input && input[category.code] === true);
            }
        });
        return result;
    }

    function readCookie() {
        var match = document.cookie.match(new RegExp('(?:^|; )' + cookieName + '=([^;]*)'));
        if (!match) {
            return null;
        }
        try {
            var data = JSON.parse(decodeURIComponent(match[1]));
            return data && data.v === cfg.version ? normalize(data) : null;
        } catch (e) {
            return null;
        }
    }

    function writeCookie(values) {
        // Written directly, not with hyva.setCookie: with Magento's Cookie Restriction
        // Mode on, Hyvä holds back every cookie until that mode's own consent is given.
        var cookieConfig = window.COOKIE_CONFIG || {};
        var payload = copy(values);
        payload.v = cfg.version;
        payload.ts = Math.floor(Date.now() / 1000);
        var expires = new Date(Date.now() + (cfg.lifetimeDays || 180) * 86400000);
        document.cookie = cookieName + '=' + encodeURIComponent(JSON.stringify(payload))
            + '; expires=' + expires.toUTCString()
            + '; path=' + (cookieConfig.path || '/')
            + (cookieConfig.domain ? '; domain=' + cookieConfig.domain : '')
            + (window.location.protocol === 'https:' ? '; secure' : '')
            + '; samesite=lax';
    }

    var stored = readCookie();
    var decided = stored !== null;
    var choices = stored || uniform(false);

    function emit(name) {
        window.dispatchEvent(new CustomEvent(name, { detail: copy(choices) }));
        // The same for Google Tag Manager: a Custom Event trigger on slova_consent_ready /
        // slova_consent_updated, with Data Layer Variables slova_consent.<code>.
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: name.replace(':', '_').replace('-', '_'),
            slova_consent: copy(choices),
            slova_consent_decided: decided
        });
    }

    // ---- blocking until consent -------------------------------------------------------------

    function isAllowed(element) {
        var needed = (element.getAttribute('data-consent') || '').split(/\s+/).filter(Boolean);
        return needed.length > 0 && needed.every(function (code) { return choices[code] === true; });
    }

    function activate() {
        document.querySelectorAll('script[type="text/plain"][data-consent]').forEach(function (placeholder) {
            if (!isAllowed(placeholder)) {
                return;
            }
            var script = document.createElement('script');
            Array.prototype.forEach.call(placeholder.attributes, function (attribute) {
                if (['type', 'data-consent', 'data-type', 'nonce'].indexOf(attribute.name) === -1) {
                    script.setAttribute(attribute.name, attribute.value);
                }
            });
            if (placeholder.getAttribute('data-type')) {
                script.type = placeholder.getAttribute('data-type');
            }
            if (placeholder.nonce) {
                script.nonce = placeholder.nonce;
            }
            if (script.src) {
                // Inserted scripts are async by default; keep page order unless async was asked for.
                script.async = placeholder.hasAttribute('async');
            }
            script.text = placeholder.text;
            placeholder.parentNode.replaceChild(script, placeholder);
        });
        document.querySelectorAll('iframe[data-consent][data-src]').forEach(function (frame) {
            if (isAllowed(frame)) {
                frame.setAttribute('src', frame.getAttribute('data-src'));
                frame.removeAttribute('data-src');
            }
        });
    }

    // ---- public API -------------------------------------------------------------------------

    function save(input) {
        var next = normalize(input);
        var withdrawn = decided && Object.keys(choices).some(function (code) {
            return choices[code] === true && next[code] !== true;
        });
        choices = next;
        decided = true;
        writeCookie(choices);
        emit('slova:consent-updated');
        if (withdrawn) {
            window.location.reload();
            return;
        }
        activate();
    }

    window.slovaConsent = {
        has: function (code) { return choices[code] === true; },
        get: function () { return copy(choices); },
        isDecided: function () { return decided; },
        open: function () { window.dispatchEvent(new CustomEvent('slova:consent-open')); },
        acceptAll: function () { save(uniform(true)); },
        rejectAll: function () { save(uniform(false)); },
        save: save
    };

    // Any element with data-consent-open, or link to #cookie-settings on this page, opens the
    // preferences. A link to #cookie-settings on another page navigates; that page opens them.
    document.addEventListener('click', function (event) {
        var trigger = event.target && event.target.closest
            && event.target.closest('[data-consent-open], a[href*="#cookie-settings"]');
        if (!trigger) {
            return;
        }
        if (trigger.tagName === 'A' && !trigger.hasAttribute('data-consent-open')
            && (trigger.hash !== '#cookie-settings' || trigger.pathname !== window.location.pathname)) {
            return;
        }
        event.preventDefault();
        window.slovaConsent.open();
    });
    window.addEventListener('hashchange', function () {
        if (window.location.hash === '#cookie-settings') {
            window.slovaConsent.open();
        }
    });

    if (decided) {
        activate();
    }
    emit('slova:consent-ready');

    // ---- banner and preferences (Alpine) ----------------------------------------------------

    function slovaCookieConsent() {
        return {
            decided: decided,
            open: false,
            draft: {},
            returnFocus: null,

            init() {
                if (cfg.accent) {
                    this.$el.style.setProperty('--slova-cc-accent', cfg.accent);
                }
                var self = this;
                window.addEventListener('slova:consent-updated', function () {
                    self.decided = true;
                    self.closeModal();
                });
                window.addEventListener('slova:consent-open', function () {
                    self.openModal();
                });
                if (window.location.hash === '#cookie-settings') {
                    this.openModal();
                }
            },

            isBannerVisible() {
                return !this.decided && !this.open;
            },

            isModalVisible() {
                return this.open;
            },

            isReopenVisible() {
                return this.decided && !this.open;
            },

            // Bound to each switch; the category is on the element.
            isChecked() {
                return this.draft[this.$el.getAttribute('data-category')] === true;
            },

            onToggle(event) {
                this.draft[event.target.getAttribute('data-category')] = event.target.checked;
            },

            openModal() {
                if (this.open) {
                    return;
                }
                this.draft = window.slovaConsent.get();
                this.returnFocus = document.activeElement;
                this.open = true;
                var dialog = this.$refs.dialog;
                this.$nextTick(function () {
                    if (window.hyva && typeof window.hyva.trapFocus === 'function') {
                        window.hyva.trapFocus(dialog);
                    } else {
                        dialog.focus();
                    }
                });
            },

            closeModal() {
                if (!this.open) {
                    return;
                }
                this.open = false;
                if (window.hyva && typeof window.hyva.releaseFocus === 'function') {
                    window.hyva.releaseFocus(this.$refs.dialog);
                }
                if (this.returnFocus && typeof this.returnFocus.focus === 'function') {
                    this.returnFocus.focus();
                }
                this.returnFocus = null;
            },

            acceptAll() {
                window.slovaConsent.acceptAll();
            },

            rejectAll() {
                window.slovaConsent.rejectAll();
            },

            savePreferences() {
                window.slovaConsent.save(this.draft);
            }
        };
    }

    window.addEventListener('alpine:init', function () {
        window.Alpine.data('slovaCookieConsent', slovaCookieConsent);
    }, { once: true });
})();
