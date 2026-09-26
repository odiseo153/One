<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $municipality_id
 * @property int|null $sector_id
 * @property string $name
 * @property string $type
 * @property string|null $description
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $address_text
 * @property string $status
 * @property string|null $budget_assigned
 * @property string $budget_executed
 * @property Carbon|null $start_date_planned
 * @property Carbon|null $end_date_planned
 * @property Carbon|null $start_date_real
 * @property Carbon|null $end_date_real
 * @property int $progress_percentage
 * @property string|null $contractor_name
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'municipality_id',
    'sector_id',
    'name',
    'type',
    'description',
    'latitude',
    'longitude',
    'address_text',
    'status',
    'budget_assigned',
    'budget_executed',
    'start_date_planned',
    'end_date_planned',
    'start_date_real',
    'end_date_real',
    'progress_percentage',
    'contractor_name',
    'created_by',
])]
class Project extends BaseModel
{
    public const STATUS_PLANNED = 'planned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const TYPE_STREET = 'street';

    public const TYPE_SCHOOL = 'school';

    public const TYPE_PARK = 'park';

    public const TYPE_SEWAGE = 'sewage';

    public const TYPE_PUBLIC_BUILDING = 'public_building';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_STREET,
        self::TYPE_SCHOOL,
        self::TYPE_PARK,
        self::TYPE_SEWAGE,
        self::TYPE_PUBLIC_BUILDING,
        self::TYPE_OTHER,
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'budget_assigned' => 'decimal:2',
            'budget_executed' => 'decimal:2',
            'start_date_planned' => 'date',
            'end_date_planned' => 'date',
            'start_date_real' => 'date',
            'end_date_real' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ProjectUser, $this>
     */
    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class)->with('user');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_in_project', 'assigned_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ProjectUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->oldest('update_date');
    }

    /**
     * @return HasMany<ProjectPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class)->latest('id');
    }

    /**
     * @return HasMany<ProjectMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('order');
    }

    /**
     * Cambia el estado de la obra y actualiza fechas reales relacionadas.
     *
     * @return $this
     */
    public function changeStatus(string $newStatus, ?int $userId, ?string $note = null, bool $log = true): static
    {
        $previousStatus = $this->status;

        if ($newStatus === self::STATUS_IN_PROGRESS && $this->start_date_real === null) {
            $this->start_date_real = today();
        }

        if ($newStatus === self::STATUS_COMPLETED) {
            $this->end_date_real = $this->end_date_real ?? today();
            $this->progress_percentage = max($this->progress_percentage, 100);
        }

        $this->status = $newStatus;
        $this->save();

        if ($log) {
            $this->updates()->create([
                'user_id' => $userId,
                'update_date' => today(),
                'progress_percentage_at_update' => $this->progress_percentage,
                'description' => $note ?? self::statusTransitionMessage($previousStatus, $newStatus),
                'status_at_update' => $newStatus,
                'budget_spent_at_update' => null,
            ]);
        }

        return $this;
    }

    public static function statusTransitionMessage(string $previousStatus, string $newStatus): string
    {
        return match ($newStatus) {
            self::STATUS_IN_PROGRESS => 'La obra pasó a estar en ejecución.',
            self::STATUS_PAUSED => 'La obra fue pausada temporalmente.',
            self::STATUS_COMPLETED => 'La obra fue completada.',
            self::STATUS_CANCELLED => 'La obra fue cancelada.',
            default => 'Estado actualizado de '.self::statusLabelFor($previousStatus).' a '.self::statusLabelFor($newStatus).'.',
        };
    }

    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public function typeLabel(): string
    {
        return self::typeLabelFor($this->type);
    }

    public static function statusLabelFor(?string $status): string
    {
        return match ($status) {
            self::STATUS_IN_PROGRESS => 'En ejecución',
            self::STATUS_PAUSED => 'Pausada',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => 'Planificada',
        };
    }

    public static function typeLabelFor(?string $type): string
    {
        return match ($type) {
            self::TYPE_STREET => 'Calle / vía',
            self::TYPE_SCHOOL => 'Escuela',
            self::TYPE_PARK => 'Parque',
            self::TYPE_SEWAGE => 'Alcantarillado',
            self::TYPE_PUBLIC_BUILDING => 'Edificio público',
            default => 'Otra',
        };
    }
}
