<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeSeconController;
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

    Route::get('/employeesSec', [EmployeeSeconController::class, 'index'])->name('employeeSec.index');
    Route::get('/employeesSec/create', [EmployeeSeconController::class, 'create'])->name('employeeSec.create');
    Route::post('/employeesSec', [EmployeeSeconController::class, 'store'])->name('employeeSec.store');
    Route::get('/employeesSec/{employeeSec}/edit', [EmployeeSeconController::class, 'edit'])->name('employeeSec.edit');
    Route::put('/employeesSec/{employeeSec}', [EmployeeSeconController::class, 'update'])->name('employeeSec.update');
    Route::delete('/employeesSec/{employeeSec}', [EmployeeSeconController::class, 'destroy'])->name('employeeSec.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
