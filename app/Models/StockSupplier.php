<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockSupplier extends Model
{
    use HasFactory;

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
