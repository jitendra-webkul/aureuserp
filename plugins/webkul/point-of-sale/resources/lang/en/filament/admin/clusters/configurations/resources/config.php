<?php

return [
    'navigation' => [
        'title' => 'Point of Sale',
        'group' => 'Point of Sale',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'Point of Sale',

                'fields' => [
                    'name'                      => 'Point of Sale',
                    'name-placeholder'          => 'e.g. NYC Shop',
                    'code'                      => 'Short Code',
                    'code-helper-text'          => 'Used as the prefix for order and session numbers.',
                    'is-restaurant'             => 'Is a Bar/Restaurant',
                    'is-restaurant-helper-text' => 'Enable table management, split bills and preparation tickets.',
                ],

                'more-settings' => 'More settings: <a class="fi-link fi-size-sm" href=":url">Configurations &rsaquo; Settings</a>',
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'entries' => [
                    'name'           => 'Point of Sale',
                    'code'           => 'Short Code',
                    'company-name'   => 'Company',
                    'warehouse-name' => 'Warehouse',
                    'journal-name'   => 'Orders Journal',
                    'is-restaurant'  => 'Is a Bar/Restaurant',
                    'is-active'      => 'Active',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'              => 'Name',
            'closing'           => 'Closing',
            'balance'           => 'Balance',
            'code'              => 'Code',
            'warehouse'         => 'Warehouse',
            'operation-type'    => 'Operation Type',
            'journal'           => 'Sales Journal',
            'stock-update-mode' => 'Update Stock',
            'is-restaurant'     => 'Restaurant',
            'is-active'         => 'Active',
            'company'           => 'Company',
        ],

        'groups' => [
            'warehouse' => 'Warehouse',
            'company'   => 'Company',
        ],

        'record-actions' => [
            'restore' => [
                'notification' => [
                    'success' => [
                        'title' => 'Point of sale restored',
                        'body'  => 'The point of sale has been restored.',
                    ],
                ],
            ],

            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Point of sale deleted',
                        'body'  => 'The point of sale has been deleted.',
                    ],
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Point of sale deleted permanently',
                        'body'  => 'The point of sale has been deleted permanently.',
                    ],
                ],
            ],
        ],
    ],
];
