<?php

namespace App\Modules\Project\Domain\Services;

use App\Models\Project;
use App\Models\User;
use App\Modules\Project\Adapters\Repositories\ProjectRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectReportService
{
    public const DEFAULT_STAGNANT_DAYS = 30;

    public function __construct(private readonly ProjectRepository $repository) {}

    /**
     * @param  array{from?: ?string, to?: ?string, stagnant_days?: ?int}  $filters
     * @return array<string, mixed>
     */
    public function report(?int $municipalityId, array $filters = []): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $staleDays = (int) ($filters['stagnant_days'] ?? self::DEFAULT_STAGNANT_DAYS);

        $projects = $this->baseProjects($municipalityId);
        $lastUpdateDates = $this->lastUpdateDates();
        $managers = $this->managerByProject();

        if ($from || $to) {
            $projects = $projects->filter(fn (Project $project) => $this->inPeriod($project, $from, $to));
        }

        $overall = $this->overall($projects, $lastUpdateDates, $staleDays);

        $blocks = [
            $this->completionBlock($projects, $from, $to),
            $this->deadlinesBlock($projects, $from, $to),
            $this->budgetBlock($projects),
            $this->stagnantBlock($projects, $lastUpdateDates, $managers, $staleDays),
            $this->sectorBlock($projects),
            $this->typeBlock($projects),
            $this->productivityBlock($projects, $managers),
        ];

        return [
            'municipality' => $municipalityId,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'stagnant_days' => $staleDays,
            ],
            'overall' => $overall,
            'blocks' => $blocks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function overall(Collection $projects, Collection $lastUpdateDates, int $staleDays): array
    {
        $byStatus = collect(Project::STATUSES)->mapWithKeys(fn (string $status) => [$status => 0])->all();
        $assigned = 0.0;
        $executed = 0.0;
        $stagnant = 0;

        foreach ($projects as $project) {
            $byStatus[$project->status] = ($byStatus[$project->status] ?? 0) + 1;
            $assigned += (float) $project->budget_assigned;
            $executed += (float) $project->budget_executed;

            if ($project->status === Project::STATUS_IN_PROGRESS) {
                $last = $lastUpdateDates->get($project->id);
                if (! $last || Carbon::parse($last)->diffInDays(now()) >= $staleDays) {
                    $stagnant++;
                }
            }
        }

        return [
            'total' => $projects->count(),
            'by_status' => $byStatus,
            'assigned' => round($assigned, 2),
            'executed' => round($executed, 2),
            'execution_rate' => $assigned > 0 ? round(($executed / $assigned) * 100, 1) : 0,
            'stagnant' => $stagnant,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function completionBlock(Collection $projects, ?string $from, ?string $to): array
    {
        $plannedInPeriod = $projects->filter(
            fn (Project $project) => $project->end_date_planned !== null
                && $this->dateInRange($project->end_date_planned, $from, $to),
        );
        $completedInPeriod = $projects->filter(
            fn (Project $project) => $project->status === Project::STATUS_COMPLETED
                && $project->end_date_real !== null
                && $this->dateInRange($project->end_date_real, $from, $to),
        );

        $byTypePlanned = collect(Project::TYPES)->mapWithKeys(fn (string $type) => [$type => 0])->all();
        $byTypeCompleted = $byTypePlanned;

        foreach ($plannedInPeriod as $project) {
            $byTypePlanned[$project->type]++;
        }
        foreach ($completedInPeriod as $project) {
            $byTypeCompleted[$project->type]++;
        }

        $rows = $completedInPeriod
            ->sortByDesc(fn (Project $project) => $project->end_date_real)
            ->values()
            ->map(fn (Project $project): array => [
                $project->name,
                $project->sector?->name ?? '—',
                Project::typeLabelFor($project->type),
                $this->fmtDate($project->start_date_planned),
                $this->fmtDate($project->end_date_real),
            ])
            ->all();

        return [
            'slug' => 'completion',
            'title' => 'Obras completadas vs planificadas',
            'subtitle' => 'Compara las obras que debían terminar en el período con las realmente completadas.',
            'summary' => [
                ['label' => 'Planificadas a terminar', 'value' => (string) $plannedInPeriod->count()],
                ['label' => 'Completadas en el período', 'value' => (string) $completedInPeriod->count()],
                [
                    'label' => 'Tasa de finalización',
                    'value' => $plannedInPeriod->isEmpty()
                        ? '—'
                        : round(($completedInPeriod->count() / $plannedInPeriod->count()) * 100, 1).' %',
                ],
            ],
            'headers' => ['Obra', 'Sector', 'Tipo', 'Inicio', 'Fin real'],
            'rows' => $rows,
            'chart' => [
                'kind' => 'grouped-bars',
                'categories' => array_map(fn (string $type) => Project::typeLabelFor($type), Project::TYPES),
                'series' => [
                    ['name' => 'Planificadas', 'values' => array_values($byTypePlanned)],
                    ['name' => 'Completadas', 'values' => array_values($byTypeCompleted)],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deadlinesBlock(Collection $projects, ?string $from, ?string $to): array
    {
        $completed = $projects->filter(function (Project $project) use ($from, $to) {
            return $project->status === Project::STATUS_COMPLETED
                && $project->end_date_planned !== null
                && $project->end_date_real !== null
                && (! $from || $project->end_date_real->greaterThanOrEqualTo($from))
                && (! $to || $project->end_date_real->lessThanOrEqualTo($to));
        });

        $delayDays = [];
        $onTime = 0;

        foreach ($completed as $project) {
            $diff = $project->end_date_planned->diffInDays($project->end_date_real, false);
            if ($diff <= 0) {
                $onTime++;
            } else {
                $delayDays[] = $diff;
            }
        }

        $rows = $completed
            ->sortBy(fn (Project $project) => $project->end_date_planned->diffInDays($project->end_date_real, false), SORT_REGULAR, true)
            ->values()
            ->map(function (Project $project): array {
                $diff = (int) $project->end_date_planned->diffInDays($project->end_date_real, false);

                return [
                    $project->name,
                    $this->fmtDate($project->end_date_planned),
                    $this->fmtDate($project->end_date_real),
                    $diff > 0 ? $diff.' días' : '0 días',
                    $diff <= 0 ? 'En plazo' : 'Con atraso',
                ];
            })
            ->all();

        return [
            'slug' => 'deadlines',
            'title' => 'Cumplimiento de plazos',
            'subtitle' => 'Evalúa las obras completadas comparando la fecha real de cierre con la planificada.',
            'summary' => [
                ['label' => 'Completadas evaluadas', 'value' => (string) $completed->count()],
                [
                    'label' => '% de cumplimiento',
                    'value' => $completed->isEmpty()
                        ? '—'
                        : round(($onTime / $completed->count()) * 100, 1).' %',
                ],
                ['label' => 'Terminadas en plazo', 'value' => (string) $onTime],
                ['label' => 'Con atraso', 'value' => (string) count($delayDays)],
                [
                    'label' => 'Promedio de atraso',
                    'value' => $delayDays ? round(array_sum($delayDays) / count($delayDays), 1).' días' : '—',
                ],
            ],
            'headers' => ['Obra', 'Fin planificado', 'Fin real', 'Diferencia', 'Cumplimiento'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function budgetBlock(Collection $projects): array
    {
        $assigned = (float) $projects->sum('budget_assigned');
        $executed = (float) $projects->sum('budget_executed');

        $rows = [];
        $assignedByType = [];
        $executedByType = [];

        foreach (Project::TYPES as $type) {
            $subset = $projects->where('type', $type);
            $typeAssigned = (float) $subset->sum('budget_assigned');
            $typeExecuted = (float) $subset->sum('budget_executed');
            $assignedByType[] = round($typeAssigned, 2);
            $executedByType[] = round($typeExecuted, 2);

            $rows[] = [
                Project::typeLabelFor($type),
                $subset->count(),
                $this->money($typeAssigned),
                $this->money($typeExecuted),
                $typeAssigned > 0 ? round(($typeExecuted / $typeAssigned) * 100, 1).' %' : '—',
            ];
        }

        return [
            'slug' => 'budget',
            'title' => 'Ejecución presupuestaria',
            'subtitle' => 'Presupuesto asignado frente al ejecutado, global y por tipo de obra.',
            'summary' => [
                ['label' => 'Asignado', 'value' => $this->money($assigned)],
                ['label' => 'Ejecutado', 'value' => $this->money($executed)],
                ['label' => '% de ejecución', 'value' => $assigned > 0 ? round(($executed / $assigned) * 100, 1).' %' : '—'],
            ],
            'headers' => ['Tipo de obra', 'Cantidad', 'Asignado', 'Ejecutado', '% ejecución'],
            'rows' => $rows,
            'chart' => [
                'kind' => 'grouped-bars',
                'categories' => array_map(fn (string $type) => Project::typeLabelFor($type), Project::TYPES),
                'series' => [
                    ['name' => 'Asignado', 'values' => $assignedByType],
                    ['name' => 'Ejecutado', 'values' => $executedByType],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stagnantBlock(Collection $projects, Collection $lastUpdateDates, Collection $managers, int $staleDays): array
    {
        $stagnant = $projects
            ->filter(fn (Project $project) => $project->status === Project::STATUS_IN_PROGRESS)
            ->map(function (Project $project) use ($lastUpdateDates, $managers) {
                $last = $lastUpdateDates->get($project->id);

                return [
                    'project' => $project,
                    'last' => $last,
                    'days' => $last ? Carbon::parse($last)->diffInDays(now()) : null,
                    'manager' => $managers->get($project->id)?->first(),
                ];
            })
            ->filter(fn (array $item) => $item['last'] === null || $item['days'] >= $staleDays)
            ->values();

        $rows = $stagnant
            ->map(fn (array $item): array => [
                $item['project']->name,
                $item['project']->sector?->name ?? '—',
                $item['project']->progress_percentage.' %',
                $item['last'] ? $this->fmtDate(Carbon::parse($item['last'])) : 'Sin actualizaciones',
                $item['days'] !== null ? $item['days'].' días' : '—',
                $item['manager']?->name ?? 'Sin encargado',
            ])
            ->all();

        return [
            'slug' => 'stagnant',
            'title' => 'Obras estancadas',
            'subtitle' => 'Obras en ejecución sin avances registrados en los últimos '.$staleDays.' días.',
            'summary' => [
                ['label' => 'Obras estancadas', 'value' => (string) $stagnant->count()],
                ['label' => 'Días de inactividad mínimos', 'value' => (string) $staleDays],
            ],
            'headers' => ['Obra', 'Sector', 'Avance', 'Última actualización', 'Sin avance', 'Encargado'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sectorBlock(Collection $projects): array
    {
        $grouped = $projects->groupBy(fn (Project $project) => $project->sector_id ?? 'unassigned');
        $totalAssigned = (float) $projects->sum('budget_assigned');

        $rows = $grouped
            ->map(function (Collection $subset, $key) use ($totalAssigned): array {
                $sector = $subset->first()?->sector;
                $name = $key === 'unassigned' ? 'Sin sector asignado' : ($sector?->name ?? 'Sector #'.$key);
                $assigned = (float) $subset->sum('budget_assigned');

                return [
                    $name,
                    $subset->count(),
                    $this->money($assigned),
                    $this->money($subset->sum('budget_executed')),
                    $totalAssigned > 0 ? round(($assigned / $totalAssigned) * 100, 1).' %' : '—',
                ];
            })
            ->sortByDesc(fn (array $row) => $row[1])
            ->values()
            ->all();

        return [
            'slug' => 'sectors',
            'title' => 'Distribución por sector',
            'subtitle' => 'Obras e inversión por sector del municipio para evidenciar la distribución de recursos.',
            'summary' => [
                ['label' => 'Sectores con obras', 'value' => (string) $grouped->count()],
                ['label' => 'Total de obras', 'value' => (string) $projects->count()],
            ],
            'headers' => ['Sector', 'Obras', 'Asignado', 'Ejecutado', 'Participación'],
            'rows' => $rows,
            'chart' => [
                'kind' => 'bars',
                'labels' => $grouped->keys()
                    ->map(fn ($key) => $key === 'unassigned' ? 'Sin sector' : ($grouped->get($key)->first()?->sector?->name ?? 'Sector '.$key))
                    ->values()
                    ->all(),
                'values' => $grouped->map->count()->values()->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function typeBlock(Collection $projects): array
    {
        $labels = collect(Project::TYPES)->mapWithKeys(fn (string $type) => [$type => Project::typeLabelFor($type)]);
        $counts = collect(Project::TYPES)->mapWithKeys(fn (string $type) => [$type => $projects->where('type', $type)->count()]);
        $investment = collect(Project::TYPES)->mapWithKeys(fn (string $type) => [$type => round((float) $projects->where('type', $type)->sum('budget_assigned'), 2)]);

        $rows = collect(Project::TYPES)
            ->map(fn (string $type): array => [
                $labels[$type],
                $counts[$type],
                $this->money($investment[$type]),
            ])
            ->all();

        return [
            'slug' => 'types',
            'title' => 'Distribución por tipo de obra',
            'subtitle' => 'Composición de la cartera de obras según el tipo de intervención.',
            'summary' => [
                ['label' => 'Total de obras', 'value' => (string) $projects->count()],
                ['label' => 'Inversión total', 'value' => $this->money((float) $projects->sum('budget_assigned'))],
            ],
            'headers' => ['Tipo de obra', 'Cantidad', 'Inversión'],
            'rows' => $rows,
            'chart' => [
                'kind' => 'donut',
                'labels' => $labels->values()->all(),
                'values' => $counts->values()->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productivityBlock(Collection $projects, Collection $managers): array
    {
        $byUser = collect();

        foreach ($projects as $project) {
            $projectManagers = $managers->get($project->id, collect());

            foreach ($projectManagers as $manager) {
                $bucket = $byUser->get($manager->id, [
                    'name' => $manager->name,
                    'managed' => 0,
                    'completed' => 0,
                    'on_time' => 0,
                ]);

                $bucket['managed']++;
                if ($project->status === Project::STATUS_COMPLETED) {
                    $bucket['completed']++;
                    if ($project->end_date_real !== null
                        && $project->end_date_planned !== null
                        && $project->end_date_real->lessThanOrEqualTo($project->end_date_planned)) {
                        $bucket['on_time']++;
                    }
                }

                $byUser->put($manager->id, $bucket);
            }
        }

        $rows = $byUser
            ->sortByDesc(fn (array $row) => $row['managed'])
            ->map(fn (array $row): array => [
                $row['name'],
                $row['managed'],
                $row['completed'],
                $row['on_time'],
                $row['completed'] > 0
                    ? round(($row['on_time'] / $row['completed']) * 100, 1).' %'
                    : '—',
            ])
            ->values()
            ->all();

        return [
            'slug' => 'productivity',
            'title' => 'Productividad por responsable',
            'subtitle' => 'Obras a cargo de cada encargado y porcentaje terminado dentro del plazo planificado.',
            'summary' => [
                ['label' => 'Responsables', 'value' => (string) $byUser->count()],
                ['label' => 'Obras a cargo', 'value' => (string) collect($rows)->sum(fn (array $row) => $row[1])],
            ],
            'headers' => ['Encargado', 'Obras a cargo', 'Completadas', 'En plazo', '% en plazo'],
            'rows' => $rows,
        ];
    }

    /**
     * @return Collection<int, Project>
     */
    private function baseProjects(?int $municipalityId): Collection
    {
        return $this->repository->getReportProjects($municipalityId);
    }

    private function inPeriod(Project $project, ?string $from, ?string $to): bool
    {
        $dates = array_filter([
            $project->start_date_planned,
            $project->end_date_planned,
            $project->start_date_real,
            $project->end_date_real,
        ]);

        foreach ($dates as $date) {
            if ($this->dateInRange($date, $from, $to)) {
                return true;
            }
        }

        return false;
    }

    private function dateInRange(Carbon $date, ?string $from, ?string $to): bool
    {
        if ($from && $date->lt($from)) {
            return false;
        }
        if ($to && $date->gt($to)) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<string, string>
     */
    private function lastUpdateDates(): Collection
    {
        return $this->repository->getLastUpdateDates();
    }

    /**
     * @return Collection<int|string, Collection<int, User>>
     */
    private function managerByProject(): Collection
    {
        return $this->repository->getManagersByProject();
    }

    private function fmtDate(?Carbon $date): string
    {
        return $date ? $date->format('d/m/Y') : '—';
    }

    private function money(float $amount): string
    {
        return 'RD$ '.number_format($amount, 2);
    }
}
