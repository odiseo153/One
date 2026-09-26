<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int $order
 * @property Carbon|null $planned_date
 * @property Carbon|null $completed_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'project_id',
    'name',
    'order',
    'planned_date',
    'completed_date',
    'status',
])]
class ProjectMilestone extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DELAYED = 'delayed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_DELAYED,
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'planned_date' => 'date',
            'completed_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Marca el hito como completado.
     *
     * @return $this
     */
    public function markCompleted(): static
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_date = $this->completed_date ?? today();
        $this->save();

        return $this;
    }

    public static function statusLabelFor(?string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => 'Completado',
            self::STATUS_DELAYED => 'Atrasado',
            default => 'Pendiente',
        };
    }
}
