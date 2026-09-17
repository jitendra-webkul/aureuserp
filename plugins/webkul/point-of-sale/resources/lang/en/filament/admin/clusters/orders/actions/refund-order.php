<?php

return [
    'label' => 'Return Products',

    'form' => [
        'fields' => [
            'payment-method' => 'Refund Payment Method',
            'lines'          => 'Lines to refund',
            'product'        => 'Product',
            'quantity'       => 'Quantity',
        ],
    ],

    'notification' => [
        'success' => [
            'title' => 'Refund created',
            'body'  => 'A refund order has been created for the selected lines.',
        ],
    ],
];
