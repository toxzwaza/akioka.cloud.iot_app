<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function classification(): BelongsTo
    {
        return $this->belongsTo(Classification::class);
    }

    public function orderRequests(): HasMany
    {
        return $this->hasMany(OrderRequest::class);
    }

    public function documentStocks(): HasMany
    {
        return $this->hasMany(DocumentStock::class);
    }

    public function stockSupplierPrices(): HasMany
    {
        return $this->hasMany(StockSupplierPrice::class);
    }

    public function objectRequests(): HasMany
    {
        return $this->hasMany(ObjectRequest::class);
    }

    public function productAliases(): HasMany
    {
        return $this->hasMany(ProductAlias::class);
    }

    public function stockRequests(): HasMany
    {
        return $this->hasMany(StockRequest::class);
    }

    public function stockRequestOrders(): HasMany
    {
        return $this->hasMany(StockRequestOrder::class);
    }

    public function getMainSupplierAttribute()
    {
        return $this->stockSuppliers()->where('main_supplier_flg', 1)->first()?->supplier;
    }

    public function getMainSupplierNameAttribute()
    {
        return $this->mainSupplier?->name;
    }

    public function getMainSupplierIdAttribute()
    {
        return $this->mainSupplier?->id;
    }

    public function getMainSupplierNoAttribute()
    {
        return $this->mainSupplier?->supplier_no;
    }
}
