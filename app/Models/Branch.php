<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'branch_name',
        'location',
        'is_active',
        'contact_person',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}