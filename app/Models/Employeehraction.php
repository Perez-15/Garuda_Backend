<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeHrAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'subject',
        'description',
        'action_date',
        'loa_start',
        'loa_end',
        'created_by',
    ];

    protected $casts = [
        'action_date' => 'date',
        'loa_start'   => 'date',
        'loa_end'     => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'memo' => 'Memo',
            'ir'   => 'Incident Report',
            'loa'  => 'Leave of Absence',
            default => strtoupper($this->type),
        };
    }
}