<?php

use Webkul\PointOfSale\Models\Bill;
use Webkul\PointOfSale\Models\Category;

require_once __DIR__.'/../../../../support/tests/Helpers/CompanyHelper.php';
require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');

    SecurityHelper::disableUserEvents();
});

afterEach(fn () => SecurityHelper::restoreUserEvents());

it('hides categories owned by another company', function () {
    $companyA = CompanyHelper::company();
    $companyB = CompanyHelper::company();

    $ownCategory = Category::factory()->company($companyA)->create();
    $otherCategory = Category::factory()->company($companyB)->create();

    CompanyHelper::actingAsCompanyUser($companyA);

    $visible = Category::query()->pluck('id');

    expect($visible)->toContain($ownCategory->id)
        ->not->toContain($otherCategory->id);
});

it('stamps a new category with the active company', function () {
    $companyB = CompanyHelper::company();

    CompanyHelper::actingAsCompanyUser($companyB);

    $category = Category::factory()->create();

    expect($category->company_id)->toBe($companyB->id);
});

it('shows a denomination without a company to every company', function () {
    Bill::withoutGlobalScopes()->delete();

    $shared = Bill::factory()->shared()->create();

    $companyA = CompanyHelper::company();
    $companyB = CompanyHelper::company();

    $owned = Bill::factory()->company($companyA)->create();

    CompanyHelper::actingAsCompanyUser($companyA);

    expect(Bill::query()->pluck('id'))->toContain($shared->id)
        ->toContain($owned->id);

    CompanyHelper::actingAsCompanyUser($companyB);

    expect(Bill::query()->pluck('id'))->toContain($shared->id)
        ->not->toContain($owned->id);
});
