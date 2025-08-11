<?php

return [
    'frontend' => [
        'tpwd/kiscore/tracking' => [
            'target' => \Tpwd\Kiscore\Middleware\KiscoreTrackingMiddleware::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe'
            ],
            'before' => [
                'typo3/cms-frontend/shortcut-and-mountpoint-redirect'
            ]
        ]
    ]
];