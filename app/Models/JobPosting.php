<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'location',
        'category',
        'salary',
        'type',
        'description',
        'is_active',
        'posted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function websiteApplications()
    {
        return $this->hasMany(WebsiteApplication::class);
    }

    // Scope: only active listings (for public endpoint)
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
