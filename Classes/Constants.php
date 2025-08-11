<?php

declare(strict_types=1);

namespace Tpwd\Kiscore;

/**
 * Constants for the Kiscore extension
 */
class Constants
{
    /**
     * Default site ID for kiscore tracking
     */
    public const DEFAULT_SITE_ID = 'TYPO3_DEFAULT_SITE_ID';

    /**
     * Endpoint for kiscore tracking API
     */
    public const KISCORE_TRACKING_ENDPOINT = 'https://kiscore.de/track/';

    /**
     * HTTP client overall request timeout in seconds
     */
    public const HTTP_TIMEOUT = 1.0;

    /**
     * HTTP client connection timeout in seconds
     */
    public const HTTP_CONNECT_TIMEOUT = 0.5;
}