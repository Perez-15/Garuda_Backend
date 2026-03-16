<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomColumn;
use App\Models\CustomTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomColumnController extends Controller
{
    // ── GET /custom-columns?page=hired ────────────────────────────────────────
    // Returns all column definitions for a given page, sorted by section + order
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|string',
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
            'page'      => 'required|string',
            'section'   => 'nullable|string|max:100',
            'field_key' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'label'     => 'required|string|max:100',
            'type'      => 'required|in:text,number,date,email,phone,file,select',
            'options'   => 'nullable|array',
            'options.*' => 'string|max:100',
            'order'     => 'nullable|integer|min:0',
            'scope'     => 'nullable|in:ext,int,both',
            'required'  => 'nullable|boolean',
            'is_fixed'  => 'nullable|boolean',
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
            $validated['order'] = CustomColumn::where('page', $validated['page'])
                ->where('section', $validated['section'] ?? 'General')
                ->max('order') + 1;
        }

        $validated['scope']      = $validated['scope']    ?? 'both';
        $validated['section']    = $validated['section']  ?? 'General';
        $validated['created_by'] = auth()->id();

        $column = CustomColumn::create($validated);

        return response()->json([
            'message' => 'Custom column created successfully.',
            'column'  => $column,
        ], 201);
    }

    // ── PATCH /custom-columns/{column} ────────────────────────────────────────
    // Rename label, change type, update scope, required, section, or reorder
    public function update(Request $request, CustomColumn $column)
    {
        $validated = $request->validate([
            'section'   => 'sometimes|string|max:100',
            'label'     => 'sometimes|string|max:100',
            'type'      => 'sometimes|in:text,number,date,email,phone,file,select',
            'options'   => 'nullable|array',
            'options.*' => 'string|max:100',
            'order'     => 'sometimes|integer|min:0',
            'scope'     => 'sometimes|in:ext,int,both',
            'required'  => 'sometimes|boolean',
            'is_fixed'  => 'sometimes|boolean',
        ]);

        $column->update($validated);

        return response()->json([
            'message' => 'Column updated successfully.',
            'column'  => $column,
        ]);
    }

    // ── DELETE /custom-columns/{column} ───────────────────────────────────────
    // Remove a custom column definition
    public function destroy(CustomColumn $column)
    {
        $column->delete();

        return response()->json([
            'message' => 'Column deleted successfully.',
        ]);
    }

    // ── POST /custom-columns/reorder ──────────────────────────────────────────
    // Bulk update the order of all columns for a page
    // Body: { page: 'hired', order: [1, 2, 3, ...] }
    public function reorder(Request $request)
    {
        $request->validate([
            'page'    => 'required|string',
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

    // ═════════════════════════════════════════════════════════════════════════
    // TABLE MANAGEMENT
    // ═════════════════════════════════════════════════════════════════════════

    // ── GET /custom-columns/tables ────────────────────────────────────────────
    // Returns all distinct pages/tables with their scope and label
    // Merges default system tables with any custom ones created by admin
    public function getTables()
    {
        $defaultTables = [
            ['page' => 'hired',              'label' => 'Hired Applicants',   'scope' => 'both', 'is_default' => true],
            ['page' => 'applicants',         'label' => 'Applicants',         'scope' => 'both', 'is_default' => true],
            ['page' => 'in_process',         'label' => 'In Process',         'scope' => 'both', 'is_default' => true],
            ['page' => 'employed',           'label' => 'Employed',           'scope' => 'both', 'is_default' => true],
            ['page' => 'internal_employees', 'label' => 'Internal Employees', 'scope' => 'int',  'is_default' => true],
        ];

        // Fetch custom (non-default) tables from custom_tables table
        $customTables = CustomTable::orderBy('created_at')
            ->get()
            ->map(fn($t) => [
                'page'       => $t->page,
                'label'      => $t->label,
                'scope'      => $t->scope,
                'is_default' => false,
            ])
            ->toArray();

        // Merge: default tables first, then custom ones
        $defaultPages = array_column($defaultTables, 'page');
        $merged = $defaultTables;

        foreach ($customTables as $ct) {
            if (!in_array($ct['page'], $defaultPages)) {
                $merged[] = $ct;
            }
        }

        return response()->json(['tables' => $merged]);
    }

    // ── POST /custom-columns/tables ───────────────────────────────────────────
    // Create a new custom table/page
    public function storeTable(Request $request)
    {
        $validated = $request->validate([
            'page'  => 'required|string|max:100|regex:/^[a-z0-9_]+$/|unique:custom_tables,page',
            'label' => 'required|string|max:100',
            'scope' => 'required|in:ext,int,both',
        ]);

        $validated['created_by'] = auth()->id();

        $table = CustomTable::create($validated);

        return response()->json([
            'message' => 'Table created successfully.',
            'table'   => $table,
        ], 201);
    }

    // ── PATCH /custom-columns/tables/{page} ───────────────────────────────────
    // Update a custom table's label or scope
    public function updateTable(Request $request, string $page)
    {
        $table = CustomTable::where('page', $page)->firstOrFail();

        $validated = $request->validate([
            'label' => 'sometimes|string|max:100',
            'scope' => 'sometimes|in:ext,int,both',
        ]);

        $table->update($validated);

        return response()->json([
            'message' => 'Table updated successfully.',
            'table'   => $table,
        ]);
    }

    // ── DELETE /custom-columns/tables/{page} ──────────────────────────────────
    // Delete a custom table and all its column definitions
    // Note: only non-default tables can be deleted
    public function destroyTable(string $page)
    {
        $defaultPages = ['hired', 'applicants', 'in_process', 'employed', 'internal_employees'];

        if (in_array($page, $defaultPages)) {
            return response()->json([
                'message' => 'Default tables cannot be deleted.',
            ], 403);
        }

        DB::transaction(function () use ($page) {
            CustomColumn::where('page', $page)->delete();
            CustomTable::where('page', $page)->delete();
        });

        return response()->json([
            'message' => 'Table and all its columns deleted successfully.',
        ]);
    }
}