<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{

    public function index()
    {
        return Inertia::render('Employees/Index', [
            'employees' => Employee::all(),
            //'employees' => Employee::select('id', 'name', 'email', 'position', 'salary')->paginate(10), //recupera solo los campos necesarios y paginados
        ]);
    }

    public function create()
    {
        return Inertia::render('Employees/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
        ]);
        Employee::create($request->all());
        return redirect()->route('employees.index');
    }

    public function edit(Employee $employee)
    {
        dd($employee);
        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
        ]);
        $employee->update($request->all());
        return redirect()->route('employees.index');
    }


    public function destroy(Employee $employee)
    {
        $employee->delete();
        return Inertia::render('Employees/Index', [
            'employees' => Employee::all(),
        ]);
    }

}
