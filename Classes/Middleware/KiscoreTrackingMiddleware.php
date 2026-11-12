<?php

declare(strict_types=1);

namespace Tpwd\Kiscore\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tpwd\Kiscore\Constants;
use Tpwd\Kiscore\Tracking\PageView;
use Tpwd\Kiscore\Tracking\SiteTrackingSettings;
use Tpwd\Kiscore\Tracking\TrackingPayload;
use TYPO3\CMS\Core\Context\Context;
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

        try {
            $this->report($request, $response);
        } catch (\Throwable $e) {
            // Tracking must never break the page
        }

        return $response;
    }

    protected function report(ServerRequestInterface $request, ResponseInterface $response): void
    {
        if (!$this->isFrontendRequest($request)) {
            return;
        }

        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return;
        }

        // Only pages someone opened: no form submits, AJAX, prefetches,
        // redirects or error pages
        if (!PageView::isReportable($request, $response) || $this->isBackendPreview()) {
            return;
        }

        // Without site ID and tracking secret the API rejects every request
        $settings = SiteTrackingSettings::fromSiteConfiguration($site->getConfiguration());
        if ($settings === null) {
            return;
        }

        $payload = TrackingPayload::fromRequest($request);
        $this->afterResponse(function () use ($settings, $payload): void {
            $this->sendTrackingData($settings, $payload);
        });
    }

    /**
     * Runs the report once the visitor has the page. PHP-FPM and LiteSpeed
     * close the connection first; elsewhere the output is flushed, so the
     * browser already has the complete page while the report is sent.
     */
    protected function afterResponse(callable $report): void
    {
        register_shutdown_function(static function () use ($report): void {
            try {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } elseif (function_exists('litespeed_finish_request')) {
                    litespeed_finish_request();
                } else {
                    while (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                    flush();
                }
                $report();
            } catch (\Throwable $e) {
                // Nothing left to protect but the error log
            }
        });
    }

    /**
     * An editor previewing pages is not a visitor
     */
    protected function isBackendPreview(): bool
    {
        try {
            return (bool)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Send tracking data to kiscore.ai with short timeouts
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
