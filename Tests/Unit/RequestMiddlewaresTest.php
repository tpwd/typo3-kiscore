<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * An ordering anchor that does not exist is ignored silently by TYPO3, so a
 * renamed core middleware would move the tracking without any error.
 */
class RequestMiddlewaresTest extends TestCase
{
    public function testOrdersTheTrackingAroundExistingFrontendMiddlewares(): void
    {
        $core = require dirname(__DIR__, 2) . '/.Build/vendor/typo3/cms-frontend/Configuration/RequestMiddlewares.php';
        $ours = require dirname(__DIR__, 2) . '/Configuration/RequestMiddlewares.php';
        $tracking = $ours['frontend']['tpwd/kiscore/tracking'];

        foreach (array_merge($tracking['after'], $tracking['before']) as $anchor) {
            self::assertArrayHasKey($anchor, $core['frontend'], $anchor);
        }
    }
}
