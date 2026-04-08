<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaxSortSetting extends Model
{
    use HasFactory;

    public function faxGroup(): BelongsTo
    {
        return $this->belongsTo(FaxGroup::class);
    }
}
