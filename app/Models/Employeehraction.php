<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeHrAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'type',
        'subject',
        'description',
        'action_date',
        'loa_start',
        'loa_end',
        // ── File attachment ──
        'file_name',
        'file_path',
        'file_size',
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

    public function hasFile(): bool
    {
        return !empty($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) return '—';
        $kb = $this->file_size / 1024;
        return $kb < 1024
            ? round($kb, 1) . ' KB'
            : round($kb / 1024, 2) . ' MB';
    }
}