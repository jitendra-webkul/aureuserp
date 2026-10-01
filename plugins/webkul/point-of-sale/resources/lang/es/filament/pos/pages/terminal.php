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
        'heading' => 'Entrada / salida de efectivo',
        'in'      => 'Entrada de efectivo',
        'out'     => 'Salida de efectivo',
        'amount'  => 'Importe',
        'reason'  => 'Motivo',
        'confirm' => 'Registrar movimiento',
        'close'   => 'Cerrar',

        'notification' => [
            'title' => 'Movimiento de efectivo registrado',
        ],
    ],

    'closing' => [
        'copy'                     => 'Copiar el importe esperado',
        'heading'                  => 'Cerrando la caja',
        'expected'                 => 'Esperado en el cajón',
        'counted'                  => 'Contado',
        'note'                     => 'Nota de cierre',
        'confirm'                  => 'Cerrar caja',
        'back'                     => 'Volver al TPV',
        'method'                   => 'Método de pago',
        'total'                    => 'Total',
        'opening'                  => 'Apertura',
        'payments'                 => 'Pagos en efectivo',
        'moves'                    => 'Entrada / salida de efectivo',
        'cash-in'                  => 'Entrada de efectivo :number',
        'cash-out'                 => 'Salida de efectivo :number',
        'orders'                   => ':quantity pedidos',
        'difference'               => 'Diferencia',
        'count'                    => 'Recuento de efectivo',
        'clear'                    => 'Limpiar',
        'discard'                  => 'Descartar',
        'daily-sale'               => 'Venta diaria',
        'opening-note'             => 'Nota de apertura',
        'authorized-diff'          => 'Diferencia máxima permitida: :amount',
        'authorized-diff-exceeded' => 'La diferencia supera el límite permitido. Solo un responsable puede cerrar esta caja.',
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
        'available'        => 'disponibles en esta caja',
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
        'heading'      => 'Seleccionar un cliente',
        'search'       => 'Buscar clientes',
        'no-match'     => 'Ningún cliente coincide con esa búsqueda. Solo se pueden buscar sin conexión los clientes cargados al iniciar la sesión.',
        'clear'        => 'Quitar cliente',
        'badge-new'    => 'nuevo',

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
            'body'    => "Está intentando vender productos con números de serie/lote, pero algunos no están definidos.\n¿Desea continuar de todas formas?",
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
        'label'            => 'Monedas/Billetes',
        'opening-heading'  => 'Detalles de apertura:',
        'closing-heading'  => 'Detalles de cierre:',
        'total'            => 'Total: :total',
        'confirm'          => 'Confirmar',
        'close'            => 'Cerrar',
        'increase'         => 'Añadir uno',
        'decrease'         => 'Quitar uno',
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
        'search'          => 'Buscar productos',
        'create-product'  => 'Crear producto',
        'info'            => 'Información del producto :product',
        'info-depleted'   => 'Información del producto :product, sin unidades disponibles',
    ],

    'numpad' => [
        'qty'       => 'Cant.',
        'price'     => 'Precio',
        'backspace' => 'Retroceso',
    ],

    'payment' => [
        'ship-later'         => 'Enviar más tarde',
        'ship-later-heading' => 'Seleccione la fecha de envío',
        'select-method'      => 'Seleccione un método de pago',
        'invoice'            => 'Factura',
        'change'             => 'cambio',
        'remove'             => 'Quitar el pago de :method',
        'validate'           => 'Validar',
    ],

    'floor' => [
        'back'     => 'Plano',
        'table'    => 'Mesa :table',
        'no-table' => 'Sin mesa',
        'empty'    => 'Aún no hay mesas en esta planta. Añádelas desde la administración.',
        'seats'    => '{1} :count asiento|[2,*] :count asientos',
        'orders'   => '{1} :count pedido|[2,*] :count pedidos',
        'guests'   => '{1} :count comensal|[2,*] :count comensales',
    ],

    'guests' => [
        'label'     => 'Comensales',
        'heading'   => 'Número de comensales',
        'increase'  => 'Añadir comensal',
        'decrease'  => 'Quitar comensal',
        'per-guest' => ':amount por comensal',
    ],

    'split' => [
        'label'    => 'Dividir',
        'heading'  => 'Dividir la cuenta',
        'hint'     => 'Toca una línea para pasar una unidad a la nueva cuenta. Toca de nuevo para pasar más.',
        'new-bill' => 'Nueva cuenta',
        'confirm'  => 'Dividir pedido',
    ],

    'bill' => [
        'label'   => 'Cuenta',
        'heading' => 'Imprimir la cuenta',
    ],

    'takeaway' => [
        'badge'       => 'Para llevar',
        'to-takeaway' => 'Cambiar a para llevar',
        'to-dine-in'  => 'Cambiar a comer aquí',
    ],

    'order-name' => [
        'label'       => 'Editar nombre del pedido',
        'heading'     => 'Editar nombre del pedido',
        'placeholder' => 'p. ej. 18:45 Juan 4P',
    ],

    'tip' => [
        'label'  => 'Propina',
        'add'    => 'Añadir propina',
        'change' => 'Cambiar propina',
        'remove' => 'Quitar propina',
    ],

    'global-discount' => [
        'label'   => 'Descuento',
        'heading' => 'Porcentaje de descuento',
        'apply'   => 'Aplicar',
        'hint'    => 'Sustituye cualquier descuento ya aplicado al pedido.',
    ],

    'booking' => [
        'book'    => 'Reservar mesa',
        'release' => 'Liberar mesa',
    ],

    'transfer' => [
        'label'        => 'Transferir / Fusionar',
        'prompt'       => 'Selecciona una mesa para transferir :order',
        'has-payments' => 'Este pedido tiene pagos. Elimínalos antes de fusionarlo con otra mesa.',
    ],

    'table-selector' => [
        'label'       => 'Mesa',
        'heading'     => 'Selector de mesa',
        'hint'        => 'Introduce un número de mesa o un nombre para un pedido sin mesa.',
        'placeholder' => 'Número de mesa o nombre',
        'jump'        => 'Ir',
    ],

    'session-closed' => [
        'heading' => 'Caja cerrada',
        'body'    => 'Esta caja se cerró desde otra ventana o dispositivo. Los pedidos abiertos en este dispositivo no se enviaron; vuelve a abrir la caja para continuar.',
        'back'    => 'Volver a las cajas',
    ],

    'floor-plan' => [
        'edit'                 => 'Editar plano',
        'switch-view'          => 'Cambiar vista de planta',
        'view-map'             => 'Vista de mapa',
        'view-grid'            => 'Vista de cuadrícula',
        'floor-name'           => 'Nombre de la planta',
        'background'           => 'Fondo de la planta',
        'no-colour'            => 'Sin color',
        'add-table'            => 'Añadir mesa',
        'add-floor'            => 'Añadir planta',
        'delete-floor'         => 'Eliminar planta',
        'confirm-delete-floor' => 'Confirmar eliminación',
        'table-number'         => 'Mesa',
        'fewer-seats'          => 'Menos asientos',
        'more-seats'           => 'Más asientos',
        'make-square'          => 'Hacer cuadrada',
        'make-round'           => 'Hacer redonda',
        'duplicate'            => 'Duplicar',
        'delete-table'         => 'Eliminar mesa',
        'hint'                 => 'Arrastra las mesas para moverlas, arrastra la esquina para cambiar su tamaño y toca una mesa para editarla.',
        'new-floor'            => 'Planta :number',
        'failed'               => 'No se pudo guardar el plano (:status).',
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
        'guests'    => '{1} :count comensal|[2,*] :count comensales',
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
        'retry'               => 'Reintentar',
    ],

    'actions' => [
        'customer' => 'Cliente',
        'note'     => 'Nota',
        'payment'  => 'Pago',
        'heading'  => 'Acciones',
        'label'    => 'Acciones',

        'cancel-order' => [
            'label'   => 'Cancelar pedido',
            'confirm' => 'Confirmar',
            'hint'    => 'Se perderán sus líneas. Esta acción no se puede deshacer.',
            'failed'  => 'No se pudo cancelar el pedido (:status).',
            'offline' => 'Cancelar este pedido requiere conexión.',
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
