<?php

return [
    'label'        => 'إرجاع المنتجات',

    'form' => [
        'fields' => [
            'payment-method' => 'طريقة دفع الاسترداد',
            'lines'          => 'البنود المراد استردادها',
            'product'        => 'المنتج',
            'quantity'       => 'الكمية',
        ],
    ],

    'notification' => [
        'success' => [
            'title' => 'تم إنشاء الاسترداد',
            'body'  => 'تم إنشاء طلب استرداد للبنود المحددة.',
        ],
    ],
];
