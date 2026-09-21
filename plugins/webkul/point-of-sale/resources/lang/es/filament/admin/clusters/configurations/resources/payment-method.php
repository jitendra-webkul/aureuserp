<?php

return [
    'navigation' => [
        'title' => 'Métodos de pago',
        'group' => 'Punto de venta',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'General',

                'fields' => [
                    'name'                             => 'Nombre',
                    'company'                          => 'Empresa',
                    'terminal-type'                    => 'Integración',
                    'is-split-transaction'             => 'Identificar cliente',
                    'is-split-transaction-helper-text' => 'Contabilizar un apunte por pago en la cuenta a cobrar del cliente.',
                    'is-active'                        => 'Activo',
                ],
            ],

            'accounting' => [
                'title'  => 'Contabilidad',

                'fields' => [
                    'journal'             => 'Diario',
                    'journal-placeholder' => 'Déjalo vacío para usar la cuenta a cobrar del cliente',
                    'account-placeholder' => 'Déjalo vacío para usar la cuenta predeterminada de la configuración de la empresa',
                    'payment-method-line' => 'Línea de método de pago',
                    'receivable-account'  => 'Cuenta intermedia',
                    'outstanding-account' => 'Cuenta pendiente',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'General',

                'entries' => [
                    'name'                    => 'Nombre',
                    'type'                    => 'Tipo',
                    'terminal-type'           => 'Terminal de pago',
                    'journal-name'            => 'Diario',
                    'receivableAccount-name'  => 'Cuenta a cobrar',
                    'outstandingAccount-name' => 'Cuenta pendiente',
                    'is-cash-count'           => 'Contado en el cajón',
                    'is-split-transaction'    => 'Dividir transacciones',
                    'is-active'               => 'Activo',
                    'company-name'            => 'Empresa',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'               => 'Nombre',
            'type'               => 'Tipo',
            'journal'            => 'Diario',
            'terminal-type'      => 'Integración',
            'receivable-account' => 'Cuenta intermedia',
            'is-cash-count'      => 'Cajón de efectivo',
            'is-active'          => 'Activo',
            'company'            => 'Empresa',
        ],

        'groups' => [
            'type'    => 'Tipo',
            'company' => 'Empresa',
        ],

        'filters' => [
            'type' => 'Tipo',
        ],

        'record-actions' => [
            'restore' => [
                'notification' => [
                    'success' => [
                        'title' => 'Método de pago restaurado',
                        'body'  => 'El método de pago se ha restaurado.',
                    ],
                ],
            ],

            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Método de pago eliminado',
                        'body'  => 'El método de pago se ha eliminado.',
                    ],
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Método de pago eliminado permanentemente',
                        'body'  => 'El método de pago se ha eliminado permanentemente.',
                    ],
                ],
            ],
        ],
    ],
];
