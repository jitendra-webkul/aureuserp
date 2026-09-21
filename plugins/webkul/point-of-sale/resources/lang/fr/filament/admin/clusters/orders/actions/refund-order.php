<?php

return [
    'label'        => 'Retourner des produits',

    'form' => [
        'fields' => [
            'payment-method' => 'Mode de paiement de l\'avoir',
            'lines'          => 'Lignes à rembourser',
            'product'        => 'Produit',
            'quantity'       => 'Quantité',
        ],
    ],

    'notification' => [
        'success' => [
            'title' => 'Avoir créé',
            'body'  => 'Un avoir a été créé pour les lignes sélectionnées.',
        ],
    ],
];
