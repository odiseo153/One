<?php

namespace Database\Factories;

use App\Models\Municipality;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $municipalityId = Municipality::query()->inRandomOrder()->value('id');
        $sectorId = Sector::query()
            ->where('municipality_id', $municipalityId)
            ->inRandomOrder()
            ->value('id');

        $budgetAssigned = $this->faker->randomFloat(2, 500000, 60000000);
        $type = $this->faker->randomElement(Project::TYPES);

        return [
            'municipality_id' => $municipalityId,
            'sector_id' => $sectorId,
            'name' => $this->faker->catchPhrase(),
            'type' => $type,
            'description' => $this->faker->paragraph(),
            'latitude' => $this->faker->latitude(18.3, 18.7),
            'longitude' => $this->faker->longitude(-70.1, -69.7),
            'address_text' => $this->faker->streetAddress(),
            'status' => Project::STATUS_PLANNED,
            'budget_assigned' => $budgetAssigned,
            'budget_executed' => 0,
            'start_date_planned' => Carbon::now()->addMonths($this->faker->numberBetween(0, 3))->format('Y-m-d'),
            'end_date_planned' => Carbon::now()->addMonths($this->faker->numberBetween(4, 14))->format('Y-m-d'),
            'start_date_real' => null,
            'end_date_real' => null,
            'progress_percentage' => 0,
            'contractor_name' => $this->faker->boolean(60) ? $this->faker->company() : null,
            'created_by' => User::query()->inRandomOrder()->value('id') ?: null,
        ];
    }

    public function planned(): static
    {
        return $this->state(fn () => [
            'status' => Project::STATUS_PLANNED,
            'start_date_real' => null,
            'end_date_real' => null,
            'budget_executed' => 0,
            'progress_percentage' => 0,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            $progress = $this->faker->numberBetween(5, 85);

            return [
                'status' => Project::STATUS_IN_PROGRESS,
                'start_date_real' => Carbon::parse($attributes['start_date_planned'])->subDays($this->faker->numberBetween(-20, 30))->format('Y-m-d'),
                'end_date_real' => null,
                'budget_executed' => round(((float) $attributes['budget_assigned']) * ($progress / 100) * $this->faker->randomFloat(2, 0.7, 1.0), 2),
                'progress_percentage' => $progress,
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Project::STATUS_COMPLETED,
                'start_date_real' => Carbon::parse($attributes['start_date_planned'])->format('Y-m-d'),
                'end_date_real' => Carbon::parse($attributes['end_date_planned'])->addDays($this->faker->numberBetween(-20, 60))->format('Y-m-d'),
                'budget_executed' => round(((float) $attributes['budget_assigned']) * $this->faker->randomFloat(2, 0.85, 1.05), 2),
                'progress_percentage' => 100,
            ];
        });
    }

    public function paused(): static
    {
        return $this->state(function (array $attributes) {
            $progress = $this->faker->numberBetween(10, 50);

            return [
                'status' => Project::STATUS_PAUSED,
                'start_date_real' => Carbon::parse($attributes['start_date_planned'])->format('Y-m-d'),
                'end_date_real' => null,
                'budget_executed' => round(((float) $attributes['budget_assigned']) * ($progress / 100) * 0.8, 2),
                'progress_percentage' => $progress,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Project::STATUS_CANCELLED,
            'start_date_real' => $this->faker->boolean(60)
                ? Carbon::parse($attributes['start_date_planned'])->format('Y-m-d')
                : null,
            'end_date_real' => null,
            'budget_executed' => round(((float) $attributes['budget_assigned']) * 0.1, 2),
            'progress_percentage' => $this->faker->numberBetween(0, 30),
        ]);
    }
}
