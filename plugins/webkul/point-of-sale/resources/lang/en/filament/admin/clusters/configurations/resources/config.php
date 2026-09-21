<?php

return [
    'navigation' => [
        'title' => 'Point of Sale',
        'group' => 'Point of Sale',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title' => 'General',

                'fields' => [
                    'name'             => 'Point of Sale',
                    'name-placeholder' => 'e.g. NYC Shop',
                    'code'             => 'Short Code',
                    'code-helper-text' => 'Used as the prefix for order and session numbers.',
                    'is-active'        => 'Active',
                ],
            ],

            'configurations' => [
                'title' => 'Configurations',

                'tabs' => [
                    'restaurant' => [
                        'title' => 'Restaurant Mode',

                        'fields' => [
                            'is-restaurant'             => 'Is a Bar/Restaurant',
                            'is-restaurant-helper-text' => 'Enable table management, split bills and preparation tickets.',
                            'enable-split-bill'         => 'Bill Splitting',
                            'enable-print-bill'         => 'Bill Printing',
                            'enable-takeaway'           => 'Takeaway',
                            'takeaway-fiscal-position'  => 'Alternative Fiscal Position',
                            'floors'                    => 'Floors',
                        ],
                    ],

                    'payment' => [
                        'title' => 'Payment',

                        'fields' => [
                            'payment-methods'                        => 'Payment Methods',
                            'enable-cash-control'                    => 'Cash Control',
                            'enable-cash-control-helper-text'        => 'Check the amount of the cashbox at opening and closing.',
                            'enable-maximum-difference'              => 'Set Maximum Difference',
                            'enable-maximum-difference-helper-text'  => 'Set a maximum difference allowed between the expected and counted money during the closing of the session.',
                            'amount-authorized-diff'                 => 'Maximum Difference',
                            'enable-cash-rounding'                   => 'Cash Rounding',
                            'enable-cash-rounding-helper-text'       => 'Define the smallest coinage of the currency used to pay by cash.',
                            'cash-rounding'                          => 'Rounding Method',
                            'enable-only-round-cash-method'          => 'Only apply rounding on cash',
                            'enable-tip'                             => 'Tips',
                            'enable-tip-helper-text'                 => 'Accept customer tips or convert their change to a tip.',
                            'tip-product'                            => 'Tip Product',
                        ],
                    ],

                    'interface' => [
                        'title' => 'PoS Interface',

                        'fields' => [
                            'enable-customer-required'              => 'Customer Required',
                            'show-product-images'                   => 'Show product images',
                            'show-category-images'                  => 'Show category images',
                            'limited-products-amount'               => 'Products Loaded',
                            'limited-products-amount-helper-text'   => 'Number of products loaded into the terminal when a session opens.',
                        ],
                    ],

                    'products' => [
                        'title' => 'Product & PoS categories',

                        'fields' => [
                            'limit-categories'             => 'Restrict Categories',
                            'limit-categories-helper-text' => 'Pick which product PoS categories are available.',
                            'categories'                   => 'Available PoS Product Categories',
                        ],
                    ],

                    'accounting' => [
                        'title' => 'Accounting',

                        'fields' => [
                            'journal'                                  => 'Orders Journal',
                            'journal-helper-text'                      => 'Journal used for the session closing entry.',
                            'invoice-journal'                          => 'Invoices Journal',
                            'is-closing-entry-by-product'              => 'Closing Entry by product',
                            'is-closing-entry-by-product-helper-text'  => 'Display the breakdown of sales lines by product in the automatically generated closing entry.',
                            'enable-fiscal-position'                   => 'Flexible Taxes',
                            'enable-fiscal-position-helper-text'       => 'Use fiscal positions to get different taxes by order.',
                            'fiscal-position'                          => 'Default Fiscal Position',
                            'fiscal-positions'                         => 'Fiscal Positions',
                            'receivable-account'                       => 'Intermediary Account',
                            'receivable-account-helper-text'           => 'Leave empty to use the default receivable account from the Point of Sale settings.',
                            'cash-movement-account'                    => 'Cash In/Out Account',
                            'balancing-account'                        => 'Balancing Account',
                            'enable-cogs'                              => 'Cost of Goods Sold',
                            'enable-cogs-helper-text'                  => 'Post the cost of sold products at session closing.',
                            'cogs-journal'                             => 'Cost of Goods Sold Journal',
                            'stock-output-account'                     => 'Stock Output Account',
                        ],
                    ],

                    'pricing' => [
                        'title' => 'Pricing',

                        'fields' => [
                            'tax-display'                        => 'Product Prices',
                            'tax-display-helper-text'            => 'Product prices shown on the terminal and on receipts.',
                            'enable-price-control'               => 'Price Control',
                            'enable-price-control-helper-text'   => 'Restrict price modification to managers.',
                            'enable-price-list'                  => 'Flexible Pricelists',
                            'enable-price-list-helper-text'      => 'Set multiple prices per product, automated discounts, etc.',
                            'price-list'                         => 'Default Pricelist',
                            'price-list-helper-text'             => 'Applied to every order on this register. Cashiers can switch lists only when flexible pricelists are on.',
                            'price-lists'                        => 'Available Pricelists',
                            'enable-line-discount'               => 'Line Discounts',
                            'enable-line-discount-helper-text'   => 'Allow cashiers to set a discount per line.',
                            'enable-global-discount'             => 'Global Discounts',
                            'enable-global-discount-helper-text' => 'Adds a button to set a global discount.',
                            'discount-product'                   => 'Discount Product',
                        ],
                    ],

                    'receipts' => [
                        'title' => 'Bills & Receipts',

                        'fields' => [
                            'enable-receipt-print'                   => 'Receipt Printing',
                            'enable-receipt-auto-print'              => 'Automatic Receipt Printing',
                            'enable-receipt-auto-print-helper-text'  => 'Print receipts automatically once the payment is registered.',
                            'receipt-header'                         => 'Receipt Header',
                            'receipt-footer'                         => 'Receipt Footer',
                            'bills'                                  => 'Coins/Bills',
                            'bills-helper-text'                      => 'Denominations offered on the cash payment screen.',
                        ],
                    ],

                    'preparation' => [
                        'title' => 'Preparation',

                        'fields' => [
                            'printers'             => 'Preparation Printers',
                            'printers-helper-text' => 'Print orders at the kitchen, at the bar, etc.',
                        ],
                    ],

                    'inventory' => [
                        'title' => 'Inventory',

                        'fields' => [
                            'operation-type'                => 'Operation Type',
                            'operation-type-helper-text'    => 'Used to record product pickings. Products are consumed from its default source location.',
                            'return-operation-type'         => 'Return Operation Type',
                            'warehouse'                     => 'Warehouse',
                            'enable-ship-later'             => 'Allow Ship Later',
                            'enable-ship-later-helper-text' => 'Sell products and deliver them later.',
                            'ship-later-route'              => 'Specific Route',
                            'picking-policy'                => 'Shipping Policy',
                        ],
                    ],
                ],
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
            'name'           => 'Name',
            'closing'        => 'Closing',
            'balance'        => 'Balance',
            'code'           => 'Code',
            'warehouse'      => 'Warehouse',
            'operation-type' => 'Operation Type',
            'journal'        => 'Sales Journal',
            'is-restaurant'  => 'Restaurant',
            'is-active'      => 'Active',
            'company'        => 'Company',
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
