<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Exports\ClientsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClientController extends Controller
{
    public function index()
    {
        // dd(Client::all());
        return Inertia::render('Client/Index', [
            'clients' => Client::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Client/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:clients',
            'company_name' => 'required',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $data['id'] = Str::uuid();
        $client = Client::create($data);
        
        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Client',
            'action_type' => 'Create',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $client->toArray(),
        ]);

        return redirect()->route('client.index')
            ->with('success', 'Client created successfully!');
    }


    public function edit(Client $client)
    {
        // dd($client);
        return Inertia::render('Client/Edit', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'sometimes|required',
            'email' => 'sometimes|required|email',
            'company_name' => 'sometimes|required',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $client->update($data);
        
        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Client',
            'action_type' => 'Update',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $client->toArray(),
        ]);

        return redirect()->route('client.index')
            ->with('success', 'Client Edited successfully!');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        ActivityLog::create([
            'id' => Str::uuid(),
            'type' => 'Client',
            'action_type' => 'Delete',
            'user_id' => Auth::id(), // pastikan user login
            'log' => $client->toArray(),
        ]);

        return redirect()->route('client.index')
            ->with('success', 'Client Deteled successfully!');
    }

    public function export()
    {
        return Excel::download(new ClientsExport, 'clients.xlsx');
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
            $expectedHeaders = ['Name', 'Email', 'Company Name', 'NPWP', 'Bidang', 'Status'];
            $fileHeaders = $rows[0];
            
            // Check if headers match (case-insensitive)
            for ($i = 0; $i < count($expectedHeaders); $i++) {
                if (!isset($fileHeaders[$i]) || strtolower($fileHeaders[$i]) !== strtolower($expectedHeaders[$i])) {
                    return response()->json([
                        'error' => 'The file format is incorrect. Please ensure the columns are in the correct order: Name, Email, Company Name, NPWP, Bidang, Status.',
                    ], 422);
                }
            }
            
            // Process data rows
            $clientsData = [];
            $parsingErrors = [];
            
            // Start from row 1 (skip header row 0)
            for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                
                // Skip empty rows
                if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                    continue;
                }
                
                // Check if row has enough columns
                if (count($row) < 6) {
                    $parsingErrors[$rowIndex + 1] = ["Row doesn't have enough columns"];
                    continue;
                }
                
                // Row format: [Name, Email, Company Name, NPWP, Bidang, Status]
                $name = trim($row[0]);
                $email = trim($row[1]);
                $companyName = trim($row[2]);
                $npwp = trim($row[3]);
                $bidang = trim($row[4]);
                $status = trim($row[5]);
                
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
                
                if (empty($companyName)) {
                    $rowErrors[] = "Company Name is required";
                }
                
                // Normalize status to boolean
                $isActive = true;
                if (!empty($status)) {
                    $isActive = strtolower($status) === 'active';
                }
                
                // If there are validation errors for this row, add to parsing errors
                if (!empty($rowErrors)) {
                    $parsingErrors[$rowIndex + 1] = $rowErrors;
                    continue;
                }
                
                // Create metadata as a proper PHP array (not JSON string)
                $metadata = [
                    'npwp' => $npwp,
                    'industri' => $bidang,
                ];
                
                // Create client data array
                $clientsData[] = [
                    'id' => Str::uuid()->toString(),
                    'name' => $name,
                    'email' => $email,
                    'company_name' => $companyName,
                    'metadata' => $metadata, // Store as PHP array, not JSON string
                    'is_active' => $isActive,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                ActivityLog::create([
                    'id' => Str::uuid(),
                    'type' => 'Client',
                    'action_type' => 'Import',
                    'user_id' => Auth::id(), // pastikan user login
                    'log' => $clientsData,
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
                // Insert clients in batches
                foreach ($clientsData as $client) {
                    // Check if email exists
                    $existingClient = Client::where('email', $client['email'])->first();
                    
                    if ($existingClient) {
                        // Update existing client
                        $existingClient->name = $client['name'];
                        $existingClient->company_name = $client['company_name'];
                        $existingClient->metadata = $client['metadata']; // PHP array, Laravel will handle JSON conversion
                        $existingClient->is_active = $client['is_active'];
                        $existingClient->save();
                    } else {
                        // Insert new client
                        Client::create([
                            'id' => $client['id'],
                            'name' => $client['name'],
                            'email' => $client['email'],
                            'company_name' => $client['company_name'],
                            'metadata' => $client['metadata'], // PHP array, Laravel will handle JSON conversion
                            'is_active' => $client['is_active'],
                        ]);
                    }
                }
                
                // Commit the transaction
                DB::commit();
                
                // Return success response
                return redirect()->route('client.index')
                    ->with('success', 'Client created successfully!');
                
            } catch (\Exception $e) {
                // Rollback the transaction if any error occurs
                DB::rollBack();
                
                return response()->json([
                    'error' => 'An error occurred while importing clients: ' . $e->getMessage(),
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process the file: ' . $e->getMessage(),
            ], 500);
        }
    }

}
