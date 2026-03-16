<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'source',
        'branch_id',
        'workflow_id',
        'current_step_id',
        'resume_path',
        'notes',
        'applied_at',
        'status',
        'created_by', 
        'custom_fields',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentStep()
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function notes()
    {
        return $this->hasMany(ApplicantNote::class);
    }

    public function activities()
    {
        return $this->hasMany(ApplicantActivity::class)->orderBy('created_at', 'desc');
    }

    // ← NEW: tracks which TA added this applicant
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employee()
{
    return $this->hasOne(Employee::class, 'applicant_id');
}

    // Helper method to move to next step
    public function moveToNextStep()
    {
        if ($this->currentStep) {
            $nextStep = $this->currentStep->nextStep();

            if ($nextStep) {
                $this->current_step_id = $nextStep->id;
                $this->save();

                // Log activity
                $this->activities()->create([
                    // @phpstan-ignore-next-line
                    'user_id'       => auth()->id(),
                    'activity_type' => 'step_change',
                    'description'   => "Moved from '{$this->currentStep->step_name}' to '{$nextStep->step_name}'",
                    'metadata'      => json_encode([
                        'from_step' => $this->currentStep->id,
                        'to_step'   => $nextStep->id,
                    ]),
                ]);

                return true;
            }
        }

        return false;
    }
}