<?php

namespace Webkul\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Enums;
use Webkul\Security\Models\User;

class RuleSeeder extends Seeder
{
    /**
     * Seed the application's database with currencies.
     */
    public function run(): void
    {
        $user = User::first();

        DB::table('inventories_rules')->delete();

        DB::table('inventories_rules')->insert([
            [
                'id'                       => 1,
                'sort'                     => 1,
                'name'                     => 'WH: Vendors → Stock',
                'route_sort'               => 9,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PULL,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_STOCK,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => false,
                'source_location_id'       => 4,
                'destination_location_id'  => 12,
                'route_id'                 => 2,
                'operation_type_id'        => 1,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => null, // Check this line
            ], [
                'id'                       => 2,
                'sort'                     => 2,
                'name'                     => 'WH: Stock → Customers',
                'route_sort'               => 10,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PULL,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_STOCK,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => true,
                'source_location_id'       => 12,
                'destination_location_id'  => 5,
                'route_id'                 => 3,
                'operation_type_id'        => 2,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => null,
            ], [
                'id'                       => 3,
                'sort'                     => 3,
                'name'                     => 'WH: Vendors → Customers',
                'route_sort'               => 20,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PULL,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_STOCK,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => false,
                'source_location_id'       => 4,
                'destination_location_id'  => 5,
                'route_id'                 => 4,
                'operation_type_id'        => 1,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => null,
            ], [
                'id'                       => 4,
                'sort'                     => 4,
                'name'                     => 'WH: Input → Output',
                'route_sort'               => 20,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PUSH,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_ORDER,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => false,
                'source_location_id'       => 13,
                'destination_location_id'  => 15,
                'route_id'                 => 4,
                'operation_type_id'        => 8,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => null,
            ], [
                'id'                       => 5,
                'sort'                     => 5,
                'name'                     => 'WH: Stock → Customers (MTO)',
                'route_sort'               => 5,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PULL,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_ORDER,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => true,
                'source_location_id'       => 12,
                'destination_location_id'  => 5,
                'route_id'                 => 1,
                'operation_type_id'        => 2,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => null,
            ], [
                'id'                       => 6,
                'sort'                     => 6,
                'name'                     => 'WH: Input → Quality Control',
                'route_sort'               => 6,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PUSH,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_ORDER,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => true,
                'propagate_carrier'        => false,
                'source_location_id'       => 13,
                'destination_location_id'  => 14,
                'route_id'                 => 2,
                'operation_type_id'        => 5,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 7,
                'sort'                     => 7,
                'name'                     => 'WH: Quality Control → Stock',
                'route_sort'               => 7,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PUSH,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_ORDER,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => false,
                'source_location_id'       => 14,
                'destination_location_id'  => 12,
                'route_id'                 => 2,
                'operation_type_id'        => 6,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 8,
                'sort'                     => 8,
                'name'                     => 'WH: Stock → Customers',
                'route_sort'               => 8,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PULL,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_STOCK,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => true,
                'source_location_id'       => 12,
                'destination_location_id'  => 5,
                'route_id'                 => 3,
                'operation_type_id'        => 3,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 9,
                'sort'                     => 9,
                'name'                     => 'WH: Packing Zone → Output',
                'route_sort'               => 9,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PUSH,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_ORDER,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => true,
                'source_location_id'       => 16,
                'destination_location_id'  => 15,
                'route_id'                 => 3,
                'operation_type_id'        => 4,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 10,
                'sort'                     => 10,
                'name'                     => 'WH: Output → Customers',
                'route_sort'               => 10,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PUSH,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_ORDER,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => true,
                'source_location_id'       => 15,
                'destination_location_id'  => 5,
                'route_id'                 => 3,
                'operation_type_id'        => 2,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 11,
                'sort'                     => 11,
                'name'                     => 'WH: Input → Stock',
                'route_sort'               => 11,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::PUSH,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_ORDER,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => false,
                'source_location_id'       => 13,
                'destination_location_id'  => 12,
                'route_id'                 => 2,
                'operation_type_id'        => 6,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 12,
                'sort'                     => 12,
                'name'                     => 'WH: False → Customers',
                'route_sort'               => 12,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::BUY,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_STOCK,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => false,
                'source_location_id'       => null,
                'destination_location_id'  => 5,
                'route_id'                 => 4,
                'operation_type_id'        => 1,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 13,
                'sort'                     => 13,
                'name'                     => 'WH: Stock (Buy)',
                'route_sort'               => 13,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::NONE,
                'action'                   => Enums\RuleAction::BUY,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_STOCK,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => true,
                'propagate_carrier'        => false,
                'source_location_id'       => null,
                'destination_location_id'  => 12,
                'route_id'                 => 5,
                'operation_type_id'        => 1,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ], [
                'id'                       => 14,
                'sort'                     => 14,
                'name'                     => 'Vendors → Customers',
                'route_sort'               => 14,
                'delay'                    => 0,
                'group_propagation_option' => Enums\GroupPropagation::PROPAGATE,
                'action'                   => Enums\RuleAction::BUY,
                'procure_method'           => Enums\ProcureMethod::MAKE_TO_STOCK,
                'auto'                     => Enums\RuleAuto::MANUAL,
                'location_dest_from_rule'  => false,
                'propagate_cancel'         => false,
                'propagate_carrier'        => false,
                'source_location_id'       => 4,
                'destination_location_id'  => 5,
                'route_id'                 => 6,
                'operation_type_id'        => 9,
                'company_id'               => $user->default_company_id,
                'creator_id'               => $user->id,
                'created_at'               => now(),
                'updated_at'               => now(),
                'deleted_at'               => now(),
            ],
        ]);
    }
}
