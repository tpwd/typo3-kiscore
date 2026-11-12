<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Tracking;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Decides whether a frontend request is a page someone opened. KIscore counts
 * crawler visits and AI referrals per page view; form submits, AJAX calls,
 * prefetches and error pages are none.
 */
final class PageView
{
    public static function isReportable(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        if (strtoupper($request->getMethod()) !== 'GET') {
            return false;
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return false;
        }

        // JSON, XML sitemaps and feeds are page types, not pages
        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if ($contentType !== '' && !str_contains($contentType, 'text/html')) {
            return false;
        }
        $pageType = $request->getQueryParams()['type'] ?? '0';
        if (!is_scalar($pageType) || (string)$pageType !== '0') {
            return false;
        }

        if (strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest') {
            return false;
        }

        // Browsers announce what a request is for; crawlers send none of these
        $fetchMode = strtolower($request->getHeaderLine('Sec-Fetch-Mode'));
        if ($fetchMode !== '' && $fetchMode !== 'navigate') {
            return false;
        }
        $fetchDest = strtolower($request->getHeaderLine('Sec-Fetch-Dest'));
        if ($fetchDest !== '' && !in_array($fetchDest, ['document', 'iframe', 'frame'], true)) {
            return false;
        }

        // A prefetched or prerendered page may never be seen
        foreach (['Sec-Purpose', 'Purpose', 'X-Purpose', 'X-Moz'] as $header) {
            $purpose = strtolower($request->getHeaderLine($header));
            if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender') || str_contains($purpose, 'preview')) {
                return false;
            }
        }

        return true;
    }
}
