<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index()
    {
        // dd(Client::all());
        return Inertia::render('ActivityLog/Index', [
            'activity' => ActivityLog::with('user:id,name')->get(),
        ]);
    }
}
