<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tpwd\Kiscore\Constants;

class ConstantsTest extends TestCase
{
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

    public function testUserAgentNamesTheExtensionAndItsVersion(): void
    {
        $_EXTKEY = 'kiscore';
        $EM_CONF = [];
        require __DIR__ . '/../../ext_emconf.php';

        // KIscore marks the TYPO3 card as connected by this User-Agent
        $this->assertSame('KIscore-TYPO3/' . $EM_CONF[$_EXTKEY]['version'], Constants::USER_AGENT);
    }
}
