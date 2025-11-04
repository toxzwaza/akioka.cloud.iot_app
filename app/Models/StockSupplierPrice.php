<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockSupplierPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'stock_supplier_id',
        'price',
        'start_date',
        'end_date',
        'active_flg',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'active_flg' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * 在庫との関連
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * 手配先との関連
     */
    public function stockSupplier()
    {
        return $this->belongsTo(StockSupplier::class);
    }

    /**
     * 有効な価格のみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('active_flg', true);
    }

    /**
     * 指定日時点で有効な価格を取得するスコープ
     */
    public function scopeValidAt($query, $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }
}
