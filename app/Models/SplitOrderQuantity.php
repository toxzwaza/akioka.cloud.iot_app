<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitOrderQuantity extends Model
{
    use HasFactory;

    public function initialOrder(): BelongsTo
    {
        return $this->belongsTo(InitialOrder::class);
    }
}
