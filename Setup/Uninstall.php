<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

/**
 * `module:uninstall Slova_CookieConsent --remove-data`: deletes the module's settings.
 * The module has no tables; visitors' consent cookies expire on their own.
 */
class Uninstall implements UninstallInterface
{
    /**
     * Delete all slova_cookieconsent/* settings.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        $setup->getConnection()->delete(
            $setup->getTable('core_config_data'),
            ['path LIKE ?' => 'slova_cookieconsent/%']
        );
        $setup->endSetup();
    }
}
