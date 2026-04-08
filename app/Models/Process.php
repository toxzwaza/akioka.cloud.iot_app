<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Process extends Model
{
    use HasFactory;

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function locationProcesses(): HasMany
    {
        return $this->hasMany(LocationProcess::class);
    }

    public function raspiData(): HasMany
    {
        return $this->hasMany(RaspiData::class);
    }

    public function stockProcesses(): HasMany
    {
        return $this->hasMany(StockProcess::class);
    }
}
