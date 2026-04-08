<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotifyGroup extends Model
{
    use HasFactory;

    public function notifyGroupUsers(): HasMany
    {
        return $this->hasMany(NotifyGroupUser::class);
    }
}
