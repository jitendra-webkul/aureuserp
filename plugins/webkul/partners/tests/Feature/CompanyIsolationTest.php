<?php

use Webkul\Partner\Models\Partner;

require_once __DIR__.'/../../../support/tests/Helpers/CompanyHelper.php';
require_once __DIR__.'/../../../support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    SecurityHelper::disableUserEvents();
});

afterEach(fn () => SecurityHelper::restoreUserEvents());

it('hides partners owned by another company', function () {
    $companyA = CompanyHelper::company();
    $companyB = CompanyHelper::company();

    $ownPartner = Partner::factory()->company($companyA)->create();
    $otherPartner = Partner::factory()->company($companyB)->create();

    CompanyHelper::actingAsCompanyUser($companyA);

    $visible = Partner::query()->pluck('id');

    expect($visible)->toContain($ownPartner->id)
        ->not->toContain($otherPartner->id);
});

it('stamps a new partner with the active company', function () {
    $companyB = CompanyHelper::company();

    CompanyHelper::actingAsCompanyUser($companyB);

    $partner = Partner::factory()->create();

    expect($partner->company_id)->toBe($companyB->id);
});
