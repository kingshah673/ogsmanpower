<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContractTemplate;

class ContractTemplateController extends Controller
{
    public function index()
    {
        $templates = ContractTemplate::latest()->get();
        return view('backend.hrtemplates.index', compact('templates'));
    }

    public function create()
    {
        return view('backend.hrtemplates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required'
        ]);

        ContractTemplate::create([
            'title' => $request->title,
            'content' => $request->content
        ]);

        return redirect()->route('admin.hrtemplates.index')
            ->with('success', 'Template created');
    }

    public function edit($id)
    {
        $template = ContractTemplate::findOrFail($id);
        return view('backend.hrtemplates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = ContractTemplate::findOrFail($id);

        $template->update([
            'title' => $request->title,
            'content' => $request->content
        ]);

        return redirect()->route('admin.hrtemplates.index')
            ->with('success', 'Template updated');
    }

    public function destroy($id)
    {
        $template = ContractTemplate::findOrFail($id);
        $template->delete();

        return back()->with('success', 'Template deleted');
    }
}