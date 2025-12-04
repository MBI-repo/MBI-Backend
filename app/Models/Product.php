<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'product_type',
        'price',
        'category',
        'name',
        'description',
        'discount_code',
        'waranty',
        'ukca_mark',
        'manufacturer',
        'model_number',
        'condition',
        'age_of_equipment',
        'last_serviced_date',
        'known_issues',
        'known_issues_details',
        'accessories',
        'pickup_available_date',
        'equipment_location',
        'shipping_cost_contribution',
       
        'created_by',
    ];

    protected $casts = [
        'last_serviced_date' => 'date',
        'pickup_available_date' => 'date',
        'known_issues' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
