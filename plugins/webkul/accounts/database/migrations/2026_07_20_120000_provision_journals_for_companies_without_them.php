<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\JournalType;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Journal;
use Webkul\Account\Services\JournalProvisioner;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Scopes\CompanyScope;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts_journals') || ! Schema::hasTable('companies')) {
            return;
        }

        $this->repointLiquidityJournals();

        $provisioner = app(JournalProvisioner::class);

        Company::query()
            ->orderBy('id')
            ->each(function (Company $company) use ($provisioner) {
                if ($provisioner->isProvisioned($company)) {
                    return;
                }

                $provisioner->provision($company);
            });
    }

    public function down(): void {}

    protected function repointLiquidityJournals(): void
    {
        $journals = Journal::query()
            ->withoutGlobalScope(CompanyScope::class)
            ->whereIn('type', [JournalType::BANK, JournalType::CASH])
            ->get();

        foreach ($journals as $journal) {
            $current = $journal->default_account_id
                ? Account::query()->whereKey($journal->default_account_id)->first()
                : null;

            if ($current && $current->account_type === AccountType::ASSET_CASH && $this->matchesType($current, $journal)) {
                continue;
            }

            $replacement = $this->liquidityAccountFor($journal);

            if (! $replacement) {
                continue;
            }

            $journal->forceFill(['default_account_id' => $replacement->id])->saveQuietly();
        }
    }

    protected function matchesType(Account $account, Journal $journal): bool
    {
        $prefix = $journal->type === JournalType::CASH ? '1015' : '1014';

        return str_starts_with((string) $account->code, $prefix);
    }

    protected function liquidityAccountFor(Journal $journal): ?Account
    {
        $prefix = $journal->type === JournalType::CASH ? '1015' : '1014';

        $claimed = Journal::query()
            ->withoutGlobalScope(CompanyScope::class)
            ->whereNotNull('default_account_id')
            ->where('company_id', '!=', $journal->company_id)
            ->pluck('default_account_id')
            ->all();

        return Account::query()
            ->where('account_type', AccountType::ASSET_CASH)
            ->where('code', 'like', $prefix.'%')
            ->whereNotIn('id', $claimed)
            ->orderBy('code')
            ->first();
    }
};
