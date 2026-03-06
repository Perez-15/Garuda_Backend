<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'applicant_id',
        'branch_id',
        'position_id',

        // Personal
        'full_name',
        'date_of_birth',
        'age',
        'gender',
        'civil_status',

        // Contact
        'contact_number',
        'email',
        'address',
        'emergency_contact_name',
        'emergency_contact_number',

        // Gov IDs
        'sss',
        'pagibig',
        'philhealth',
        'tin',

        // Documents
        'nbi_status',
        'nbi_expiry',
        'police_clearance_status',
        'police_clearance_expiry',
        'medcert_status',
        'medcert_expiry',

        // 201 Status
        'requirements_status',

        // Employment
        'date_hired',
        'date_resigned',
        'date_ended',
        'daily_rate',
        'employment_status',

        // Misc
        'source',
        'created_by',
        'remarks',
    ];

    protected $casts = [
        'date_of_birth'           => 'date',
        'date_hired'              => 'date',
        'date_resigned'           => 'date',
        'date_ended'              => 'date',
        'nbi_expiry'              => 'date',
        'police_clearance_expiry' => 'date',
        'medcert_expiry'          => 'date',
        'daily_rate'              => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function hrActions()
    {
        return $this->hasMany(EmployeeHrAction::class)->orderBy('action_date', 'desc');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('employment_status', $status);
    }

    public function scopeHired($query)
    {
        return $query->where('employment_status', 'hired');
    }

    public function scopeResigned($query)
    {
        return $query->where('employment_status', 'resigned');
    }

    public function scopeTerminated($query)
    {
        return $query->where('employment_status', 'terminated');
    }

    public function scopeEndo($query)
    {
        return $query->where('employment_status', 'endo');
    }

    public function scopeAwol($query)
    {
        return $query->where('employment_status', 'awol');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Auto-calculate age from date_of_birth before saving.
     */
    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if ($employee->date_of_birth) {
                $employee->age = $employee->date_of_birth->age;
            }
        });
    }
}