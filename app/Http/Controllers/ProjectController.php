<?php

namespace App\Http\Controllers;

use App\Exports\ProjectSecondExport;
use App\Models\Assignment;
use App\Models\Project;
use App\Models\Client;
use App\Models\EmployeeSecond;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

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

        $client = Client::findOrFail($validatedData['client_id']);
        $companyInitial = collect(explode(' ', strtoupper($client->company_name)))
            ->take(2)
            ->map(fn($word) => substr($word, 0, 1))
            ->implode('');
        $year = now()->format('y');
        $projectCountThisYear = Project::whereYear('created_at', now()->year)->count() + 1;
        $assignmentCountThisYear = Assignment::whereYear('created_at', now()->year)->count() + 1;
        $order = str_pad($projectCountThisYear, 4, '0', STR_PAD_LEFT);
        $orderAssignment = str_pad($assignmentCountThisYear, 4, '0', STR_PAD_LEFT);
        $generatedProjectId = "PR{$year}{$order}{$companyInitial}";
        $generatedAssignmentId = "ASS{$year}{$orderAssignment}";

        // Create a new Project, not Assignment
        $project = new Project();
        $projectId = Str::uuid();
        $project->id = $projectId;
        $project->project_id = $generatedProjectId;
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

        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Project',
            'action_type' => 'Create',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $project->toArray(),
        ]);

        // Handle additional items if project is approved
        if ($request->input('is_approved') == '1' && $request->has('additionalItems')) {
            $additionalItems = json_decode($request->input('additionalItems'), true);
            
            foreach ($additionalItems as $item) {
                // Create a new assignment
                $assignment = new Assignment();
                $assignment->id = Str::uuid();
                $assignment->project_id = $projectId; // Use the saved project's ID
                $assignment->assignments_id = $generatedAssignmentId; // Use the saved project's ID
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

                ActivityLog::create([
                    'id' => Str::uuid(),
                    'type' => 'Assignment',
                    'action_type' => 'Create',
                    'user_id' => Auth::id(), // pastikan user login
                    'log' => $project->toArray(),
                ]);
            }
        }

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully!');
    }

    public function edit(Project $project)
    {
        // dd($project);
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
        $project->is_approved = $request->input('is_approved') == '1' ? true : false;
        
        // Handle settings - parse it if it's a JSON string
        if ($request->has('settings')) {
            $settings = $request->input('settings');
            $project->settings = is_string($settings) ? $settings : json_encode($settings);
        }
        
        // Save the project
        $project->save();

        return redirect()->route('projects.index')
            ->with('success', 'Project updated successfully!');
    }


    public function destroy(Project $project)
    {
        $project->delete();

        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Project',
            'action_type' => 'Delete',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $project->toArray(),
        ]);

        return redirect()->route('projects.index')
            ->with('success', 'Project Deleted successfully!');
    }

    public function export()
    {
        return Excel::download(new ProjectSecondExport, 'project.xlsx');
    }

    public function import(Request $request)
    {
        // Validate the file
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,csv,xls|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid file. Please upload a valid Excel file (.xlsx, .csv, .xls) under 5MB.',
                'file' => $validator->errors()->get('file'),
            ], 422);
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
            $expectedHeaders = ['Project Name', 'Client Company', 'Start Date', 'Status', 'Lokasi', 'Cost'];
            $fileHeaders = $rows[0];
            
            // Check if headers match (case-insensitive)
            for ($i = 0; $i < count($expectedHeaders); $i++) {
                if (!isset($fileHeaders[$i]) || strtolower($fileHeaders[$i]) !== strtolower($expectedHeaders[$i])) {
                    return response()->json([
                        'error' => 'The file format is incorrect. Please ensure the columns are in the correct order: Project Name, Client Company, Start Date, Status, Lokasi, Cost.',
                    ], 422);
                }
            }
            
            // Process data rows
            $projectsData = [];
            $parsingErrors = [];
            
            // Start from row 1 (skip header row 0)
            for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                
                // Skip empty rows
                if (empty($row[0]) && empty($row[1])) {
                    continue;
                }
                
                // Check if row has enough columns
                if (count($row) < 6) {
                    $parsingErrors[$rowIndex + 1] = ["Row doesn't have enough columns"];
                    continue;
                }
                
                // Row format: [Project Name, Client Company, Start Date, Status, Lokasi, Cost]
                $projectName = trim($row[0]);
                $clientCompany = trim($row[1]);
                $startDateStr = trim($row[2]);
                $status = trim($row[3]);
                $lokasi = trim($row[4]);
                $cost = trim($row[5]);
                
                // Validate the required fields
                $rowErrors = [];
                
                if (empty($projectName)) {
                    $rowErrors[] = "Project Name is required";
                }
                
                if (empty($clientCompany)) {
                    $rowErrors[] = "Client Company is required";
                }
                
                // Find client by company name
                $client = Client::where('company_name', $clientCompany)->first();
                
                if (!$client) {
                    $rowErrors[] = "Client Company not found: $clientCompany";
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
                
                // Normalize status to boolean
                $isApproved = strtolower($status) === 'approved';
                
                // Sanitize cost (remove commas and convert to number)
                $sanitizedCost = str_replace(',', '', $cost);
                if (!is_numeric($sanitizedCost)) {
                    $rowErrors[] = "Cost must be a number";
                }
                
                // If there are validation errors for this row, add to parsing errors
                if (!empty($rowErrors)) {
                    $parsingErrors[$rowIndex + 1] = $rowErrors;
                    continue;
                }
                
                // Generate a unique project ID (format: PR + YEAR + sequential number)
                $year = now()->format('y');
                $lastProject = Project::orderBy('project_id', 'desc')->first();
                
                $sequentialNumber = '0001';
                if ($lastProject) {
                    // Extract the sequential number from the last project ID
                    preg_match('/PR\d{2}(\d{4})/', $lastProject->project_id, $matches);
                    if (isset($matches[1])) {
                        $sequentialNumber = str_pad((int)$matches[1] + 1, 4, '0', STR_PAD_LEFT);
                    }
                }
                
                $projectId = 'PR' . $year . $sequentialNumber;
                
                // Create settings as a proper PHP array
                $settings = [
                    'lokasi' => $lokasi,
                    'cost' => $sanitizedCost,
                    'employee_count' => 0 // Default as requested
                ];
                
                // Create project data array
                $projectsData[] = [
                    'id' => Str::uuid()->toString(),
                    'client_id' => $client->id,
                    'project_id' => $projectId,
                    'project_name' => $projectName,
                    'start_date' => $startDate,
                    'is_approved' => $isApproved,
                    'settings' => $settings, // Store as PHP array, Laravel will handle JSON conversion
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                ActivityLog::create([
                    'id' => Str::uuid(),
                    'type' => 'Project',
                    'action_type' => 'Import',
                    'user_id' => Auth::id(), // pastikan user login
                    'log' => json_encode([
                        'project_name' => $projectName,
                        'client_company' => $clientCompany,
                        'project_id' => $projectId,
                        'start_date' => $startDate,
                        'is_approved' => $isApproved,
                        'settings' => $settings
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
                // Insert projects in batches
                foreach ($projectsData as $project) {
                    // Check if project_id exists (though it should be unique as we generate it)
                    $existingProject = Project::where('project_id', $project['project_id'])->first();
                    
                    if ($existingProject) {
                        // Update existing project - increment project_id to make it unique
                        preg_match('/PR\d{2}(\d{4})/', $project['project_id'], $matches);
                        if (isset($matches[1])) {
                            $sequentialNumber = str_pad((int)$matches[1] + 1, 4, '0', STR_PAD_LEFT);
                            $project['project_id'] = 'PR' . now()->format('y') . $sequentialNumber;
                        } else {
                            // Append a unique suffix
                            $project['project_id'] = $project['project_id'] . '-' . uniqid();
                        }
                    }
                    
                    // Insert project
                    Project::create([
                        'id' => $project['id'],
                        'client_id' => $project['client_id'],
                        'project_id' => $project['project_id'],
                        'project_name' => $project['project_name'],
                        'start_date' => $project['start_date'],
                        'is_approved' => $project['is_approved'],
                        'settings' => $project['settings'], // PHP array, Laravel will handle JSON conversion
                    ]);
                }
                
                // Commit the transaction
                DB::commit();
                
                // Return success response
                return redirect()->route('project.index')
                    ->with('success', 'Projects imported successfully!');
                
            } catch (\Exception $e) {
                // Rollback the transaction if any error occurs
                DB::rollBack();
                
                return response()->json([
                    'error' => 'An error occurred while importing projects: ' . $e->getMessage(),
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process the file: ' . $e->getMessage(),
            ], 500);
        }
    }
}
