<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintUpdate>
 */
class ComplaintUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'user_id' => User::query()->value('id'),
            'previous_status' => Complaint::STATUS_RECEIVED,
            'new_status' => Complaint::STATUS_IN_PROGRESS,
            'note' => $this->faker->sentence(),
        ];
    }
}
