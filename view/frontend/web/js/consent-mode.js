/**
 * Slova Cookie Consent — Google Consent Mode v2.
 *
 * Loaded as a blocking script right after <title> (Plugin\ConsentModeDefaults), so it runs
 * before any Google tag on the page. It sends the default (every optional signal denied),
 * then, for a returning visitor, an update from the stored choice; later choices made in
 * the banner arrive through the `slova:consent-updated` event.
 *
 * Settings come from the script tag's `data-slova-consent-mode` attribute:
 *   cookieName, version, defaults {signal: granted|denied}, signals {category: [signal, ...]},
 *   waitForUpdate (ms), adsDataRedaction (bool).
 */
(function () {
    'use strict';

    var script = document.currentScript;
    var cfg;
    try {
        cfg = JSON.parse((script && script.getAttribute('data-slova-consent-mode')) || '{}');
    } catch (e) {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    // The same stub Google's snippet defines; gtag.js or GTM picks up the queue.
    var gtag = window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };

    var defaults = {};
    Object.keys(cfg.defaults || {}).forEach(function (signal) { defaults[signal] = cfg.defaults[signal]; });
    if (cfg.waitForUpdate > 0) {
        defaults.wait_for_update = cfg.waitForUpdate;
    }
    gtag('consent', 'default', defaults);
    if (cfg.adsDataRedaction) {
        gtag('set', 'ads_data_redaction', true);
    }

    function update(choices) {
        var state = {};
        Object.keys(cfg.signals || {}).forEach(function (category) {
            cfg.signals[category].forEach(function (signal) {
                // A signal two categories share is granted when either of them is.
                if (state[signal] !== 'granted') {
                    state[signal] = choices[category] === true ? 'granted' : 'denied';
                }
            });
        });
        if (Object.keys(state).length) {
            gtag('consent', 'update', state);
        }
    }

    function readStored() {
        var match = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookieName + '=([^;]*)'));
        if (!match) { return null; }
        try {
            var data = JSON.parse(decodeURIComponent(match[1]));
            return data && data.v === cfg.version ? data : null;
        } catch (e) {
            return null;
        }
    }

    var stored = readStored();
    if (stored) {
        update(stored);
    }

    window.addEventListener('slova:consent-updated', function (event) {
        update((event && event.detail) || {});
    });
})();
