<?php

return [
    'navigation' => [
        'title' => 'Andares',
        'group' => 'Ponto de venda',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Geral',

                'fields' => [
                    'name'             => 'Nome',
                    'company'          => 'Empresa',
                    'background-color' => 'Cor de fundo',
                    'background-image' => 'Imagem de fundo',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'Geral',

                'entries' => [
                    'name'             => 'Nome',
                    'background-color' => 'Cor de fundo',
                    'company-name'     => 'Empresa',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Nome',
            'tables'           => 'Mesas',
            'background-color' => 'Cor de fundo',
            'company'          => 'Empresa',
        ],
    ],
];
