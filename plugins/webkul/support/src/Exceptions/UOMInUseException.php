<?php

namespace Webkul\Support\Exceptions;

use Exception;
use Webkul\Support\Models\UOM;

class UOMInUseException extends Exception
{
    public static function forRatioChange(UOM $uom): self
    {
        return new self(__('support::exceptions/uom-in-use.ratio-change', [
            'uom' => $uom->name,
        ]));
    }
}
