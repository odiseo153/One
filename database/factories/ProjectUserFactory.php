<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ProjectUser>
 */
class ProjectUserFactory extends Factory
{
    protected $model = ProjectUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'role_in_project' => $this->faker->randomElement(ProjectUser::ROLES),
            'assigned_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 90)),
        ];
    }
}
