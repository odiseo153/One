<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\ComplaintUpdate;
use App\Models\Municipality;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        $municipality = Municipality::query()
            ->where('name', 'Santo Domingo de Guzmán')
            ->first()
            ?? Municipality::query()->firstOrFail();

        $sectors = Sector::query()
            ->where('municipality_id', $municipality->id)
            ->get();

        $users = User::query()
            ->where('municipality_id', $municipality->id)
            ->get();

        $descriptions = [
            'Bache profundo que daña los vehículos en plena vía principal.',
            'Luminaria apagada en la intersección, alto riesgo nocturno.',
            'Acumulación de basura en la esquina sin recolección hace días.',
            'Fuga de agua potable que desborda la acera.',
            'Semáforo fuera de servicio en horario de alto tráfico.',
            'Poste inclinado con cables a baja altura.',
            'Contenedor de residuos desbordado junto al parque.',
            'Alcantarilla tapada que provoca inundación en lluvia.',
            'Socavón en la calzada señalizado de forma insuficiente.',
            'Cumplimiento irregular de la recogida de desechos.',
            'Montículo de escombros abandonado en un solar.',
            'Falta de iluminación en el paso peatonal escolar.',
        ];

        $addresses = [
            'Av. Indepencia, esquina Calle Duarte',
            'Calle 27 de Febrero, No. 15',
            'Av. Bolívar, sector Ensanche Naco',
            'Av. Winston Churchill, esquina Max Henríquez Ureña',
            'Calle Padre Castañeda, No. 45',
        ];

        foreach ($descriptions as $index => $description) {
            $sector = $sectors->get($index % max(1, $sectors->count()))
                ?? $sectors->first();
            $status = match ($index % 3) {
                0 => Complaint::STATUS_RECEIVED,
                1 => Complaint::STATUS_IN_PROGRESS,
                default => Complaint::STATUS_RESOLVED,
            };
            $assignedUser = $status === Complaint::STATUS_RECEIVED
                ? null
                : $users->first();

            $complaint = Complaint::factory()->create([
                'municipality_id' => $municipality->id,
                'sector_id' => $sector?->id,
                'category' => Complaint::CATEGORIES[$index % count(Complaint::CATEGORIES)],
                'description' => $description,
                'latitude' => 18.4861 + ($this->nudge() / 100),
                'longitude' => -69.9312 + ($this->nudge() / 100),
                'address_text' => $addresses[$index % count($addresses)],
                'citizen_name' => 'Ciudadano '.($index + 1),
                'citizen_phone' => '+1 809-555-'.str_pad((string) (1000 + $index), 4, '0', STR_PAD_LEFT),
                'status' => $status,
                'assigned_user_id' => $assignedUser?->id,
                'resolved_at' => $status === Complaint::STATUS_RESOLVED
                    ? now()->subDays($index)
                    : null,
            ]);

            $this->track($complaint, $users->first()?->id, Complaint::STATUS_RECEIVED);

            if ($status === Complaint::STATUS_IN_PROGRESS || $status === Complaint::STATUS_RESOLVED) {
                $this->track($complaint, $users->first()?->id, Complaint::STATUS_IN_PROGRESS);
            }

            if ($status === Complaint::STATUS_RESOLVED) {
                $this->track($complaint, $users->first()?->id, Complaint::STATUS_RESOLVED);
            }
        }
    }

    private function track(Complaint $complaint, ?int $userId, string $newStatus): void
    {
        ComplaintUpdate::create([
            'complaint_id' => $complaint->id,
            'user_id' => $userId,
            'previous_status' => $complaint->status === $newStatus
                ? null
                : ($newStatus === Complaint::STATUS_IN_PROGRESS ? Complaint::STATUS_RECEIVED : Complaint::STATUS_IN_PROGRESS),
            'new_status' => $newStatus,
            'note' => match ($newStatus) {
                Complaint::STATUS_IN_PROGRESS => 'Se asignó un encargado y se inició la gestión.',
                Complaint::STATUS_RESOLVED => 'Atención realizada en el lugar, queja resuelta.',
                default => 'Queja recibida y registrada en el sistema.',
            },
        ]);
    }

    private function nudge(): float
    {
        return (float) (mt_rand(-30, 30) / 10);
    }
}
