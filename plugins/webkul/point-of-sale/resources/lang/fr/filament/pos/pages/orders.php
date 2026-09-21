<?php

return [
    'title'        => 'Commandes',

    'navigation' => [
        'label' => 'Commandes',
    ],

    'walk-in'      => 'Client de passage',
    'search'       => 'Rechercher par commande, reçu ou client',
    'select-order' => 'Sélectionnez une commande pour voir ses lignes.',
    'taxes'        => 'Taxes',
    'total'        => 'Total',

    'status' => [
        'active' => 'Toutes les commandes actives',
    ],

    'columns' => [
        'date'     => 'Date',
        'receipt'  => 'Numéro de reçu',
        'order'    => 'Numéro de commande',
        'customer' => 'Client',
        'cashier'  => 'Caissier',
        'total'    => 'Total',
        'status'   => 'Statut',
    ],

    'refund' => [
        'prompt'    => 'Sélectionnez le(s) produit(s) à rembourser et indiquez la quantité',
        'to-refund' => 'À rembourser :',
        'qty'       => 'Qté',
        'price'     => 'Prix',
        'backspace' => 'Retour arrière',
    ],

    'actions' => [
        'print'               => 'Imprimer le reçu',
        'back'                => 'Retour',
        'details'             => 'Détails',
        'refund'              => 'Avoir',
        'previous'            => 'Page précédente',
        'next'                => 'Page suivante',

        'refund-notification' => [
            'title' => 'Avoir créé',
            'body'  => 'L\'avoir :order est prêt à être réglé.',
        ],

        'invoice' => [
            'label'        => 'Facture',
            'heading'      => 'Créer une facture pour cette commande ?',

            'notification' => [
                'title' => 'Facture créée',
            ],
        ],
    ],

    'empty' => [
        'heading'     => 'Aucune commande pour l\'instant',
        'description' => 'Les commandes passées pendant la session ouverte apparaîtront ici.',
    ],
];
