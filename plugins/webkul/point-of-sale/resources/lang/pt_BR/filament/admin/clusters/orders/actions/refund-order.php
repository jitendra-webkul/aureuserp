<?php

return [
    'label'        => 'Devolver produtos',

    'form' => [
        'fields' => [
            'payment-method' => 'Método de pagamento do reembolso',
            'lines'          => 'Linhas a reembolsar',
            'product'        => 'Produto',
            'quantity'       => 'Quantidade',
        ],
    ],

    'notification' => [
        'success' => [
            'title' => 'Reembolso criado',
            'body'  => 'Um pedido de reembolso foi criado para as linhas selecionadas.',
        ],
    ],
];
