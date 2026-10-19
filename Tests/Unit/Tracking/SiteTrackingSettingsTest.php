<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit\Tracking;

use PHPUnit\Framework\TestCase;
use Tpwd\Kiscore\Tracking\SiteTrackingSettings;

class SiteTrackingSettingsTest extends TestCase
{
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

    public function testReadsSiteIdAndSecretFromTheConnectionKey(): void
    {
        $settings = SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_connection_key' => ' 6f1c2d3e.s3cr3t ',
        ]);

        self::assertNotNull($settings);
        self::assertSame('6f1c2d3e', $settings->siteId);
        self::assertSame('s3cr3t', $settings->trackingSecret);
    }

    public function testPrefersTheConnectionKeyOverTheSeparateFields(): void
    {
        $settings = SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_connection_key' => 'new-site.new-secret',
            'kiscore_site_id' => 'old-site',
            'kiscore_tracking_secret' => 'old-secret',
        ]);

        self::assertSame(['new-site', 'new-secret'], [$settings?->siteId, $settings?->trackingSecret]);
    }

    public function testFallsBackToTheSeparateFieldsWhenTheConnectionKeyIsMalformed(): void
    {
        $settings = SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_connection_key' => 'only-the-site-id',
            'kiscore_site_id' => 'old-site',
            'kiscore_tracking_secret' => 'old-secret',
        ]);

        self::assertSame(['old-site', 'old-secret'], [$settings?->siteId, $settings?->trackingSecret]);
        self::assertNull(SiteTrackingSettings::fromSiteConfiguration(['kiscore_connection_key' => 'site.secret.extra']));
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
