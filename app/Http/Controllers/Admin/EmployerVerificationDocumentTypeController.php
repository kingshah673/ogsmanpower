<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployerVerificationDocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployerVerificationDocumentTypeController extends Controller
{
    public function index()
    {
        abort_if(! userCan('company.update') && ! auth()->user()->hasRole('superadmin'), 403);

        $documentTypes = EmployerVerificationDocumentType::query()->ordered()->get();

        return view('backend.company.verification-document-types', compact('documentTypes'));
    }

    public function store(Request $request)
    {
        abort_if(! userCan('company.update') && ! auth()->user()->hasRole('superadmin'), 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:2000'],
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/', 'unique:employer_verification_document_types,slug'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $slug = $validated['slug'] ?? EmployerVerificationDocumentType::slugFromLabel($validated['label']);

        $documentType = EmployerVerificationDocumentType::query()->create([
            'slug' => $slug,
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'is_required' => $request->boolean('is_required', true),
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default', false),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'document_type' => $documentType,
        ]);
    }

    public function update(Request $request, EmployerVerificationDocumentType $documentType)
    {
        abort_if(! userCan('company.update') && ! auth()->user()->hasRole('superadmin'), 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:2000'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('employer_verification_document_types', 'slug')->ignore($documentType->id),
            ],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $documentType->update([
            'label' => $validated['label'],
            'help_text' => $validated['help_text'] ?? null,
            'slug' => $validated['slug'] ?? $documentType->slug,
            'is_required' => $request->boolean('is_required', $documentType->is_required),
            'is_active' => $request->boolean('is_active', $documentType->is_active),
            'is_default' => $request->boolean('is_default', $documentType->is_default),
            'sort_order' => $validated['sort_order'] ?? $documentType->sort_order,
        ]);

        return response()->json([
            'success' => true,
            'document_type' => $documentType->fresh(),
        ]);
    }

    public function destroy(EmployerVerificationDocumentType $documentType)
    {
        abort_if(! userCan('company.update') && ! auth()->user()->hasRole('superadmin'), 403);

        $documentType->delete();

        return response()->json(['success' => true]);
    }

    public function toggleActive(Request $request, EmployerVerificationDocumentType $documentType)
    {
        abort_if(! userCan('company.update') && ! auth()->user()->hasRole('superadmin'), 403);

        $documentType->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json(['success' => true]);
    }
}
