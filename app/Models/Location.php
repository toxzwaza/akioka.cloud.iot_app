<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    public function locationProcesses(): HasMany
    {
        return $this->hasMany(LocationProcess::class);
    }

    public function storageAddresses(): HasMany
    {
        return $this->hasMany(StorageAddress::class);
    }

    public function processes(): BelongsToMany
    {
        return $this->belongsToMany(Process::class, 'location_processes');
    }
}
