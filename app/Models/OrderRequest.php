<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderRequest extends Model
{
    use HasFactory;

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function orderRequestApprovals(): HasMany
    {
        return $this->hasMany(OrderRequestApproval::class);
    }

    public function requestUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockProcess(): BelongsTo
    {
        return $this->belongsTo(StockProcess::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
