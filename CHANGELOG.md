# Changelog

## 1.0.0

First public release, free under the MIT License.

- Banner with **Reject all** and **Accept all** of equal weight, plus **Customize**; a preferences dialog with one switch per category. Necessary is always on; Preferences, Analytics and Marketing are optional and chosen per store view; more categories can be added from `di.xml`.
- **Google Consent Mode v2**: every optional signal denied right after `<title>`, before any Google tag (including a Google Tag Manager snippet in *HTML Head > Scripts and Style Sheets*), then updated from the visitor's choice. `wait_for_update` and `ads_data_redaction` configurable.
- **Blocking until consent**: `<script type="text/plain" data-consent="…">` and `<iframe data-consent="…" data-src="…">` are activated once their categories are allowed.
- **Withdrawal**: a corner settings button, any link to `#cookie-settings`, and `window.slovaConsent.open()`. Withdrawing a category reloads the page so scripts that already ran stop.
- Browser API `window.slovaConsent`, events `slova:consent-ready` / `slova:consent-updated` (also pushed to `dataLayer` for Google Tag Manager), server-side `Slova\CookieConsent\Model\Consent`.
- Admin texts per store view, banner position (corner card or bottom bar), accent color.
- Replaces Magento's Cookie Restriction Mode and hides its notice while enabled.
- Compatible with the Alpine CSP build; no inline scripts or styles; no Tailwind rebuild.
- Translations: English, Romanian, German, French, Spanish, Italian, Dutch.
- `module:uninstall --remove-data` deletes the module's settings.
