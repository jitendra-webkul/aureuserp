<?php

return [
    'common' => [
        'close'   => 'Close',
        'cancel'  => 'Cancel',
        'save'    => 'Save',
        'saving'  => 'Saving…',
        'discard' => 'Discard',
        'clear'   => 'Clear',
        'apply'   => 'Apply',
        'open'    => 'Open',
        'resume'  => 'Resume',
        'confirm' => 'Confirm',
        'back'    => 'Back',
        'print'   => 'Print',
        'new'     => 'New',
        'more'    => '+ :count more',
    ],

    'parked' => [
        'walk-in'         => 'Walk-in',
        'items'           => '{1} :count item|[2,*] :count items',
        'count'           => ':count parked',
        'view-all'        => 'View all',
        'heading'         => 'Parked orders',
        'search'          => 'Search orders by name, reference or product',
        'no-match'        => 'No order matches that search.',
        'parked-ago'      => 'parked :time',
        'discard'         => 'Discard order',
        'discard-confirm' => [
            'heading'     => 'Discard parked order?',
            'description' => 'Its lines will be lost. This cannot be undone.',
        ],

        'empty' => [
            'heading'     => 'Nothing parked',
            'description' => 'Orders you set aside will wait here.',
        ],
    ],

    'opening-control' => [
        'heading'     => 'Opening Control',
        'cash'        => 'Opening cash',
        'note'        => 'Opening note',
        'placeholder' => 'Add an opening note…',
        'confirm'     => 'Open Register',
    ],

    'tabs' => [
        'new'      => 'New order',
        'all'      => 'All orders',
        'view-all' => 'View all',
    ],

    'menu' => [
        'label'          => 'Menu',
        'orders'         => 'Orders',
        'cash-in-out'    => 'Cash In / Out',
        'create-product' => 'Create product',
        'back-office'    => 'Back office',
        'close-register' => 'Close register',
    ],

    'cash-movement' => [
        'heading' => 'Cash in / out',
        'in'      => 'Cash in',
        'out'     => 'Cash out',
        'amount'  => 'Amount',
        'reason'  => 'Reason',
        'confirm' => 'Record movement',
        'close'   => 'Close',

        'notification' => [
            'title' => 'Cash movement recorded',
        ],
    ],

    'closing' => [
        'heading'         => 'Closing register',
        'expected'        => 'Expected in drawer',
        'counted'         => 'Counted',
        'note'            => 'Closing note',
        'confirm'         => 'Close register',
        'back'            => 'Back to the till',
        'method'          => 'Payment method',
        'total'           => 'Total',
        'opening'         => 'Opening',
        'payments'        => 'Payments',
        'moves'           => 'Cash in / out',
        'cash-in'         => 'Cash in :number',
        'cash-out'        => 'Cash out :number',
        'orders'          => ':quantity orders',
        'difference'      => 'Difference',
        'count'           => 'Cash count',
        'clear'           => 'Clear',
        'discard'         => 'Discard',
        'daily-sale'      => 'Daily sale',
        'opening-note'    => 'Opening note',
        'authorized-diff' => 'Maximum allowed difference: :amount',
    ],

    'product-form' => [
        'heading'             => 'New product',
        'name'                => 'Product name',
        'name-placeholder'    => 'e.g. Cheese Burger',
        'barcode'             => 'Barcode',
        'barcode-placeholder' => 'e.g. 1234567890',
        'tracking'            => 'Track inventory',
        'price'               => 'Sales price',
        'taxes'               => 'Sales taxes',
        'tax-included'        => '(= :amount incl. taxes)',
        'category'            => 'PoS category',
        'unsaleable'          => 'Unsaleable',

        'notification' => [
            'title' => 'Product created',
        ],

        'error' => [
            'failed'  => 'Could not create the product (:status)',
            'offline' => 'Creating a product needs a connection.',
        ],
    ],

    'product-info' => [
        'heading'          => 'Product information',
        'inventory'        => 'Inventory',
        'on-hand'          => 'on hand at this register',
        'negative-warning' => 'Selling is still allowed; stock will go negative and the back office will show the shortfall.',
        'financials'       => 'Financials',
        'price'            => 'Price',
        'cost'             => 'Cost',
        'margin'           => 'Margin',
        'order'            => 'In this order',
        'quantity'         => 'Quantity',
        'total-price'      => 'Total price',
        'total-margin'     => 'Total margin',
        'add'              => 'Add to order',
    ],

    'customers' => [
        'heading'      => 'Select a customer',
        'search'       => 'Search customers',
        'no-match'     => 'No customer matches that search. Only customers loaded at session start are searchable offline.',
        'clear'        => 'Remove customer',
        'badge-new'    => 'new',

        'create' => [
            'label'   => 'New customer',
            'heading' => 'New customer',
            'created' => ':name added and selected.',

            'fields' => [
                'name'  => 'Name',
                'email' => 'Email',
                'phone' => 'Phone',
            ],
        ],
    ],

    'variants' => [
        'heading'     => 'Attribute selection',
        'confirm'     => 'Add',
        'unavailable' => 'This combination does not exist.',
    ],

    'lots' => [
        'heading'        => 'Lot/Serial Number(s) Required',
        'placeholder'    => 'Serial/Lot Number',
        'add'            => 'Add',
        'remove'         => 'Remove number',
        'missing'        => 'Set lot / serial number',
        'none-available' => 'There is no serial/lot number for the selected product, and their creation is not allowed from the Point of Sale app.',

        'warning' => [
            'heading' => 'Some Serial/Lot Numbers are missing',
            'body'    => "You are trying to sell products with serial/lot numbers, but some of them are not set.\nWould you like to proceed anyway?",
            'proceed' => 'Ok',
        ],
    ],

    'notes' => [
        'internal'    => 'Internal note',
        'kitchen'     => 'Kitchen note',
        'heading'     => 'Add Internal Note',
        'empty'       => 'No note models configured.',
        'hint'        => 'Pick a line first, then choose a note.',
        'placeholder' => 'Add a note for this line',
    ],

    'money-details' => [
        'label'    => 'Coins/Notes',
        'heading'  => 'Opening details:',
        'total'    => 'Total: :total',
        'confirm'  => 'Confirm',
        'close'    => 'Close',
        'increase' => 'Add one',
        'decrease' => 'Remove one',
    ],

    'cart' => [
        'discount' => ':percentage% discount',
        'subtotal' => 'Subtotal',
        'tax'      => 'Taxes',
        'rounding' => 'Rounding',
        'total'    => 'Total',
        'remove'   => 'Remove :product',

        'empty' => [
            'description' => 'Scan or tap a product to start',
        ],
    ],

    'catalogue' => [
        'search'          => 'Search products',
        'create-product'  => 'Create product',
        'info'            => 'Product info for :product',
        'info-depleted'   => 'Product info for :product, none on hand',
    ],

    'numpad' => [
        'qty'       => 'Qty',
        'price'     => 'Price',
        'backspace' => 'Backspace',
    ],

    'payment' => [
        'select-method' => 'Please select a payment method',
        'invoice'       => 'Invoice',
        'change'        => 'change',
        'remove'        => 'Remove :method payment',
        'validate'      => 'Validate',
    ],

    'receipt' => [
        'phone'     => 'Tel:',
        'served-by' => 'Served by :cashier',
        'untaxed'   => 'Untaxed amount',
        'rounding'  => 'Rounding',
        'total'     => 'TOTAL',
        'change'    => 'Change',
        'order'     => 'Order :order',
        'new-order' => 'New order',
    ],

    'scanner' => [
        'unsupported'   => 'This browser cannot scan with the camera. Use an attached barcode scanner instead.',
        'hardware-hint' => 'An attached barcode scanner works everywhere in the till without opening this.',
        'heading'       => 'Scan a barcode',
        'start'         => 'Scan with the camera',
        'stop'          => 'Stop',
    ],

    'offline' => [
        'banner'              => 'Offline — sales continue and sync when the connection returns',
        'waiting'             => ':count order(s) waiting to sync',
        'rejected'            => ':count order(s) rejected by the server',
        'storage-unavailable' => 'Local storage unavailable — reload before taking more orders',
    ],

    'actions' => [
        'customer' => 'Customer',
        'note'     => 'Note',
        'payment'  => 'Payment',
        'heading'  => 'Actions',
        'label'    => 'Actions',

        'cancel-order' => [
            'label'   => 'Cancel order',
            'confirm' => 'Confirm',
            'hint'    => 'Its lines will be lost. This cannot be undone.',
        ],
    ],

    'price-lists' => [
        'label'   => 'Pricelist',
        'heading' => 'Select the pricelist',
        'default' => 'Default Price',
    ],

    'notification' => [
        'success' => [
            'title' => 'Order completed',
            'body'  => 'Order :order has been settled.',
        ],

        'queued' => [
            'title' => 'Order queued offline',
            'body'  => ':count order(s) will sync when the connection returns.',
        ],
    ],
];
