<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources;

use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\RelationManagers\RelationGroup;
use Webkul\Field\Filament\Traits\HasCustomFields;
use Webkul\Partner\Filament\Resources\PartnerResource;
use Webkul\Partner\Filament\Resources\PartnerResource\RelationManagers;
use Webkul\Purchase\Filament\Clusters\Orders;
use Webkul\Purchase\Filament\Clusters\Orders\Resources\VendorResource\Pages;

class VendorResource extends PartnerResource
{
    use HasCustomFields;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Orders::class;

    protected static ?int $navigationSort = 4;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/clusters/orders/resources/vendor.navigation.title');
    }

    public static function form(Form $form): Form
    {
        $form = PartnerResource::form($form);

        $components = $form->getComponents();

        $form->components($components);

        return $form;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $infolist = PartnerResource::infolist($infolist);

        $components = $infolist->getComponents();

        $infolist->components($components);

        return $infolist;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewVendor::class,
            Pages\EditVendor::class,
            Pages\ManageContacts::class,
            Pages\ManageAddresses::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('Contacts', [
                RelationManagers\ContactsRelationManager::class,
            ])
                ->icon('heroicon-o-users'),

            RelationGroup::make('Addresses', [
                RelationManagers\AddressesRelationManager::class,
            ])
                ->icon('heroicon-o-map-pin'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'     => Pages\ListVendors::route('/'),
            'create'    => Pages\CreateVendor::route('/create'),
            'view'      => Pages\ViewVendor::route('/{record}'),
            'edit'      => Pages\EditVendor::route('/{record}/edit'),
            'contacts'  => Pages\ManageContacts::route('/{record}/contacts'),
            'addresses' => Pages\ManageAddresses::route('/{record}/addresses'),
        ];
    }
}
