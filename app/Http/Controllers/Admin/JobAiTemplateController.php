<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobAiTemplate;
use Illuminate\Http\Request;
use App\Models\JobCategory;

class JobAiTemplateController extends Controller
{
    
    public function index()
{
    $data = \App\Models\JobAiTemplate::latest()->get();
    return view('backend.job_ai.index', compact('data'));
}

    public function create()
    {
        $jobCategories = JobCategory::select('id','slug')->get();

    return view('backend.job_ai.create', compact('jobCategories'));
    }

    public function store(Request $request)
{
    $skills = array_map('trim', explode(',', $request->skills_input));
    $tags = array_map('trim', explode(',', $request->tags_input));

    \App\Models\JobAiTemplate::create([
        'job_title' => $request->job_title,
        'description' => $request->description,
        'skills' => $skills,
        'tags' => $tags,
        'min_salary' => $request->min_salary,
        'max_salary' => $request->max_salary,
        'experience' => $request->experience,
    ]);

    return back()->with('success','Saved Successfully');
}
public function edit($id)
{
    $data = \App\Models\JobAiTemplate::findOrFail($id);
    return view('backend.job_ai.edit', compact('data'));
}

public function update(Request $request, $id)
{
    $skills = array_map('trim', explode(',', $request->skills_input));
    $tags = array_map('trim', explode(',', $request->tags_input));

    $data = \App\Models\JobAiTemplate::findOrFail($id);

    $data->update([
        'job_title' => $request->job_title,
        'description' => $request->description,
        'skills' => $skills,
        'tags' => $tags,
        'min_salary' => $request->min_salary,
        'max_salary' => $request->max_salary,
        'experience' => $request->experience,
    ]);

    return redirect()->route('backend.job_ai.index')->with('success','Updated');
}

public function destroy($id)
{
    \App\Models\JobAiTemplate::findOrFail($id)->delete();
    return back()->with('success','Deleted');
}
}