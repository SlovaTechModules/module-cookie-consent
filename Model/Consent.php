<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Model;

use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * The visitor's choice, read on the server from the consent cookie.
 *
 * For server-side code that must respect consent (a Conversions API call when an
 * order is placed, a cookie set by a controller). Do not use it to vary the HTML
 * of full-page-cached pages: the cached copy would carry the first visitor's choice.
 * In the browser, use window.slovaConsent (see README).
 *
 * A cookie written under another consent version counts as no choice, exactly as
 * the banner treats it.
 */
class Consent
{
    /** @var array<string, bool>|null */
    private ?array $choices = null;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param Config $config
     * @param CategoryPool $categoryPool
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly Config $config,
        private readonly CategoryPool $categoryPool
    ) {
    }

    /**
     * Whether the visitor has answered the current banner.
     *
     * @return bool
     */
    public function hasDecided(): bool
    {
        return $this->read() !== [];
    }

    /**
     * Whether a category is allowed. Required categories always are; anything else needs an explicit yes.
     *
     * @param string $category e.g. "analytics", "marketing"
     * @return bool
     */
    public function allows(string $category): bool
    {
        if ($this->categoryPool->isRequired($category)) {
            return true;
        }

        return $this->read()[$category] ?? false;
    }

    /**
     * Category code => allowed, for every category in the pool; all optional ones false before a choice.
     *
     * @return array<string, bool>
     */
    public function getChoices(): array
    {
        $choices = [];
        foreach (array_keys($this->categoryPool->getAll()) as $code) {
            $choices[$code] = $this->allows($code);
        }

        return $choices;
    }

    /**
     * Stored choices for the current version; [] when there is none.
     *
     * @return array<string, bool>
     */
    private function read(): array
    {
        if ($this->choices !== null) {
            return $this->choices;
        }

        $data = $this->decode((string) $this->cookieManager->getCookie($this->config->getCookieName()));
        if (!isset($data['v']) || (int) $data['v'] !== $this->config->getConsentVersion()) {
            return $this->choices = [];
        }

        $choices = [];
        foreach (array_keys($this->categoryPool->getAll()) as $code) {
            $choices[$code] = ($data[$code] ?? false) === true;
        }

        return $this->choices = $choices;
    }

    /**
     * The cookie arrives percent-encoded from the browser; accept it raw as well.
     *
     * @param string $raw
     * @return array<string, mixed>
     */
    private function decode(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- a cookie value, not a URL
        foreach ([$raw, rawurldecode($raw)] as $candidate) {
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
