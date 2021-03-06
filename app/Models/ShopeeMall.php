<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopeeMall extends Model
{
    use HasFactory;

    protected $table = 'shopee_mall';

    protected $fillable = [
        'name',
        'url',
        'cate_id',
        'shop_id',
        'product_count',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'shop_id');
    }

    public function category()
    {
        return $this->belongsTo(ShopeeCategory::class, 'cate_id');
    }

    public function revenues()
    {
        return $this->hasMany(ProductRevenue::class, 'shop_id');
    }
}
