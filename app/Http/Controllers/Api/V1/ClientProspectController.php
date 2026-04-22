<?php

namespace App\Http\Controllers\Api\V1;   

use App\Http\Controllers\Controller;
use App\Models\ClientProspect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientProspectController extends Controller
{
    /**
     * List all prospects.
     * - marketing role: only sees their own entries
     * - super_admin / hr_admin: sees all
     */
   public function index(Request $request)
{
    $query = ClientProspect::with('creator:id,name')->latest();

    if ($request->filled('search')) {
        $s = $request->search;
        $query->where(function ($q) use ($s) {
            $q->where('company_name',    'like', "%{$s}%")
              ->orWhere('contact_person', 'like', "%{$s}%")
              ->orWhere('email_address',  'like', "%{$s}%")
              ->orWhere('location',       'like', "%{$s}%");
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    $user = Auth::user();
    if ($user->hasRole('marketing')) {
        // Marketing users always scoped to their own
        $query->where('created_by', $user->id);
    } elseif ($request->filled('created_by')) {
        // Admins can filter by specific marketing employee
        $query->where('created_by', $request->created_by);
    }

    return response()->json($query->get());
}
    /**
     * Create a new prospect.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name'     => 'required|string|max:255',
            'phone_number'     => 'nullable|string|max:50',
            'telephone_number' => 'nullable|string|max:50',
            'contact_person'   => 'nullable|string|max:255',
            'email_address'    => 'nullable|email|max:255',
            'location'         => 'nullable|string|max:500',
            'status'           => 'nullable|in:sent_email,updated,they_emailed,hard_copy_needed,no_response,after_1_month,email_back,for_follow_up',
            'remarks'          => 'nullable|string',
        ]);

        $data['created_by'] = Auth::id();

        $prospect = ClientProspect::create($data);

        return response()->json($prospect, 201);
    }

    /**
     * Update an existing prospect.
     */
    public function update(Request $request, ClientProspect $clientProspect)
    {
        $data = $request->validate([
            'company_name'     => 'required|string|max:255',
            'phone_number'     => 'nullable|string|max:50',
            'telephone_number' => 'nullable|string|max:50',
            'contact_person'   => 'nullable|string|max:255',
            'email_address'    => 'nullable|email|max:255',
            'location'         => 'nullable|string|max:500',
            'status'           => 'nullable|in:sent_email,updated,they_emailed,hard_copy_needed,no_response,after_1_month,email_back,for_follow_up',
            'remarks'          => 'nullable|string',
        ]);

        $clientProspect->update($data);

        return response()->json($clientProspect);
    }

    /**
     * Soft delete a prospect.
     */
    public function destroy(ClientProspect $clientProspect)
    {
        $clientProspect->delete();

        return response()->json(['message' => 'Prospect deleted successfully.']);
    }
}