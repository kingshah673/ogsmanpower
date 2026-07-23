<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AIKnowledgeBase;

class AIKnowledgeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = AIKnowledgeBase::query();

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->search) {

            $query->where('title', 'LIKE', '%' . $request->search . '%')
                ->orWhere('question', 'LIKE', '%' . $request->search . '%')
                ->orWhere('answer', 'LIKE', '%' . $request->search . '%');
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        if ($request->category) {

            $query->where('category', $request->category);
        }

        /*
        |--------------------------------------------------------------------------
        | GET DATA
        |--------------------------------------------------------------------------
        */

        $knowledges = $query->latest()->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'backend.ai.knowledge.index',
            compact('knowledges')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view(
            'backend.ai.knowledge.create'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([

            'title'    => 'required',
            'question' => 'required',
            'answer'   => 'required',

        ]);

        AIKnowledgeBase::create([

            'title'    => $request->title,
            'question' => $request->question,
            'answer'   => $request->answer,
            'category' => $request->category,
            'status'   => $request->status ?? 1,

        ]);

        return redirect()

            ->route('admin.ai.knowledge.index')

            ->with(
                'success',
                'AI knowledge created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $knowledge = AIKnowledgeBase::findOrFail($id);

        return view(
            'backend.ai.knowledge.show',
            compact('knowledge')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $knowledge = AIKnowledgeBase::findOrFail($id);

        return view(
            'backend.ai.knowledge.edit',
            compact('knowledge')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $knowledge = AIKnowledgeBase::findOrFail($id);

        $request->validate([

            'title'    => 'required',
            'question' => 'required',
            'answer'   => 'required',

        ]);

        $knowledge->update([

            'title'    => $request->title,
            'question' => $request->question,
            'answer'   => $request->answer,
            'category' => $request->category,
            'status'   => $request->status ?? 1,

        ]);

        return redirect()

            ->route('admin.ai.knowledge.index')

            ->with(
                'success',
                'AI knowledge updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $knowledge = AIKnowledgeBase::findOrFail($id);

        $knowledge->delete();

        return redirect()

            ->route('admin.ai.knowledge.index')

            ->with(
                'success',
                'AI knowledge deleted successfully.'
            );
    }
}