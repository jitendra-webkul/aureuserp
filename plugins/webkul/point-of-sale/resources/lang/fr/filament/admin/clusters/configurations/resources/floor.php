<?php

return [
    'navigation' => [
        'title' => 'Étages',
        'group' => 'Point de vente',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Général',

                'fields' => [
                    'name'             => 'Nom',
                    'company'          => 'Société',
                    'background-color' => 'Couleur de fond',
                    'background-image' => 'Image de fond',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'Général',

                'entries' => [
                    'name'             => 'Nom',
                    'background-color' => 'Couleur de fond',
                    'company-name'     => 'Société',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Nom',
            'tables'           => 'Tables',
            'background-color' => 'Couleur de fond',
            'company'          => 'Société',
        ],
    ],
];
