<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'token',
        'last_access_date',
    ];

    protected $casts = [
        'last_access_date' => 'datetime',
    ];
}
