<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Webkul\PointOfSale\Filament\Admin\Clusters\Settings\Pages\ManageTerminal;

class ConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.form.sections.general.fields.name'))
                            ->placeholder(__('point-of-sale::filament/admin/clusters/configurations/resources/config.form.sections.general.fields.name-placeholder'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->columnSpanFull(),

                        TextInput::make('code')
                            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.form.sections.general.fields.code'))
                            ->helperText(__('point-of-sale::filament/admin/clusters/configurations/resources/config.form.sections.general.fields.code-helper-text'))
                            ->required()
                            ->maxLength(16),

                        Toggle::make('is_restaurant')
                            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.form.sections.general.fields.is-restaurant'))
                            ->helperText(__('point-of-sale::filament/admin/clusters/configurations/resources/config.form.sections.general.fields.is-restaurant-helper-text'))
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->columnSpanFull(),

                        Text::make(fn (): HtmlString => new HtmlString(__(
                            'point-of-sale::filament/admin/clusters/configurations/resources/config.form.sections.general.more-settings',
                            ['url' => ManageTerminal::getUrl()],
                        )))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }
}
