<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $role_in_project
 * @property Carbon|null $assigned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'project_id',
    'user_id',
    'role_in_project',
    'assigned_at',
])]
class ProjectUser extends Model
{
    public const ROLE_MANAGER = 'manager';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_INSPECTOR = 'inspector';

    public const ROLE_COLLABORATOR = 'collaborator';

    public const ROLES = [
        self::ROLE_MANAGER,
        self::ROLE_SUPERVISOR,
        self::ROLE_INSPECTOR,
        self::ROLE_COLLABORATOR,
    ];

    protected $table = 'project_user';

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function roleLabelFor(?string $role): string
    {
        return match ($role) {
            self::ROLE_MANAGER => 'Encargado',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_INSPECTOR => 'Inspector',
            default => 'Colaborador',
        };
    }
}
