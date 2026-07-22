<?php

namespace Webkul\Account\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Webkul\Account\Services\JournalProvisioner;
use Webkul\Support\Models\Company;

class CompanyObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Company $company): void
    {
        app(JournalProvisioner::class)->provision($company);
    }
}
