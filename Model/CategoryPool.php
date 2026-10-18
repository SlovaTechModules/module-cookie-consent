<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Model;

/**
 * Every consent category the module knows, from di.xml, sorted by sort_order.
 *
 * Which optional categories a store view offers is a setting
 * ({@see Config::getEnabledCategoryCodes()}); required ones are always offered.
 *
 * A category, as returned: code (string), label and description (English, pass them
 * through __()), required (bool), sort_order (int), consent_mode (string[]: the Google
 * Consent Mode signals it grants).
 */
class CategoryPool
{
    /** @var array<string, array<string, mixed>> code => category */
    private array $categories = [];

    /**
     * Normalize and sort the categories from di.xml.
     *
     * @param array $categories code => definition (see etc/di.xml)
     */
    public function __construct(array $categories = [])
    {
        foreach ($categories as $code => $definition) {
            $code = (string) $code;
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $code)) {
                // The code becomes a cookie key and a JS property; keep it boring.
                continue;
            }
            $this->categories[$code] = [
                'code' => $code,
                'label' => (string) ($definition['label'] ?? $code),
                'description' => (string) ($definition['description'] ?? ''),
                'required' => (bool) ($definition['required'] ?? false),
                'sort_order' => (int) ($definition['sort_order'] ?? 100),
                'consent_mode' => array_values(array_map('strval', (array) ($definition['consent_mode'] ?? []))),
            ];
        }
        uasort($this->categories, fn(array $a, array $b) => $a['sort_order'] <=> $b['sort_order']);
    }

    /**
     * All categories, sorted.
     *
     * @return array<string, array<string, mixed>> code => category
     */
    public function getAll(): array
    {
        return $this->categories;
    }

    /**
     * Required categories plus the given optional ones, in pool order.
     *
     * @param string[] $optionalCodes
     * @return array<string, array<string, mixed>> code => category
     */
    public function getOffered(array $optionalCodes): array
    {
        return array_filter(
            $this->categories,
            fn(array $category) => $category['required'] || in_array($category['code'], $optionalCodes, true)
        );
    }

    /**
     * Whether a category is always granted.
     *
     * @param string $code
     * @return bool
     */
    public function isRequired(string $code): bool
    {
        return $this->categories[$code]['required'] ?? false;
    }
}
