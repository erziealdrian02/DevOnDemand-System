<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $year = now()->format('y');
        $assignmentCountThisYear = Assignment::whereYear('created_at', now()->year)->count() + 1;
        $orderAssignment = str_pad($assignmentCountThisYear, 4, '0', STR_PAD_LEFT);
        $generatedAssignmentId = "ASS{$year}{$orderAssignment}";

        $data['assignments_id'] = $generatedAssignmentId;
        $data['id'] = Str::uuid();

        // Handle file upload if present
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('attachments', 'public');
            $data['attachment'] = Storage::url($path);
        }

        Assignment::create($data);

        // Increment employee_count in project.settings
        $project = Project::findOrFail($request->project_id);

        $settings = $project->settings ? json_decode($project->settings, true) : [];
        $settings['employee_count'] = isset($settings['employee_count']) ? (int)$settings['employee_count'] + 1 : 1;

        $project->settings = json_encode($settings);
        $project->save();

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

        // Ambil project terkait
        $project = Project::findOrFail($assignment->project_id);

        // Kurangi employee_count jika ada
        if ($project->settings) {
            $settings = json_decode($project->settings, true);
            if (isset($settings['employee_count']) && $settings['employee_count'] > 0) {
                $settings['employee_count'] -= 1;
            }
            $project->settings = json_encode($settings);
            $project->save();
        }

        $assignment->delete();

        return redirect()->route('projects.index')->with('success', 'Assignment has been removed.');
    }

    public function import(Request $request)
    {
        // Validate the file and project_id
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,csv,xls|max:5120', // 5MB max
            'project_id' => 'required|exists:projects,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid file or project ID.',
                'file' => $validator->errors()->get('file'),
                'project_id' => $validator->errors()->get('project_id'),
            ], 422);
        }

        // Get the associated project
        $project = Project::find($request->project_id);
        if (!$project) {
            return response()->json([
                'error' => 'Project not found.',
            ], 404);
        }

        // Process the Excel file
        $file = $request->file('file');
        
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Skip the header row and validate the data structure
            if (count($rows) < 2) {
                return response()->json([
                    'error' => 'The file is empty or contains only the header row.',
                ], 422);
            }
            
            // Validate headers
            $expectedHeaders = ['Employee Name', 'Start Date', 'End Date', 'Notes'];
            $fileHeaders = $rows[0];
            
            // Check if headers match (case-insensitive)
            for ($i = 0; $i < count($expectedHeaders); $i++) {
                if (!isset($fileHeaders[$i]) || strtolower($fileHeaders[$i]) !== strtolower($expectedHeaders[$i])) {
                    return response()->json([
                        'error' => 'The file format is incorrect. Please ensure the columns are in the correct order: Employee Name, Start Date, End Date, Notes.',
                    ], 422);
                }
            }
            
            // Process data rows
            $assignmentsData = [];
            $parsingErrors = [];
            
            // Start from row 1 (skip header row 0)
            for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                
                // Skip empty rows
                if (empty($row[0])) {
                    continue;
                }
                
                // Check if row has enough columns
                if (count($row) < 4) {
                    $parsingErrors[$rowIndex + 1] = ["Row doesn't have enough columns"];
                    continue;
                }
                
                // Row format: [Employee Name, Start Date, End Date, Notes]
                $employeeName = trim($row[0]);
                $startDateStr = trim($row[1]);
                $endDateStr = trim($row[2]);
                $notes = trim($row[3]);
                
                // Validate the required fields
                $rowErrors = [];
                
                if (empty($employeeName)) {
                    $rowErrors[] = "Employee Name is required";
                }
                
                // Find employee by name
                $employee = \App\Models\EmployeeSecond::where('name', $employeeName)->first();
                
                if (!$employee) {
                    $rowErrors[] = "Employee not found: $employeeName";
                }
                
                // Parse and validate start date
                try {
                    // Try to parse the date in format DD/MM/YYYY
                    $startDate = Carbon::createFromFormat('d/m/Y', $startDateStr)->startOfDay();
                } catch (\Exception $e) {
                    try {
                        // Fallback to format MM/DD/YYYY if the first one fails
                        $startDate = Carbon::createFromFormat('m/d/Y', $startDateStr)->startOfDay();
                    } catch (\Exception $e) {
                        $rowErrors[] = "Invalid start date format: $startDateStr. Use DD/MM/YYYY format.";
                        $startDate = null;
                    }
                }
                
                // Parse and validate end date if provided
                $endDate = null;
                if (!empty($endDateStr)) {
                    try {
                        // Try to parse the date in format DD/MM/YYYY
                        $endDate = Carbon::createFromFormat('d/m/Y', $endDateStr)->startOfDay();
                    } catch (\Exception $e) {
                        try {
                            // Fallback to format MM/DD/YYYY if the first one fails
                            $endDate = Carbon::createFromFormat('m/d/Y', $endDateStr)->startOfDay();
                        } catch (\Exception $e) {
                            $rowErrors[] = "Invalid end date format: $endDateStr. Use DD/MM/YYYY format.";
                        }
                    }
                }
                
                // Validate that end date is after start date if both are provided
                if ($startDate && $endDate && $endDate->lt($startDate)) {
                    $rowErrors[] = "End date must be after start date";
                }
                
                // If there are validation errors for this row, add to parsing errors
                if (!empty($rowErrors)) {
                    $parsingErrors[$rowIndex + 1] = $rowErrors;
                    continue;
                }
                
                // Generate a unique assignment ID (format: ASG + YEAR + sequential number)
                $year = now()->format('y');
                $lastAssignment = \App\Models\Assignment::orderBy('assignments_id', 'desc')->first();
                
                $sequentialNumber = '0001';
                if ($lastAssignment) {
                    // Extract the sequential number from the last assignment ID
                    preg_match('/ASG\d{2}(\d{4})/', $lastAssignment->assignments_id, $matches);
                    if (isset($matches[1])) {
                        $sequentialNumber = str_pad((int)$matches[1] + 1, 4, '0', STR_PAD_LEFT);
                    }
                }
                
                $assignmentId = 'ASG' . $year . $sequentialNumber;
                
                // Create assignment data array
                $assignmentsData[] = [
                    'id' => Str::uuid()->toString(),
                    'project_id' => $project->id,
                    'employee_id' => $employee->id,
                    'assignments_id' => $assignmentId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'notes' => $notes,
                    'attachment' => null, // No attachment when importing from Excel
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                ActivityLog::create([
                    'id' => Str::uuid(),
                    'type' => 'Assignment',
                    'action_type' => 'Import',
                    'user_id' => Auth::id(),
                    'log' => json_encode([
                        'project_id' => $project->project_id,
                        'project_name' => $project->project_name,
                        'employee_name' => $employeeName,
                        'assignments_id' => $assignmentId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]),
                ]);
            }
            
            // If there are any parsing errors, return them
            if (!empty($parsingErrors)) {
                return response()->json([
                    'error' => 'There were errors in the file. Please fix them and try again.',
                    'parsing_errors' => $parsingErrors,
                ], 422);
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            try {
                // Insert assignments in batches
                foreach ($assignmentsData as $assignment) {
                    // Check if assignment_id exists (though it should be unique as we generate it)
                    $existingAssignment = \App\Models\Assignment::where('assignments_id', $assignment['assignments_id'])->first();
                    
                    if ($existingAssignment) {
                        // Update existing assignment - increment assignment_id to make it unique
                        preg_match('/ASG\d{2}(\d{4})/', $assignment['assignments_id'], $matches);
                        if (isset($matches[1])) {
                            $sequentialNumber = str_pad((int)$matches[1] + 1, 4, '0', STR_PAD_LEFT);
                            $assignment['assignments_id'] = 'ASG' . now()->format('y') . $sequentialNumber;
                        } else {
                            // Append a unique suffix
                            $assignment['assignments_id'] = $assignment['assignments_id'] . '-' . uniqid();
                        }
                    }
                    
                    // Insert assignment
                    \App\Models\Assignment::create([
                        'id' => $assignment['id'],
                        'project_id' => $assignment['project_id'],
                        'employee_id' => $assignment['employee_id'],
                        'assignments_id' => $assignment['assignments_id'],
                        'start_date' => $assignment['start_date'],
                        'end_date' => $assignment['end_date'],
                        'notes' => $assignment['notes'],
                        'attachment' => null,
                    ]);
                }
                
                // Update employee count in project settings
                $currentSettings = $project->settings ? (is_array($project->settings) ? $project->settings : json_decode($project->settings, true)) : [];
                $assignmentCount = \App\Models\Assignment::where('project_id', $project->id)->count();
                $currentSettings['employee_count'] = $assignmentCount;
                $project->settings = $currentSettings;
                $project->save();
                
                // Commit the transaction
                DB::commit();
                
                // Return success response
                return redirect()->back()
                    ->with('success', 'Assignments imported successfully!');
                
            } catch (\Exception $e) {
                // Rollback the transaction if any error occurs
                DB::rollBack();
                
                return response()->json([
                    'error' => 'An error occurred while importing assignments: ' . $e->getMessage(),
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process the file: ' . $e->getMessage(),
            ], 500);
        }
    }
}
