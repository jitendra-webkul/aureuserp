<?php

return [
    'label'        => 'Devolver productos',

    'form' => [
        'fields' => [
            'payment-method' => 'Método de pago del reembolso',
            'lines'          => 'Líneas a reembolsar',
            'product'        => 'Producto',
            'quantity'       => 'Cantidad',
        ],
    ],

    'notification' => [
        'success' => [
            'title' => 'Reembolso creado',
            'body'  => 'Se ha creado un pedido de reembolso para las líneas seleccionadas.',
        ],
    ],
];
