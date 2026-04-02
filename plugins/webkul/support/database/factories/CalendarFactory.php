<?php

namespace Webkul\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Calendar;
use Webkul\Support\Models\Company;

class CalendarFactory extends Factory
{
    protected $model = Calendar::class;

    public function definition(): array
    {
        return [
            'name'                      => fake()->name,
            'timezone'                  => fake()->timezone,
            'hours_per_day'             => fake()->randomFloat(2, 0, 24),
            'is_active'                 => true,
            'two_weeks_calendar'        => false,
            'flexible_hours'            => false,
            'full_time_required_hours'  => 40,
            'creator_id'                => User::query()->value('id') ?? User::factory(),
            'company_id'                => Company::factory(),
        ];
    }
}
