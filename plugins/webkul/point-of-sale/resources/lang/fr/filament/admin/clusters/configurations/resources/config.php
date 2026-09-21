<?php

return [
    'navigation' => [
        'title' => 'Point de vente',
        'group' => 'Point de vente',
    ],

    'form' => [
        'sections' => [
            'general' => [
                'title'  => 'Général',

                'fields' => [
                    'name'             => 'Point de vente',
                    'name-placeholder' => 'p. ex. Boutique NYC',
                    'code'             => 'Code court',
                    'code-helper-text' => 'Utilisé comme préfixe des numéros de commande et de session.',
                    'is-active'        => 'Actif',
                ],
            ],

            'configurations' => [
                'title' => 'Configurations',

                'tabs' => [
                    'restaurant' => [
                        'title'  => 'Mode restaurant',

                        'fields' => [
                            'is-restaurant'             => 'Est un bar/restaurant',
                            'is-restaurant-helper-text' => 'Activer la gestion des tables, le partage d\'addition et les tickets de préparation.',
                            'enable-split-bill'         => 'Partage d\'addition',
                            'enable-print-bill'         => 'Impression de l\'addition',
                            'enable-takeaway'           => 'À emporter',
                            'takeaway-fiscal-position'  => 'Position fiscale alternative',
                            'floors'                    => 'Étages',
                        ],
                    ],

                    'payment' => [
                        'title'  => 'Paiement',

                        'fields' => [
                            'payment-methods'                       => 'Modes de paiement',
                            'enable-cash-control'                   => 'Contrôle des espèces',
                            'enable-cash-control-helper-text'       => 'Vérifier le montant de la caisse à l\'ouverture et à la clôture.',
                            'enable-maximum-difference'             => 'Définir un écart maximal',
                            'enable-maximum-difference-helper-text' => 'Définir un écart maximal autorisé entre le montant attendu et le montant compté lors de la clôture de la session.',
                            'amount-authorized-diff'                => 'Écart maximal',
                            'enable-cash-rounding'                  => 'Arrondi des espèces',
                            'enable-cash-rounding-helper-text'      => 'Définir la plus petite coupure de la devise utilisée pour payer en espèces.',
                            'cash-rounding'                         => 'Méthode d\'arrondi',
                            'enable-only-round-cash-method'         => 'Appliquer l\'arrondi uniquement aux espèces',
                            'enable-tip'                            => 'Pourboires',
                            'enable-tip-helper-text'                => 'Accepter les pourboires des clients ou convertir leur monnaie en pourboire.',
                            'tip-product'                           => 'Produit pourboire',
                        ],
                    ],

                    'interface' => [
                        'title'  => 'Interface PdV',

                        'fields' => [
                            'enable-customer-required'            => 'Client obligatoire',
                            'show-product-images'                 => 'Afficher les images des produits',
                            'show-category-images'                => 'Afficher les images des catégories',
                            'limited-products-amount'             => 'Produits chargés',
                            'limited-products-amount-helper-text' => 'Nombre de produits chargés dans le terminal à l\'ouverture d\'une session.',
                        ],
                    ],

                    'products' => [
                        'title'  => 'Produits et catégories PdV',

                        'fields' => [
                            'limit-categories'             => 'Restreindre les catégories',
                            'limit-categories-helper-text' => 'Choisissez les catégories de produits PdV disponibles.',
                            'categories'                   => 'Catégories de produits PdV disponibles',
                        ],
                    ],

                    'accounting' => [
                        'title'  => 'Comptabilité',

                        'fields' => [
                            'journal'                                 => 'Journal des commandes',
                            'journal-helper-text'                     => 'Journal utilisé pour l\'écriture de clôture de la session.',
                            'invoice-journal'                         => 'Journal des factures',
                            'is-closing-entry-by-product'             => 'Écriture de clôture par produit',
                            'is-closing-entry-by-product-helper-text' => 'Afficher le détail des lignes de vente par produit dans l\'écriture de clôture générée automatiquement.',
                            'enable-fiscal-position'                  => 'Taxes flexibles',
                            'enable-fiscal-position-helper-text'      => 'Utiliser les positions fiscales pour appliquer des taxes différentes par commande.',
                            'fiscal-position'                         => 'Position fiscale par défaut',
                            'fiscal-positions'                        => 'Positions fiscales',
                            'receivable-account'                      => 'Compte intermédiaire',
                            'receivable-account-helper-text'          => 'Laissez vide pour utiliser le compte client par défaut des paramètres du Point de vente.',
                            'cash-movement-account'                   => 'Compte d\'entrée/sortie d\'espèces',
                            'balancing-account'                       => 'Compte d\'équilibrage',
                            'enable-cogs'                             => 'Coût des ventes',
                            'enable-cogs-helper-text'                 => 'Comptabiliser le coût des produits vendus à la clôture de la session.',
                            'cogs-journal'                            => 'Journal du coût des ventes',
                            'stock-output-account'                    => 'Compte de sortie de stock',
                        ],
                    ],

                    'pricing' => [
                        'title'  => 'Tarification',

                        'fields' => [
                            'tax-display'                        => 'Prix des produits',
                            'tax-display-helper-text'            => 'Prix des produits affichés sur le terminal et sur les reçus.',
                            'enable-price-control'               => 'Contrôle des prix',
                            'enable-price-control-helper-text'   => 'Réserver la modification des prix aux responsables.',
                            'enable-price-list'                  => 'Listes de prix flexibles',
                            'enable-price-list-helper-text'      => 'Définir plusieurs prix par produit, des remises automatiques, etc.',
                            'price-list'                         => 'Liste de prix par défaut',
                            'price-list-helper-text'             => 'Appliquée à toutes les commandes de cette caisse. Les caissiers ne peuvent changer de liste que si les listes de prix flexibles sont activées.',
                            'price-lists'                        => 'Listes de prix disponibles',
                            'enable-line-discount'               => 'Remises par ligne',
                            'enable-line-discount-helper-text'   => 'Autoriser les caissiers à appliquer une remise par ligne.',
                            'enable-global-discount'             => 'Remises globales',
                            'enable-global-discount-helper-text' => 'Ajoute un bouton pour appliquer une remise globale.',
                            'discount-product'                   => 'Produit de remise',
                        ],
                    ],

                    'receipts' => [
                        'title'  => 'Additions et reçus',

                        'fields' => [
                            'enable-receipt-print'                  => 'Impression des reçus',
                            'enable-receipt-auto-print'             => 'Impression automatique des reçus',
                            'enable-receipt-auto-print-helper-text' => 'Imprimer les reçus automatiquement dès que le paiement est enregistré.',
                            'receipt-header'                        => 'En-tête du reçu',
                            'receipt-footer'                        => 'Pied du reçu',
                            'bills'                                 => 'Pièces/Billets',
                            'bills-helper-text'                     => 'Coupures proposées sur l\'écran de paiement en espèces.',
                        ],
                    ],

                    'preparation' => [
                        'title'  => 'Préparation',

                        'fields' => [
                            'printers'             => 'Imprimantes de préparation',
                            'printers-helper-text' => 'Imprimer les commandes en cuisine, au bar, etc.',
                        ],
                    ],

                    'inventory' => [
                        'title'  => 'Inventaire',

                        'fields' => [
                            'operation-type'                => 'Type d\'opération',
                            'operation-type-helper-text'    => 'Utilisé pour enregistrer les transferts de produits. Les produits sont consommés depuis son emplacement source par défaut.',
                            'return-operation-type'         => 'Type d\'opération de retour',
                            'warehouse'                     => 'Entrepôt',
                            'enable-ship-later'             => 'Autoriser la livraison ultérieure',
                            'enable-ship-later-helper-text' => 'Vendre des produits et les livrer plus tard.',
                            'ship-later-route'              => 'Route spécifique',
                            'picking-policy'                => 'Politique d\'expédition',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'infolist' => [
        'sections' => [
            'general' => [
                'title'   => 'Général',

                'entries' => [
                    'name'           => 'Point de vente',
                    'code'           => 'Code court',
                    'company-name'   => 'Société',
                    'warehouse-name' => 'Entrepôt',
                    'journal-name'   => 'Journal des commandes',
                    'is-restaurant'  => 'Est un bar/restaurant',
                    'is-active'      => 'Actif',
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'           => 'Nom',
            'closing'        => 'Clôture',
            'balance'        => 'Solde',
            'code'           => 'Code',
            'warehouse'      => 'Entrepôt',
            'operation-type' => 'Type d\'opération',
            'journal'        => 'Journal des ventes',
            'is-restaurant'  => 'Restaurant',
            'is-active'      => 'Actif',
            'company'        => 'Société',
        ],

        'groups' => [
            'warehouse' => 'Entrepôt',
            'company'   => 'Société',
        ],

        'record-actions' => [
            'restore' => [
                'notification' => [
                    'success' => [
                        'title' => 'Point de vente restauré',
                        'body'  => 'Le point de vente a été restauré.',
                    ],
                ],
            ],

            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Point de vente supprimé',
                        'body'  => 'Le point de vente a été supprimé.',
                    ],
                ],
            ],

            'force-delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Point de vente supprimé définitivement',
                        'body'  => 'Le point de vente a été supprimé définitivement.',
                    ],
                ],
            ],
        ],
    ],
];
