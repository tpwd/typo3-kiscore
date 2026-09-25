<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Add Ki-Score fields to site configuration
$GLOBALS['SiteConfiguration']['site']['columns']['kiscore_site_id'] = [
    'label' => 'Ki-Score Site ID',
    'description' => 'The site ID shown under Traffic > Integration in the Ki-Score dashboard',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['kiscore_tracking_secret'] = [
    'label' => 'Ki-Score Tracking Secret',
    'description' => 'The tracking secret shown under Traffic > Integration in the Ki-Score dashboard. Without it no requests are tracked.',
    'config' => [
        'type' => 'input',
        'eval' => 'trim',
    ],
];

// Add to showitem list
$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] = str_replace(
    'base,',
    'base, kiscore_site_id, kiscore_tracking_secret,',
    $GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']
);
