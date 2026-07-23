<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AIKnowledgeBase;

class AIKnowledgeBaseController extends Controller
{
    public function index()
    {
        $rows =
            AIKnowledgeBase::latest()
            ->paginate(20);

        return view(
            'admin.ai.index',
            compact('rows')
        );
    }

    public function create()
    {
        return view(
            'admin.ai.create'
        );
    }

    public function store(
        Request $request
    ) {

        $request->validate([

            'question'
                => 'required',

            'answer'
                => 'required'
        ]);

        AIKnowledgeBase::create([

            'category'
                => $request->category,

            'intent'
                => $request->intent,

            'question'
                => $request->question,

            'answer'
                => $request->answer,

            'keywords'
                => $request->keywords,

            'status'
                => $request->status
                ?? 1
        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Knowledge added successfully'
            );
    }

    public function edit($id)
    {
        $row =
            AIKnowledgeBase::findOrFail($id);

        return view(
            'admin.ai.edit',
            compact('row')
        );
    }

    public function update(
        Request $request,
        $id
    ) {

        $row =
            AIKnowledgeBase::findOrFail($id);

        $row->update([

            'category'
                => $request->category,

            'intent'
                => $request->intent,

            'question'
                => $request->question,

            'answer'
                => $request->answer,

            'keywords'
                => $request->keywords,

            'status'
                => $request->status
        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Knowledge updated successfully'
            );
    }

    public function destroy($id)
    {
        AIKnowledgeBase::findOrFail($id)
            ->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'Knowledge deleted successfully'
            );
    }
}