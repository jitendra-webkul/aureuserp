<?php

return [
    'stats' => [
        'open-sessions' => [
            'label'       => 'Sesiones abiertas',
            'description' => 'Sesiones todavía en el mostrador',
        ],

        'today-orders' => [
            'label'       => 'Pedidos de hoy',
            'description' => 'Pedidos liquidados desde medianoche',
        ],

        'today-revenue' => [
            'label'       => 'Ingresos de hoy',
            'description' => 'Total de pedidos liquidados',
        ],

        'failed-operations' => [
            'label'       => 'Operaciones fallidas',
            'description' => 'Pedidos cuyo movimiento de stock necesita atención',
        ],
    ],
];
