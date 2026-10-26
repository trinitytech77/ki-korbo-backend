<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'description' => $this->faker->sentence(),
            'icon'        => 'default-icon',
            'is_active'   => true,
        ];
    }
}
