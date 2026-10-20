<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit\Tracking;

use PHPUnit\Framework\TestCase;
use Tpwd\Kiscore\Tracking\SiteTrackingSettings;

class SiteTrackingSettingsTest extends TestCase
{
    public function testReadsBothCredentialsFromOneConnectionKey(): void
    {
        $settings = SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_connection_key' => ' new-site.new-secret ',
            'kiscore_site_id' => 'old-site',
            'kiscore_tracking_secret' => 'old-secret',
        ]);

        self::assertNotNull($settings);
        self::assertSame('new-site', $settings->siteId);
        self::assertSame('new-secret', $settings->trackingSecret);
    }

    public function testRejectsMalformedKeysWithoutUsingOldCredentials(): void
    {
        foreach (['missing-separator', '.secret', 'site.', 'site.secret.extra', 'site id.secret', '%env(MISSING_KEY)%'] as $key) {
            self::assertNull(SiteTrackingSettings::fromSiteConfiguration([
                'kiscore_connection_key' => $key,
                'kiscore_site_id' => 'old-site',
                'kiscore_tracking_secret' => 'old-secret',
            ]));
        }
    }

    public function testReadsSiteIdAndSecretFromTheSiteConfiguration(): void
    {
        $settings = SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_site_id' => ' 6f1c2d3e ',
            'kiscore_tracking_secret' => ' s3cr3t ',
        ]);

        self::assertNotNull($settings);
        self::assertSame('6f1c2d3e', $settings->siteId);
        self::assertSame('s3cr3t', $settings->trackingSecret);
    }

    public function testIsIncompleteWithoutTrackingSecret(): void
    {
        self::assertNull(SiteTrackingSettings::fromSiteConfiguration(['kiscore_site_id' => '6f1c2d3e']));
        self::assertNull(SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_site_id' => '6f1c2d3e',
            'kiscore_tracking_secret' => '  ',
        ]));
    }

    public function testIsIncompleteWithoutSiteId(): void
    {
        self::assertNull(SiteTrackingSettings::fromSiteConfiguration(['kiscore_tracking_secret' => 's3cr3t']));
    }

    public function testBuildsTheTrackingUrlOnTheKiscoreApi(): void
    {
        $settings = SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_site_id' => 'a/b',
            'kiscore_tracking_secret' => 's3cr3t',
        ]);

        self::assertSame('https://api.kiscore.ai/track/a%2Fb', $settings?->trackingUrl());
    }
}
