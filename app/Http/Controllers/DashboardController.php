<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Client;
use App\Models\EmployeeSecond;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $users = User::with('role')->latest()->get();
        $roles = Role::all();
        $clients = Client::all();
        $employees = EmployeeSecond::all();
        $assignments = Assignment::all();
        $projects = Project::all();

        return Inertia::render('Dashboard', [
            'users' => $users,
            'roles' => $roles,
            'clients' => $clients,
            'employees' => $employees,
            'assignments' => $assignments,
            'projects' => $projects,
        ]);
    }
}
