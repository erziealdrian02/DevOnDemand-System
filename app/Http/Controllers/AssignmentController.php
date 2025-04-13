<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AssignmentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'employee_id' => 'required|exists:employee_seconds,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max file size
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['id'] = Str::uuid();
        
        // Handle file upload if present
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            $data['attachment'] = Storage::url($path);
        }

        Assignment::create($data);

        return redirect()->route('projects.index')->with('success', 'Assignment created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employee_seconds,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max file size
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Handle file upload if present
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($assignment->attachment) {
                $oldPath = str_replace('/storage/', '', $assignment->attachment);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            $data['attachment'] = Storage::url($path);
        }

        $assignment->update($data);

        return redirect()->route('projects.index')->with('success', 'Assignment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assignment $assignment)
    {
        // Delete attachment if exists
        if ($assignment->attachment) {
            $path = str_replace('/storage/', '', $assignment->attachment);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $assignment->delete();

        return redirect()->route('projects.index')->with('success', 'Assignment has been removed.');
    }
}
