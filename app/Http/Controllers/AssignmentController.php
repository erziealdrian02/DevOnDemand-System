<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    public function index()
    {
        return Assignment::with(['project', 'employee'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'attachment' => 'nullable|file|mimes:pdf|max:512', // max in KB
            'notes' => 'nullable|string',
        ]);

        $data['id'] = (string) Str::uuid();

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments');
        }

        return Assignment::create($data);
    }

    public function show(Assignment $assignment)
    {
        return $assignment->load(['project', 'employee']);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $data = $request->validate([
            'project_id' => 'sometimes|required|exists:projects,id',
            'employee_id' => 'sometimes|required|exists:employees,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'attachment' => 'nullable|file|mimes:pdf|max:512',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('attachment')) {
            if ($assignment->attachment) {
                Storage::delete($assignment->attachment);
            }
            $data['attachment'] = $request->file('attachment')->store('attachments');
        }

        $assignment->update($data);
        return $assignment;
    }

    public function destroy(Assignment $assignment)
    {
        if ($assignment->attachment) {
            Storage::delete($assignment->attachment);
        }

        $assignment->delete();
        return response()->noContent();
    }
}
