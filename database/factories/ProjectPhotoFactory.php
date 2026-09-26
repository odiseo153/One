<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectPhoto;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectPhoto>
 */
class ProjectPhotoFactory extends Factory
{
    protected $model = ProjectPhoto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $projectId = Project::query()->inRandomOrder()->value('id');
        $updateId = ProjectUpdate::query()
            ->where('project_id', $projectId)
            ->inRandomOrder()
            ->value('id');

        return [
            'project_id' => $projectId,
            'project_update_id' => $updateId,
            'photo_url' => 'https://picsum.photos/seed/project-'.$this->faker->unique()->numberBetween(1000, 99999).'/800/600',
            'caption' => $this->faker->boolean(70) ? $this->faker->sentence(8) : null,
            'taken_at' => $this->faker->dateTimeBetween('-90 days'),
            'uploaded_by' => User::query()->inRandomOrder()->value('id') ?: null,
        ];
    }
}
