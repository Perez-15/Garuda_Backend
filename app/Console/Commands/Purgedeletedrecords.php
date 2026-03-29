<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeDeletedRecords extends Command
{
    /**
     * The name and signature of the console command.
     * Run manually: php artisan garuda:purge-deleted
     * Run with custom days: php artisan garuda:purge-deleted --days=60
     */
    protected $signature = 'garuda:purge-deleted {--days=30 : Number of days after which soft-deleted records are permanently purged}';

    protected $description = 'Permanently delete applicant and employee records that have been soft-deleted for more than the specified number of days.';

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $cutoff    = now()->subDays($days);
        $purgedApp = 0;
        $purgedEmp = 0;

        $this->info("Purging records soft-deleted before {$cutoff->toDateTimeString()} ({$days}-day threshold)...");

        // ── Applicants ──────────────────────────────────────────────────────────
        // We process applicants first. For each one, we also permanently delete
        // the linked employee record (if it exists and is also soft-deleted),
        // regardless of how long the employee has been in trash.
        // This preserves Option A restore symmetry — they live and die together.

        $applicants = Applicant::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->get();

        foreach ($applicants as $applicant) {
            DB::transaction(function () use ($applicant, &$purgedApp, &$purgedEmp) {
                // Purge linked employee first (FK constraint)
                $linkedEmployee = Employee::withTrashed()
                    ->where('applicant_id', $applicant->id)
                    ->first();

                if ($linkedEmployee) {
                    if ($linkedEmployee->profile_photo) {
                        Storage::disk(config('filesystems.default'))
                            ->delete($linkedEmployee->profile_photo);
                    }
                    $linkedEmployee->forceDelete();
                    $purgedEmp++;
                }

                // Delete resume file if exists
                if ($applicant->resume_path) {
                    Storage::disk('public')->delete($applicant->resume_path);
                }

                $applicant->forceDelete();
                $purgedApp++;
            });
        }

        // ── Orphaned Employees ──────────────────────────────────────────────────
        // Employees that are soft-deleted but have no applicant link
        // (manually created employees, or applicant was already force-deleted).

        $orphanedEmployees = Employee::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->whereNull('applicant_id')
            ->get();

        foreach ($orphanedEmployees as $employee) {
            if ($employee->profile_photo) {
                Storage::disk(config('filesystems.default'))
                    ->delete($employee->profile_photo);
            }
            $employee->forceDelete();
            $purgedEmp++;
        }

        // ── Summary ─────────────────────────────────────────────────────────────
        $this->info("✓ Purged {$purgedApp} applicant(s) and {$purgedEmp} employee record(s).");

        return Command::SUCCESS;
    }
}