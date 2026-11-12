<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit\Tracking;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tpwd\Kiscore\Tracking\PageView;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;

class PageViewTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     */
    private static function request(string $method = 'GET', string $uri = 'https://example.com/page', array $headers = []): ServerRequest
    {
        $request = new ServerRequest($uri, $method);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        parse_str((string)parse_url($uri, PHP_URL_QUERY), $query);

        return $request->withQueryParams($query);
    }

    private static function html(int $status = 200): Response
    {
        return (new Response('php://temp', $status))->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function testAPageOpenedByABrowserOrACrawlerIsReported(): void
    {
        self::assertTrue(PageView::isReportable(self::request(), self::html()));
        self::assertTrue(PageView::isReportable(
            self::request('GET', 'https://example.com/?utm_source=chatgpt.com', [
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Dest' => 'document',
            ]),
            self::html()
        ));
        // TYPO3 sets the content type late; a response without one is a page
        self::assertTrue(PageView::isReportable(self::request(), new Response()));
    }

    /**
     * @return array<string, array{ServerRequest, Response}>
     */
    public static function notPageViews(): array
    {
        return [
            'form submit' => [self::request('POST'), self::html()],
            'HEAD' => [self::request('HEAD'), self::html()],
            'OPTIONS' => [self::request('OPTIONS'), self::html()],
            'redirect' => [self::request(), self::html(302)],
            'not found' => [self::request(), self::html(404)],
            'server error' => [self::request(), self::html(503)],
            'JSON page type' => [self::request(), (new Response())->withHeader('Content-Type', 'application/json')],
            'XML sitemap' => [self::request(), (new Response())->withHeader('Content-Type', 'application/xml; charset=utf-8')],
            'page type parameter' => [self::request('GET', 'https://example.com/page?type=1533906435'), self::html()],
            'AJAX' => [self::request('GET', 'https://example.com/page', ['X-Requested-With' => 'XMLHttpRequest']), self::html()],
            'fetch from script' => [self::request('GET', 'https://example.com/page', ['Sec-Fetch-Mode' => 'cors']), self::html()],
            'image request' => [self::request('GET', 'https://example.com/page', ['Sec-Fetch-Dest' => 'image']), self::html()],
            'prefetch' => [self::request('GET', 'https://example.com/page', ['Sec-Purpose' => 'prefetch;prerender']), self::html()],
            'legacy prefetch' => [self::request('GET', 'https://example.com/page', ['Purpose' => 'prefetch']), self::html()],
            'Firefox prefetch' => [self::request('GET', 'https://example.com/page', ['X-Moz' => 'prefetch']), self::html()],
        ];
    }

    #[DataProvider('notPageViews')]
    public function testEverythingElseStaysOnTheServer(ServerRequest $request, Response $response): void
    {
        self::assertFalse(PageView::isReportable($request, $response));
    }
}
