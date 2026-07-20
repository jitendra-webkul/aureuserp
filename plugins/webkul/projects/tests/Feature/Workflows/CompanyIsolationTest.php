<?php

use Webkul\Project\Models\ProjectStage;

require_once __DIR__.'/../../../../support/tests/Helpers/CompanyHelper.php';
require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('projects');
    SecurityHelper::disableUserEvents();
});

afterEach(fn () => SecurityHelper::restoreUserEvents());

it('hides project stages owned by another company', function () {
    $companyA = CompanyHelper::company();
    $companyB = CompanyHelper::company();

    $ownStage = ProjectStage::factory()->company($companyA)->create();
    $otherStage = ProjectStage::factory()->company($companyB)->create();

    CompanyHelper::actingAsCompanyUser($companyA);

    $visible = ProjectStage::query()->pluck('id');

    expect($visible)->toContain($ownStage->id)
        ->not->toContain($otherStage->id);
});

it('stamps a new project stage with the active company', function () {
    $companyB = CompanyHelper::company();

    CompanyHelper::actingAsCompanyUser($companyB);

    $stage = ProjectStage::factory()->create();

    expect($stage->company_id)->toBe($companyB->id);
});
