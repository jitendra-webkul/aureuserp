<?php

return [
    'products' => [
        'tip'      => 'Pourboires',
        'discount' => 'Remise',
    ],

    'config' => [
        'terminal-journal' => 'Point de vente',
    ],

    'order-workflow' => [
        'customer' => [
            'required-by-terminal'       => 'Ce point de vente exige un client sur chaque commande.',
            'required-to-invoice'        => 'Sélectionnez un client avant de facturer cette commande.',
            'required-to-ship'           => 'Sélectionnez un client avant de livrer cette commande ultérieurement.',
            'required-by-payment-method' => 'Le mode de paiement sélectionné exige un client.',
        ],

        'mark-paid' => [
            'already-settled'      => 'La commande :order a déjà été réglée.',
            'insufficient-payment' => 'La commande :order n\'est pas entièrement payée.',
        ],

        'cancel' => [
            'not-draft' => 'La commande :order ne peut plus être annulée.',
        ],

        'split' => [
            'not-draft' => 'La commande :order ne peut plus être partagée.',
        ],

        'tip' => [
            'not-enabled' => 'Activez les pourboires et définissez un produit pourboire sur le point de vente au préalable.',
        ],

        'refund' => [
            'not-refundable'    => 'La commande :order ne peut pas être remboursée.',
            'exceeds-sold'      => 'La quantité remboursée dépasse ce qui a été vendu pour :product.',
            'no-payment-method' => 'Le terminal :order n\'a aucun mode de paiement permettant de rembourser.',
            'nothing-to-refund' => 'Sélectionnez au moins une ligne à rembourser.',
        ],
    ],

    'global-discount' => [
        'not-enabled' => 'Activez la remise globale et définissez un produit de remise sur le point de vente au préalable.',
        'not-draft'   => 'La commande :order ne peut plus faire l\'objet d\'une remise.',
    ],

    'terminal-product-creator' => [
        'name-required'    => 'Donnez un nom au produit.',
        'defaults-missing' => 'Configurez une unité de mesure et une catégorie de produits avant de créer des produits à la caisse.',
    ],

    'payment-method-provisioner' => [
        'cash' => 'Espèces',
    ],

    'session-preflight' => [
        'draft-orders'       => 'Payez ou annulez ces commandes avant de clôturer la session : :orders.',

        'journal' => [
            'missing'      => 'Définissez un journal des ventes sur le point de vente.',
            'invalid-type' => 'Le journal du point de vente doit être un journal des ventes.',
        ],

        'invoice-journal' => [
            'missing'      => 'Définissez un journal des factures sur le point de vente.',
            'invalid-type' => 'Le journal des factures doit être un journal des ventes.',
        ],

        'receivable-account' => [
            'missing'          => 'Définissez un compte client sur le point de vente.',
            'invalid-type'     => 'Le compte client du point de vente doit être un compte client.',
            'not-reconcilable' => 'Le compte client du point de vente doit autoriser le lettrage.',
            'deprecated'       => 'Le compte client du point de vente est obsolète.',
        ],

        'payment-methods' => [
            'missing'               => 'Ajoutez au moins un mode de paiement au point de vente.',
            'pay-later-unsupported' => 'Le mode de paiement :method n\'a pas de journal et les comptes clients ne sont pas encore pris en charge.',
            'journal-missing'       => 'Définissez un journal sur le mode de paiement :method.',
            'journal-company'       => 'Le journal de :method appartient à une autre société.',
            'receivable-missing'    => 'Définissez un compte client pour :method.',
        ],

        'cash-journal' => [
            'multiple'            => 'Un seul mode de paiement en espèces est pris en charge par point de vente.',
            'profit-loss-missing' => 'Définissez les comptes de profits et pertes sur le journal des espèces :journal.',
        ],

        'taxes' => [
            'account-missing' => 'La taxe :tax comporte une ligne de répartition sans compte.',
        ],

        'cogs' => [
            'stock-output-missing' => 'Définissez un compte de sortie de stock avant d\'activer le coût des ventes.',
        ],
    ],

    'session-closer' => [
        'entry-reference'                 => 'Session du point de vente :session',
        'payment-difference-reference'    => 'Écart sur :method pour :session',
        'cogs-reference'                  => 'Coût des ventes pour :session',
        'sales-line'                      => 'Ventes du point de vente',
        'tax-line'                        => 'Taxes du point de vente',
        'receivable-line'                 => 'Compte client du point de vente',
        'invoice-receivable-line'         => 'Compte client des factures du point de vente',
        'cash-line'                       => 'Espèces du point de vente',
        'cash-difference-line'            => 'Écart d\'espèces à la clôture',
        'cogs-line'                       => 'Coût des ventes',
        'stock-output-line'               => 'Sortie de stock',
        'balancing-line'                  => 'Écart à la clôture',
        'rounding-line'                   => 'Arrondi des espèces',
        'unbalanced'                      => 'L\'écriture de clôture est déséquilibrée de :delta. Choisissez un compte d\'équilibrage pour la comptabiliser.',
        'income-account-missing'          => 'Aucun compte de produits résolu pour :product.',
        'expense-account-missing'         => 'Aucun compte de charges résolu pour :product.',
        'cash-account-missing'            => 'Le journal des espèces n\'a pas de compte par défaut.',
        'cash-difference-account-missing' => 'Le journal des espèces n\'a pas de compte de profits ou de pertes.',
        'stock-output-missing'            => 'Définissez un compte de sortie de stock sur le point de vente.',
        'line-account-missing'            => 'Une ligne de l\'écriture de clôture n\'a pas de compte.',
        'rounding-account-missing'        => 'L\'arrondi des espèces n\'a pas de compte de profits ou de pertes.',
    ],

    'invoicer' => [
        'customer-required' => 'La commande :order nécessite un client avant de pouvoir être facturée.',
        'journal-missing'   => 'Définissez un journal des factures sur le point de vente.',
        'session-closed'    => 'La commande :order appartient à une session clôturée et est déjà comptabilisée dans l\'écriture de clôture.',
    ],

    'session-workflow' => [
        'open' => [
            'already-open' => 'Une session est déjà ouverte pour :config.',
        ],

        'assert-open' => [
            'not-open' => 'La session :session n\'est pas ouverte.',
        ],

        'assert-state' => [
            'invalid' => 'La session :session ne peut pas quitter l\'état :state.',
        ],

        'discard' => [
            'not-discardable' => 'La session :name a une activité enregistrée et doit être clôturée, pas abandonnée.',
        ],

        'cash-movement' => [
            'invalid-amount' => 'Le montant d\'un mouvement d\'espèces doit être supérieur à zéro.',
        ],
    ],
];
