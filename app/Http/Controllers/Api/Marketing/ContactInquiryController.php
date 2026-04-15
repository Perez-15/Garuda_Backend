<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\Request;

class ContactInquiryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'    => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'contact_number' => 'nullable|string|max:20',
            'inquiry_type' => 'required|in:job-seeker,partnership',
            'message'      => 'required|string|max:2000',
        ]);

        ContactInquiry::create($data);

        return response()->json(['message' => 'Inquiry received.'], 201);
    }

    public function index()
    {
        $inquiries = ContactInquiry::latest()->get();
        return response()->json($inquiries);
    }

    public function markRead(ContactInquiry $inquiry)
    {
        $inquiry->update(['status' => 'read']);
        return response()->json($inquiry);
    }

    public function archive(ContactInquiry $inquiry)
    {
        $inquiry->update(['status' => 'archived']);
        return response()->json($inquiry);
    }

    public function destroy(ContactInquiry $inquiry)
    {
        $inquiry->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}