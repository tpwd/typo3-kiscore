<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tracking;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;

/**
 * Builds the body of a tracking request from a frontend request
 */
final class TrackingPayload
{
    /**
     * Byte limits the Ki-Score API accepts per field
     */
    private const USER_AGENT_BYTES = 1024;
    private const REFERER_BYTES = 2048;
    private const URL_BYTES = 2048;

    /**
     * @return array<string, string>
     */
    public static function fromRequest(ServerRequestInterface $request): array
    {
        $payload = [
            'user_agent' => self::truncate($request->getHeaderLine('User-Agent'), self::USER_AGENT_BYTES),
            'referer' => self::truncate($request->getHeaderLine('Referer'), self::REFERER_BYTES),
            'url' => self::truncate(self::trackedUrl($request), self::URL_BYTES),
        ];

        // Without an IP the API would count the TYPO3 server as the visitor
        $ip = self::clientIp($request);
        if ($ip !== '') {
            $payload['ip'] = $ip;
        }

        return $payload;
    }

    /**
     * Ki-Score recognises AI referrals by utm_source as well. Only the path and
     * utm parameters are sent: other query parameters can hold tokens or email
     * addresses.
     */
    private static function trackedUrl(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath() === '' ? '/' : $uri->getPath();

        $utmPairs = array_filter(
            explode('&', $uri->getQuery()),
            static fn(string $pair): bool => stripos(rawurldecode($pair), 'utm_') === 0
        );

        return $utmPairs === [] ? $path : $path . '?' . implode('&', $utmPairs);
    }

    private static function clientIp(ServerRequestInterface $request): string
    {
        // NormalizedParams honours TYPO3's reverse proxy configuration
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            return $normalizedParams->getRemoteAddress();
        }

        return (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
    }

    private static function truncate(string $value, int $maxBytes): string
    {
        return mb_strcut($value, 0, $maxBytes, 'UTF-8');
    }
}
