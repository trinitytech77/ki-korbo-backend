<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'service_category_id' => ServiceCategory::factory(),
            'name'                => ucwords($name),
            'slug'                => Str::slug($name),
            'description'         => $this->faker->paragraph(),
            'estimated_days'      => $this->faker->numberBetween(3, 30),
            'is_active'           => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
