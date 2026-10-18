<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Slova\CookieConsent\Model\CategoryPool;

/**
 * Category definitions from di.xml: normalized, sorted, filtered per store view.
 */
class CategoryPoolTest extends TestCase
{
    private function pool(): CategoryPool
    {
        return new CategoryPool([
            'marketing' => ['label' => 'Marketing', 'sort_order' => 40, 'consent_mode' => ['a' => 'ad_storage']],
            'necessary' => ['label' => 'Necessary', 'required' => true, 'sort_order' => 10],
            'analytics' => ['label' => 'Analytics', 'sort_order' => 30, 'consent_mode' => ['analytics_storage']],
            'Bad Code' => ['label' => 'Ignored'],
        ]);
    }

    public function testCategoriesAreSortedAndInvalidCodesDropped(): void
    {
        $this->assertSame(['necessary', 'analytics', 'marketing'], array_keys($this->pool()->getAll()));
    }

    public function testDefinitionsAreNormalized(): void
    {
        $marketing = $this->pool()->getAll()['marketing'];

        $this->assertSame('', $marketing['description']);
        $this->assertFalse($marketing['required']);
        $this->assertSame(['ad_storage'], $marketing['consent_mode']);
    }

    public function testRequiredCategoriesAreAlwaysOffered(): void
    {
        $this->assertSame(['necessary', 'marketing'], array_keys($this->pool()->getOffered(['marketing', 'unknown'])));
        $this->assertSame(['necessary'], array_keys($this->pool()->getOffered([])));
    }

    public function testIsRequired(): void
    {
        $this->assertTrue($this->pool()->isRequired('necessary'));
        $this->assertFalse($this->pool()->isRequired('analytics'));
        $this->assertFalse($this->pool()->isRequired('unknown'));
    }
}
