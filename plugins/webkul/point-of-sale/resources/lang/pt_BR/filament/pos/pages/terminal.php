<?php

return [
    'common' => [
        'close'   => 'Fechar',
        'cancel'  => 'Cancelar',
        'save'    => 'Salvar',
        'saving'  => 'Salvando…',
        'discard' => 'Descartar',
        'clear'   => 'Limpar',
        'apply'   => 'Aplicar',
        'open'    => 'Abrir',
        'resume'  => 'Retomar',
        'confirm' => 'Confirmar',
        'back'    => 'Voltar',
        'print'   => 'Imprimir',
        'new'     => 'Novo',
        'more'    => '+ :count a mais',
    ],

    'parked' => [
        'walk-in'         => 'Cliente avulso',
        'items'           => '{1} :count item|[2,*] :count itens',
        'count'           => ':count em espera',
        'view-all'        => 'Ver tudo',
        'heading'         => 'Pedidos em espera',
        'search'          => 'Buscar pedidos por nome, referência ou produto',
        'no-match'        => 'Nenhum pedido corresponde a essa busca.',
        'parked-ago'      => 'em espera :time',
        'discard'         => 'Descartar pedido',

        'discard-confirm' => [
            'heading'     => 'Descartar o pedido em espera?',
            'description' => 'As linhas dele serão perdidas. Esta ação não pode ser desfeita.',
        ],

        'empty' => [
            'heading'     => 'Nada em espera',
            'description' => 'Os pedidos que você deixar de lado ficarão aqui.',
        ],
    ],

    'opening-control' => [
        'heading'     => 'Controle de abertura',
        'cash'        => 'Dinheiro de abertura',
        'note'        => 'Observação de abertura',
        'placeholder' => 'Adicione uma observação de abertura…',
        'confirm'     => 'Abrir caixa',
    ],

    'tabs' => [
        'new'      => 'Novo pedido',
        'all'      => 'Todos os pedidos',
        'view-all' => 'Ver tudo',
    ],

    'menu' => [
        'label'          => 'Menu',
        'orders'         => 'Pedidos',
        'cash-in-out'    => 'Entrada / Saída de caixa',
        'create-product' => 'Criar produto',
        'back-office'    => 'Back office',
        'close-register' => 'Fechar caixa',
    ],

    'cash-movement' => [
        'heading'      => 'Entrada / saída de caixa',
        'in'           => 'Entrada de caixa',
        'out'          => 'Saída de caixa',
        'amount'       => 'Valor',
        'reason'       => 'Motivo',
        'confirm'      => 'Registrar movimento',
        'close'        => 'Fechar',

        'notification' => [
            'title' => 'Movimento de caixa registrado',
        ],
    ],

    'closing' => [
        'heading'         => 'Fechando o caixa',
        'expected'        => 'Esperado na gaveta',
        'counted'         => 'Contado',
        'note'            => 'Observação de fechamento',
        'confirm'         => 'Fechar caixa',
        'back'            => 'Voltar ao PDV',
        'method'          => 'Método de pagamento',
        'total'           => 'Total',
        'opening'         => 'Abertura',
        'payments'        => 'Pagamentos',
        'moves'           => 'Entrada / saída de caixa',
        'cash-in'         => 'Entrada de caixa :number',
        'cash-out'        => 'Saída de caixa :number',
        'orders'          => ':quantity pedidos',
        'difference'      => 'Diferença',
        'count'           => 'Contagem de dinheiro',
        'clear'           => 'Limpar',
        'discard'         => 'Descartar',
        'daily-sale'      => 'Venda do dia',
        'opening-note'    => 'Observação de abertura',
        'authorized-diff' => 'Diferença máxima permitida: :amount',
    ],

    'product-form' => [
        'heading'             => 'Novo produto',
        'name'                => 'Nome do produto',
        'name-placeholder'    => 'ex.: X-Burger',
        'barcode'             => 'Código de barras',
        'barcode-placeholder' => 'ex.: 1234567890',
        'tracking'            => 'Controlar estoque',
        'price'               => 'Preço de venda',
        'taxes'               => 'Impostos de venda',
        'tax-included'        => '(= :amount com impostos)',
        'category'            => 'Categoria do PDV',
        'unsaleable'          => 'Não vendável',

        'notification' => [
            'title' => 'Produto criado',
        ],

        'error' => [
            'failed'  => 'Não foi possível criar o produto (:status)',
            'offline' => 'Criar um produto exige conexão.',
        ],
    ],

    'product-info' => [
        'heading'          => 'Informações do produto',
        'inventory'        => 'Estoque',
        'on-hand'          => 'em mãos neste caixa',
        'negative-warning' => 'A venda continua permitida; o estoque ficará negativo e o back office mostrará a falta.',
        'financials'       => 'Financeiro',
        'price'            => 'Preço',
        'cost'             => 'Custo',
        'margin'           => 'Margem',
        'order'            => 'Neste pedido',
        'quantity'         => 'Quantidade',
        'total-price'      => 'Preço total',
        'total-margin'     => 'Margem total',
        'add'              => 'Adicionar ao pedido',
    ],

    'customers' => [
        'heading'   => 'Selecionar um cliente',
        'search'    => 'Buscar clientes',
        'no-match'  => 'Nenhum cliente corresponde a essa busca. Somente os clientes carregados no início da sessão podem ser buscados offline.',
        'clear'     => 'Remover cliente',
        'badge-new' => 'novo',

        'create' => [
            'label'   => 'Novo cliente',
            'heading' => 'Novo cliente',
            'created' => ':name adicionado e selecionado.',

            'fields' => [
                'name'  => 'Nome',
                'email' => 'E-mail',
                'phone' => 'Telefone',
            ],
        ],
    ],

    'variants' => [
        'heading'     => 'Seleção de atributos',
        'confirm'     => 'Adicionar',
        'unavailable' => 'Esta combinação não existe.',
    ],

    'lots' => [
        'heading'        => 'Número(s) de lote/série obrigatório(s)',
        'placeholder'    => 'Número de série/lote',
        'add'            => 'Adicionar',
        'remove'         => 'Remover número',
        'missing'        => 'Definir número de lote / série',
        'none-available' => 'Não há número de série/lote para o produto selecionado, e a criação deles não é permitida pelo aplicativo do Ponto de venda.',

        'warning' => [
            'heading' => 'Faltam alguns números de série/lote',
            'body'    => 'Você está tentando vender produtos com números de série/lote, mas alguns deles não foram informados.
Deseja continuar mesmo assim?',
            'proceed' => 'Ok',
        ],
    ],

    'notes' => [
        'internal'    => 'Observação interna',
        'kitchen'     => 'Observação da cozinha',
        'heading'     => 'Adicionar observação interna',
        'empty'       => 'Nenhum modelo de observação configurado.',
        'hint'        => 'Escolha primeiro uma linha e depois uma observação.',
        'placeholder' => 'Adicione uma observação para esta linha',
    ],

    'money-details' => [
        'label'    => 'Moedas/Cédulas',
        'heading'  => 'Detalhes de abertura:',
        'total'    => 'Total: :total',
        'confirm'  => 'Confirmar',
        'close'    => 'Fechar',
        'increase' => 'Adicionar um',
        'decrease' => 'Remover um',
    ],

    'cart' => [
        'discount' => ':percentage% de desconto',
        'subtotal' => 'Subtotal',
        'tax'      => 'Impostos',
        'rounding' => 'Arredondamento',
        'total'    => 'Total',
        'remove'   => 'Remover :product',

        'empty' => [
            'description' => 'Escaneie ou toque em um produto para começar',
        ],
    ],

    'catalogue' => [
        'search'         => 'Buscar produtos',
        'create-product' => 'Criar produto',
        'info'           => 'Informações do produto :product',
        'info-depleted'  => 'Informações do produto :product, sem estoque',
    ],

    'numpad' => [
        'qty'       => 'Qtd.',
        'price'     => 'Preço',
        'backspace' => 'Retrocesso',
    ],

    'payment' => [
        'select-method' => 'Selecione um método de pagamento',
        'invoice'       => 'Fatura',
        'change'        => 'troco',
        'remove'        => 'Remover o pagamento :method',
        'validate'      => 'Validar',
    ],

    'receipt' => [
        'phone'     => 'Tel.:',
        'served-by' => 'Atendido por :cashier',
        'untaxed'   => 'Valor sem impostos',
        'rounding'  => 'Arredondamento',
        'total'     => 'TOTAL',
        'change'    => 'Troco',
        'order'     => 'Pedido :order',
        'new-order' => 'Novo pedido',
    ],

    'install' => [
        'installed'   => 'Este caixa já está instalado como aplicativo neste dispositivo.',
        'unavailable' => 'Este navegador não consegue instalar o caixa. É necessário Chrome ou Edge com HTTPS.',
        'label'       => 'Instalar o app',
    ],

    'scanner' => [
        'unsupported'   => 'Este navegador não consegue escanear com a câmera. Use um leitor de código de barras conectado.',
        'hardware-hint' => 'Um leitor de código de barras conectado funciona em todo o PDV sem abrir isto.',
        'heading'       => 'Escanear um código de barras',
        'start'         => 'Escanear com a câmera',
        'stop'          => 'Parar',
    ],

    'offline' => [
        'banner'              => 'Offline — as vendas continuam e serão sincronizadas quando a conexão voltar',
        'waiting'             => ':count pedido(s) aguardando sincronização',
        'rejected'            => ':count pedido(s) rejeitados pelo servidor',
        'storage-unavailable' => 'Armazenamento local indisponível — recarregue antes de registrar mais pedidos',
    ],

    'actions' => [
        'customer'     => 'Cliente',
        'note'         => 'Observação',
        'payment'      => 'Pagamento',
        'heading'      => 'Ações',
        'label'        => 'Ações',

        'cancel-order' => [
            'label'   => 'Cancelar pedido',
            'confirm' => 'Confirmar',
            'hint'    => 'As linhas dele serão perdidas. Esta ação não pode ser desfeita.',
        ],
    ],

    'price-lists' => [
        'label'   => 'Lista de preços',
        'heading' => 'Selecione a lista de preços',
        'default' => 'Preço padrão',
    ],

    'notification' => [
        'success' => [
            'title' => 'Pedido concluído',
            'body'  => 'O pedido :order foi liquidado.',
        ],

        'queued' => [
            'title' => 'Pedido enfileirado offline',
            'body'  => ':count pedido(s) serão sincronizados quando a conexão voltar.',
        ],
    ],
];
