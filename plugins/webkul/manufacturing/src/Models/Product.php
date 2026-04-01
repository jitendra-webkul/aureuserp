<?php

namespace Webkul\Manufacturing\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Inventory\Models\Product as BaseProduct;

class Product extends BaseProduct
{
    public function billsOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'product_id');
    }
}
