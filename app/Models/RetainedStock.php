<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetainedStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'user_id',
        'treat_id'
    ];
}
