<?php

namespace App\Models;

use App\Modules\Business\Domain\Enums\DocumentType;
use App\Modules\Business\Domain\Enums\PropertyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'municipality_id',
    'sector_id',
    'name',
    'category',
    'latitude',
    'longitude',
    'address_text',
    'registration_status',
    'property_status',
    'document_type',
    'document_number',
    'rnc',
    'primary_ciiu_id',
    'primary_activity',
    'secondary_ciiu_id',
    'secondary_activity',
    'photo_url',
    'detected_at',
    'last_verified_at',
    'inspector_id',
])]
class Business extends BaseModel
{
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'property_status' => PropertyStatus::class,
            'document_type' => DocumentType::class,
            'detected_at' => 'datetime',
            'last_verified_at' => 'datetime',
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
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * @return BelongsTo<BusinessCategory, $this>
     */
    public function primaryCiiu(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class, 'primary_ciiu_id');
    }

    /**
     * @return BelongsTo<BusinessCategory, $this>
     */
    public function secondaryCiiu(): BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class, 'secondary_ciiu_id');
    }

    /**
     * @return HasMany<BusinessEmployee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(BusinessEmployee::class);
    }
}
