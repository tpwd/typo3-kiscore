<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Tpwd\Kiscore\Middleware\KiscoreTrackingMiddleware;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class KiscoreTrackingMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    public function testOneSiteKeyAuthenticatesTheReportAndPreservesThePage(): void
    {
        $site = new Site('example', 1, [
            'base' => 'https://example.com/',
            'kiscore_connection_key' => 'test-site.test-secret',
        ]);
        $request = (new ServerRequest('https://example.com/?utm_source=chatgpt.com', 'GET'))
            ->withAttribute('site', $site)
            ->withAttribute('applicationType', 1);
        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        $factory = $this->createMock(RequestFactory::class);
        $factory->expects(self::once())->method('request')
            ->with('https://api.kiscore.ai/track/test-site', 'POST', self::callback(
                static fn(array $options): bool =>
                    $options['headers']['X-Kiscore-Tracking-Secret'] === 'test-secret'
                    && $options['json']['url'] === '/?utm_source=chatgpt.com'
                    && $options['allow_redirects'] === false
            ))->willReturn(new Response());
        GeneralUtility::addInstance(RequestFactory::class, $factory);

        self::assertSame($response, (new KiscoreTrackingMiddleware())->process($request, $handler));
    }
}
