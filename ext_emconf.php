<?php

$EM_CONF['kiscore'] = [
    'title' => 'Ki-Score',
    'description' => 'TYPO3 extension to integrate kiscore.ai, AI bot detection and analysis.',
    'category' => 'services',
    'author' => 'TPWD AG',
    'author_email' => 'hello@tpwd.de',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.99.99',
            'php' => '8.1.0-0.0.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];