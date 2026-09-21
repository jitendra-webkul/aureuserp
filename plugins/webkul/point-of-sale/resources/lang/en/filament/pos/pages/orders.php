<?php

return [
    'title' => 'Orders',

    'navigation' => [
        'label' => 'Orders',
    ],

    'walk-in' => 'Walk-in customer',

    'search' => 'Search by order, receipt or customer',

    'select-order' => 'Select an order to see its lines.',

    'taxes' => 'Taxes',

    'total' => 'Total',

    'status' => [
        'active' => 'All active orders',
    ],

    'columns' => [
        'date'     => 'Date',
        'receipt'  => 'Receipt number',
        'order'    => 'Order number',
        'customer' => 'Customer',
        'cashier'  => 'Cashier',
        'total'    => 'Total',
        'status'   => 'Status',
    ],

    'refund' => [
        'prompt'    => 'Select the product(s) to refund and set the quantity',
        'to-refund' => 'To refund:',
        'qty'       => 'Qty',
        'price'     => 'Price',
        'backspace' => 'Backspace',
    ],

    'actions' => [
        'print'    => 'Print receipt',
        'back'     => 'Back',
        'details'  => 'Details',
        'refund'   => 'Refund',
        'previous' => 'Previous page',
        'next'     => 'Next page',

        'refund-notification' => [
            'title' => 'Refund created',
            'body'  => 'Refund :order is ready to settle.',
        ],

        'invoice' => [
            'label'   => 'Invoice',
            'heading' => 'Create an invoice for this order?',

            'notification' => [
                'title' => 'Invoice created',
            ],
        ],
    ],

    'empty' => [
        'heading'     => 'No orders yet',
        'description' => 'Orders taken during the open session will appear here.',
    ],
];
