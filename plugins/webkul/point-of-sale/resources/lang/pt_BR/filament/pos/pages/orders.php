<?php

return [
    'title'        => 'Pedidos',

    'navigation' => [
        'label' => 'Pedidos',
    ],

    'walk-in'      => 'Cliente avulso',
    'search'       => 'Buscar por pedido, recibo ou cliente',
    'select-order' => 'Selecione um pedido para ver suas linhas.',
    'taxes'        => 'Impostos',
    'total'        => 'Total',

    'status' => [
        'active' => 'Todos os pedidos ativos',
    ],

    'columns' => [
        'date'     => 'Data',
        'receipt'  => 'Número do recibo',
        'order'    => 'Número do pedido',
        'customer' => 'Cliente',
        'cashier'  => 'Operador de caixa',
        'total'    => 'Total',
        'status'   => 'Status',
    ],

    'refund' => [
        'prompt'    => 'Selecione o(s) produto(s) a reembolsar e informe a quantidade',
        'to-refund' => 'A reembolsar:',
        'qty'       => 'Qtd.',
        'price'     => 'Preço',
        'backspace' => 'Retrocesso',
    ],

    'actions' => [
        'print'               => 'Imprimir recibo',
        'back'                => 'Voltar',
        'details'             => 'Detalhes',
        'refund'              => 'Reembolso',
        'previous'            => 'Página anterior',
        'next'                => 'Próxima página',

        'refund-notification' => [
            'title' => 'Reembolso criado',
            'body'  => 'O reembolso :order está pronto para ser liquidado.',
        ],

        'invoice' => [
            'label'        => 'Fatura',
            'heading'      => 'Criar uma fatura para este pedido?',

            'notification' => [
                'title' => 'Fatura criada',
            ],
        ],
    ],

    'empty' => [
        'heading'     => 'Ainda não há pedidos',
        'description' => 'Os pedidos registrados durante a sessão aberta aparecerão aqui.',
    ],
];
