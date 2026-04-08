<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMessage extends Model
{
    use HasFactory;

    public function toDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'to_device_id');
    }

    public function fromDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'from_device_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}
