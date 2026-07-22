<?php

namespace Webkul\Inventory\Models\Concerns;

use Webkul\Inventory\Models\Location;
use Webkul\Support\Models\Scopes\CompanyScope;

trait AssertsLocationCompany
{
    public function assertLocationsSameCompany(): void
    {
        $sourceId = $this->source_location_id;

        $destinationId = $this->destination_location_id;

        if (! $sourceId || ! $destinationId) {
            return;
        }

        $locations = Location::withoutGlobalScope(CompanyScope::class)
            ->whereIn('id', [$sourceId, $destinationId])
            ->get(['id', 'name', 'full_name', 'company_id'])
            ->keyBy('id');

        $source = $locations->get($sourceId);

        $destination = $locations->get($destinationId);

        if (! $source || ! $destination) {
            return;
        }

        if ($source->company_id && $destination->company_id && $source->company_id !== $destination->company_id) {
            throw new \Exception(__('inventories::filament/clusters/operations/actions/validate.notification.warning.cross-company.body', [
                'source'      => $source->full_name ?? $source->name,
                'destination' => $destination->full_name ?? $destination->name,
            ]));
        }
    }
}
