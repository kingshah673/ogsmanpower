<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyAttribute;
use Illuminate\Http\Request;

class AgencyInputController extends Controller
{
    public function renderDynamicInput($attribute)
    {
        $agency=agency::with('attributes')->where('id',$attribute->agency_id)->first();

        return view('backend.agency.dynamic-inputs', compact('attribute','agency'))->render();
    }
        public function store(Request $request)
        {
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'type' => 'required|string',
                'required' => 'required|boolean',
                'active' => 'required|boolean',
            ]);

            $attribute = new AgencyAttribute();
            $attribute->attribute_name = $validated['label'];
            $attribute->input_type = $validated['type'];
            $attribute->is_required = $validated['required'];
            $attribute->is_active = $validated['active'];
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
            $attribute = AgencyAttribute::find($id);

            if ($attribute) {
                $attribute->delete();
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false]);
        }

        // Toggle active/inactive state
        public function toggleActive(Request $request)
        {
            $attribute = agencyAttribute::find($request->id);

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
            $attribute = AgencyAttribute::find($request->id);

            if ($attribute) {
                $attribute->is_required = $request->is_required;
                $attribute->save();

                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false]);
        }
}
