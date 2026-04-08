<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentImages(): HasMany
    {
        return $this->hasMany(DocumentImage::class);
    }

    public function documentStocks(): HasMany
    {
        return $this->hasMany(DocumentStock::class);
    }
}
