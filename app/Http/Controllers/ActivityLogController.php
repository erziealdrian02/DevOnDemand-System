<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index()
    {
        // dd(Client::all());
        return Inertia::render('ActivityLog/Index', [
            'activity' => ActivityLog::with('user:id,name')->get(),
            'clients' => Client::with('projects:id,project_name')->get(),
            'assignments' => Assignment::with('employee:id,name')->get(),
        ]);
    }
}
