<?php
/**
 * Slova_CookieConsent — GDPR / ePrivacy cookie consent for Hyvä.
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Slova_CookieConsent',
    __DIR__
);
