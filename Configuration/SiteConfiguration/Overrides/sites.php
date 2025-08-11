<?php

declare(strict_types=1);

use Tpwd\Kiscore\Constants;

defined('TYPO3') or die();

// Add kiscore_site_id field to site configuration
$GLOBALS['SiteConfiguration']['site']['columns']['kiscore_site_id'] = [
    'label' => 'Ki-Score Site ID',
    'description' => 'The site ID for kiscore.de tracking',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
        'default' => Constants::DEFAULT_SITE_ID,
    ],
];

// Add to showitem list
$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = str_replace(
    'base,',
    'base, kiscore_site_id,',
    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']
);