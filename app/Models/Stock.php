<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_no',
        'name',
        's_name',
        'img_path',
        'url',
        'stock_process_id',
        'tax_included',
        'price',
        'solo_unit',
        'org_unit',
        'quantity_per_org',
        'deli_location',
        'memo',
        'del_flg',
        'classification_id',
        'not_stock_flg',
        'purchase_identification_number',
        'jan_code',
        'main_unit_flg',
        'price_check_flg',
        'approval_supplier_name',
        'special_area_cd',
        'desc_memo',
        'show_price_on_invoice',
    ];

    protected $casts = [
        'tax_included' => 'boolean',
        'price' => 'decimal:2',
        'quantity_per_org' => 'integer',
        'del_flg' => 'integer',
        'not_stock_flg' => 'boolean',
        'main_unit_flg' => 'integer',
        'price_check_flg' => 'integer',
        'show_price_on_invoice' => 'integer',
    ];

    public function aliases()
    {
        return $this->hasMany(StockAlias::class);
    }

    public function stockStorages()
    {
        return $this->hasMany(StockStorage::class);
    }

    public function stockImages()
    {
        return $this->hasMany(StockImage::class);
    }

    public function stockSuppliers()
    {
        return $this->hasMany(StockSupplier::class);
    }
}
