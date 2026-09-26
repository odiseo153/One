<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'geojson_polygon'])]
class Province extends BaseModel
{
    protected function casts(): array
    {
        return [
            'geojson_polygon' => 'array',
        ];
    }

    /**
     * @return HasMany<Municipality, $this>
     */
    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }
}
