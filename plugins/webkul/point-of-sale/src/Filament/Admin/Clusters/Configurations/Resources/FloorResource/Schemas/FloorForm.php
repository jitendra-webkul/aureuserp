<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\FloorResource\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FloorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.form.sections.general.title'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.form.sections.general.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Select::make('company_id')
                            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.form.sections.general.fields.company'))
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),

                        ColorPicker::make('background_color')
                            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.form.sections.general.fields.background-color'))
                            ->hexColor(),

                        FileUpload::make('background_image')
                            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.form.sections.general.fields.background-image'))
                            ->image()
                            ->directory('point-of-sale/floors'),
                    ])
                    ->columns(2),
            ]);
    }
}
