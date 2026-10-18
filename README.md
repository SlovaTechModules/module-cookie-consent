# Slova Cookie Consent — free GDPR cookie banner for Hyvä

A cookie consent banner for Magento 2 stores on Hyvä that does what EU regulators ask for and what Google needs: **Reject all** as easy as **Accept all**, a choice per category, nothing optional before consent, **Google Consent Mode v2** sent before any Google tag, and a way back to the settings on every page. Free, MIT-licensed, no account and no external service.

## What it does

- **A banner that passes review.** *Reject all* and *Accept all* look the same; *Customize* opens one switch per category. Nothing optional is on until the visitor says so, and the banner does not close without an answer.
- **Categories you choose.** Necessary (always on), Preferences, Analytics and Marketing; offer only the ones your store uses, per store view. Add your own from `di.xml`.
- **Google Consent Mode v2, in the right place.** All optional signals are denied right after `<title>` — before Google Tag Manager, even when its snippet sits in *HTML Head > Scripts and Style Sheets* — and updated the moment the visitor chooses. Returning visitors get their stored choice before the first tag fires.
- **Blocks other scripts until consent.** Mark a script `type="text/plain" data-consent="marketing"`, or an iframe with `data-consent` and `data-src`, and it runs only once its categories are allowed.
- **Withdrawal on every page.** A small corner button, any link to `#cookie-settings` (put one in your footer) and `window.slovaConsent.open()`. Withdrawing a category reloads the page so scripts that already ran stop.
- **Built for Hyvä.** Works with Alpine's CSP build, no inline scripts or styles, no Tailwind rebuild, safe in the full page cache, under 5 KB of JavaScript gzipped. Follows your theme's primary color.
- **Replaces Magento's Cookie Restriction Mode** and silences its notice while enabled.
- **For developers:** a browser API and events (mirrored into `dataLayer` for Google Tag Manager) and a server-side PHP class for code that must respect consent.
- **Translated** into English, Romanian, German, French, Spanish, Italian and Dutch.

### What it does not do

It does not scan your store for cookies, keep a server-side log of consents, adapt the banner to the visitor's country, or implement the IAB TCF framework. If you need any of those, a hosted consent platform (Cookiebot, Usercentrics, CookieYes…) is the better tool.

## Requirements

| | |
|---|---|
| Magento Open Source / Adobe Commerce | 2.4.8 (tested on 2.4.8-p5) |
| Hyvä Theme | 1.3 or newer (tested on 1.5.3) |
| PHP | 8.3 – 8.4 |

The storefront part uses Hyvä's Alpine.js; Luma storefronts are not supported. `hyva-themes/magento2-theme-module` comes from Hyvä's own Composer repository, which every Hyvä store already has configured.

## Installation

```bash
composer require slova/module-cookie-consent
bin/magento module:enable Slova_CookieConsent
bin/magento setup:upgrade
bin/magento setup:di:compile          # production mode
bin/magento cache:flush
```

The banner appears right away with sensible defaults. Then:

1. Set **Stores → Configuration → General → Web → Default Cookie Settings → Cookie Restriction Mode** to **No** (this module replaces it).
2. Point **Privacy Policy Link** at your policy page.
3. Mark the scripts that need consent (see *Blocking scripts until consent*), or rely on Consent Mode for Google tags.

### Upgrading

```bash
composer update slova/module-cookie-consent
bin/magento setup:upgrade
bin/magento cache:flush
```

### Uninstalling

```bash
bin/magento module:uninstall Slova_CookieConsent --remove-data
```

`--remove-data` deletes the module's settings (`slova_cookieconsent/*`). Visitors' consent cookies expire on their own.

## Configuration

**Stores → Configuration → Slova → Cookie Consent**

| Setting | Notes |
|---|---|
| Enable Cookie Consent | Per store view. |
| Optional Categories | The categories visitors can switch. Necessary is always listed. Default: Analytics, Marketing. |
| Privacy Policy Link | A CMS page URL key (`privacy-policy`) or a full `https://` URL. Empty: no link. |
| Consent Version | Raise it after you change your policy or add a category: everyone is asked again. |
| Remember the Choice For (Days) | Default 180 (about six months, as the French CNIL recommends). |
| Floating Settings Button | Reopens the preferences after a choice. If you turn it off, link to `#cookie-settings` from every page. |
| Texts | Banner title, message and link text per store view. Empty: the built-in, translated text. |
| Banner Position | Card bottom right, card bottom left, or a bar across the bottom. |
| Accent Color | A hex color for buttons, links and switches. Empty: your theme's `--color-primary`. |
| Send Google Consent Mode | On by default. Turn off only if your GTM container or another module already sends the consent defaults. |
| Wait for Update (ms) | 500, Google's recommendation. |
| Redact Ads Data Without Consent | On by default (`ads_data_redaction`). |

### Categories and Google Consent Mode

