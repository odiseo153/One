<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectPhoto;
use App\Models\ProjectUpdate;
use App\Models\ProjectUser;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectSeeder extends Seeder
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
            ->get(['id', 'name']);

        if ($users->count() < 5) {
            $users = $this->ensureTeam($municipality, $users);
        }

        $definitions = $this->definitions();

        foreach ($definitions as $index => $definition) {
            $sector = $sectors->get($index % max(1, $sectors->count())) ?? $sectors->first();

            $project = $this->createProject($municipality, $sector, $users->first()?->id, $definition, $index);

            $this->assignTeam($project, $users);

            if (! empty($definition['milestones'])) {
                $this->createMilestones($project, $definition['milestones']);
            }

            $this->createTimeline($project, $users, $definition);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function createProject(Municipality $municipality, ?Sector $sector, ?int $creatorId, array $definition, int $index): Project
    {
        $status = $definition['status'];
        $now = Carbon::now();

        $startPlanned = $now->copy()->addDays($definition['start_offset_days']);
        $endPlanned = $startPlanned->copy()->addMonths($definition['duration_months']);

        $project = Project::create([
            'municipality_id' => $municipality->id,
            'sector_id' => $sector?->id,
            'name' => $definition['name'],
            'type' => $definition['type'],
            'description' => $definition['description'],
            'latitude' => null,
            'longitude' => null,
            'address_text' => $definition['address'],
            'status' => $status,
            'budget_assigned' => $definition['budget'],
            'budget_executed' => 0,
            'start_date_planned' => $startPlanned->format('Y-m-d'),
            'end_date_planned' => $endPlanned->format('Y-m-d'),
            'start_date_real' => null,
            'end_date_real' => null,
            'progress_percentage' => 0,
            'contractor_name' => $definition['contractor'],
            'created_by' => $creatorId,
        ]);

        $progress = match ($status) {
            Project::STATUS_COMPLETED => 100,
            Project::STATUS_IN_PROGRESS => random_int(35, 80),
            Project::STATUS_PAUSED => random_int(10, 40),
            default => 0,
        };

        $payload = [
            'latitude' => $this->coord(18.452, $project->id),
            'longitude' => $this->coord(-69.95, $project->id),
            'progress_percentage' => $progress,
        ];

        if ($status === Project::STATUS_COMPLETED) {
            $payload['start_date_real'] = $startPlanned->copy()->addDays(random_int(-10, 10))->format('Y-m-d');
            $payload['end_date_real'] = $endPlanned->copy()->addDays($definition['delay_days'] ?? 0)->format('Y-m-d');
        } elseif ($status === Project::STATUS_IN_PROGRESS || $status === Project::STATUS_PAUSED) {
            $payload['start_date_real'] = $startPlanned->copy()->addDays(random_int(-15, 15))->format('Y-m-d');
        }

        $project->update($payload);

        return $project;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function assignTeam(Project $project, $users): void
    {
        $roles = [ProjectUser::ROLE_MANAGER, ProjectUser::ROLE_SUPERVISOR, ProjectUser::ROLE_INSPECTOR, ProjectUser::ROLE_COLLABORATOR];

        foreach ($roles as $position => $role) {
            $user = $users->get($position) ?? $users->first();

            if (! $user) {
                return;
            }

            ProjectUser::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $user->id],
                [
                    'role_in_project' => $role,
                    'assigned_at' => now()->subDays(random_int(10, 90)),
                ],
            );
        }
    }

    /**
     * @param  array<int, string>  $milestones
     */
    private function createMilestones(Project $project, array $milestones): void
    {
        $base = Carbon::parse($project->start_date_planned);

        foreach ($milestones as $order => $name) {
            $planned = $base->copy()->addDays(($order + 1) * random_int(20, 45));
            $completed = $project->status === Project::STATUS_COMPLETED && $planned->lt(Carbon::parse($project->end_date_real))
                ? $planned->copy()->addDays(random_int(-5, 25))
                : null;

            ProjectMilestone::create([
                'project_id' => $project->id,
                'name' => $name,
                'order' => $order + 1,
                'planned_date' => $planned->format('Y-m-d'),
                'completed_date' => $completed?->format('Y-m-d'),
                'status' => $completed
                    ? ProjectMilestone::STATUS_COMPLETED
                    : ($planned->isPast() ? ProjectMilestone::STATUS_DELAYED : ProjectMilestone::STATUS_PENDING),
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>  $definition
     */
    private function createTimeline(Project $project, $users, array $definition): void
    {
        $status = $project->status;

        if ($status === Project::STATUS_CANCELLED) {
            $this->appendUpdate($project, $users, 0, 'La obra fue cancelada por reasignación de fondos.', Project::STATUS_CANCELLED, 0);

            return;
        }

        if ($status === Project::STATUS_PLANNED) {
            return;
        }

        $progress = (int) $project->progress_percentage;
        $updatesCount = $progress === 100 ? random_int(4, 7) : random_int(2, 5);
        $stagnant = $definition['stagnant'] ?? false;
        $executed = round(((float) $project->budget_assigned) * ($progress / 100) * random_int(80, 102) / 100, 2);

        $cursor = Carbon::parse($project->start_date_real ?? $project->start_date_planned);
        $stop = $status === Project::STATUS_COMPLETED
            ? Carbon::parse($project->end_date_real)
            : ($stagnant ? Carbon::now()->subDays(random_int(35, 70)) : Carbon::now()->subDays(random_int(1, 10)));

        $stepDays = max(1, (int) floor($cursor->diffInDays($stop) / $updatesCount));

        for ($i = 1; $i <= $updatesCount; $i++) {
            $cursor = $cursor->copy()->addDays($stepDays);

            if ($cursor->gt($stop)) {
                $cursor = $stop->copy();
            }

            $stepProgress = (int) round($progress * ($i / $updatesCount));
            $alreadySpent = (float) $project->updates()->sum('budget_spent_at_update');
            $targetSpent = $executed * ($i / $updatesCount);
            $stepSpent = $i === $updatesCount
                ? round($executed - $alreadySpent, 2)
                : round($targetSpent - $alreadySpent, 2);

            $phaseStatus = match (true) {
                $status === Project::STATUS_COMPLETED => $i === $updatesCount ? Project::STATUS_COMPLETED : Project::STATUS_IN_PROGRESS,
                $status === Project::STATUS_PAUSED => $i === $updatesCount ? Project::STATUS_PAUSED : Project::STATUS_IN_PROGRESS,
                default => Project::STATUS_IN_PROGRESS,
            };

            $update = $this->appendUpdate(
                $project,
                $users,
                $stepProgress,
                $this->progressNote($phaseStatus, $i, $updatesCount),
                $phaseStatus,
                max(0, $stepSpent),
                $cursor->format('Y-m-d'),
            );

            if (random_int(0, 100) <= 55) {
                $this->attachPhotos($project, $update, $users);
            }
        }

        if ($stagnant) {
            $last = $project->updates()->latest('update_date')->first();
            $last?->update(['update_date' => Carbon::now()->subDays(random_int(40, 80))->format('Y-m-d')]);
        }

        $project->update([
            'progress_percentage' => $progress,
            'budget_executed' => $executed,
        ]);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function appendUpdate(Project $project, $users, int $progress, string $note, string $phaseStatus, float $spent, ?string $date = null): ProjectUpdate
    {
        $user = $users->get(random_int(0, max(0, $users->count() - 1)));

        return ProjectUpdate::create([
            'project_id' => $project->id,
            'user_id' => $user?->id,
            'update_date' => $date ?? now()->format('Y-m-d'),
            'progress_percentage_at_update' => $progress,
            'description' => $note,
            'status_at_update' => $phaseStatus,
            'budget_spent_at_update' => $spent > 0 ? $spent : null,
        ]);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function attachPhotos(Project $project, ProjectUpdate $update, $users): void
    {
        foreach (range(1, random_int(1, 2)) as $photoIndex) {
            ProjectPhoto::create([
                'project_id' => $project->id,
                'project_update_id' => $update->id,
                'photo_url' => 'https://picsum.photos/seed/obra-'.$project->id.'-'.$update->id.'-'.$photoIndex.'/800/600',
                'caption' => 'Avance registrado el '.$update->update_date->translatedFormat('d M Y'),
                'taken_at' => $update->update_date,
                'uploaded_by' => $update->user_id,
            ]);
        }
    }

    private function progressNote(string $phaseStatus, int $index, int $total): string
    {
        if ($phaseStatus === Project::STATUS_COMPLETED) {
            return 'Se completaron los trabajos finales, se realizó la recepción de la obra y se entregó al municipio.';
        }

        if ($phaseStatus === Project::STATUS_PAUSED) {
            return 'La obra quedó en pausa por ajustes en el presupuesto asignado.';
        }

        return 'Avance de ejecución '.$index.' de '.$total.': se verificaron los trabajos en campo y continúa el proceso según cronograma.';
    }

    private function coord(float $base, int $seed): float
    {
        mt_srand($seed);

        return round($base + ((mt_rand(0, 1400) - 700) / 10000), 7);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    private function ensureTeam(Municipality $municipality, $users)
    {
        $teamNames = ['Ing. María Rodríguez', 'Arq. José Martínez', 'Lic. Carlos Peña', 'Ing. Ana Santana', 'Téc. Pedro Núñez', 'Lic. Rosa Guzmán'];

        foreach ($teamNames as $name) {
            $users->push(User::factory()->create([
                'name' => $name,
                'municipality_id' => $municipality->id,
                'sector_id' => Sector::query()->where('municipality_id', $municipality->id)->inRandomOrder()->value('id'),
            ]));
        }

        return $users->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function definitions(): array
    {
        $contractors = ['Constructora OASIS, SRL', 'Ingeniería Cibao, SRL', 'Pavimentos del Caribe', 'Construcciones Nova', 'DICSA', 'Grupo Rizek'];

        return [
            [
                'name' => 'Rehabilitación de la Av. Independencia',
                'type' => Project::TYPE_STREET,
                'description' => 'Repavimentación asfáltica, aceras y contenes de la avenida principal, incluyendo señalización horizontal.',
                'address' => 'Av. Independencia, tramo Bolívar – Max Gómez',
                'status' => Project::STATUS_IN_PROGRESS,
                'budget' => 48500000,
                'start_offset_days' => -120,
                'duration_months' => 10,
                'contractor' => $contractors[0],
                'milestones' => ['Cierre de tramo y desvío del tránsito', 'Fresado y base', 'Capa de rodadura', 'Señalización y entrega'],
                'stagnant' => true,
            ],
            [
                'name' => 'Construcción Escuela Básica Los Ríos',
                'type' => Project::TYPE_SCHOOL,
                'description' => 'Centro educativo de 18 aulas con aula de informática, biblioteca y cancha deportiva.',
                'address' => 'Calle 8, sector Los Ríos',
                'status' => Project::STATUS_COMPLETED,
                'budget' => 96500000,
                'start_offset_days' => -420,
                'duration_months' => 16,
                'contractor' => $contractors[3],
                'milestones' => ['Cimentación', 'Estructura y techado', 'Instalaciones eléctricas y sanitarias', 'Acabados y entrega'],
                'delay_days' => -12,
            ],
            [
                'name' => 'Remozamiento Parque La Esperilla',
                'type' => Project::TYPE_PARK,
                'description' => 'Remodelación integral del parque con área infantil, senderos, iluminación LED y mobiliario urbano.',
                'address' => 'Av. Tiradentes esq. Gustavo Mejía Ricart',
                'status' => Project::STATUS_COMPLETED,
                'budget' => 18200000,
                'start_offset_days' => -300,
                'duration_months' => 8,
                'contractor' => $contractors[1],
                'milestones' => ['Desmonte y demolición', 'Obra civil y paseos', 'Área infantil e iluminación'],
                'delay_days' => 25,
            ],
            [
                'name' => 'Soterrado de alcantarillado sanitario Arroyo Hondo',
                'type' => Project::TYPE_SEWAGE,
                'description' => 'Construcción de colector principal de 2.4 km y reposición de acometidas domiciliarias.',
                'address' => 'Calle Principal de Arroyo Hondo',
                'status' => Project::STATUS_IN_PROGRESS,
                'budget' => 78000000,
                'start_offset_days' => -160,
                'duration_months' => 18,
                'contractor' => $contractors[4],
                'milestones' => ['Excavación y zanja', 'Instalación del colector', 'Reposición de aceras y vía'],
            ],
            [
                'name' => 'Reparación de aceras y contenes en Ensanche Naco',
                'type' => Project::TYPE_STREET,
                'description' => 'Reposición de aceras, contenes y badenes en 14 calles del sector.',
                'address' => 'Ensanche Naco',
                'status' => Project::STATUS_PLANNED,
                'budget' => 12800000,
                'start_offset_days' => 20,
                'duration_months' => 5,
                'contractor' => null,
                'milestones' => [],
            ],
            [
                'name' => 'Edificio administrativo del cabildo (anexo)',
                'type' => Project::TYPE_PUBLIC_BUILDING,
                'description' => 'Construcción de anexo de tres niveles para oficinas administrativas y archivo municipal.',
                'address' => 'Av. Padre Castellanos',
                'status' => Project::STATUS_IN_PROGRESS,
                'budget' => 120000000,
                'start_offset_days' => -210,
                'duration_months' => 20,
                'contractor' => $contractors[2],
                'milestones' => ['Fundaciones', 'Estructura de hormigón', 'Cerrajería y mamparas', 'Acabados interiores'],
                'stagnant' => true,
            ],
            [
                'name' => 'Iluminación del Malecón de Santo Domingo',
                'type' => Project::TYPE_OTHER,
                'description' => 'Modernización del alumbrado público con luminarias solares y postes decorativos.',
                'address' => 'Av. George Washington',
                'status' => Project::STATUS_IN_PROGRESS,
                'budget' => 34000000,
                'start_offset_days' => -60,
                'duration_months' => 7,
                'contractor' => $contractors[5],
                'milestones' => ['Retiro de luminarias existentes', 'Instalación de postes y luminarias', 'Puesta en servicio'],
            ],
            [
                'name' => 'Adecentamiento del parque central de Villa Progreso',
                'type' => Project::TYPE_PARK,
                'description' => 'Construcción de plaza central, kiosco, baños públicos y zona verde.',
                'address' => 'Parque Central, Villa Progreso',
                'status' => Project::STATUS_CANCELLED,
                'budget' => 9500000,
                'start_offset_days' => -240,
                'duration_months' => 6,
                'contractor' => $contractors[0],
                'milestones' => [],
            ],
            [
                'name' => 'Asfaltado de calles del Ensanche La Fe',
                'type' => Project::TYPE_STREET,
                'description' => 'Asfaltado de 3.5 km de calles secundarias con encintado y demarcación.',
                'address' => 'Ensanche La Fe',
                'status' => Project::STATUS_COMPLETED,
                'budget' => 27500000,
                'start_offset_days' => -360,
                'duration_months' => 9,
                'contractor' => $contractors[1],
                'milestones' => ['Preparación de la base', 'Asfaltado', 'Encintado y señalización'],
                'delay_days' => 40,
            ],
            [
                'name' => 'Ampliación del acueducto de Los Cacicazgos',
                'type' => Project::TYPE_SEWAGE,
                'description' => 'Instalación de 1.8 km de tubería de distribución para mejorar la presión del servicio.',
                'address' => 'Los Cacicazgos',
                'status' => Project::STATUS_PLANNED,
                'budget' => 52000000,
                'start_offset_days' => 45,
                'duration_months' => 11,
                'contractor' => null,
                'milestones' => [],
            ],
            [
                'name' => 'Techado de la cancha del Club Los Prados',
                'type' => Project::TYPE_OTHER,
                'description' => 'Construcción de cubierta metálica, graderías e iluminación de la cancha mixta.',
                'address' => 'Club Los Prados',
                'status' => Project::STATUS_PAUSED,
                'budget' => 16800000,
                'start_offset_days' => -140,
                'duration_months' => 6,
                'contractor' => $contractors[3],
                'milestones' => ['Fundaciones de columnas', 'Estructura de la cubierta'],
            ],
            [
                'name' => 'Remodelación del centro comunal de Bella Vista',
                'type' => Project::TYPE_PUBLIC_BUILDING,
                'description' => 'Remozamiento de salones multiusos, cocina y fachada del centro comunal.',
                'address' => 'Calle Fundación, Bella Vista',
                'status' => Project::STATUS_COMPLETED,
                'budget' => 21400000,
                'start_offset_days' => -280,
                'duration_months' => 7,
                'contractor' => $contractors[2],
                'milestones' => ['Remodelación de fachada', 'Salones interiores', 'Cocina y equipamiento'],
                'delay_days' => 5,
            ],
        ];
    }
}
