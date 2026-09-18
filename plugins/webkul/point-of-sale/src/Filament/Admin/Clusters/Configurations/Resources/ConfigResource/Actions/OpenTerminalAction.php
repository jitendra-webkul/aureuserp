<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Filament\Pos\Pages\Home;
use Webkul\PointOfSale\Models\Config;

class OpenTerminalAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'point-of-sale.config.open-terminal';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config/actions/open-terminal.label'))
            ->icon('heroicon-o-play')
            ->action(function (Config $record) {
                try {
                    $session = PointOfSale::liveSessionFor($record)
                        ?? PointOfSale::openSession($record);
                } catch (Throwable $exception) {
                    Notification::make()
                        ->danger()
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                return redirect()->to(Home::getUrl(
                    ['session' => $session->getKey()],
                    panel: 'pos',
                ));
            })
            ->visible(fn (Config $record): bool => (bool) $record->is_active);
    }
}
