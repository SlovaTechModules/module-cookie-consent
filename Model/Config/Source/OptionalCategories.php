<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Slova\CookieConsent\Model\CategoryPool;

/**
 * The categories a store may offer: every category in the pool that is not required.
 */
class OptionalCategories implements OptionSourceInterface
{
    /**
     * @param CategoryPool $categoryPool
     */
    public function __construct(
        private readonly CategoryPool $categoryPool
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->categoryPool->getAll() as $category) {
            if (!$category['required']) {
                // phpcs:ignore Magento2.Translation.ConstantUsage -- labels come from di.xml
                $options[] = ['value' => $category['code'], 'label' => __($category['label'])];
            }
        }

        return $options;
    }
}
