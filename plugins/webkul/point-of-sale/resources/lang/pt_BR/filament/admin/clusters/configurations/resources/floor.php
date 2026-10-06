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
                    'name'                => 'Nome',
                    'company'             => 'Empresa',
                    'background-color'    => 'Cor de fundo',
                    'configs'             => 'Ponto de venda',
                    'configs-helper-text' => 'Terminais no modo restaurante que usam este andar.',
                    'background-image'    => 'Imagem de fundo',
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
                    'configs'          => 'Ponto de venda',
                    'company-name'     => 'Empresa',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Nome',
            'configs'          => 'Ponto de venda',
            'tables'           => 'Mesas',
            'background-color' => 'Cor de fundo',
            'company'          => 'Empresa',
        ],
    ],
];
