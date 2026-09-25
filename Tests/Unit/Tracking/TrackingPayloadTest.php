<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tests\Unit\Tracking;

use PHPUnit\Framework\TestCase;
use Tpwd\Kiscore\Tracking\TrackingPayload;
use TYPO3\CMS\Core\Http\ServerRequest;

class TrackingPayloadTest extends TestCase
{
    public function testSendsPathWithUtmParametersOnly(): void
    {
        $request = new ServerRequest(
            'https://example.com/pricing?utm_source=chatgpt.com&token=secret&UTM_Medium=ai&email=a%40b.de',
            'GET'
        );

        $payload = TrackingPayload::fromRequest($request);

        self::assertSame('/pricing?utm_source=chatgpt.com&UTM_Medium=ai', $payload['url']);
    }

    public function testSendsPlainPathWithoutUtmParameters(): void
    {
        $request = new ServerRequest('https://example.com/about?page=2', 'GET');

        self::assertSame('/about', TrackingPayload::fromRequest($request)['url']);
    }

    public function testSendsUserAgentRefererAndClientIp(): void
    {
        $request = (new ServerRequest('https://example.com/', 'GET', null, [], ['REMOTE_ADDR' => '203.0.113.7']))
            ->withHeader('User-Agent', 'GPTBot/1.0')
            ->withHeader('Referer', 'https://chatgpt.com/');

        $payload = TrackingPayload::fromRequest($request);

        self::assertSame('GPTBot/1.0', $payload['user_agent']);
        self::assertSame('https://chatgpt.com/', $payload['referer']);
        self::assertSame('203.0.113.7', $payload['ip']);
    }

    public function testTruncatesFieldsToTheLimitsTheApiAccepts(): void
    {
        $request = (new ServerRequest('https://example.com/' . str_repeat('a', 3000), 'GET'))
            ->withHeader('User-Agent', str_repeat('b', 2000))
            ->withHeader('Referer', 'https://example.com/' . str_repeat('ä', 2000));

        $payload = TrackingPayload::fromRequest($request);

        self::assertSame(2048, strlen($payload['url']));
        self::assertSame(1024, strlen($payload['user_agent']));
        self::assertLessThanOrEqual(2048, strlen($payload['referer']));
        self::assertTrue(mb_check_encoding($payload['referer'], 'UTF-8'));
    }
}
