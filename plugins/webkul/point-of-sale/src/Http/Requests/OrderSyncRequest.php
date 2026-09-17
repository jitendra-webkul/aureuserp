<?php

namespace Webkul\PointOfSale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderSyncRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'orders'                                => ['required', 'array', 'min:1', 'max:100'],
            'orders.*.uuid'                         => ['required', 'uuid'],
            'orders.*.config_id'                    => ['required', 'integer', 'exists:pos_configs,id'],
            'orders.*.session_id'                   => ['nullable', 'integer'],
            'orders.*.partner_id'                   => ['nullable', 'integer', 'exists:partners_partners,id'],
            'orders.*.table_id'                     => ['nullable', 'integer', 'exists:pos_tables,id'],
            'orders.*.shipped_at'                   => ['nullable', 'date'],
            'orders.*.lines'                        => ['required', 'array', 'min:1'],
            'orders.*.lines.*.uuid'                 => ['required', 'uuid'],
            'orders.*.lines.*.product_id'           => ['required', 'integer', 'exists:products_products,id'],
            'orders.*.lines.*.qty'                  => ['required', 'numeric', 'not_in:0'],
            'orders.*.lines.*.price_unit'           => ['nullable', 'numeric'],
            'orders.*.lines.*.discount'             => ['nullable', 'numeric', 'min:0', 'max:100'],
            'orders.*.lines.*.tax_ids'              => ['nullable', 'array'],
            'orders.*.lines.*.lots'                 => ['nullable', 'array'],
            'orders.*.lines.*.lots.*.lot_name'      => ['required_with:orders.*.lines.*.lots', 'string'],
            'orders.*.payments'                     => ['required', 'array', 'min:1'],
            'orders.*.payments.*.uuid'              => ['required', 'uuid'],
            'orders.*.payments.*.payment_method_id' => ['required', 'integer', 'exists:pos_payment_methods,id'],
            'orders.*.payments.*.amount'            => ['required', 'numeric'],
        ];
    }
}
