<?php

return [
    'navigation' => [
        'title' => 'Plantas',
        'group' => 'Punto de venta',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General',

                'fields' => [
                    'name'             => 'Nombre',
                    'company'          => 'Empresa',
                    'background-color' => 'Color de fondo',
                    'background-image' => 'Imagen de fondo',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'General',

                'entries' => [
                    'name'             => 'Nombre',
                    'background-color' => 'Color de fondo',
                    'company-name'     => 'Empresa',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Nombre',
            'tables'           => 'Mesas',
            'background-color' => 'Color de fondo',
            'company'          => 'Empresa',
        ],
    ],
];
