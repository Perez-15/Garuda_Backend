<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeHrAction;
use Illuminate\Http\Request;

class LoaWebhookController extends Controller
{
    /**
     * Receives the webhook from Google Apps Script when a manager approves an LOA.
     * Creates an EmployeeHrAction with type='loa' directly — no separate loa_requests table.
     *
     * Route: POST /api/v1/loa/webhook  (no auth — secured by X-Webhook-Secret header)
     */
    public function receive(Request $request)
    {
        // ── Verify shared secret ───────────────────────────────────────────────
        if ($request->header('X-Webhook-Secret') !== config('services.google.webhook_secret')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        // ── Validate ───────────────────────────────────────────────────────────
        $validated = $request->validate([
            'employee_name'  => 'required|string|max:255',
            'department'     => 'nullable|string|max:255',
            'position'       => 'nullable|string|max:255',
            'branch'         => 'nullable|string|max:255',
            'date_from'      => 'required|date',
            'date_to'        => 'required|date|after_or_equal:date_from',
            'total_days'     => 'nullable|integer|min:1',
            'reason'         => 'nullable|string',
            'leave_type'     => 'nullable|string',
            'contact_number' => 'nullable|string',
            'email'          => 'nullable|email',
            'drive_file_id'  => 'nullable|string',
            'drive_url'      => 'nullable|string|max:1000',
            'file_name'      => 'nullable|string|max:255',
            'generated_at'   => 'nullable|date',
        ]);

        // ── Match to employee record ───────────────────────────────────────────
        $employee = Employee::where('full_name', 'like', "%{$validated['employee_name']}%")
            ->whereNull('deleted_at')
            ->first();

        if (!$employee) {
            // No matching employee — store as unlinked, HR can reconcile manually
            return response()->json([
                'message' => 'No matching employee found. LOA not recorded. Please create the employee record first.',
                'employee_name' => $validated['employee_name'],
            ], 422);
        }

        // ── Prevent duplicate submissions ──────────────────────────────────────
        $exists = EmployeeHrAction::where('employee_id', $employee->id)
            ->where('type', 'loa')
            ->where('loa_start', $validated['date_from'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Duplicate LOA submission ignored.'], 200);
        }

        // ── Build description from form data ───────────────────────────────────
        $parts = array_filter([
            $validated['reason']     ? "Reason: {$validated['reason']}"             : null,
            $validated['leave_type'] ? "Type: " . ucfirst($validated['leave_type']) : null,
            $validated['total_days'] ? "Duration: {$validated['total_days']} day(s)" : null,
            $validated['drive_url']  ? "Drive PDF: {$validated['drive_url']}"        : null,
        ]);

        // ── Create HR Action ───────────────────────────────────────────────────
        $action = EmployeeHrAction::create([
            'employee_id' => $employee->id,
            'type'        => 'loa',
            'subject'     => "LOA — {$validated['employee_name']}" .
                             ($validated['leave_type'] ? ' (' . ucfirst($validated['leave_type']) . ')' : ''),
            'description' => implode("\n", $parts) ?: null,
            'action_date' => $validated['date_from'],
            'loa_start'   => $validated['date_from'],
            'loa_end'     => $validated['date_to'],
            // No file yet — HR will upload PDF manually from the HR Actions tab
            'file_name'   => null,
            'file_path'   => null,
            'file_size'   => null,
            'created_by'  => null, // system-created via webhook
        ]);

        return response()->json([
            'message'    => 'LOA recorded successfully as HR action.',
            'action_id'  => $action->id,
            'employee'   => $employee->full_name,
        ], 201);
    }
}