<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotifyQueue extends Model
{
    use HasFactory;

    public function notifyQueueUsers(): HasMany
    {
        return $this->hasMany(NotifyQueueUser::class);
    }
}
