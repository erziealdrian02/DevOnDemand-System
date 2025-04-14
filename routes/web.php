<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSeconController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::get('/clients', [ClientController::class, 'index'])->name('client.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('client.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('client.store');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('client.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('client.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('client.destroy');
    Route::get('/clients/export', [ClientController::class, 'export'])->name('clients.export');

    Route::get('/employeesSec', [EmployeeSeconController::class, 'index'])->name('employeeSec.index');
    Route::get('/employeesSec/create', [EmployeeSeconController::class, 'create'])->name('employeeSec.create');
    Route::post('/employeesSec', [EmployeeSeconController::class, 'store'])->name('employeeSec.store');
    Route::get('/employeesSec/{employeeSec}/edit', [EmployeeSeconController::class, 'edit'])->name('employeeSec.edit');
    Route::put('/employeesSec/{employeeSec}', [EmployeeSeconController::class, 'update'])->name('employeeSec.update');
    Route::delete('/employeesSec/{employeeSec}', [EmployeeSeconController::class, 'destroy'])->name('employeeSec.destroy');
    Route::get('/employeesSec/export', [EmployeeSeconController::class, 'export'])->name('employeeSec.export');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('/projects/export', [ProjectController::class, 'export'])->name('projects.export');

    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
});

Route::middleware(['auth', 'verified', 'admin.only'])->group(function () {
    Route::get('/userManagement', [UserManagementController::class, 'index'])->name('userManagement.index');
    Route::post('/userManagement', [UserManagementController::class, 'store'])->name('userManagement.store');
    Route::put('/userManagement/{user}', [UserManagementController::class, 'update'])->name('userManagement.update');
    Route::delete('/userManagement/{user}', [UserManagementController::class, 'destroy'])->name('userManagement.destroy');
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
