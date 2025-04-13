<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        return Inertia::render('Project/Index', [
            'projects' => Project::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Project/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_name' => 'required|string',
            'start_date' => 'required|date',
            'is_approved' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $data['id'] = (string) Str::uuid();
        Project::create($data);

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully!');
    }

    public function edit(Project $project)
    {
        // dd($employeeSec);
        return Inertia::render('Project/Edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'client_id' => 'sometimes|required|exists:clients,id',
            'project_name' => 'sometimes|required|string',
            'start_date' => 'sometimes|required|date',
            'is_approved' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $project->update($data);

        return redirect()->route('projects.index')
            ->with('success', 'Project Edited successfully!');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project Deleted successfully!');
    }
}
