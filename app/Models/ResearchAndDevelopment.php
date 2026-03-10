<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResearchAndDevelopment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'research_and_developments';

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'slug',
        'principal_investigator',
        'abstract',
        'tags',
        'ethical_approval_id',
        'ethical_approval_file',
        'study_design',
        'methodology',
        'institution',
        'conflict_of_interest',
        'funding_disclosure',
        'data_sources',
        'external_url',
        'document_path',
        'status',
        'access_type',
        'clinical_phase',
        'peer_review',
        'funding_state',
        'methodology_type',
        'country_region',
    ];

    protected $casts = [
        'tags' => 'array',
        'study_design' => 'array',
        'methodology' => 'array',
        'data_sources' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
