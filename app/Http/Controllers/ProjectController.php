<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Project;
use App\Models\Client;
use App\Models\EmployeeSecond;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index()
    {
        return Inertia::render('Project/Index', [
            'projects' => Project::all(),
            'assignments' => Assignment::with('employee:id,name')->get(),
            'employees' => EmployeeSecond::all(),
        ]);
    }

    public function create()
    {
        // dd(\App\Models\Client::select('id', 'company_name')->get());
        return Inertia::render('Project/Create',[
            'clients' => Client::select('id', 'company_name')->get(),
            'employees' => EmployeeSecond::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        // Remove the dd() call to allow the function to proceed
        $validatedData = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_name' => 'required|string',
            'start_date' => 'required|date',
            'is_approved' => 'boolean',
            'settings' => 'nullable',
        ]);

        // Create a new Project, not Assignment
        $project = new Project();
        $project->id = Str::uuid();
        $project->client_id = $validatedData['client_id'];
        $project->project_name = $validatedData['project_name'];
        $project->start_date = $validatedData['start_date'];
        $project->is_approved = $validatedData['is_approved'] ?? false;
        
        // Handle settings
        if (isset($validatedData['settings'])) {
            // If settings is already a JSON string, use it directly
            $project->settings = $validatedData['settings'];
        }
        
        // Save the project
        $project->save();

        // Handle additional items if project is approved
        if ($request->input('is_approved') == '1' && $request->has('additionalItems')) {
            $additionalItems = json_decode($request->input('additionalItems'), true);
            
            foreach ($additionalItems as $item) {
                // Create a new assignment
                $assignment = new Assignment();
                $assignment->id = Str::uuid();
                $assignment->project_id = $project->id; // Use the saved project's ID
                $assignment->employee_id = $item['employee_id'];
                $assignment->start_date = $item['start_date'];
                $assignment->end_date = $item['end_date'] ?? null;
                $assignment->notes = $item['notes'] ?? null;
                
                // Handle file upload
                $attachmentKey = "attachment_" . $item['id'];
                if ($request->hasFile($attachmentKey)) {
                    $file = $request->file($attachmentKey);
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('attachments', $fileName, 'public');
                    $assignment->attachment = $filePath;
                }
                
                $assignment->save();
            }
        }

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully!');
    }

    public function edit(Project $project)
    {
        return Inertia::render('Project/Edit', [
            'project' => $project,
            'clients' => Client::select('id', 'company_name')->get(),
            'employees' => EmployeeSecond::select('id', 'name')->get(),
            'assignments' => Assignment::where('project_id', $project->id)
                ->with('employee:id,name')
                ->get(),
        ]);
    }


    public function update(Request $request, Project $project)
    {
        // Validate basic project data
        $validatedData = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_name' => 'required|string',
            'start_date' => 'required|date',
            'is_approved' => 'boolean',
            'settings' => 'nullable',
        ]);

        // Update project
        $project->client_id = $validatedData['client_id'];
        $project->project_name = $validatedData['project_name'];
        $project->start_date = $validatedData['start_date'];
        $project->is_approved = $validatedData['is_approved'] ?? false;
        
        // Handle settings - if it's a string (JSON), use it directly
        if (isset($validatedData['settings'])) {
            $project->settings = is_array($validatedData['settings'])
                ? json_encode($validatedData['settings'])
                : $validatedData['settings'];
        }
        
        // Save the project
        $project->save();

        // Handle deleted assignments if any
        if ($request->has('deleted_assignments')) {
            $deletedAssignments = json_decode($request->input('deleted_assignments'), true);
            if (is_array($deletedAssignments) && count($deletedAssignments) > 0) {
                Assignment::whereIn('id', $deletedAssignments)->delete();
            }
        }

        // Handle additional items if project is approved
        if ($request->input('is_approved') == '1' && $request->has('additionalItems')) {
            $additionalItems = json_decode($request->input('additionalItems'), true);
            
            foreach ($additionalItems as $item) {
                if (isset($item['assignment_id'])) {
                    // Update existing assignment
                    $assignment = Assignment::find($item['assignment_id']);
                    if ($assignment) {
                        $assignment->employee_id = $item['employee_id'];
                        $assignment->start_date = $item['start_date'];
                        $assignment->end_date = $item['end_date'] ?? null;
                        $assignment->notes = $item['notes'] ?? null;
                        
                        // Handle file upload
                        $attachmentKey = "attachment_" . $item['id'];
                        if ($request->hasFile($attachmentKey)) {
                            // Delete old file if exists
                            if ($assignment->attachment && Storage::disk('public')->exists($assignment->attachment)) {
                                Storage::disk('public')->delete($assignment->attachment);
                            }
                            
                            $file = $request->file($attachmentKey);
                            $fileName = time() . '_' . $file->getClientOriginalName();
                            $filePath = $file->storeAs('attachments', $fileName, 'public');
                            $assignment->attachment = $filePath;
                        }
                        
                        $assignment->save();
                    }
                } else {
                    // Create a new assignment
                    $assignment = new Assignment();
                    $assignment->id = Str::uuid();
                    $assignment->project_id = $project->id;
                    $assignment->employee_id = $item['employee_id'];
                    $assignment->start_date = $item['start_date'];
                    $assignment->end_date = $item['end_date'] ?? null;
                    $assignment->notes = $item['notes'] ?? null;
                    
                    // Handle file upload
                    $attachmentKey = "attachment_" . $item['id'];
                    if ($request->hasFile($attachmentKey)) {
                        $file = $request->file($attachmentKey);
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $filePath = $file->storeAs('attachments', $fileName, 'public');
                        $assignment->attachment = $filePath;
                    }
                    
                    $assignment->save();
                }
            }
        }

        return redirect()->route('projects.index')
            ->with('success', 'Project updated successfully!');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project Deleted successfully!');
    }
}
