<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Slova\CookieConsent\Model\Config;

/**
 * Where the banner sits.
 */
class Position implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::POSITION_CORNER_RIGHT, 'label' => __('Card, bottom right')],
            ['value' => Config::POSITION_CORNER_LEFT, 'label' => __('Card, bottom left')],
            ['value' => Config::POSITION_BAR, 'label' => __('Bar across the bottom')],
        ];
    }
}
