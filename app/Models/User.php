<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function notes()
    {
        return $this->hasMany(ApplicantNote::class);
    }

    public function activities()
    {
        return $this->hasMany(ApplicantActivity::class);
    }

    /**
     * Branches this user (TA) is allowed to manage.
     * For super_admin / hr_admin this is intentionally unused —
     * those roles see everything via the controller logic.
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
                    ->withTimestamps();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Returns true if the user is a Talent Acquisition user.
     */
    public function isTalentAcquisition(): bool
    {
        return $this->hasRole('talent_acquisition');
    }

    /**
     * Returns branch IDs this TA is assigned to.
     * Returns null for admins (means "all branches").
     */
    public function assignedBranchIds(): ?array
    {
        if ($this->isTalentAcquisition()) {
            return $this->branches()->pluck('branches.id')->toArray();
        }

        return null; // no restriction
    }
}