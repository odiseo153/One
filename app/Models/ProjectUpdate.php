<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $user_id
 * @property Carbon $update_date
 * @property int $progress_percentage_at_update
 * @property string|null $description
 * @property string|null $status_at_update
 * @property string|null $budget_spent_at_update
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'project_id',
    'user_id',
    'update_date',
    'progress_percentage_at_update',
    'description',
    'status_at_update',
    'budget_spent_at_update',
])]
class ProjectUpdate extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'update_date' => 'date',
            'progress_percentage_at_update' => 'integer',
            'budget_spent_at_update' => 'decimal:2',
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

    /**
     * @return HasMany<ProjectPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ProjectPhoto::class);
    }
}
