<?php
/**
 * Slova_CookieConsent
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\CookieConsent\Plugin;

use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Page\Config\Renderer;
use Psr\Log\LoggerInterface;
use Slova\CookieConsent\Model\CategoryPool;
use Slova\CookieConsent\Model\Config;

/**
 * Puts web/js/consent-mode.js right after <title>, as a blocking script.
 *
 * Google Consent Mode needs its default before any Google tag runs. The page head
 * renders head content (meta + title), then assets and the design config's Misc
 * Scripts (where Google Tag Manager usually is), then head.additional: a layout
 * block would come too late for a GTM snippet pasted into Misc Scripts.
 */
class ConsentModeDefaults
{
    /**
     * @param Config $config
     * @param CategoryPool $categoryPool
     * @param AssetRepository $assetRepository
     * @param Escaper $escaper
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly CategoryPool $categoryPool,
        private readonly AssetRepository $assetRepository,
        private readonly Escaper $escaper,
        private readonly Json $json,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Append the Consent Mode script to the head content.
     *
     * @param Renderer $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRenderHeadContent(Renderer $subject, $result)
    {
        if (!$this->config->sendsConsentMode()) {
            return $result;
        }

        try {
            $src = $this->assetRepository->getUrl('Slova_CookieConsent::js/consent-mode.js');
        } catch (\Exception $e) {
            $this->logger->error('Slova_CookieConsent: Consent Mode script unavailable: ' . $e->getMessage());
            return $result;
        }

        return $result . sprintf(
            "<script src=\"%s\" data-slova-consent-mode=\"%s\"></script>\n",
            $this->escaper->escapeUrl($src),
            $this->escaper->escapeHtmlAttr($this->getScriptConfig())
        );
    }

    /**
     * Settings for consent-mode.js.
     *
     * The signals map gives, for each category this store view offers, the Consent Mode
     * signals it grants. Required categories are granted from the start; every other
     * signal any category knows starts denied.
     *
     * @return string
     */
    private function getScriptConfig(): string
    {
        $defaults = [];
        foreach ($this->categoryPool->getAll() as $category) {
            foreach ($category['consent_mode'] as $signal) {
                $defaults[$signal] = $category['required'] ? 'granted' : ($defaults[$signal] ?? 'denied');
            }
        }

        $signals = [];
        foreach ($this->categoryPool->getOffered($this->config->getEnabledCategoryCodes()) as $category) {
            if (!$category['required'] && $category['consent_mode'] !== []) {
                $signals[$category['code']] = $category['consent_mode'];
            }
        }

        return (string) $this->json->serialize([
            'cookieName' => $this->config->getCookieName(),
            'version' => $this->config->getConsentVersion(),
            'defaults' => (object) $defaults,
            'signals' => (object) $signals,
            'waitForUpdate' => $this->config->getWaitForUpdate(),
            'adsDataRedaction' => $this->config->redactsAdsData(),
        ]);
    }
}
