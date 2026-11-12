<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit\Middleware;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Tpwd\Kiscore\Middleware\KiscoreTrackingMiddleware;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
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
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response()]));
        $stack->push(Middleware::history($history));
        $previousHttp = $GLOBALS['TYPO3_CONF_VARS']['HTTP'] ?? null;
        $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = ['verify' => true, 'handler' => $stack];
        try {
            $factory = class_exists(GuzzleClientFactory::class)
                ? new RequestFactory(new GuzzleClientFactory())
                : new RequestFactory();
            GeneralUtility::addInstance(RequestFactory::class, $factory);

            $middleware = new class () extends KiscoreTrackingMiddleware {
                /** @var list<callable> */
                public array $deferred = [];

                protected function afterResponse(callable $report): void
                {
                    $this->deferred[] = $report;
                }
            };

            // The page is returned before anything is sent to KIscore
            self::assertSame($response, $middleware->process($request, $handler));
            self::assertCount(0, $history);
            self::assertCount(1, $middleware->deferred);

            ($middleware->deferred[0])();
            self::assertCount(1, $history);
            self::assertSame('https://api.kiscore.ai/track/test-site', (string)$history[0]['request']->getUri());
            self::assertSame('test-secret', $history[0]['request']->getHeaderLine('X-Kiscore-Tracking-Secret'));
            self::assertSame('KIscore-TYPO3/2.1.0', $history[0]['request']->getHeaderLine('User-Agent'));
            $payload = json_decode((string)$history[0]['request']->getBody(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('/?utm_source=chatgpt.com', $payload['url']);
            self::assertFalse($history[0]['options']['allow_redirects']);
        } finally {
            if ($previousHttp === null) {
                unset($GLOBALS['TYPO3_CONF_VARS']['HTTP']);
            } else {
                $GLOBALS['TYPO3_CONF_VARS']['HTTP'] = $previousHttp;
            }
        }
    }

    public function testARequestThatIsNoPageViewIsNotReported(): void
    {
        $site = new Site('example', 1, [
            'base' => 'https://example.com/',
            'kiscore_connection_key' => 'test-site.test-secret',
        ]);
        $request = (new ServerRequest('https://example.com/kontakt', 'POST'))
            ->withAttribute('site', $site)
            ->withAttribute('applicationType', 1);
        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        $middleware = new class () extends KiscoreTrackingMiddleware {
            public int $deferred = 0;

            protected function afterResponse(callable $report): void
            {
                $this->deferred++;
            }
        };

        self::assertSame($response, $middleware->process($request, $handler));
        self::assertSame(0, $middleware->deferred);
    }

    public function testABrokenSiteConfigurationNeverBreaksThePage(): void
    {
        $site = new Site('example', 1, ['base' => 'https://example.com/', 'kiscore_connection_key' => ['not', 'a', 'string']]);
        $request = (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('site', $site)
            ->withAttribute('applicationType', 1);
        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        self::assertSame($response, (new KiscoreTrackingMiddleware())->process($request, $handler));
    }
}
