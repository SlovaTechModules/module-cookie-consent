<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. All rights reserved.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ArgumentInterface
{
    public const COOKIE_NAME = 'slova_consent';

    private const XML_ENABLED = 'slova_cookieconsent/general/enabled';
    private const XML_LIFETIME = 'slova_cookieconsent/general/cookie_lifetime';
    private const XML_VERSION = 'slova_cookieconsent/general/consent_version';
    private const XML_POLICY = 'slova_cookieconsent/general/privacy_page';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly UrlInterface $url
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function getCookieName(): string
    {
        return self::COOKIE_NAME;
    }

    public function getLifetimeSeconds(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_LIFETIME, ScopeInterface::SCOPE_STORE) ?: 31536000);
    }

    /**
     * hyva.setCookie așteaptă durata în zile.
     */
    public function getLifetimeDays(): int
    {
        return (int) max(1, floor($this->getLifetimeSeconds() / 86400));
    }

    public function getConsentVersion(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_VERSION, ScopeInterface::SCOPE_STORE) ?: 1);
    }

    public function getPrivacyUrl(): string
    {
        $page = $this->scopeConfig->getValue(self::XML_POLICY, ScopeInterface::SCOPE_STORE)
            ?: 'privacy-policy-cookie-restriction-mode';

        return $this->url->getUrl($page);
    }

    /**
     * Categoriile expuse în modal. Necesare = mereu on (locked).
     *
     * @return array<int, array{code:string, label:string, desc:string, locked:bool}>
     */
    public function getCategories(): array
    {
        return [
            [
                'code' => 'necessary',
                'label' => (string) __('Necessary'),
                'desc' => (string) __('Essential for the website to function (cart, login, security). These cannot be disabled.'),
                'locked' => true,
            ],
            [
                'code' => 'analytics',
                'label' => (string) __('Analytics'),
                'desc' => (string) __('Help us understand how the site is used, so we can improve it.'),
                'locked' => false,
            ],
            [
                'code' => 'marketing',
                'label' => (string) __('Marketing'),
                'desc' => (string) __('Used to show you relevant recommendations and ads.'),
                'locked' => false,
            ],
        ];
    }
}
