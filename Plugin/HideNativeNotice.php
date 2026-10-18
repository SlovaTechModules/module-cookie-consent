<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Plugin;

use Magento\Cookie\Block\Html\Notices;
use Slova\CookieConsent\Model\Config;

/**
 * Silences Magento's Cookie Restriction Mode notice while this banner is on, so a
 * store that left Cookie Restriction Mode enabled does not show two banners.
 */
class HideNativeNotice
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Drop the native notice's HTML.
     *
     * @param Notices $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterToHtml(Notices $subject, $result)
    {
        return $this->config->isEnabled() ? '' : $result;
    }
}
