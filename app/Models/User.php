<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'profile_photo',

        // ── Personal Info ──────────────────────────────────────────────────
        'contact_number',
        'address',
        'date_of_birth',
        'gender',
        'civil_status',
        'emergency_contact_name',
        'emergency_contact_number',

        // ── Employment ─────────────────────────────────────────────────────
        'department',
        'date_hired',

        // ── Requirements / Documents ───────────────────────────────────────
        'nbi_status',
        'medcert_status',
        'police_clearance_status',
        'contract_status',

        // ── Government IDs ─────────────────────────────────────────────────
        'sss',
        'pagibig',
        'philhealth',
        'tin',

        // ── Overall Requirements Status ────────────────────────────────────
        'requirements_status',

        // ── Custom Fields (JSON) ───────────────────────────────────────────
        'custom_fields',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'date_hired'        => 'date',
        'date_of_birth'     => 'date',
        'custom_fields'     => 'array',
    ];

    // ── Accessors ──────────────────────────────────────────────────────────────

    /**
     * Returns the full public URL of the profile photo.
     * Works for both local (storage link) and S3 — no code change needed when switching.
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo) return null;
        return Storage::disk(config('filesystems.default'))->url($this->profile_photo);
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function notes()
    {
        return $this->hasMany(ApplicantNote::class);
    }

    public function activities()
    {
        return $this->hasMany(ApplicantActivity::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
                    ->withTimestamps();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isTalentAcquisition(): bool
    {
        return $this->hasRole('talent_acquisition');
    }

    public function assignedBranchIds(): ?array
    {
        if ($this->isTalentAcquisition()) {
            return $this->branches()->pluck('branches.id')->toArray();
        }
        return null;
    }

    public function getDepartmentLabelAttribute(): string
    {
        $map = [
            'super_admin'        => 'Super Admin',
            'hr_admin'           => 'HR',
            'talent_acquisition' => 'Talent Acquisition',
            'accounting'         => 'Accounting',
            'marketing'          => 'Marketing',
        ];
        $role = $this->roles->first()?->name ?? '';
        return $map[$role] ?? ucfirst($role);
    }

    public function hasCompleteRequirements(): bool
    {
        return $this->requirements_status === 'complete';
    }
}