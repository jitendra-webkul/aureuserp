<?php

return [
    'navigation' => [
        'title' => 'Métodos de pagamento',
        'group' => 'Ponto de venda',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Geral',

                'fields' => [
                    'name'                             => 'Nome',
                    'company'                          => 'Empresa',
                    'terminal-type'                    => 'Integração',
                    'is-split-transaction'             => 'Identificar cliente',
                    'is-split-transaction-helper-text' => 'Lançar um item de diário por pagamento na conta a receber do cliente.',
                    'is-active'                        => 'Ativo',
                ],
            ],

            'accounting' => [
                'title'  => 'Contabilidade',

                'fields' => [
                    'journal'             => 'Diário',
                    'journal-placeholder' => 'Deixe vazio para usar a conta a receber do cliente',
                    'account-placeholder' => 'Deixe vazio para usar a conta padrão das configurações da empresa',
                    'payment-method-line' => 'Linha do método de pagamento',
                    'receivable-account'  => 'Conta intermediária',
                    'outstanding-account' => 'Conta pendente',
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'Geral',

                'entries' => [
                    'name'                    => 'Nome',
                    'type'                    => 'Tipo',
                    'terminal-type'           => 'Terminal de pagamento',
                    'journal-name'            => 'Diário',
                    'receivableAccount-name'  => 'Conta a receber',
                    'outstandingAccount-name' => 'Conta pendente',
                    'is-cash-count'           => 'Contado na gaveta de dinheiro',
                    'is-split-transaction'    => 'Dividir transações',
                    'is-active'               => 'Ativo',
                    'company-name'            => 'Empresa',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'               => 'Nome',
            'type'               => 'Tipo',
            'journal'            => 'Diário',
            'terminal-type'      => 'Integração',
            'receivable-account' => 'Conta intermediária',
            'is-cash-count'      => 'Gaveta de dinheiro',
            'is-active'          => 'Ativo',
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
                        'title' => 'Método de pagamento restaurado',
                        'body'  => 'O método de pagamento foi restaurado.',
                    ],
                ],
            ],

            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Método de pagamento excluído',
                        'body'  => 'O método de pagamento foi excluído.',
                    ],
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Método de pagamento excluído permanentemente',
                        'body'  => 'O método de pagamento foi excluído permanentemente.',
                    ],
                ],
            ],
        ],
    ],
];
