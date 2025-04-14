<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeSecondsExport;
use App\Models\EmployeeSecond;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeSeconController extends Controller
{
    public function index()
    {
        return Inertia::render('EmployeeSecond/Index', [
            'employees' => EmployeeSecond::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('EmployeeSecond/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:employees',
            'phone' => 'nullable|string',
            'skillset' => 'nullable|array',
            'is_available' => 'boolean',
        ]);

        $data['id'] = Str::uuid();
        $employeeSec = EmployeeSecond::create($data);

        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Employee',
            'action_type' => 'Create',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $employeeSec->toArray(),
        ]);

        return redirect()->route('employeeSec.index')
            ->with('success', 'Employee created successfully!');
    }

    public function edit(EmployeeSecond $employeeSec)
    {
        // dd($employeeSec);
        return Inertia::render('EmployeeSecond/Edit', [
            'employee' => $employeeSec,
        ]);
    }

    public function update(Request $request, EmployeeSecond $employeeSec)
    {
        $data = $request->validate([
            'name' => 'sometimes|required',
            'email' => 'sometimes|required|email',
            'phone' => 'nullable|string',
            'skillset' => 'nullable|array',
            'is_available' => 'boolean',
        ]);

        $employeeSec->update($data);

        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Employee',
            'action_type' => 'Update',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $employeeSec->toArray(),
        ]);

        return redirect()->route('employeeSec.index')
            ->with('success', 'Employee created successfully!');
    }

    public function destroy(EmployeeSecond $employeeSec)
    {
        $employeeSec->delete();

        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Employee',
            'action_type' => 'Delete',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $employeeSec->toArray(),
        ]);
        
        return redirect()->route('employeeSec.index')
            ->with('success', 'Employee created successfully!');
    }

    public function export()
    {
        return Excel::download(new EmployeeSecondsExport, 'Employee.xlsx');
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
            $expectedHeaders = ['No', 'Name', 'Email', 'Phone', 'Skillset', 'Availability'];
            $fileHeaders = $rows[0];
            
            // Check if headers match (case-insensitive)
            for ($i = 0; $i < count($expectedHeaders); $i++) {
                if (!isset($fileHeaders[$i]) || strtolower($fileHeaders[$i]) !== strtolower($expectedHeaders[$i])) {
                    return response()->json([
                        'error' => 'The file format is incorrect. Please ensure the columns are in the correct order: No, Name, Email, Phone, Skillset, Availability.',
                    ], 422);
                }
            }
            
            // Process data rows
            $employeesData = [];
            $parsingErrors = [];
            
            // Start from row 1 (skip header row 0)
            for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                
                // Skip empty rows
                if (empty($row[1]) && empty($row[2]) && empty($row[3])) {
                    continue;
                }
                
                // Check if row has enough columns
                if (count($row) < 6) {
                    $parsingErrors[$rowIndex + 1] = ["Row doesn't have enough columns"];
                    continue;
                }
                
                // Row format: [No, Name, Email, Phone, Skillset, Availability]
                $name = trim($row[1]);
                $email = trim($row[2]);
                $phone = trim($row[3]);
                $skillset = trim($row[4]);
                $availability = trim($row[5]);
                
                // Validate the required fields
                $rowErrors = [];
                
                if (empty($name)) {
                    $rowErrors[] = "Name is required";
                }
                
                if (empty($email)) {
                    $rowErrors[] = "Email is required";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Email is invalid";
                }
            
                // Normalize availability to boolean
                $isAvailable = true;
                if (!empty($availability)) {
                    $isAvailable = strtolower($availability) === 'available';
                }
                
                // If there are validation errors for this row, add to parsing errors
                if (!empty($rowErrors)) {
                    $parsingErrors[$rowIndex + 1] = $rowErrors;
                    continue;
                }
                
                // Convert skillset string to array
                $skillsetArray = [];
                if (!empty($skillset)) {
                    $skillsetArray = array_map('trim', explode(',', $skillset));
                }
                
                // Create employee data array
                $employeesData[] = [
                    'id' => Str::uuid()->toString(),
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'skillset' => $skillsetArray, // Store as PHP array, Laravel will handle JSON conversion
                    'is_available' => $isAvailable,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                ActivityLog::create([
                    'id' => Str::uuid(),
                    'type' => 'Client',
                    'action_type' => 'Import',
                    'user_id' => Auth::id(), // pastikan user login
                    'log' => $employeesData,
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
                // Insert employees in batches
                foreach ($employeesData as $employee) {
                    // Check if email exists
                    $existingEmployee = EmployeeSecond::where('email', $employee['email'])->first();
                    
                    if ($existingEmployee) {
                        // Update existing employee
                        $existingEmployee->name = $employee['name'];
                        $existingEmployee->phone = $employee['phone'];
                        $existingEmployee->skillset = $employee['skillset']; // PHP array, Laravel will handle JSON conversion
                        $existingEmployee->is_available = $employee['is_available'];
                        $existingEmployee->save();
                    } else {
                        // Insert new employee
                        EmployeeSecond::create([
                            'id' => $employee['id'],
                            'name' => $employee['name'],
                            'email' => $employee['email'],
                            'phone' => $employee['phone'],
                            'skillset' => $employee['skillset'], // PHP array, Laravel will handle JSON conversion
                            'is_available' => $employee['is_available'],
                        ]);
                    }
                }
                
                // Commit the transaction
                DB::commit();
                
                // Return success response
                return redirect()->route('employeeSec.index')
                    ->with('success', 'Employees imported successfully!');
                
            } catch (\Exception $e) {
                // Rollback the transaction if any error occurs
                DB::rollBack();
                
                return response()->json([
                    'error' => 'An error occurred while importing employees: ' . $e->getMessage(),
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process the file: ' . $e->getMessage(),
            ], 500);
        }
    }
}
