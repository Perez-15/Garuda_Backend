<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'address',
        'position_applied',
        'job_posting_id',
        'resume_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'converted_applicant_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function convertedApplicant()
    {
        return $this->belongsTo(Applicant::class, 'converted_applicant_id');
    }

    // Scope: only pending (for notification badge count)
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
