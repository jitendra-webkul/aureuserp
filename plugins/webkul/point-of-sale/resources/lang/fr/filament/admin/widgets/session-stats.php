<?php

return [
    'stats' => [
        'open-sessions' => [
            'label'       => 'Sessions ouvertes',
            'description' => 'Sessions encore au comptoir',
        ],

        'today-orders' => [
            'label'       => 'Commandes du jour',
            'description' => 'Commandes réglées depuis minuit',
        ],

        'today-revenue' => [
            'label'       => 'Chiffre d\'affaires du jour',
            'description' => 'Total des commandes réglées',
        ],

        'failed-operations' => [
            'label'       => 'Opérations échouées',
            'description' => 'Commandes dont le mouvement de stock nécessite une attention',
        ],
    ],
];
