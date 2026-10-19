<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tpwd\Kiscore\Constants;
use Tpwd\Kiscore\Tracking\SiteTrackingSettings;
use Tpwd\Kiscore\Tracking\TrackingPayload;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Middleware to track frontend requests and send data to kiscore.ai
 */
class KiscoreTrackingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Process the request first so tracking never changes the response
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

        // Without site ID and tracking secret the API rejects every request
        $settings = SiteTrackingSettings::fromSiteConfiguration($site->getConfiguration());
        if ($settings === null) {
            return $response;
        }

        // Send tracking data directly with short timeouts to minimize impact
        $this->sendTrackingData($settings, TrackingPayload::fromRequest($request));

        return $response;
    }

    /**
     * Send tracking data to kiscore.ai directly (no shutdown function)
     *
     * @param array<string, string> $trackingData
     */
    protected function sendTrackingData(SiteTrackingSettings $settings, array $trackingData): void
    {
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

        try {
            $requestFactory->request(
                $settings->trackingUrl(),
                'POST',
                [
                    // Use Guzzle's JSON option for encoding and headers
                    'json' => $trackingData,
                    'headers' => [
                        Constants::TRACKING_SECRET_HEADER => $settings->trackingSecret,
                        'User-Agent' => Constants::USER_AGENT,
                    ],
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
