<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tracking;

use Tpwd\Kiscore\Constants;

/**
 * Site ID and tracking secret of one TYPO3 site. The Ki-Score dashboard shows
 * both as one connection key "<site-id>.<secret>"; the separate fields of
 * version 2.0 keep working.
 */
final class SiteTrackingSettings
{
    private function __construct(
        public readonly string $siteId,
        public readonly string $trackingSecret,
    ) {}

    /**
     * Accepts one connection key, or both legacy values. The API rejects
     * requests without a matching tracking secret.
     *
     * @param array<string, mixed> $configuration
     */
    public static function fromSiteConfiguration(array $configuration): ?self
    {
        $connectionKey = trim((string)($configuration['kiscore_connection_key'] ?? ''));
        if ($connectionKey !== '') {
            if (!preg_match('/^([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)$/D', $connectionKey, $parts)) {
                return null;
            }
            return new self($parts[1], $parts[2]);
        }

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
