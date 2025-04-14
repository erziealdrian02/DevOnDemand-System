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
}
