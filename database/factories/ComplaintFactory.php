<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\Municipality;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(Complaint::STATUSES);
        $municipalityId = Municipality::query()->inRandomOrder()->value('id');
        $sectorId = Sector::query()
            ->where('municipality_id', $municipalityId)
            ->inRandomOrder()
            ->value('id');

        return [
            'municipality_id' => $municipalityId,
            'sector_id' => $sectorId,
            'category' => $this->faker->randomElement(Complaint::CATEGORIES),
            'description' => $this->faker->paragraph(),
            'latitude' => $this->faker->latitude(-90, 90),
            'longitude' => $this->faker->longitude(-180, 180),
            'address_text' => $this->faker->streetAddress(),
            'photo_url' => null,
            'citizen_name' => $this->faker->name(),
            'citizen_phone' => $this->faker->phoneNumber(),
            'tracking_code' => Complaint::newTrackingCode(),
            'status' => $status,
            'assigned_user_id' => $this->faker->boolean(70)
                ? User::query()->value('id')
                : null,
            'resolved_at' => $status === Complaint::STATUS_RESOLVED
                ? Carbon::now()->subDays($this->faker->numberBetween(0, 15))
                : null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn () => [
            'status' => Complaint::STATUS_RECEIVED,
            'resolved_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => Complaint::STATUS_IN_PROGRESS,
            'resolved_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => Complaint::STATUS_RESOLVED,
            'resolved_at' => Carbon::now()->subDays($this->faker->numberBetween(0, 15)),
        ]);
    }
}
