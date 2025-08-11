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

/**
 * Middleware to track frontend requests and send data to kiscore.de
 */
class KiscoreTrackingMiddleware implements MiddlewareInterface
{
    protected RequestFactory $requestFactory;

    public function __construct(RequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

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

        // Get site ID from configuration or use a default
        $siteId = $site->getConfiguration()['kiscore_site_id'] ?? Constants::DEFAULT_SITE_ID;

        // Prepare tracking data
        $trackingData = [
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'referer' => $request->getHeaderLine('Referer'),
            'url' => (string)$request->getUri(),
        ];

        // Send tracking data asynchronously
        $this->sendTrackingData($siteId, $trackingData);

        return $response;
    }

    /**
     * Send tracking data to kiscore.de
     *
     * @param string $siteId
     * @param array $trackingData
     */
    protected function sendTrackingData(string $siteId, array $trackingData): void
    {
        // Use a shutdown function to send the request after the response has been sent
        $requestFactory = $this->requestFactory;
        $trackingUrl = 'https://kiscore.de/track/' . $siteId;
        
        register_shutdown_function(static function () use ($requestFactory, $trackingUrl, $trackingData) {
            try {
                $requestFactory->request(
                    $trackingUrl,
                    'POST',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'body' => json_encode($trackingData),
                    ]
                );
            } catch (\Exception $e) {
                // Silently fail to not affect the user experience
            }
        });
    }

    /**
     * Check if the current request is a frontend request
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isFrontendRequest(ServerRequestInterface $request): bool
    {
        return $request->getAttribute('applicationType') === 'FE';
    }
}