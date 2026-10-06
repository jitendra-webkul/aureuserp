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
                    'name'                => 'Nom',
                    'company'             => 'Société',
                    'background-color'    => 'Couleur de fond',
                    'configs'             => 'Point de vente',
                    'configs-helper-text' => 'Terminaux en mode restaurant qui utilisent cet étage.',
                    'background-image'    => 'Image de fond',
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
                    'configs'          => 'Point de vente',
                    'company-name'     => 'Société',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Nom',
            'configs'          => 'Point de vente',
            'tables'           => 'Tables',
            'background-color' => 'Couleur de fond',
            'company'          => 'Société',
        ],
    ],
];
