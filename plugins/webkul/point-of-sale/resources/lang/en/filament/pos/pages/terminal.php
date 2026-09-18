<?php

return [
    'price-lists' => [
        'label'   => 'Pricelist',
        'heading' => 'Select a pricelist',
        'default' => 'Default Price',
    ],

    'opening-control' => [
        'heading'     => 'Opening Control',
        'cash'        => 'Opening cash',
        'note'        => 'Opening note',
        'placeholder' => 'Add an opening note…',
        'confirm'     => 'Open Register',
    ],

    'tabs' => [
        'new' => 'New order',
    ],

    'menu' => [
        'label'          => 'Menu',
        'orders'         => 'Orders',
        'cash-in-out'    => 'Cash In / Out',
        'create-product' => 'Create product',
        'back-office'    => 'Back office',
        'close-register' => 'Close register',
    ],

    'orders' => [
        'heading'  => 'Orders in this session',
        'back'     => 'Back',
        'date'     => 'Date',
        'receipt'  => 'Receipt',
        'name'     => 'Order',
        'customer' => 'Customer',
        'total'    => 'Total',
        'status'   => 'Status',
        'load'     => 'Load order',
        'discard'  => 'Discard',
        'empty'    => 'No orders in this session yet.',
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
        'heading'  => 'New product',
        'name'     => 'Product name',
        'barcode'  => 'Barcode',
        'price'    => 'Sales price',
        'category' => 'PoS category',
        'confirm'  => 'Save',
        'close'    => 'Discard',

        'notification' => [
            'title' => 'Product created',
        ],
    ],

    'product-info' => [
        'heading'      => 'Product information',
        'inventory'    => 'Inventory',
        'available'    => 'Units available',
        'forecasted'   => 'Forecasted',
        'financials'   => 'Financials',
        'price'        => 'Price excl. tax',
        'cost'         => 'Cost',
        'margin'       => 'Margin',
        'order'        => 'Order',
        'total-price'  => 'Total price excl. tax',
        'total-cost'   => 'Total cost',
        'total-margin' => 'Total margin',
        'close'        => 'Ok',
    ],

    'customers' => [
        'heading'  => 'Select a customer',
        'search'   => 'Search customers…',
        'clear'    => 'Walk-in customer',
        'close'    => 'Close',
        'empty'    => 'No customers yet.',
    ],

    'variants' => [
        'heading'     => 'Attribute selection',
        'confirm'     => 'Add',
        'discard'     => 'Discard',
        'unavailable' => 'This combination does not exist.',
    ],

    'notes' => [
        'heading'     => 'Add Internal Note',
        'close'       => 'Close',
        'empty'       => 'No note models configured.',
        'hint'        => 'Pick a line first, then choose a note.',
        'placeholder' => 'Write a note for this line',
        'apply'       => 'Apply',
        'discard'     => 'Discard',
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
        'item-count'     => '{1} :count item|[2,*] :count items',
        'clear-all'      => 'Clear all',
        'discount-total' => 'Discount (:percentage%)',
        'heading'  => 'Order',
        'discount' => ':percentage% Discount',
        'subtotal' => 'Subtotal',
        'tax'      => 'Taxes',
        'total'    => 'Total',
        'increase' => 'Increase quantity',
        'decrease' => 'Decrease quantity',
        'remove'   => 'Remove line',

        'empty' => [
            'heading'     => 'Nothing scanned yet',
            'description' => 'Pick a product to start the order.',
        ],
    ],

    'catalogue' => [
        'stock' => [
            'available' => 'Available',
            'low'       => 'Last :quantity left',
            'out'       => 'Sold out',
        ],

        'heading'        => 'Products',
        'search'         => 'Search products…',
        'all-categories' => 'All',

        'empty' => [
            'heading'     => 'No product found',
            'description' => 'Flag products as available in the point of sale from the back office.',
        ],
    ],

    'numpad' => [
        'qty'       => 'Qty',
        'price'     => 'Price',
        'discount'  => 'Disc %',
        'backspace' => 'Backspace',
        'clear'     => 'Clear',
        'hint'      => 'Select a line to change its quantity, price or discount.',
    ],

    'payment' => [
        'heading'       => 'Payment',
        'due'           => 'Amount due',
        'remove'        => 'Remove payment',
        'invoice'       => 'Invoice',
        'select-method' => 'Please select a payment method',
        'remaining'     => 'Remaining',
        'change'        => 'Change',
    ],

    'receipt' => [
        'heading' => 'Receipt',
        'total'   => 'Total',
        'change'  => 'Change',
    ],

    'offline' => [
        'offline' => 'Offline',
        'pending' => 'Pending sync: :count',
    ],

    'actions' => [
        'tip'        => 'Tip',
        'takeaway'   => 'Take Away',
        'dine-in'    => 'Dine In',
        'ship-later' => 'Ship Later',
        'customer'    => 'Customer',
        'remove-line' => 'Remove line',
        'note'        => 'Internal Note',
        'payment'     => 'Payment',
        'back'        => 'Back',
        'validate'    => 'Validate',
        'new-order'   => 'New Order',
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
