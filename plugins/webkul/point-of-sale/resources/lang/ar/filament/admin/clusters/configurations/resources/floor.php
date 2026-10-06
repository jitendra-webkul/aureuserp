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
                    'name'                => 'الاسم',
                    'company'             => 'الشركة',
                    'background-color'    => 'لون الخلفية',
                    'configs'             => 'نقطة البيع',
                    'configs-helper-text' => 'محطات وضع المطعم التي تستخدم هذا الطابق.',
                    'background-image'    => 'صورة الخلفية',
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
                    'configs'          => 'نقطة البيع',
                    'company-name'     => 'الشركة',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'             => 'الاسم',
            'configs'          => 'نقطة البيع',
            'tables'           => 'الطاولات',
            'background-color' => 'لون الخلفية',
            'company'          => 'الشركة',
        ],
    ],
];
