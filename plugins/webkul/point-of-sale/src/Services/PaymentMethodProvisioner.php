<?php

namespace Webkul\PointOfSale\Services;

use Illuminate\Database\Eloquent\Collection;
use Webkul\Account\Enums\JournalType;
use Webkul\Account\Models\Journal;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\PaymentMethod;

class PaymentMethodProvisioner
{
    public function ensureFor(Config $config): void
    {
        if ($config->paymentMethods()->exists()) {
            return;
        }

        $methods = $this->defaultsFor($config);

        if ($methods->isEmpty()) {
            $methods = collect([$this->provisionCashMethod($config)])->filter();
        }

        if ($methods->isEmpty()) {
            return;
        }

        $config->paymentMethods()->syncWithoutDetaching($methods->pluck('id')->all());
    }

    public function defaultsFor(Config $config): Collection
    {
        $nonCash = PaymentMethod::query()
            ->where(owned_by_company($config->company_id))
            ->where('is_cash_count', false)
            ->where('is_split_transaction', false)
            ->get();

        $cash = PaymentMethod::query()
            ->where(owned_by_company($config->company_id))
            ->where('is_cash_count', true)
            ->whereDoesntHave('configs')
            ->orderBy('id')
            ->first();

        return $cash
            ? $nonCash->push($cash)
            : $nonCash;
    }

    public function provisionCashMethod(Config $config): ?PaymentMethod
    {
        $journal = $this->cashJournalFor($config);

        if (! $journal) {
            return null;
        }

        return PaymentMethod::create([
            'name'       => __('point-of-sale::system.payment-method-provisioner.cash'),
            'journal_id' => $journal->id,
            'company_id' => $config->company_id,
        ]);
    }

    protected function cashJournalFor(Config $config): ?Journal
    {
        return Journal::query()
            ->where('type', JournalType::CASH)
            ->where(owned_by_company($config->company_id))
            ->orderBy('id')
            ->first();
    }
}
