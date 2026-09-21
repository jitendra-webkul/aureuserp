<?php

return [
    'config' => [
        'terminal-journal' => 'Point of Sale',
    ],

    'order-workflow' => [
        'customer' => [
            'required-by-terminal'       => 'This point of sale requires a customer on every order.',
            'required-to-invoice'        => 'Select a customer before invoicing this order.',
            'required-to-ship'           => 'Select a customer before shipping this order later.',
            'required-by-payment-method' => 'The selected payment method requires a customer.',
        ],

        'mark-paid' => [
            'already-settled'      => 'Order :order has already been settled.',
            'insufficient-payment' => 'Order :order is not fully paid.',
        ],

        'cancel' => [
            'not-draft' => 'Order :order can no longer be cancelled.',
        ],

        'split' => [
            'not-draft' => 'Order :order can no longer be split.',
        ],

        'tip' => [
            'not-enabled' => 'Enable tips and set a tip product on the point of sale first.',
        ],

        'refund' => [
            'not-refundable'    => 'Order :order cannot be refunded.',
            'exceeds-sold'      => 'The refunded quantity exceeds what was sold on :product.',
            'no-payment-method' => 'Terminal :order has no payment method to refund with.',
            'nothing-to-refund' => 'Select at least one line to refund.',
        ],
    ],

    'global-discount' => [
        'not-enabled' => 'Enable the global discount and set a discount product on the point of sale first.',
        'not-draft'   => 'Order :order can no longer be discounted.',
    ],

    'terminal-product-creator' => [
        'name-required'    => 'Give the product a name.',
        'defaults-missing' => 'Set up a unit of measure and a product category before creating products at the till.',
    ],

    'payment-method-provisioner' => [
        'cash' => 'Cash',
    ],

    'session-preflight' => [
        'draft-orders' => 'Pay or cancel these orders before closing the session: :orders.',

        'journal' => [
            'missing'      => 'Set a sales journal on the point of sale.',
            'invalid-type' => 'The point of sale journal must be a sales journal.',
        ],

        'invoice-journal' => [
            'missing'      => 'Set an invoice journal on the point of sale.',
            'invalid-type' => 'The invoice journal must be a sales journal.',
        ],

        'receivable-account' => [
            'missing'          => 'Set a receivable account on the point of sale.',
            'invalid-type'     => 'The point of sale receivable account must be a receivable account.',
            'not-reconcilable' => 'The point of sale receivable account must allow reconciliation.',
            'deprecated'       => 'The point of sale receivable account is deprecated.',
        ],

        'payment-methods' => [
            'missing'               => 'Add at least one payment method to the point of sale.',
            'pay-later-unsupported' => 'Payment method :method has no journal and customer accounts are not supported yet.',
            'journal-missing'       => 'Set a journal on payment method :method.',
            'journal-company'       => 'The journal of :method belongs to another company.',
            'receivable-missing'    => 'Set a receivable account for :method.',
        ],

        'cash-journal' => [
            'multiple'             => 'Only one cash payment method is supported per point of sale.',
            'profit-loss-missing'  => 'Set profit and loss accounts on the cash journal :journal.',
        ],

        'taxes' => [
            'account-missing' => 'Tax :tax has a distribution line without an account.',
        ],

        'cogs' => [
            'stock-output-missing' => 'Set a stock output account before enabling cost of goods sold.',
        ],
    ],

    'session-closer' => [
        'entry-reference'                 => 'Point of sale session :session',
        'payment-difference-reference'    => 'Difference on :method for :session',
        'cogs-reference'                  => 'Cost of goods sold for :session',
        'sales-line'                      => 'Point of sale sales',
        'tax-line'                        => 'Point of sale taxes',
        'receivable-line'                 => 'Point of sale receivable',
        'invoice-receivable-line'         => 'Point of sale invoice receivable',
        'cash-line'                       => 'Point of sale cash',
        'cash-difference-line'            => 'Cash difference at closing',
        'cogs-line'                       => 'Cost of goods sold',
        'stock-output-line'               => 'Stock output',
        'balancing-line'                  => 'Difference at closing',
        'rounding-line'                   => 'Cash rounding',
        'unbalanced'                      => 'The closing entry is out of balance by :delta. Choose a balancing account to post it.',
        'income-account-missing'          => 'No income account resolved for :product.',
        'expense-account-missing'         => 'No expense account resolved for :product.',
        'cash-account-missing'            => 'The cash journal has no default account.',
        'cash-difference-account-missing' => 'The cash journal has no profit or loss account.',
        'stock-output-missing'            => 'Set a stock output account on the point of sale.',
        'line-account-missing'            => 'A closing entry line has no account.',
        'rounding-account-missing'        => 'The cash rounding has no profit or loss account.',
    ],

    'invoicer' => [
        'customer-required' => 'Order :order needs a customer before it can be invoiced.',
        'journal-missing'   => 'Set an invoice journal on the point of sale.',
        'session-closed'    => 'Order :order belongs to a closed session and is already accounted for in the closing entry.',
    ],

    'session-workflow' => [
        'open' => [
            'already-open' => 'A session is already open for :config.',
        ],

        'assert-open' => [
            'not-open' => 'Session :session is not open.',
        ],

        'assert-state' => [
            'invalid' => 'Session :session cannot move from :state.',
        ],

        'discard' => [
            'not-discardable' => 'Session :name has activity recorded against it and must be closed, not discarded.',
        ],

        'cash-movement' => [
            'invalid-amount' => 'A cash movement amount must be greater than zero.',
        ],
    ],
];
