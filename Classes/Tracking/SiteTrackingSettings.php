<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tracking;

use Tpwd\Kiscore\Constants;

/**
 * Site ID and tracking secret of one TYPO3 site
 */
final class SiteTrackingSettings
{
    private function __construct(
        public readonly string $siteId,
        public readonly string $trackingSecret,
    ) {}

    /**
     * Returns null unless both values are configured: the API rejects
     * requests without a matching tracking secret.
     *
     * @param array<string, mixed> $configuration
     */
    public static function fromSiteConfiguration(array $configuration): ?self
    {
        $siteId = trim((string)($configuration['kiscore_site_id'] ?? ''));
        $trackingSecret = trim((string)($configuration['kiscore_tracking_secret'] ?? ''));

        if ($siteId === '' || $trackingSecret === '') {
            return null;
        }

        return new self($siteId, $trackingSecret);
    }

    public function trackingUrl(): string
    {
        return Constants::KISCORE_TRACKING_ENDPOINT . rawurlencode($this->siteId);
    }
}
