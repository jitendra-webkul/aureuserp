<?php

return [
    'title' => 'Point of Sale',

    'form' => [
        'terminal'             => 'Point of Sale',
        'terminal-helper-text' => 'Settings on this page will apply to this point of sale.',
        'empty'                => 'Please create or select a point of sale above to show the configuration options.',
        'is-active'            => 'Active',

        'sections' => [
            'restaurant' => [
                'title' => 'Restaurant Mode',

                'fields' => [
                    'is-restaurant'              => 'Restaurant Mode',
                    'enable-split-bill'          => 'Split Bill',
                    'enable-print-bill'          => 'Print Bill',
                    'enable-takeaway'            => 'Takeaway',
                    'takeaway-fiscal-position'   => 'Takeaway Fiscal Position',
                    'floors'                     => 'Floors',
                ],
            ],

            'payment' => [
                'title' => 'Payment',

                'fields' => [
                    'payment-methods'                => 'Payment Methods',
                    'enable-cash-control'            => 'Cash Control',
                    'enable-maximum-difference'      => 'Maximum Difference',
                    'amount-authorized-diff'         => 'Authorized Difference',
                    'enable-cash-rounding'           => 'Cash Rounding',
                    'cash-rounding'                  => 'Rounding Method',
                    'enable-only-round-cash-method'  => 'Only on cash methods',
                    'enable-tip'                     => 'Tips',
                    'tip-product'                    => 'Tip Product',
                ],
            ],

            'interface' => [
                'title' => 'PoS Interface',

                'fields' => [
                    'enable-customer-required' => 'Customer Required',
                    'show-product-images'      => 'Show product images',
                    'show-category-images'     => 'Show category images',
                    'limited-products-amount'  => 'Products Loaded',
                ],
            ],

            'products' => [
                'title' => 'Product & PoS Categories',

                'fields' => [
                    'limit-categories' => 'Restrict Categories',
                    'categories'       => 'Available Categories',
                ],
            ],

            'accounting' => [
                'title' => 'Accounting',

                'fields' => [
                    'journal'                     => 'Orders Journal',
                    'invoice-journal'             => 'Invoices Journal',
                    'invoice-payment-mode'        => 'Invoice Payment',
                    'receivable-account'          => 'Default Temporary Account',
                    'cash-movement-account'       => 'Cash In/Out Account',
                    'balancing-account'           => 'Balancing Account',
                    'enable-cogs'                 => 'Cost of Goods Sold',
                    'cogs-journal'                => 'Cost of Goods Sold Journal',
                    'stock-output-account'        => 'Stock Output Account',
                    'is-closing-entry-by-product' => 'Closing Entry by product',
                    'enable-fiscal-position'      => 'Flexible Taxes',
                    'fiscal-position'             => 'Default Fiscal Position',
                    'fiscal-positions'            => 'Allowed Fiscal Positions',
                ],
            ],

            'pricing' => [
                'title' => 'Pricing',

                'fields' => [
                    'tax-display'            => 'Product Prices',
                    'enable-price-list'      => 'Flexible Price Lists',
                    'price-list'             => 'Default Price List',
                    'price-lists'            => 'Available Price Lists',
                    'enable-price-control'   => 'Price Control',
                    'enable-line-discount'   => 'Line Discounts',
                    'enable-global-discount' => 'Global Discount',
                    'discount-product'       => 'Discount Product',
                ],
            ],

            'receipt' => [
                'title' => 'Bills & Receipts',

                'fields' => [
                    'enable-receipt-print'      => 'Print Receipt',
                    'enable-receipt-auto-print' => 'Print Automatically',
                    'receipt-header'            => 'Header',
                    'receipt-footer'            => 'Footer',
                    'bills'                     => 'Coins / Bills',
                ],
            ],

            'preparation' => [
                'title' => 'Preparation',

                'fields' => [
                    'printers' => 'Preparation Printers',
                ],
            ],

            'inventory' => [
                'title' => 'Inventory',

                'fields' => [
                    'warehouse'              => 'Warehouse',
                    'operation-type'         => 'Operation Type',
                    'return-operation-type'  => 'Return Operation Type',
                    'stock-update-mode'      => 'Update Stock',
                    'enable-ship-later'      => 'Allow Ship Later',
                    'ship-later-route'       => 'Specific Route',
                    'picking-policy'         => 'Shipping Policy',
                ],
            ],
        ],
    ],

    'header-actions' => [
        'create' => [
            'label' => 'New Point of Sale',
        ],
    ],

    'notification' => [
        'saved' => [
            'title' => 'Settings saved',
        ],
    ],
];
