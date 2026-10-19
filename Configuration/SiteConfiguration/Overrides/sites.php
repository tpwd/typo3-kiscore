<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Add Ki-Score fields to site configuration
$GLOBALS['SiteConfiguration']['site']['columns']['kiscore_connection_key'] = [
    'label' => 'Ki-Score Connection Key',
    'description' => 'The connection key shown under Settings > Traffic tracking > TYPO3 in the Ki-Score dashboard. It replaces the site ID and tracking secret below.',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['kiscore_site_id'] = [
    'label' => 'Ki-Score Site ID',
    'description' => 'Only needed without a connection key, as in extension version 2.0.',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['kiscore_tracking_secret'] = [
    'label' => 'Ki-Score Tracking Secret',
    'description' => 'Only needed without a connection key, as in extension version 2.0. Without a key or both values no requests are tracked.',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];

// Add to showitem list
$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = str_replace(
    'base,',
    'base, kiscore_connection_key, kiscore_site_id, kiscore_tracking_secret,',
    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']
);
