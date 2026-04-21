<?php

return [
    'tray' => [
        'reminder_hours' => 2,
        'needs_verification_hours' => 24,
    ],

    'statuses' => [
        'installed',
        'in_stock',
        'in_transfer',
        'needs_verification',
        'defective',
        'destruction_pending',
        'destroyed_recycled',
        'sold_returned',
    ],

    'default_storage_locations' => [
        [
            'name' => 'Stock',
            'code' => 'stock',
            'type' => 'stock',
        ],
        [
            'name' => 'Tray',
            'code' => 'tray',
            'type' => 'general',
        ],
        [
            'name' => 'Verification',
            'code' => 'verification',
            'type' => 'verification',
        ],
        [
            'name' => 'Destruction',
            'code' => 'destruction',
            'type' => 'destruction',
        ],
    ],
];
