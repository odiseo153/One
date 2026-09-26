<?php

namespace App\Models;

use App\Traits\ModelHelperTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model
{
    use HasFactory, ModelHelperTrait,SoftDeletes;

    public function getFormattedCreatedAt()
    {
        return $this->created_at->translatedFormat('l j \d\e F Y \a \l\a\s H:i');
    }

    public function getFormattedUpdatedAt()
    {
        return $this->updated_at->translatedFormat('l j \d\e F Y \a \l\a\s H:i');
    }
}
