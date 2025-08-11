<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tpwd\Kiscore\Constants;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Middleware to track frontend requests and send data to kiscore.de
 */
class KiscoreTrackingMiddleware implements MiddlewareInterface
{
    protected RequestFactory $requestFactory;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Process the request first to not delay the response
        $response = $handler->handle($request);

        // Only track frontend requests
        if (!$this->isFrontendRequest($request)) {
            return $response;
        }

        // Get site from request
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $response;
        }

        // Do not track redirects
        if ($response->getStatusCode() >= 300 && $response->getStatusCode() <= 399) {
            return $response;
        }

        // Get site ID from configuration or use a default
        $siteId = $site->getConfiguration()['kiscore_site_id'] ?? Constants::DEFAULT_SITE_ID;

        // Prepare tracking data
        $trackingData = [
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'referer' => $request->getHeaderLine('Referer'),
            'url' => (string)$request->getUri(),
        ];

        // Send tracking data directly with short timeouts to minimize impact
        $this->sendTrackingData($siteId, $trackingData);

        return $response;
    }

    /**
     * Send tracking data to kiscore.de directly (no shutdown function)
     *
     * @param string $siteId
     * @param array $trackingData
     */
    protected function sendTrackingData(string $siteId, array $trackingData): void
    {
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $trackingUrl = Constants::KISCORE_TRACKING_ENDPOINT . rawurlencode($siteId);

        try {
            $requestFactory->request(
                $trackingUrl,
                'POST',
                [
                    // Use Guzzle's JSON option for encoding and headers
                    'json' => $trackingData,
                    // Keep impact minimal
                    'timeout' => Constants::HTTP_TIMEOUT,
                    'connect_timeout' => Constants::HTTP_CONNECT_TIMEOUT,
                    'http_errors' => false,
                    'allow_redirects' => false,
                ]
            );
        } catch (\Throwable $e) {
            // Silently fail to not affect the user experience
        }
    }

    /**
     * Check if the current request is a frontend request
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isFrontendRequest(ServerRequestInterface $request): bool
    {
        $applicationType = $request->getAttribute('applicationType');
        return ($applicationType === 1);
    }
}
