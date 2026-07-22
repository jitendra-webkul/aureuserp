<?php

namespace Webkul\Inventory\Exceptions;

use Exception;

class CrossCompanyTransferException extends Exception
{
    public function __construct(
        public readonly string $sourceLocation,
        public readonly string $destinationLocation,
    ) {
        parent::__construct(__('inventories::system.move.cross-company.body', [
            'source'      => $sourceLocation,
            'destination' => $destinationLocation,
        ]));
    }

    public function title(): string
    {
        return __('inventories::system.move.cross-company.title');
    }
}
