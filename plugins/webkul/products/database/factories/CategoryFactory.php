<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Product\Models\Category;
use Webkul\Security\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Webkul\Product\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'       => fake()->name(),
            'full_name'  => fake()->name(),
            'creator_id' => User::factory(),
        ];
    }
}
