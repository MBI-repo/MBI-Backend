<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_uuid',
        'bio',
        'areas_of_expertise',
        'educations',
        'experiences',
        'certifications',
        'publications',
        'memberships',
        'awards',
    ];

    protected $casts = [
        'areas_of_expertise' => 'array',
        'educations'         => 'array',
        'experiences'        => 'array',
        'certifications'     => 'array',
        'publications'       => 'array',
        'memberships'        => 'array',
        'awards'             => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
}
