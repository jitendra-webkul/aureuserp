<?php

use Webkul\Support\Models\UtmCampaign;

require_once __DIR__.'/../Helpers/CompanyHelper.php';
require_once __DIR__.'/../Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    SecurityHelper::disableUserEvents();
});

afterEach(fn () => SecurityHelper::restoreUserEvents());

it('hides utm campaigns owned by another company', function () {
    $companyA = CompanyHelper::company();
    $companyB = CompanyHelper::company();

    $ownCampaign = UtmCampaign::factory()->company($companyA)->create();
    $otherCampaign = UtmCampaign::factory()->company($companyB)->create();

    CompanyHelper::actingAsCompanyUser($companyA);

    $visible = UtmCampaign::query()->pluck('id');

    expect($visible)->toContain($ownCampaign->id)
        ->not->toContain($otherCampaign->id);
});

it('stamps a new utm campaign with the active company', function () {
    $companyB = CompanyHelper::company();

    CompanyHelper::actingAsCompanyUser($companyB);

    $campaign = UtmCampaign::factory()->create();

    expect($campaign->company_id)->toBe($companyB->id);
});
