<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lunch extends Model
{
    use HasFactory;

    public function lunchOrders(): HasMany
    {
        return $this->hasMany(LunchOrder::class);
    }

    public function preLunchOrders(): HasMany
    {
        return $this->hasMany(PreLunchOrder::class);
    }
}
