<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@evoweb/ew-collapsible-container/' => [
            'path' => 'EXT:ew_collapsible_container/Resources/Public/JavaScript/',
        ],
    ],
];
