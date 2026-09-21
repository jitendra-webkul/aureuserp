<?php

return [
    'navigation' => [
        'title' => 'الطوابق',
        'group' => 'نقطة البيع',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'عام',

                'fields' => [
                    'name'             => 'الاسم',
                    'company'          => 'الشركة',
                    'background-color' => 'لون الخلفية',
                    'background-image' => 'صورة الخلفية',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'عام',

                'entries' => [
                    'name'             => 'الاسم',
                    'background-color' => 'لون الخلفية',
                    'company-name'     => 'الشركة',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'الاسم',
            'tables'           => 'الطاولات',
            'background-color' => 'لون الخلفية',
            'company'          => 'الشركة',
        ],
    ],
];
