<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'service_id'       => Service::factory(),
            'agent_id'         => null,
            'status'           => TaskStatus::NEW->value,
            'government_fee'   => 4025.00,
            'service_fee'      => 500.00,
            'agent_fee'        => 800.00,
            'total_amount'     => 5325.00,
            'location'         => $this->faker->city() . ', ' . $this->faker->streetAddress(),
            'appointment_date' => $this->faker->dateTimeBetween('+3 days', '+30 days')->format('Y-m-d'),
            'notes'            => $this->faker->optional()->sentence(),
            'completed_at'     => null,
        ];
    }

    public function assigned(User $agent): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => $agent->id,
            'status'   => TaskStatus::ASSIGNED->value,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => TaskStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::CANCELLED->value,
        ]);
    }
}
