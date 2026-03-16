<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomColumn;
use Illuminate\Http\Request;

class CustomColumnController extends Controller
{
    // ── GET /custom-columns?page=employed ─────────────────────────────────────
    // Returns all column definitions for a given page, sorted by order
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|string|in:employed,applicants,in_process,hired,internal_employees',
        ]);

        $columns = CustomColumn::where('page', $request->page)
            ->orderBy('order')
            ->get();

        return response()->json($columns);
    }

    // ── POST /custom-columns ──────────────────────────────────────────────────
    // Create a new custom column for a page
    public function store(Request $request)
    {
        $validated = $request->validate([
             'page'      => 'required|string|in:employed,applicants,in_process,hired,internal_employees',
            'field_key' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'label'     => 'required|string|max:100',
            'type'      => 'required|in:text,number,date,select',
            'options'   => 'nullable|array',
            'options.*' => 'string|max:100',
            'order'     => 'nullable|integer|min:0',
        ]);

        // Prevent duplicate field_key on the same page
        $exists = CustomColumn::where('page', $validated['page'])
            ->where('field_key', $validated['field_key'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A column with this field key already exists on this page.',
            ], 409);
        }

        // Auto-assign order if not provided
        if (!isset($validated['order'])) {
            $validated['order'] = CustomColumn::where('page', $validated['page'])->max('order') + 1;
        }

        $validated['created_by'] = auth()->id();

        $column = CustomColumn::create($validated);

        return response()->json([
            'message' => 'Custom column created successfully.',
            'column'  => $column,
        ], 201);
    }

    // ── PATCH /custom-columns/{column} ────────────────────────────────────────
    // Rename label, change type, update options or reorder
    public function update(Request $request, CustomColumn $column)
    {
        $validated = $request->validate([
            'label'     => 'sometimes|string|max:100',
            'type'      => 'sometimes|in:text,number,date,select',
            'options'   => 'nullable|array',
            'options.*' => 'string|max:100',
            'order'     => 'sometimes|integer|min:0',
        ]);

        $column->update($validated);

        return response()->json([
            'message' => 'Column updated successfully.',
            'column'  => $column,
        ]);
    }

    // ── DELETE /custom-columns/{column} ───────────────────────────────────────
    // Remove a custom column definition
    // Note: existing data in employee custom_fields JSON is left as-is
    //       (it becomes ignored since no column definition exists for it)
    public function destroy(CustomColumn $column)
    {
        $column->delete();

        return response()->json([
            'message' => 'Column deleted successfully.',
        ]);
    }

    // ── POST /custom-columns/reorder ─────────────────────────────────────────
    // Bulk update the order of all columns for a page
    // Body: { page: 'employed', order: ['col_id_1', 'col_id_2', ...] }
    public function reorder(Request $request)
    {
        $request->validate([
             'page'    => 'required|string|in:employed,applicants,in_process,hired,internal_employees',
            'order'   => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($request->order as $position => $id) {
            CustomColumn::where('id', $id)
                ->where('page', $request->page)
                ->update(['order' => $position]);
        }

        return response()->json(['message' => 'Columns reordered successfully.']);
    }
}