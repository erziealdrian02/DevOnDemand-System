<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSecond;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
        EmployeeSecond::create($data);

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

        return redirect()->route('employeeSec.index')
            ->with('success', 'Employee created successfully!');
    }

    public function destroy(EmployeeSecond $employeeSec)
    {
        $employeeSec->delete();
        
        return redirect()->route('employeeSec.index')
            ->with('success', 'Employee created successfully!');
    }
}
