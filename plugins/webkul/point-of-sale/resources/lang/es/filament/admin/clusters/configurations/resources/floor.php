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
                    'name'                => 'Nombre',
                    'company'             => 'Empresa',
                    'background-color'    => 'Color de fondo',
                    'configs'             => 'Punto de venta',
                    'configs-helper-text' => 'Terminales en modo restaurante que usan esta planta.',
                    'background-image'    => 'Imagen de fondo',
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
                    'configs'          => 'Punto de venta',
                    'company-name'     => 'Empresa',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Nombre',
            'configs'          => 'Punto de venta',
            'tables'           => 'Mesas',
            'background-color' => 'Color de fondo',
            'company'          => 'Empresa',
        ],
    ],
];
