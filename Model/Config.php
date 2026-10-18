<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Settings under Stores > Configuration > Slova > Cookie Consent, for the current store view.
 */
class Config
{
    /** The visitor's choice: {"v": version, "ts": unix time, "<category>": bool, ...}. */
    public const COOKIE_NAME = 'slova_consent';

    public const POSITION_CORNER_RIGHT = 'corner_right';
    public const POSITION_CORNER_LEFT = 'corner_left';
    public const POSITION_BAR = 'bar';

    private const XML_ENABLED = 'slova_cookieconsent/general/enabled';
    private const XML_CATEGORIES = 'slova_cookieconsent/general/categories';
    private const XML_PRIVACY_PAGE = 'slova_cookieconsent/general/privacy_page';
    private const XML_VERSION = 'slova_cookieconsent/general/consent_version';
    private const XML_LIFETIME_DAYS = 'slova_cookieconsent/general/lifetime_days';
    private const XML_REOPEN_BUTTON = 'slova_cookieconsent/general/reopen_button';
    private const XML_TITLE = 'slova_cookieconsent/content/title';
    private const XML_MESSAGE = 'slova_cookieconsent/content/message';
    private const XML_LINK_LABEL = 'slova_cookieconsent/content/link_label';
    private const XML_POSITION = 'slova_cookieconsent/appearance/position';
    private const XML_ACCENT = 'slova_cookieconsent/appearance/accent_color';
    private const XML_CONSENT_MODE = 'slova_cookieconsent/consent_mode/enabled';
    private const XML_WAIT_FOR_UPDATE = 'slova_cookieconsent/consent_mode/wait_for_update';
    private const XML_ADS_REDACTION = 'slova_cookieconsent/consent_mode/ads_data_redaction';

    private const DEFAULT_LIFETIME_DAYS = 180;
    private const MAX_LIFETIME_DAYS = 3650;
    private const MAX_WAIT_FOR_UPDATE = 10000;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Whether the banner runs in this store view.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Name of the cookie holding the visitor's choice.
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return self::COOKIE_NAME;
    }

    /**
     * Codes of the optional categories offered in this store view.
     *
     * @return string[]
     */
    public function getEnabledCategoryCodes(): array
    {
        $value = (string) $this->getValue(self::XML_CATEGORIES);

        return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
    }

    /**
     * A choice stored under another version counts as no choice.
     *
     * @return int
     */
    public function getConsentVersion(): int
    {
        return max(1, (int) $this->getValue(self::XML_VERSION));
    }

    /**
     * How long the choice is kept.
     *
     * @return int
     */
    public function getLifetimeDays(): int
    {
        $days = (int) $this->getValue(self::XML_LIFETIME_DAYS);

        return $days > 0 ? min($days, self::MAX_LIFETIME_DAYS) : self::DEFAULT_LIFETIME_DAYS;
    }

    /**
     * CMS page URL key or absolute URL; empty for no link.
     *
     * @return string
     */
    public function getPrivacyPage(): string
    {
        return trim((string) $this->getValue(self::XML_PRIVACY_PAGE));
    }

    /**
     * Whether the corner button that reopens the preferences is shown.
     *
     * @return bool
     */
    public function showReopenButton(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_REOPEN_BUTTON, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Custom banner title; empty for the built-in one.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return trim((string) $this->getValue(self::XML_TITLE));
    }

    /**
     * Custom banner message; empty for the built-in one.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return trim((string) $this->getValue(self::XML_MESSAGE));
    }

    /**
     * Custom policy link text; empty for the built-in one.
     *
     * @return string
     */
    public function getLinkLabel(): string
    {
        return trim((string) $this->getValue(self::XML_LINK_LABEL));
    }

    /**
     * One of the POSITION_* constants.
     *
     * @return string
     */
    public function getPosition(): string
    {
        $position = (string) $this->getValue(self::XML_POSITION);
        $known = [self::POSITION_CORNER_RIGHT, self::POSITION_CORNER_LEFT, self::POSITION_BAR];

        return in_array($position, $known, true) ? $position : self::POSITION_CORNER_RIGHT;
    }

    /**
     * Hex accent color, or '' to follow the theme.
     *
     * @return string
     */
    public function getAccentColor(): string
    {
        $color = trim((string) $this->getValue(self::XML_ACCENT));

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $color) ? $color : '';
    }

    /**
     * Whether this module sends the Google Consent Mode v2 default and updates.
     *
     * @return bool
     */
    public function sendsConsentMode(): bool
    {
        return $this->isEnabled()
            && $this->scopeConfig->isSetFlag(self::XML_CONSENT_MODE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Consent Mode wait_for_update, in milliseconds.
     *
     * @return int
     */
    public function getWaitForUpdate(): int
    {
        return min(max(0, (int) $this->getValue(self::XML_WAIT_FOR_UPDATE)), self::MAX_WAIT_FOR_UPDATE);
    }

    /**
     * Consent Mode ads_data_redaction.
     *
     * @return bool
     */
    public function redactsAdsData(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ADS_REDACTION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Store view value of a setting.
     *
     * @param string $path
     * @return mixed
     */
    private function getValue(string $path): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
