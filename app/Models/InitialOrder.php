<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InitialOrder extends Model
{
    use HasFactory;

    public function orderRequest(): BelongsTo
    {
        return $this->belongsTo(OrderRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'order_user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function stockProcess(): BelongsTo
    {
        return $this->belongsTo(StockProcess::class, 'stock_process_id');
    }

    public function faxParameter(): BelongsTo
    {
        return $this->belongsTo(FaxParameter::class, 'fax_parameter_id');
    }

    public function splitOrderQuantities(): HasMany
    {
        return $this->hasMany(SplitOrderQuantity::class);
    }
}
