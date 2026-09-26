<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ProjectUpdate>
 */
class ProjectUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $projectId = Project::query()->inRandomOrder()->value('id');
        $project = Project::query()->find($projectId);

        return [
            'project_id' => $projectId,
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'update_date' => Carbon::now()->subDays($this->faker->numberBetween(0, 120)),
            'progress_percentage_at_update' => $project
                ? $this->faker->numberBetween(0, $project->progress_percentage)
                : $this->faker->numberBetween(0, 100),
            'description' => $this->faker->paragraph(),
            'status_at_update' => $project?->status,
            'budget_spent_at_update' => $project
                ? $this->faker->randomFloat(2, 10000, max(20000, (float) $project->budget_assigned / 10))
                : null,
        ];
    }
}
