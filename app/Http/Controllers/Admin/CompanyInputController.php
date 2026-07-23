<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyAttribute;
use Illuminate\Http\Request;

class CompanyInputController extends Controller
{
    public function renderDynamicInput($attribute)
    {
        $company=Company::with('attributes')->where('id',$attribute->company_id)->first();

        return view('backend.company.dynamic-inputs', compact('attribute','company'))->render();
    }
        public function store(Request $request)
        {
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'type' => 'required|in:text,email,password,number,textarea,date,dropdown,file',
                'required' => 'required|in:0,1,true,false',
                'active' => 'required|in:0,1,true,false',
                'section' => 'nullable|string|max:64',
                'options' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $options = null;
            if ($validated['type'] === 'dropdown' && ! empty($request->options)) {
                $lines = array_filter(array_map('trim', explode("\n", $request->options)));
                $options = json_encode(array_values($lines));
            }

            $attribute = new CompanyAttribute();
            $attribute->attribute_name = $validated['label'];
            $attribute->input_type = $validated['type'];
            $attribute->section = $validated['section'] ?? 'job_post';
            $attribute->is_required = filter_var($validated['required'], FILTER_VALIDATE_BOOLEAN);
            $attribute->is_active = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);
            $attribute->options = $options;
            $attribute->sort_order = $validated['sort_order'] ?? 0;
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
            $attribute = CompanyAttribute::find($id);

            if ($attribute) {
                $attribute->delete();
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false]);
        }

        // Toggle active/inactive state
        public function toggleActive(Request $request)
        {
            $attribute = CompanyAttribute::find($request->id);

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
            $attribute = CompanyAttribute::find($request->id);

            if ($attribute) {
                $attribute->is_required = $request->is_required;
                $attribute->save();

                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false]);
        }
}
