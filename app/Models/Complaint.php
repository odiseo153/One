<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'municipality_id',
    'sector_id',
    'category',
    'description',
    'latitude',
    'longitude',
    'address_text',
    'photo_url',
    'citizen_name',
    'citizen_phone',
    'tracking_code',
    'status',
    'assigned_user_id',
    'resolved_at',
])]
class Complaint extends BaseModel
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED,
    ];

    public const CATEGORY_BACHE = 'bache';

    public const CATEGORY_ALUMBRADO = 'alumbrado';

    public const CATEGORY_BASURA = 'basura';

    public const CATEGORY_AGUA = 'agua';

    public const CATEGORY_OTRO = 'otro';

    public const CATEGORIES = [
        self::CATEGORY_BACHE,
        self::CATEGORY_ALUMBRADO,
        self::CATEGORY_BASURA,
        self::CATEGORY_AGUA,
        self::CATEGORY_OTRO,
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'resolved_at' => 'datetime',
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
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return HasMany<ComplaintUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(ComplaintUpdate::class)->latest();
    }

    public static function newTrackingCode(): string
    {
        do {
            $code = 'CMP-'.strtoupper(Str::random(6));
        } while (static::query()->where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Cambia el estado de la queja y registra la bitácora de seguimiento.
     *
     * @return $this
     */
    public function changeStatus(string $newStatus, ?int $userId, ?string $note = null): static
    {
        $previousStatus = $this->status;

        $this->status = $newStatus;
        $this->resolved_at = $newStatus === self::STATUS_RESOLVED ? now() : null;
        $this->save();

        $this->updates()->create([
            'user_id' => $userId,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'note' => $note,
        ]);

        return $this;
    }

    /**
     * Asigna la queja a un usuario del municipio.
     *
     * @return $this
     */
    public function assignTo(?int $userId): static
    {
        $this->assigned_user_id = $userId;
        $this->save();

        $this->updates()->create([
            'user_id' => $userId,
            'previous_status' => $this->status,
            'new_status' => $this->status,
            'note' => $userId
                ? 'Queja asignada a un responsable.'
                : 'Queja desasignada.',
        ]);

        return $this;
    }

    /**
     * Estado formateado para mostrar en la interfaz.
     */
    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    public static function statusLabelFor(?string $status): string
    {
        return match ($status) {
            self::STATUS_IN_PROGRESS => 'En proceso',
            self::STATUS_RESOLVED => 'Resuelta',
            default => 'Recibida',
        };
    }

    public static function categoryLabelFor(string $category): string
    {
        return match ($category) {
            self::CATEGORY_BACHE => 'Bache',
            self::CATEGORY_ALUMBRADO => 'Alumbrado',
            self::CATEGORY_BASURA => 'Basura',
            self::CATEGORY_AGUA => 'Agua',
            default => 'Otro',
        };
    }
}
