<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalcProductFile extends Model
{
    use HasFactory;

    /**
     * セカンドデータベース（remacs_calc）を使用
     */
    protected $connection = 'remacs_calc';
}
