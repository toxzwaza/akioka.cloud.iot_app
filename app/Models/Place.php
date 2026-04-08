<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    use HasFactory;

    public function computers(): HasMany
    {
        return $this->hasMany(Computer::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class);
    }
}
