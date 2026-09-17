<?php

return [
    'navigation' => [
        'title' => 'Floors',
        'group' => 'Point of Sale',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'name'             => 'Name',
                    'company'          => 'Company',
                    'background-color' => 'Background Colour',
                    'background-image' => 'Background Image',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'entries' => [
                    'name'             => 'Name',
                    'background-color' => 'Background Colour',
                    'company-name'     => 'Company',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'Name',
            'tables'           => 'Tables',
            'background-color' => 'Background Colour',
            'company'          => 'Company',
        ],
    ],
];
