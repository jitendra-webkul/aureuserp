<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\PaymentTermResource\Pages;

use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\ManageRelatedRecords;
use Webkul\Account\Filament\Clusters\Configuration\Resources\PaymentTermResource;
use Webkul\Account\Traits\PaymentDueTerm;

class ManagePaymentDueTerm extends ManageRelatedRecords
{
    use PaymentDueTerm;

    protected static string $resource = PaymentTermResource::class;

    protected static string $relationship = 'dueTerm';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public static function getNavigationLabel(): string
    {
        return __('accounts::filament/clusters/configurations/resources/payment-term/pages/manage-payment-term.navigation.title');
    }
}
