<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Settings\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use UnitEnum;
use Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource;
use Webkul\PointOfSale\Filament\Admin\Clusters\Settings\Pages\ManageTerminal\Schemas\TerminalSettingsForm;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Settings\TerminalSettings;
use Webkul\Support\Filament\Clusters\Settings;

class ManageTerminal extends SettingsPage
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $slug = 'point-of-sale/manage-terminal';

    protected static string|UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 1;

    protected static string $settings = TerminalSettings::class;

    protected static ?string $cluster = Settings::class;

    public ?int $configId = null;

    protected static function getPagePermission(): ?string
    {
        return 'page_point_of_sale_manage_terminal';
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('point-of-sale::filament/admin/clusters/settings/pages/manage-terminal.title'),
        ];
    }

    public function getTitle(): string
    {
        return __('point-of-sale::filament/admin/clusters/settings/pages/manage-terminal.title');
    }

    public function mount(): void
    {
        $this->configId ??= Config::query()->orderBy('sort')->value('id');

        $this->fillForm();
    }

    public function getConfig(): ?Config
    {
        return $this->configId
            ? Config::query()->with(['paymentMethods', 'priceLists', 'fiscalPositions', 'categories', 'bills', 'floors', 'printers'])->find($this->configId)
            : null;
    }

    public function loadConfig(?int $configId): void
    {
        $this->configId = $configId;

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $config = $this->getConfig();

        if (! $config) {
            $this->form->fill(['config_id' => $this->configId]);

            return;
        }

        $this->form->fill(array_merge($config->attributesToArray(), [
            'config_id'       => $config->id,
            'paymentMethods'  => $config->paymentMethods->pluck('id')->all(),
            'priceLists'      => $config->priceLists->pluck('id')->all(),
            'fiscalPositions' => $config->fiscalPositions->pluck('id')->all(),
            'categories'      => $config->categories->pluck('id')->all(),
            'bills'           => $config->bills->pluck('id')->all(),
            'floors'          => $config->floors->pluck('id')->all(),
            'printers'        => $config->printers->pluck('id')->all(),
        ]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('point-of-sale::filament/admin/clusters/settings/pages/manage-terminal.header-actions.create.label'))
                ->icon('heroicon-o-plus')
                ->url(ConfigResource::getUrl('create')),
        ];
    }

    public function save(): void
    {
        $config = $this->getConfig();

        if (! $config || ! $this->canEdit()) {
            return;
        }

        $data = $this->form->getState();

        unset($data['config_id']);

        DB::transaction(function () use ($config, $data): void {
            foreach (static::relations() as $relation) {
                $config->{$relation}()->sync($data[$relation] ?? []);

                unset($data[$relation]);
            }

            $config->update($data);
        });

        $this->fillForm();

        Notification::make()
            ->success()
            ->title(__('point-of-sale::filament/admin/clusters/settings/pages/manage-terminal.notification.saved.title'))
            ->send();
    }

    /**
     * @return array<int, string>
     */
    public static function relations(): array
    {
        return [
            'paymentMethods',
            'priceLists',
            'fiscalPositions',
            'categories',
            'bills',
            'floors',
            'printers',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return TerminalSettingsForm::configure($schema);
    }
}
