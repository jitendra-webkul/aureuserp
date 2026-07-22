<?php

namespace Webkul\Account\Services;

use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\JournalType;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Journal;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Scopes\CompanyScope;

class JournalProvisioner
{
    protected const BANK_ACCOUNT_CODE_PREFIX = '1014';

    protected const CASH_ACCOUNT_CODE_PREFIX = '1015';

    protected const CODE_LENGTH = 6;

    public function provision(Company $company): array
    {
        $created = [];

        foreach ($this->definitions() as $definition) {
            $journal = Journal::query()
                ->withoutGlobalScope(CompanyScope::class)
                ->where('company_id', $company->id)
                ->where('type', $definition['type'])
                ->where('code', $definition['code'])
                ->first();

            if ($journal) {
                continue;
            }

            $created[] = $this->create($company, $definition);
        }

        return $created;
    }

    public function isProvisioned(Company $company): bool
    {
        return Journal::query()
            ->withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->whereIn('type', [JournalType::SALE, JournalType::PURCHASE])
            ->distinct()
            ->count('type') >= 2;
    }

    protected function create(Company $company, array $definition): Journal
    {
        return Journal::create([
            'company_id'         => $company->id,
            'currency_id'        => $company->currency_id,
            'creator_id'         => $company->creator_id,
            'name'               => $definition['name'],
            'code'               => $definition['code'],
            'type'               => $definition['type'],
            'sort'               => $definition['sort'],
            'color'              => $definition['color'],
            'default_account_id' => $this->resolveDefaultAccount($company, $definition),
            'show_on_dashboard'  => true,
            'auto_check_on_post' => $definition['auto_check_on_post'] ?? false,
            'refund_order'       => $definition['refund_order'] ?? false,
            'payment_order'      => false,
            'restrict_mode_hash_table' => false,
            'invoice_reference_type'   => $definition['invoice_reference_type'] ?? 'invoice',
            'invoice_reference_model'  => $definition['invoice_reference_model'] ?? 'aureus',
        ]);
    }

    protected function resolveDefaultAccount(Company $company, array $definition): ?int
    {
        if (in_array($definition['type'], [JournalType::BANK, JournalType::CASH], true)) {
            return $this->createLiquidityAccount($company, $definition)->id;
        }

        if (! isset($definition['account_code'])) {
            return null;
        }

        return Account::query()
            ->where('code', $definition['account_code'])
            ->value('id');
    }

    protected function createLiquidityAccount(Company $company, array $definition): Account
    {
        $prefix = $definition['type'] === JournalType::CASH
            ? static::CASH_ACCOUNT_CODE_PREFIX
            : static::BANK_ACCOUNT_CODE_PREFIX;

        return Account::create([
            'name'         => $definition['name'].' - '.$company->name,
            'code'         => $this->nextAccountCode($prefix),
            'account_type' => AccountType::ASSET_CASH,
            'currency_id'  => $company->currency_id,
            'creator_id'   => $company->creator_id,
            'reconcile'    => false,
            'deprecated'   => false,
            'non_trade'    => false,
        ]);
    }

    protected function nextAccountCode(string $prefix): string
    {
        $code = str_pad($prefix, static::CODE_LENGTH, '0');

        $taken = Account::query()
            ->where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->all();

        while (in_array($code, $taken, true)) {
            $code = str_pad((string) ((int) $code + 1), strlen($code), '0', STR_PAD_LEFT);
        }

        return $code;
    }

    protected function definitions(): array
    {
        return [
            [
                'name'                    => 'Customer Invoices',
                'code'                    => 'INV',
                'type'                    => JournalType::SALE,
                'sort'                    => 5,
                'color'                   => 11,
                'account_code'            => '400000',
                'auto_check_on_post'      => true,
                'refund_order'            => true,
                'invoice_reference_type'  => 'invoice',
                'invoice_reference_model' => 'aureus',
            ],
            [
                'name'         => 'Vendor Bills',
                'code'         => 'BILL',
                'type'         => JournalType::PURCHASE,
                'sort'         => 6,
                'color'        => 11,
                'account_code' => '600000',
                'refund_order' => true,
            ],
            [
                'name'  => 'Miscellaneous Operations',
                'code'  => 'MISC',
                'type'  => JournalType::GENERAL,
                'sort'  => 7,
                'color' => 11,
            ],
            [
                'name'  => 'Exchange Difference',
                'code'  => 'EXCH',
                'type'  => JournalType::GENERAL,
                'sort'  => 8,
                'color' => 11,
            ],
            [
                'name'  => 'Bank',
                'code'  => 'BANK',
                'type'  => JournalType::BANK,
                'sort'  => 9,
                'color' => 11,
            ],
            [
                'name'  => 'Cash',
                'code'  => 'CASH',
                'type'  => JournalType::CASH,
                'sort'  => 10,
                'color' => 11,
            ],
        ];
    }
}
