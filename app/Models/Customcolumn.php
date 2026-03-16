<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomColumn extends Model
{
    protected $fillable = [
        'page',
        'field_key',
        'label',
        'type',
        'options',
        'order',
        'created_by',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}