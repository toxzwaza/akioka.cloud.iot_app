<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    public function movieTag(): BelongsTo
    {
        return $this->belongsTo(MovieTag::class, 'movie_tag_id');
    }

    public function movieMemos(): HasMany
    {
        return $this->hasMany(MovieMemo::class);
    }
}
