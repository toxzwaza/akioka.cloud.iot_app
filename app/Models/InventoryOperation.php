<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryOperation extends Model
{
    use HasFactory;

    public function records(): HasMany
    {
        return $this->hasMany(InventoryOperationRecord::class);
    }
}
