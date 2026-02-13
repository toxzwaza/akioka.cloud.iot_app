<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockStorage extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'storage_address_id',
        'quantity',
        'reorder_point',
    ];

    public function storageAddress()
    {
        return $this->belongsTo(StorageAddress::class, 'storage_address_id');
    }
}
