<?php
/**
 * Slova_CookieConsent — consimțământ cookie granular (GDPR/ePrivacy).
 * Copyright © Slova. All rights reserved.
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Slova_CookieConsent',
    __DIR__
);
