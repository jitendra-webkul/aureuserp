<?php

return [
    'stats' => [
        'open-sessions' => [
            'label'       => 'Open Sessions',
            'description' => 'Sessions still on the counter',
        ],

        'today-orders' => [
            'label'       => 'Orders Today',
            'description' => 'Settled orders since midnight',
        ],

        'today-revenue' => [
            'label'       => 'Revenue Today',
            'description' => 'Total of settled orders',
        ],

        'failed-operations' => [
            'label'       => 'Failed Operations',
            'description' => 'Orders whose stock move needs attention',
        ],
    ],
];
