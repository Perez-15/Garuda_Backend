<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProspect extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'phone_number',
        'telephone_number',
        'contact_person',
        'email_address',
        'location',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}