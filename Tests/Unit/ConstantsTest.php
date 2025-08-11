<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tpwd\Kiscore\Constants;

class ConstantsTest extends TestCase
{
    public function testDefaultSiteIdIsNonEmptyString(): void
    {
        $this->assertIsString(Constants::DEFAULT_SITE_ID);
        $this->assertNotSame('', Constants::DEFAULT_SITE_ID);
    }

    public function testTrackingEndpointEndsWithSlash(): void
    {
        $this->assertIsString(Constants::KISCORE_TRACKING_ENDPOINT);
        $this->assertStringEndsWith('/', Constants::KISCORE_TRACKING_ENDPOINT);
    }

    public function testHttpTimeoutsArePositiveFloats(): void
    {
        $this->assertIsFloat(Constants::HTTP_TIMEOUT);
        $this->assertGreaterThan(0.0, Constants::HTTP_TIMEOUT);

        $this->assertIsFloat(Constants::HTTP_CONNECT_TIMEOUT);
        $this->assertGreaterThan(0.0, Constants::HTTP_CONNECT_TIMEOUT);

        // connect timeout should be less or equal than total timeout
        $this->assertLessThanOrEqual(Constants::HTTP_TIMEOUT, Constants::HTTP_CONNECT_TIMEOUT);
    }
}
