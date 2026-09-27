<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name'])]
class BusinessCategory extends Model
{
    /**
     * @return HasMany<Business, $this>
     */
    public function primaryBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'primary_ciiu_id');
    }

    /**
     * @return HasMany<Business, $this>
     */
    public function secondaryBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'secondary_ciiu_id');
    }
}
