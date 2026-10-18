<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Test\Unit\Model;

use Magento\Framework\Stdlib\CookieManagerInterface;
use PHPUnit\Framework\TestCase;
use Slova\CookieConsent\Model\CategoryPool;
use Slova\CookieConsent\Model\Config;
use Slova\CookieConsent\Model\Consent;

/**
 * Reading the visitor's choice on the server.
 */
class ConsentTest extends TestCase
{
    private function consent(?string $cookie, int $version = 2): Consent
    {
        $cookieManager = $this->createMock(CookieManagerInterface::class);
        $cookieManager->method('getCookie')->with('slova_consent')->willReturn($cookie);

        $config = $this->createMock(Config::class);
        $config->method('getCookieName')->willReturn('slova_consent');
        $config->method('getConsentVersion')->willReturn($version);

        $pool = new CategoryPool([
            'necessary' => ['required' => true],
            'analytics' => [],
            'marketing' => [],
        ]);

        return new Consent($cookieManager, $config, $pool);
    }

    public function testNoCookieMeansNoChoiceAndOnlyRequiredCategories(): void
    {
        $consent = $this->consent(null);

        $this->assertFalse($consent->hasDecided());
        $this->assertTrue($consent->allows('necessary'));
        $this->assertFalse($consent->allows('analytics'));
        $this->assertSame(['necessary' => true, 'analytics' => false, 'marketing' => false], $consent->getChoices());
    }

    public function testPercentEncodedCookieFromTheBrowser(): void
    {
        $consent = $this->consent(rawurlencode('{"v":2,"necessary":true,"analytics":true,"marketing":false,"ts":1}'));

        $this->assertTrue($consent->hasDecided());
        $this->assertTrue($consent->allows('analytics'));
        $this->assertFalse($consent->allows('marketing'));
    }

    public function testRawJsonCookie(): void
    {
        $this->assertTrue($this->consent('{"v":2,"marketing":true}')->allows('marketing'));
    }

    public function testAnotherVersionCountsAsNoChoice(): void
    {
        $consent = $this->consent('{"v":1,"analytics":true,"marketing":true}');

        $this->assertFalse($consent->hasDecided());
        $this->assertFalse($consent->allows('analytics'));
    }

    public function testOnlyTrueGrants(): void
    {
        $consent = $this->consent('{"v":2,"analytics":"yes","marketing":1}');

        $this->assertFalse($consent->allows('analytics'));
        $this->assertFalse($consent->allows('marketing'));
    }

    public function testGarbageCookie(): void
    {
        $consent = $this->consent('not json');

        $this->assertFalse($consent->hasDecided());
        $this->assertTrue($consent->allows('necessary'));
    }

    public function testUnknownCategoryIsNotAllowed(): void
    {
        $this->assertFalse($this->consent('{"v":2,"social":true}')->allows('social'));
    }
}
