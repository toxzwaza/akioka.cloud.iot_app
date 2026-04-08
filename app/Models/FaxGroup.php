<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaxGroup extends Model
{
    use HasFactory;

    public function faxSortSettings(): HasMany
    {
        return $this->hasMany(FaxSortSetting::class);
    }

    public function faxUserGroups(): HasMany
    {
        return $this->hasMany(FaxUserGroup::class);
    }
}
