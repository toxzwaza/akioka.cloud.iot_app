<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaspiData extends Model
{
    use HasFactory;

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }
}
