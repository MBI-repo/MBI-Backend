<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBidding extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'request_code',
        'applicant_type',
        'organization_name',
        'organization_website',
        'facility_address',
        'email',
        'phone',
        'contact_person',
        'equipment_name',
        'urgency',
        'preferred_manufacturer',
        'quantity',
        'can_contribute',
        'budget',
        'statement_of_need',
        'intended_use',
        'agreed',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

