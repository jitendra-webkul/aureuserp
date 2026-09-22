<?php

return [
    'common' => [
        'close'   => 'Cerrar',
        'cancel'  => 'Cancelar',
        'save'    => 'Guardar',
        'saving'  => 'Guardando…',
        'discard' => 'Descartar',
        'clear'   => 'Limpiar',
        'apply'   => 'Aplicar',
        'open'    => 'Abrir',
        'resume'  => 'Reanudar',
        'confirm' => 'Confirmar',
        'back'    => 'Atrás',
        'print'   => 'Imprimir',
        'new'     => 'Nuevo',
        'more'    => '+ :count más',
    ],

    'parked' => [
        'walk-in'         => 'Sin registrar',
        'items'           => '{1} :count artículo|[2,*] :count artículos',
        'count'           => ':count aparcados',
        'view-all'        => 'Ver todo',
        'heading'         => 'Pedidos aparcados',
        'search'          => 'Buscar pedidos por nombre, referencia o producto',
        'no-match'        => 'Ningún pedido coincide con esa búsqueda.',
        'parked-ago'      => 'aparcado :time',
        'discard'         => 'Descartar pedido',

        'discard-confirm' => [
            'heading'     => '¿Descartar el pedido aparcado?',
            'description' => 'Se perderán sus líneas. Esta acción no se puede deshacer.',
        ],

        'empty' => [
            'heading'     => 'Nada aparcado',
            'description' => 'Los pedidos que dejes a un lado esperarán aquí.',
        ],
    ],

    'opening-control' => [
        'heading'     => 'Control de apertura',
        'cash'        => 'Efectivo de apertura',
        'note'        => 'Nota de apertura',
        'placeholder' => 'Añade una nota de apertura…',
        'confirm'     => 'Abrir caja',
    ],

    'tabs' => [
        'new'      => 'Nuevo pedido',
        'all'      => 'Todos los pedidos',
        'view-all' => 'Ver todo',
    ],

    'menu' => [
        'label'          => 'Menú',
        'orders'         => 'Pedidos',
        'cash-in-out'    => 'Entrada / Salida de efectivo',
        'create-product' => 'Crear producto',
        'back-office'    => 'Back office',
        'close-register' => 'Cerrar caja',
    ],

    'cash-movement' => [
        'heading'      => 'Entrada / salida de efectivo',
        'in'           => 'Entrada de efectivo',
        'out'          => 'Salida de efectivo',
        'amount'       => 'Importe',
        'reason'       => 'Motivo',
        'confirm'      => 'Registrar movimiento',
        'close'        => 'Cerrar',

        'notification' => [
            'title' => 'Movimiento de efectivo registrado',
        ],
    ],

    'closing' => [
        'heading'         => 'Cerrando la caja',
        'expected'        => 'Esperado en el cajón',
        'counted'         => 'Contado',
        'note'            => 'Nota de cierre',
        'confirm'         => 'Cerrar caja',
        'back'            => 'Volver al TPV',
        'method'          => 'Método de pago',
        'total'           => 'Total',
        'opening'         => 'Apertura',
        'payments'        => 'Pagos',
        'moves'           => 'Entrada / salida de efectivo',
        'cash-in'         => 'Entrada de efectivo :number',
        'cash-out'        => 'Salida de efectivo :number',
        'orders'          => ':quantity pedidos',
        'difference'      => 'Diferencia',
        'count'           => 'Recuento de efectivo',
        'clear'           => 'Limpiar',
        'discard'         => 'Descartar',
        'daily-sale'      => 'Venta diaria',
        'opening-note'    => 'Nota de apertura',
        'authorized-diff' => 'Diferencia máxima permitida: :amount',
    ],

    'product-form' => [
        'heading'             => 'Nuevo producto',
        'name'                => 'Nombre del producto',
        'name-placeholder'    => 'p. ej. Hamburguesa con queso',
        'barcode'             => 'Código de barras',
        'barcode-placeholder' => 'p. ej. 1234567890',
        'tracking'            => 'Seguir inventario',
        'price'               => 'Precio de venta',
        'taxes'               => 'Impuestos de venta',
        'tax-included'        => '(= :amount impuestos incluidos)',
        'category'            => 'Categoría del TPV',
        'unsaleable'          => 'No vendible',

        'notification' => [
            'title' => 'Producto creado',
        ],

        'error' => [
            'failed'  => 'No se pudo crear el producto (:status)',
            'offline' => 'Crear un producto requiere conexión.',
        ],
    ],

    'product-info' => [
        'heading'          => 'Información del producto',
        'inventory'        => 'Inventario',
        'on-hand'          => 'disponibles en esta caja',
        'negative-warning' => 'La venta sigue permitida; el stock quedará en negativo y el back office mostrará el faltante.',
        'financials'       => 'Finanzas',
        'price'            => 'Precio',
        'cost'             => 'Coste',
        'margin'           => 'Margen',
        'order'            => 'En este pedido',
        'quantity'         => 'Cantidad',
        'total-price'      => 'Precio total',
        'total-margin'     => 'Margen total',
        'add'              => 'Añadir al pedido',
    ],

    'customers' => [
        'heading'   => 'Seleccionar un cliente',
        'search'    => 'Buscar clientes',
        'no-match'  => 'Ningún cliente coincide con esa búsqueda. Solo se pueden buscar sin conexión los clientes cargados al iniciar la sesión.',
        'clear'     => 'Quitar cliente',
        'badge-new' => 'nuevo',

        'create' => [
            'label'   => 'Nuevo cliente',
            'heading' => 'Nuevo cliente',
            'created' => ':name añadido y seleccionado.',

            'fields' => [
                'name'  => 'Nombre',
                'email' => 'Correo electrónico',
                'phone' => 'Teléfono',
            ],
        ],
    ],

    'variants' => [
        'heading'     => 'Selección de atributos',
        'confirm'     => 'Añadir',
        'unavailable' => 'Esta combinación no existe.',
    ],

    'lots' => [
        'heading'        => 'Número(s) de lote/serie requerido(s)',
        'placeholder'    => 'Número de serie/lote',
        'add'            => 'Añadir',
        'remove'         => 'Quitar número',
        'missing'        => 'Indicar número de lote / serie',
        'none-available' => 'No hay número de serie/lote para el producto seleccionado y su creación no está permitida desde la aplicación del Punto de venta.',

        'warning' => [
            'heading' => 'Faltan algunos números de serie/lote',
            'body'    => 'Está intentando vender productos con números de serie/lote, pero algunos no están definidos.
¿Desea continuar de todas formas?',
            'proceed' => 'Aceptar',
        ],
    ],

    'notes' => [
        'internal'    => 'Nota interna',
        'kitchen'     => 'Nota de cocina',
        'heading'     => 'Añadir nota interna',
        'empty'       => 'No hay modelos de nota configurados.',
        'hint'        => 'Elige primero una línea y luego una nota.',
        'placeholder' => 'Añade una nota para esta línea',
    ],

    'money-details' => [
        'label'    => 'Monedas/Billetes',
        'heading'  => 'Detalles de apertura:',
        'total'    => 'Total: :total',
        'confirm'  => 'Confirmar',
        'close'    => 'Cerrar',
        'increase' => 'Añadir uno',
        'decrease' => 'Quitar uno',
    ],

    'cart' => [
        'discount' => ':percentage% de descuento',
        'subtotal' => 'Subtotal',
        'tax'      => 'Impuestos',
        'rounding' => 'Redondeo',
        'total'    => 'Total',
        'remove'   => 'Quitar :product',

        'empty' => [
            'description' => 'Escanea o toca un producto para empezar',
        ],
    ],

    'catalogue' => [
        'search'         => 'Buscar productos',
        'create-product' => 'Crear producto',
        'info'           => 'Información del producto :product',
        'info-depleted'  => 'Información del producto :product, sin existencias',
    ],

    'numpad' => [
        'qty'       => 'Cant.',
        'price'     => 'Precio',
        'backspace' => 'Retroceso',
    ],

    'payment' => [
        'select-method' => 'Seleccione un método de pago',
        'invoice'       => 'Factura',
        'change'        => 'cambio',
        'remove'        => 'Quitar el pago de :method',
        'validate'      => 'Validar',
    ],

    'receipt' => [
        'phone'     => 'Tel.:',
        'served-by' => 'Atendido por :cashier',
        'untaxed'   => 'Base imponible',
        'rounding'  => 'Redondeo',
        'total'     => 'TOTAL',
        'change'    => 'Cambio',
        'order'     => 'Pedido :order',
        'new-order' => 'Nuevo pedido',
    ],

    'install' => [
        'installed'   => 'Esta caja ya está instalada como aplicación en este dispositivo.',
        'unavailable' => 'Este navegador no puede instalar la caja. Se requiere Chrome o Edge con HTTPS.',
        'label'       => 'Instalar la app',
    ],

    'scanner' => [
        'unsupported'   => 'Este navegador no puede escanear con la cámara. Usa un lector de códigos de barras conectado.',
        'hardware-hint' => 'Un lector de códigos de barras conectado funciona en todo el TPV sin abrir esto.',
        'heading'       => 'Escanear un código de barras',
        'start'         => 'Escanear con la cámara',
        'stop'          => 'Detener',
    ],

    'offline' => [
        'banner'              => 'Sin conexión — las ventas continúan y se sincronizan cuando vuelva la conexión',
        'waiting'             => ':count pedido(s) pendientes de sincronizar',
        'rejected'            => ':count pedido(s) rechazados por el servidor',
        'storage-unavailable' => 'Almacenamiento local no disponible — recarga antes de tomar más pedidos',
    ],

    'actions' => [
        'customer'     => 'Cliente',
        'note'         => 'Nota',
        'payment'      => 'Pago',
        'heading'      => 'Acciones',
        'label'        => 'Acciones',

        'cancel-order' => [
            'label'   => 'Cancelar pedido',
            'confirm' => 'Confirmar',
            'hint'    => 'Se perderán sus líneas. Esta acción no se puede deshacer.',
        ],
    ],

    'price-lists' => [
        'label'   => 'Lista de precios',
        'heading' => 'Selecciona la lista de precios',
        'default' => 'Precio predeterminado',
    ],

    'notification' => [
        'success' => [
            'title' => 'Pedido completado',
            'body'  => 'El pedido :order se ha liquidado.',
        ],

        'queued' => [
            'title' => 'Pedido en cola sin conexión',
            'body'  => ':count pedido(s) se sincronizarán cuando vuelva la conexión.',
        ],
    ],
];
