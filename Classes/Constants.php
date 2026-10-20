<?php

declare(strict_types=1);

namespace Tpwd\Kiscore;

/**
 * Constants for the Kiscore extension
 */
class Constants
{
    /**
     * Endpoint for kiscore tracking API
     */
    public const KISCORE_TRACKING_ENDPOINT = 'https://api.kiscore.ai/track/';

    /**
     * Header carrying the tracking secret of the website
     */
    public const TRACKING_SECRET_HEADER = 'X-Kiscore-Tracking-Secret';

    /**
     * User-Agent of the reports; KIscore marks the TYPO3 integration as
     * connected by it. Keep the version in sync with ext_emconf.php.
     */
    public const USER_AGENT = 'KIscore-TYPO3/2.1.0';

    /**
     * HTTP client overall request timeout in seconds
     */
    public const HTTP_TIMEOUT = 1.0;

    /**
     * HTTP client connection timeout in seconds
     */
    public const HTTP_CONNECT_TIMEOUT = 0.5;
}
