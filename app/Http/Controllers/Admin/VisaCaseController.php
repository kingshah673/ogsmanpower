<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisaCase;
use App\Services\VisaCaseService;
use Illuminate\Http\Request;

class VisaCaseController extends Controller
{
    public function index()
    {
        $cases = VisaCase::latest()->get();
        return view('admin.visa.index', compact('cases'));
    }

    public function create()
    {
        return view('admin.visa.create');
    }

    public function store(Request $request)
    {
        VisaCaseService::createCase([
            'candidate_id' => $request->candidate_id,
            'company_id' => $request->company_id,
            'agency_id' => $request->agency_id,
            'agent_id' => $request->agent_id,
            'created_by' => auth()->id(),
            'country' => $request->country,
            'visa_type' => $request->visa_type,
        ]);

        return redirect()->back()->with('success', 'Case Created');
    }
}