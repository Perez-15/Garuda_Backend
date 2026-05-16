<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomColumn extends Model

// force redeploy 2026-05-16..
{
    protected $fillable = [
        'page',
        'section', 
        'field_key',
        'label',
        'type',
        'options',
        'order',
        'scope',       
        'required',       
        'is_fixed',  
        'created_by',
    ];

    protected $casts = [
        'options' => 'array',
        'required'  => 'boolean',  
        'is_fixed'  => 'boolean', 
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}