| Category | Consent Mode signals it grants |
|---|---|
| Necessary (always on) | `security_storage` |
| Preferences | `functionality_storage`, `personalization_storage` |
| Analytics | `analytics_storage` |
| Marketing | `ad_storage`, `ad_user_data`, `ad_personalization` |

A signal whose category is not offered stays denied.

## Blocking scripts until consent

Google tags (gtag.js, Google Tag Manager's Google tags) follow Consent Mode on their own. Everything else must not run before consent. Change the script's `type` to `text/plain` and name the categories it needs:

```html
<!-- Runs once marketing is allowed -->
<script type="text/plain" data-consent="marketing">
    !function(f,b,e,v,n,t,s){ /* Meta Pixel base code */ }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '123456789');
    fbq('track', 'PageView');
</script>

<!-- External script; needs both categories -->
<script type="text/plain" data-consent="analytics marketing" src="https://example.com/tag.js"></script>

<!-- A module script: the real type goes in data-type -->
<script type="text/plain" data-consent="analytics" data-type="module" src="/js/stats.mjs"></script>

<!-- Embedded video: loads once marketing is allowed -->
<iframe data-consent="marketing" data-src="https://www.youtube.com/embed/VIDEO_ID" width="560" height="315"></iframe>
```

Blocked scripts keep their order. Under a strict Content Security Policy, an inline script still needs to be allowed by hash or nonce: with Hyvä, call `$hyvaCsp->registerInlineScript()` right after the placeholder. Whether Hyvä adds a hash (same text once activated) or a nonce (copied to the activated script), it carries over.

In **Google Tag Manager**, non-Google tags can use the built-in consent checks (they read Consent Mode), or a *Custom Event* trigger on `slova_consent_ready` / `slova_consent_updated` with a *Data Layer Variable* `slova_consent.marketing` (or any category code).

## Letting visitors change their mind

Any of these opens the preferences:

```html
<a href="#cookie-settings">Cookie settings</a>
<button type="button" data-consent-open>Cookie settings</button>
```

A link to `/some-page#cookie-settings` opens them on that page after it loads.

## For developers

### Browser

```js
window.slovaConsent.has('analytics');   // true once allowed (required categories always are)
window.slovaConsent.get();              // { necessary: true, analytics: false, marketing: true }
window.slovaConsent.isDecided();        // false until the visitor answers
window.slovaConsent.open();             // open the preferences
window.slovaConsent.acceptAll(); window.slovaConsent.rejectAll(); window.slovaConsent.save({ analytics: true });

window.addEventListener('slova:consent-ready',   e => console.log(e.detail)); // once per page
window.addEventListener('slova:consent-updated', e => console.log(e.detail)); // after every choice
```

`window.slovaConsent` exists as soon as the module's deferred script has run, before Alpine starts. Scripts in `<head>` should listen for `slova:consent-ready` rather than call the API directly.

The choice is stored in the first-party cookie `slova_consent` as JSON: `{"v": <consent version>, "ts": <unix time>, "necessary": true, "analytics": false, …}`.

### Server

For server-side code that must respect consent — a Conversions API call when an order is placed, a cookie set by a controller:

```php
public function __construct(private \Slova\CookieConsent\Model\Consent $consent) {}

if ($this->consent->allows('marketing')) { /* … */ }
$this->consent->hasDecided();
$this->consent->getChoices();   // ['necessary' => true, 'analytics' => false, …]
```

Do not use it to vary the HTML of full-page-cached pages: the cached copy would carry the first visitor's choice.

### Adding a category

In your module's `etc/di.xml`:

```xml
<type name="Slova\CookieConsent\Model\CategoryPool">
    <arguments>
        <argument name="categories" xsi:type="array">
            <item name="social" xsi:type="array">
                <item name="label" xsi:type="string">Social media</item>
                <item name="description" xsi:type="string">Share buttons and embedded posts.</item>
                <item name="required" xsi:type="boolean">false</item>
                <item name="sort_order" xsi:type="number">35</item>
            </item>
        </argument>
    </arguments>
</type>
```

It then appears under *Optional Categories*. Codes are lowercase letters, digits and underscores. Translate the label and description in your module's `i18n/*.csv`. Raise *Consent Version* so existing visitors are asked about it.

### Styling

Styles live in `view/frontend/web/css/consent.css` and are driven by custom properties on `.slova-cc`; override them from your theme:

```css
.slova-cc {
    --slova-cc-accent: #0f766e;
    --slova-cc-banner-bg: #f0fafa;
    --slova-cc-banner-border: #0f766e;
    --slova-cc-radius: .75rem;
    --slova-cc-z-banner: 30;
    --slova-cc-z-modal: 60;
}
```

For deeper changes, override `Slova_CookieConsent::consent.phtml` in your theme; keep the `x-data`, `data-category` and event bindings.

## License

MIT — see [LICENSE](LICENSE). © 2026 Carusel Multimedia SRL, trading as Slova.
