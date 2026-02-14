<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workflow_id',
        'step_name',
        'step_order',
        'is_final_step',
        'description',
    ];

    protected $casts = [
        'is_final_step' => 'boolean',
    ];

    // Relationships
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'current_step_id');
    }

    // Helper method to get next step
    public function nextStep()
    {
        return static::where('workflow_id', $this->workflow_id)
            ->where('step_order', '>', $this->step_order)
            ->orderBy('step_order')
            ->first();
    }

    // Helper method to get previous step
    public function previousStep()
    {
        return static::where('workflow_id', $this->workflow_id)
            ->where('step_order', '<', $this->step_order)
            ->orderBy('step_order', 'desc')
            ->first();
    }
}