<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageAddress extends Model
{
    use HasFactory;

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function stockStorages(): HasMany
    {
        return $this->hasMany(StockStorage::class);
    }
}
