<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Test\Unit\Plugin;

use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Page\Config\Renderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slova\CookieConsent\Model\CategoryPool;
use Slova\CookieConsent\Model\Config;
use Slova\CookieConsent\Plugin\ConsentModeDefaults;

/**
 * The Consent Mode script tag and the defaults it carries.
 */
class ConsentModeDefaultsTest extends TestCase
{
    private function render(bool $enabled, array $offered = ['analytics', 'marketing']): string
    {
        $config = $this->createMock(Config::class);
        $config->method('sendsConsentMode')->willReturn($enabled);
        $config->method('getEnabledCategoryCodes')->willReturn($offered);
        $config->method('getCookieName')->willReturn('slova_consent');
        $config->method('getConsentVersion')->willReturn(1);
        $config->method('getWaitForUpdate')->willReturn(500);
        $config->method('redactsAdsData')->willReturn(true);

        $pool = new CategoryPool([
            'necessary' => ['required' => true, 'sort_order' => 10, 'consent_mode' => ['security_storage']],
            'preferences' => ['sort_order' => 20, 'consent_mode' => ['functionality_storage']],
            'analytics' => ['sort_order' => 30, 'consent_mode' => ['analytics_storage']],
            'marketing' => ['sort_order' => 40, 'consent_mode' => ['ad_storage', 'ad_user_data']],
        ]);

        $assets = $this->createMock(AssetRepository::class);
        $assets->method('getUrl')->willReturn('https://shop.test/static/Slova_CookieConsent/js/consent-mode.js');

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeUrl')->willReturnArgument(0);
        $escaper->method('escapeHtmlAttr')->willReturnCallback(fn($s) => htmlspecialchars((string) $s, ENT_QUOTES));

        $plugin = new ConsentModeDefaults(
            $config,
            $pool,
            $assets,
            $escaper,
            new Json(),
            $this->createMock(LoggerInterface::class)
        );

        return $plugin->afterRenderHeadContent($this->createMock(Renderer::class), '<title>Shop</title>');
    }

    /**
     * @return array<string, mixed>
     */
    private function scriptConfig(string $html): array
    {
        $this->assertSame(1, preg_match('/data-slova-consent-mode="([^"]*)"/', $html, $match));

        return json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
    }

    public function testNothingAddedWhenDisabled(): void
    {
        $this->assertSame('<title>Shop</title>', $this->render(false));
    }

    public function testScriptComesAfterTheTitle(): void
    {
        $html = $this->render(true);

        $this->assertStringStartsWith('<title>Shop</title><script src="https://shop.test/', $html);
        $this->assertStringNotContainsString('async', $html);
        $this->assertStringNotContainsString('defer', $html);
    }

    public function testEverythingOptionalStartsDenied(): void
    {
        $cfg = $this->scriptConfig($this->render(true));

        $this->assertSame([
            'security_storage' => 'granted',
            'functionality_storage' => 'denied',
            'analytics_storage' => 'denied',
            'ad_storage' => 'denied',
            'ad_user_data' => 'denied',
        ], $cfg['defaults']);
        $this->assertSame(500, $cfg['waitForUpdate']);
        $this->assertTrue($cfg['adsDataRedaction']);
    }

    public function testOnlyOfferedCategoriesCanGrantSignals(): void
    {
        $cfg = $this->scriptConfig($this->render(true, ['marketing']));

        $this->assertSame(['marketing' => ['ad_storage', 'ad_user_data']], $cfg['signals']);
        // Not offered, so never granted — but still denied by default.
        $this->assertSame('denied', $cfg['defaults']['analytics_storage']);
    }
}
