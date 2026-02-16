<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }
}
