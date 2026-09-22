<?php

return [
    'common' => [
        'close'   => 'Fermer',
        'cancel'  => 'Annuler',
        'save'    => 'Enregistrer',
        'saving'  => 'Enregistrement…',
        'discard' => 'Abandonner',
        'clear'   => 'Effacer',
        'apply'   => 'Appliquer',
        'open'    => 'Ouvrir',
        'resume'  => 'Reprendre',
        'confirm' => 'Confirmer',
        'back'    => 'Retour',
        'print'   => 'Imprimer',
        'new'     => 'Nouveau',
        'more'    => '+ :count de plus',
    ],

    'parked' => [
        'walk-in'         => 'Client de passage',
        'items'           => '{1} :count article|[2,*] :count articles',
        'count'           => ':count en attente',
        'view-all'        => 'Tout voir',
        'heading'         => 'Commandes en attente',
        'search'          => 'Rechercher des commandes par nom, référence ou produit',
        'no-match'        => 'Aucune commande ne correspond à cette recherche.',
        'parked-ago'      => 'mise en attente :time',
        'discard'         => 'Abandonner la commande',

        'discard-confirm' => [
            'heading'     => 'Abandonner la commande en attente ?',
            'description' => 'Ses lignes seront perdues. Cette action est irréversible.',
        ],

        'empty' => [
            'heading'     => 'Rien en attente',
            'description' => 'Les commandes que vous mettez de côté vous attendront ici.',
        ],
    ],

    'opening-control' => [
        'heading'     => 'Contrôle d\'ouverture',
        'cash'        => 'Espèces d\'ouverture',
        'note'        => 'Note d\'ouverture',
        'placeholder' => 'Ajouter une note d\'ouverture…',
        'confirm'     => 'Ouvrir la caisse',
    ],

    'tabs' => [
        'new'      => 'Nouvelle commande',
        'all'      => 'Toutes les commandes',
        'view-all' => 'Tout voir',
    ],

    'menu' => [
        'label'          => 'Menu',
        'orders'         => 'Commandes',
        'cash-in-out'    => 'Entrée / Sortie d\'espèces',
        'create-product' => 'Créer un produit',
        'back-office'    => 'Back-office',
        'close-register' => 'Fermer la caisse',
    ],

    'cash-movement' => [
        'heading'      => 'Entrée / sortie d\'espèces',
        'in'           => 'Entrée d\'espèces',
        'out'          => 'Sortie d\'espèces',
        'amount'       => 'Montant',
        'reason'       => 'Motif',
        'confirm'      => 'Enregistrer le mouvement',
        'close'        => 'Fermer',

        'notification' => [
            'title' => 'Mouvement d\'espèces enregistré',
        ],
    ],

    'closing' => [
        'heading'         => 'Fermeture de la caisse',
        'expected'        => 'Attendu dans le tiroir',
        'counted'         => 'Compté',
        'note'            => 'Note de clôture',
        'confirm'         => 'Fermer la caisse',
        'back'            => 'Retour à la caisse',
        'method'          => 'Mode de paiement',
        'total'           => 'Total',
        'opening'         => 'Ouverture',
        'payments'        => 'Paiements',
        'moves'           => 'Entrée / sortie d\'espèces',
        'cash-in'         => 'Entrée d\'espèces :number',
        'cash-out'        => 'Sortie d\'espèces :number',
        'orders'          => ':quantity commandes',
        'difference'      => 'Écart',
        'count'           => 'Comptage des espèces',
        'clear'           => 'Effacer',
        'discard'         => 'Abandonner',
        'daily-sale'      => 'Vente du jour',
        'opening-note'    => 'Note d\'ouverture',
        'authorized-diff' => 'Écart maximal autorisé : :amount',
    ],

    'product-form' => [
        'heading'             => 'Nouveau produit',
        'name'                => 'Nom du produit',
        'name-placeholder'    => 'p. ex. Cheeseburger',
        'barcode'             => 'Code-barres',
        'barcode-placeholder' => 'p. ex. 1234567890',
        'tracking'            => 'Suivre l\'inventaire',
        'price'               => 'Prix de vente',
        'taxes'               => 'Taxes à la vente',
        'tax-included'        => '(= :amount TTC)',
        'category'            => 'Catégorie PdV',
        'unsaleable'          => 'Non vendable',

        'notification' => [
            'title' => 'Produit créé',
        ],

        'error' => [
            'failed'  => 'Impossible de créer le produit (:status)',
            'offline' => 'La création d\'un produit nécessite une connexion.',
        ],
    ],

    'product-info' => [
        'heading'          => 'Informations produit',
        'inventory'        => 'Inventaire',
        'on-hand'          => 'en stock sur cette caisse',
        'negative-warning' => 'La vente reste autorisée ; le stock passera en négatif et le back-office affichera le manque.',
        'financials'       => 'Données financières',
        'price'            => 'Prix',
        'cost'             => 'Coût',
        'margin'           => 'Marge',
        'order'            => 'Dans cette commande',
        'quantity'         => 'Quantité',
        'total-price'      => 'Prix total',
        'total-margin'     => 'Marge totale',
        'add'              => 'Ajouter à la commande',
    ],

    'customers' => [
        'heading'   => 'Sélectionner un client',
        'search'    => 'Rechercher des clients',
        'no-match'  => 'Aucun client ne correspond à cette recherche. Seuls les clients chargés au démarrage de la session sont consultables hors ligne.',
        'clear'     => 'Retirer le client',
        'badge-new' => 'nouveau',

        'create' => [
            'label'   => 'Nouveau client',
            'heading' => 'Nouveau client',
            'created' => ':name ajouté et sélectionné.',

            'fields' => [
                'name'  => 'Nom',
                'email' => 'E-mail',
                'phone' => 'Téléphone',
            ],
        ],
    ],

    'variants' => [
        'heading'     => 'Sélection des attributs',
        'confirm'     => 'Ajouter',
        'unavailable' => 'Cette combinaison n\'existe pas.',
    ],

    'lots' => [
        'heading'        => 'Numéro(s) de lot/série requis',
        'placeholder'    => 'Numéro de série/lot',
        'add'            => 'Ajouter',
        'remove'         => 'Retirer le numéro',
        'missing'        => 'Définir le numéro de lot / série',
        'none-available' => 'Il n\'existe aucun numéro de série/lot pour le produit sélectionné, et leur création n\'est pas autorisée depuis l\'application Point de vente.',

        'warning' => [
            'heading' => 'Certains numéros de série/lot sont manquants',
            'body'    => 'Vous essayez de vendre des produits avec des numéros de série/lot, mais certains ne sont pas renseignés.
Souhaitez-vous continuer malgré tout ?',
            'proceed' => 'OK',
        ],
    ],

    'notes' => [
        'internal'    => 'Note interne',
        'kitchen'     => 'Note cuisine',
        'heading'     => 'Ajouter une note interne',
        'empty'       => 'Aucun modèle de note configuré.',
        'hint'        => 'Choisissez d\'abord une ligne, puis une note.',
        'placeholder' => 'Ajouter une note pour cette ligne',
    ],

    'money-details' => [
        'label'    => 'Pièces/Billets',
        'heading'  => 'Détails d\'ouverture :',
        'total'    => 'Total : :total',
        'confirm'  => 'Confirmer',
        'close'    => 'Fermer',
        'increase' => 'Ajouter un',
        'decrease' => 'Retirer un',
    ],

    'cart' => [
        'discount' => ':percentage% de remise',
        'subtotal' => 'Sous-total',
        'tax'      => 'Taxes',
        'rounding' => 'Arrondi',
        'total'    => 'Total',
        'remove'   => 'Retirer :product',

        'empty' => [
            'description' => 'Scannez ou touchez un produit pour commencer',
        ],
    ],

    'catalogue' => [
        'search'         => 'Rechercher des produits',
        'create-product' => 'Créer un produit',
        'info'           => 'Informations sur le produit :product',
        'info-depleted'  => 'Informations sur le produit :product, aucun en stock',
    ],

    'numpad' => [
        'qty'       => 'Qté',
        'price'     => 'Prix',
        'backspace' => 'Retour arrière',
    ],

    'payment' => [
        'select-method' => 'Veuillez sélectionner un mode de paiement',
        'invoice'       => 'Facture',
        'change'        => 'monnaie',
        'remove'        => 'Retirer le paiement :method',
        'validate'      => 'Valider',
    ],

    'receipt' => [
        'phone'     => 'Tél. :',
        'served-by' => 'Servi par :cashier',
        'untaxed'   => 'Montant hors taxes',
        'rounding'  => 'Arrondi',
        'total'     => 'TOTAL',
        'change'    => 'Monnaie',
        'order'     => 'Commande :order',
        'new-order' => 'Nouvelle commande',
    ],

    'install' => [
        'installed'   => 'Cette caisse est déjà installée comme application sur cet appareil.',
        'unavailable' => 'Ce navigateur ne peut pas installer la caisse. Chrome ou Edge en HTTPS est requis.',
        'label'       => 'Installer l\'application',
    ],

    'scanner' => [
        'unsupported'   => 'Ce navigateur ne peut pas scanner avec la caméra. Utilisez plutôt une douchette connectée.',
        'hardware-hint' => 'Une douchette connectée fonctionne partout dans la caisse sans ouvrir ceci.',
        'heading'       => 'Scanner un code-barres',
        'start'         => 'Scanner avec la caméra',
        'stop'          => 'Arrêter',
    ],

    'offline' => [
        'banner'              => 'Hors ligne — les ventes continuent et se synchronisent au retour de la connexion',
        'waiting'             => ':count commande(s) en attente de synchronisation',
        'rejected'            => ':count commande(s) rejetée(s) par le serveur',
        'storage-unavailable' => 'Stockage local indisponible — rechargez la page avant de prendre d\'autres commandes',
    ],

    'actions' => [
        'customer'     => 'Client',
        'note'         => 'Note',
        'payment'      => 'Paiement',
        'heading'      => 'Actions',
        'label'        => 'Actions',

        'cancel-order' => [
            'label'   => 'Annuler la commande',
            'confirm' => 'Confirmer',
            'hint'    => 'Ses lignes seront perdues. Cette action est irréversible.',
        ],
    ],

    'price-lists' => [
        'label'   => 'Liste de prix',
        'heading' => 'Sélectionner la liste de prix',
        'default' => 'Prix par défaut',
    ],

    'notification' => [
        'success' => [
            'title' => 'Commande terminée',
            'body'  => 'La commande :order a été réglée.',
        ],

        'queued' => [
            'title' => 'Commande mise en file d\'attente hors ligne',
            'body'  => ':count commande(s) seront synchronisées au retour de la connexion.',
        ],
    ],
];
