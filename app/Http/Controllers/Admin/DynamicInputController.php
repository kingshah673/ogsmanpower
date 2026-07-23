<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CandidateAttribute;
use App\Models\Candidate;

class DynamicInputController extends Controller
{
    public function renderDynamicInput($attribute)
{
    $candidate=Candidate::with('attributes')->where('id',$attribute->candidate_id)->first();

    return view('backend.candidate.dynamic-inputs', compact('attribute','candidate'))->render();
}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'        => 'required|string|max:255',
            'type'         => 'required|in:text,textarea,date,dropdown,file,email,number',
            'required'     => 'required|in:0,1,true,false',
            'active'       => 'required|in:0,1,true,false',
            'candidate_id' => 'nullable|exists:candidates,id',
            'section'      => 'nullable|string|max:64',
            'options'      => 'nullable|string',
            'sort_order'   => 'nullable|integer|min:0',
        ]);

        $options = null;
        if ($validated['type'] === 'dropdown' && ! empty($validated['options'])) {
            $lines = array_filter(array_map('trim', explode("\n", $validated['options'])));
            $options = json_encode(array_values($lines));
        }

        $attribute = new CandidateAttribute();
        $attribute->candidate_id   = $validated['candidate_id'] ?? null;
        $attribute->section          = $validated['section'] ?? 'basic-info';
        $attribute->attribute_name   = $validated['label'];
        $attribute->input_type       = $validated['type'];
        $attribute->is_required      = filter_var($validated['required'], FILTER_VALIDATE_BOOLEAN);
        $attribute->is_active        = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);
        $attribute->options          = $options;
        $attribute->sort_order       = $validated['sort_order'] ?? 0;
        $attribute->save();

        // return response()->json(['success' => true, 'html' => $this->renderDynamicInput($attribute)]);
        return response()->json([
            'success' => true,
            'attribute' => $attribute
        ]);
    }

   
    // Delete a dynamic input
    public function destroy($id)
    {
        $attribute = CandidateAttribute::find($id);

        if ($attribute) {
            // Remove per-candidate values tied to a global definition.
            if ($attribute->candidate_id === null) {
                CandidateAttribute::where('definition_id', $attribute->id)->delete();
            }

            $attribute->delete();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    // Toggle active/inactive state
    public function toggleActive(Request $request)
    {
        $attribute = CandidateAttribute::find($request->id);

        if ($attribute) {
            $attribute->is_active = $request->is_active;
            $attribute->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    // Toggle required/optional state
    public function toggleRequired(Request $request)
    {
        $attribute = CandidateAttribute::find($request->id);

        if ($attribute) {
            $attribute->is_required = $request->is_required;
            $attribute->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }
}
