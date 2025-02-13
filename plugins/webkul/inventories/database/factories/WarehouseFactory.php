<?php

namespace Webkul\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Security\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Webkul\Inventory\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'sort'       => fake()->randomNumber(),
            'creator_id' => User::factory(),
        ];
    }
}
