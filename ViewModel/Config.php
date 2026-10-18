<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Slova\CookieConsent\Model\CategoryPool;
use Slova\CookieConsent\Model\Config as ModuleConfig;

/**
 * Everything the banner template needs. Nothing here depends on the visitor, so the
 * markup is safe in the full page cache; the browser decides what to show.
 */
class Config implements ArgumentInterface
{
    /** Kept from 1.x, where the constant lived here. */
    public const COOKIE_NAME = ModuleConfig::COOKIE_NAME;

    /**
     * @param ModuleConfig $config
     * @param CategoryPool $categoryPool
     * @param UrlInterface $url
     * @param Json $json
     */
    public function __construct(
        private readonly ModuleConfig $config,
        private readonly CategoryPool $categoryPool,
        private readonly UrlInterface $url,
        private readonly Json $json
    ) {
    }

    /**
     * Whether the banner runs in this store view.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Name of the consent cookie.
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->config->getCookieName();
    }

    /**
     * Current consent version.
     *
     * @return int
     */
    public function getConsentVersion(): int
    {
        return $this->config->getConsentVersion();
    }

    /**
     * Offered categories, labels translated.
     *
     * @return array<int, array{code:string, label:string, description:string, required:bool}>
     */
    public function getCategories(): array
    {
        $categories = [];
        foreach ($this->categoryPool->getOffered($this->config->getEnabledCategoryCodes()) as $category) {
            $categories[] = [
                'code' => $category['code'],
                // phpcs:ignore Magento2.Translation.ConstantUsage -- labels come from di.xml
                'label' => (string) __($category['label']),
                // phpcs:ignore Magento2.Translation.ConstantUsage -- descriptions come from di.xml
                'description' => (string) __($category['description']),
                'required' => $category['required'],
            ];
        }

        return $categories;
    }

    /**
     * Banner title.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->config->getTitle() ?: (string) __('We value your privacy');
    }

    /**
     * Banner message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        // One literal, so that i18n:collect-phrases finds it.
        // phpcs:ignore Generic.Files.LineLength.TooLong
        return $this->config->getMessage() ?: (string) __('We use cookies to run this store and, with your permission, to understand how it is used and to show you relevant ads. You can change your choice at any time.');
    }

    /**
     * Text of the privacy policy link.
     *
     * @return string
     */
    public function getLinkLabel(): string
    {
        return $this->config->getLinkLabel() ?: (string) __('Privacy policy');
    }

    /**
     * Privacy policy URL; '' when none is configured.
     *
     * @return string
     */
    public function getPrivacyUrl(): string
    {
        $page = $this->config->getPrivacyPage();
        if ($page === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $page)) {
            return $page;
        }

        return $this->url->getUrl(null, ['_direct' => ltrim($page, '/')]);
    }

    /**
     * CSS modifier for the banner position.
     *
     * @return string
     */
    public function getPositionClass(): string
    {
        return 'slova-cc-banner--' . str_replace('_', '-', $this->config->getPosition());
    }

    /**
     * Whether the corner button that reopens the preferences is shown.
     *
     * @return bool
     */
    public function showReopenButton(): bool
    {
        return $this->config->showReopenButton();
    }

    /**
     * Settings for web/js/consent.js, as JSON for a data attribute.
     *
     * @return string
     */
    public function getJsConfig(): string
    {
        $categories = [];
        foreach ($this->categoryPool->getOffered($this->config->getEnabledCategoryCodes()) as $category) {
            $categories[] = ['code' => $category['code'], 'required' => $category['required']];
        }

        return (string) $this->json->serialize([
            'cookieName' => $this->config->getCookieName(),
            'version' => $this->config->getConsentVersion(),
            'lifetimeDays' => $this->config->getLifetimeDays(),
            'categories' => $categories,
            'accent' => $this->config->getAccentColor(),
        ]);
    }
}
