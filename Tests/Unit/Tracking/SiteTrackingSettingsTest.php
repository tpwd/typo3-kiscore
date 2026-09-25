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

    public function testBuildsTheTrackingUrlOnTheKiscoreApi(): void
    {
        $settings = SiteTrackingSettings::fromSiteConfiguration([
            'kiscore_site_id' => 'a/b',
            'kiscore_tracking_secret' => 's3cr3t',
        ]);

        self::assertSame('https://api.kiscore.ai/track/a%2Fb', $settings?->trackingUrl());
    }
}
