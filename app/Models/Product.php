<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    public $table = "shopify_products";
    protected $casts = [
        'options' => 'array',
        'variants' => 'array',
        'images' => 'array',
        'zalando_sizes' => 'array',
        'zalando_images' => 'array',
        'material' => 'array',
        'zalando_prices' => 'array',
        'target_genders' => 'array',
        'target_age_groups' => 'array',
        'image' => 'array',
        'required_attributes' => 'array'
    ];

    public function errors()
    {
        return $this->hasMany('App\Models\ProductError','shopify_products_id');
    }

    public function parent()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_variants()
    {
        return $this->hasMany(Product::class, "parent_id");
    }

}
