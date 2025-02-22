<?php

namespace Webkul\Invoice\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Clusters\Configuration\Resources\FiscalPositionResource as BaseFiscalPositionResource;
use Webkul\Invoice\Filament\Clusters\Configuration;
use Webkul\Invoice\Filament\Clusters\Configuration\Resources\FiscalPositionResource\Pages;

class FiscalPositionResource extends BaseFiscalPositionResource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Configuration::class;

    public static function getPages(): array
    {
        return [
            'index'               => Pages\ListFiscalPositions::route('/'),
            'create'              => Pages\CreateFiscalPosition::route('/create'),
            'view'                => Pages\ViewFiscalPosition::route('/{record}'),
            'edit'                => Pages\EditFiscalPosition::route('/{record}/edit'),
            'fiscal-position-tax' => Pages\ManageFiscalPositionTax::route('/{record}/fiscal-position-tax'),
        ];
    }
}
