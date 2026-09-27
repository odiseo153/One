<?php

namespace App\Models;

use App\Modules\Business\Domain\Enums\DocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'first_name',
    'last_name',
    'document_type',
    'document_number',
    'salary',
])]
class BusinessEmployee extends Model
{
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'salary' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
