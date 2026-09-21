<?php

return [
    'title'        => 'Pedidos',

    'navigation' => [
        'label' => 'Pedidos',
    ],

    'walk-in'      => 'Cliente ocasional',
    'search'       => 'Buscar por pedido, recibo o cliente',
    'select-order' => 'Selecciona un pedido para ver sus líneas.',
    'taxes'        => 'Impuestos',
    'total'        => 'Total',

    'status' => [
        'active' => 'Todos los pedidos activos',
    ],

    'columns' => [
        'date'     => 'Fecha',
        'receipt'  => 'Número de recibo',
        'order'    => 'Número de pedido',
        'customer' => 'Cliente',
        'cashier'  => 'Cajero',
        'total'    => 'Total',
        'status'   => 'Estado',
    ],

    'refund' => [
        'prompt'    => 'Selecciona el/los producto(s) a reembolsar e indica la cantidad',
        'to-refund' => 'A reembolsar:',
        'qty'       => 'Cant.',
        'price'     => 'Precio',
        'backspace' => 'Retroceso',
    ],

    'actions' => [
        'print'               => 'Imprimir recibo',
        'back'                => 'Atrás',
        'details'             => 'Detalles',
        'refund'              => 'Reembolso',
        'previous'            => 'Página anterior',
        'next'                => 'Página siguiente',

        'refund-notification' => [
            'title' => 'Reembolso creado',
            'body'  => 'El reembolso :order está listo para liquidarse.',
        ],

        'invoice' => [
            'label'        => 'Factura',
            'heading'      => '¿Crear una factura para este pedido?',

            'notification' => [
                'title' => 'Factura creada',
            ],
        ],
    ],

    'empty' => [
        'heading'     => 'Aún no hay pedidos',
        'description' => 'Los pedidos registrados durante la sesión abierta aparecerán aquí.',
    ],
];
