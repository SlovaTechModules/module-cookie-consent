<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Slova\CookieConsent\Model\Config;

/**
 * Settings parsing and the guards on admin input.
 */
class ConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $values path => value
     * @return Config
     */
    private function config(array $values): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(fn($path) => $values[$path] ?? null);
        $scopeConfig->method('isSetFlag')->willReturnCallback(fn($path) => (bool) ($values[$path] ?? false));

        return new Config($scopeConfig);
    }

    public function testCategoriesFromTheMultiselect(): void
    {
        $config = $this->config(['slova_cookieconsent/general/categories' => 'analytics, marketing,,']);

        $this->assertSame(['analytics', 'marketing'], $config->getEnabledCategoryCodes());
        $this->assertSame([], $this->config([])->getEnabledCategoryCodes());
    }

    public function testVersionIsAtLeastOne(): void
    {
        $path = 'slova_cookieconsent/general/consent_version';

        $this->assertSame(1, $this->config([])->getConsentVersion());
        $this->assertSame(3, $this->config([$path => '3'])->getConsentVersion());
    }

    public function testLifetimeFallsBackAndIsCapped(): void
    {
        $path = 'slova_cookieconsent/general/lifetime_days';

        $this->assertSame(180, $this->config([])->getLifetimeDays());
        $this->assertSame(365, $this->config([$path => '365'])->getLifetimeDays());
        $this->assertSame(3650, $this->config([$path => '99999'])->getLifetimeDays());
    }

    public function testOnlyHexAccentColorsPass(): void
    {
        $path = 'slova_cookieconsent/appearance/accent_color';

        $this->assertSame('#0f766e', $this->config([$path => ' #0f766e '])->getAccentColor());
        $this->assertSame('#FFF', $this->config([$path => '#FFF'])->getAccentColor());
        $this->assertSame('', $this->config([$path => 'red;} body{display:none'])->getAccentColor());
        $this->assertSame('', $this->config([$path => '#12345'])->getAccentColor());
    }

    public function testUnknownPositionFallsBackToTheCorner(): void
    {
        $path = 'slova_cookieconsent/appearance/position';

        $this->assertSame(Config::POSITION_BAR, $this->config([$path => 'bar'])->getPosition());
        $this->assertSame(Config::POSITION_CORNER_RIGHT, $this->config([$path => 'top'])->getPosition());
    }

    public function testConsentModeNeedsTheBannerEnabled(): void
    {
        $this->assertFalse($this->config(['slova_cookieconsent/consent_mode/enabled' => '1'])->sendsConsentMode());
        $this->assertTrue($this->config([
            'slova_cookieconsent/general/enabled' => '1',
            'slova_cookieconsent/consent_mode/enabled' => '1',
        ])->sendsConsentMode());
    }

    public function testWaitForUpdateIsBounded(): void
    {
        $path = 'slova_cookieconsent/consent_mode/wait_for_update';

        $this->assertSame(500, $this->config([$path => '500'])->getWaitForUpdate());
        $this->assertSame(0, $this->config([$path => '-5'])->getWaitForUpdate());
        $this->assertSame(10000, $this->config([$path => '600000'])->getWaitForUpdate());
    }
}
