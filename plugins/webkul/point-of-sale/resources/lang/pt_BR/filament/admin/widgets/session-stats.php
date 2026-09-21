<?php

return [
    'stats' => [
        'open-sessions' => [
            'label'       => 'Sessões abertas',
            'description' => 'Sessões ainda no balcão',
        ],

        'today-orders' => [
            'label'       => 'Pedidos de hoje',
            'description' => 'Pedidos liquidados desde a meia-noite',
        ],

        'today-revenue' => [
            'label'       => 'Receita de hoje',
            'description' => 'Total dos pedidos liquidados',
        ],

        'failed-operations' => [
            'label'       => 'Operações com falha',
            'description' => 'Pedidos cuja movimentação de estoque precisa de atenção',
        ],
    ],
];
