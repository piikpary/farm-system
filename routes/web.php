<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DriverGpsTrackController;
use App\Http\Controllers\DriverWorkActionController;
use App\Http\Controllers\FarmWorkLogExportController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\TaskCategorySummaryReportController;

Route::middleware(['setLocale'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
});

Route::middleware(['auth', 'setLocale'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::livewire('/dashboard', 'dashboard.index')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Tractors
    |--------------------------------------------------------------------------
    */
    Route::livewire('/tractors', 'tractors.index')
        ->name('tractors.index');

    Route::livewire('/tractors/create', 'tractors.create')
        ->name('tractors.create');

    Route::livewire('/tractors/{tractor}/edit', 'tractors.edit')
        ->name('tractors.edit');

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */
    Route::livewire('/drivers', 'drivers.index')
        ->name('drivers.index');

    Route::livewire('/drivers/create', 'drivers.create')
        ->name('drivers.create');

    Route::livewire('/drivers/{driver}/edit', 'drivers.edit')
        ->name('drivers.edit');

    /*
    |--------------------------------------------------------------------------
    | Zones / Farm Locations
    |--------------------------------------------------------------------------
    */
    Route::livewire('/zones', 'zones.index')
        ->name('zones.index');

    Route::livewire('/zones/create', 'zones.create')
        ->name('zones.create');

    Route::livewire('/zones/{zone}/edit', 'zones.edit')
        ->name('zones.edit');

    Route::livewire('/zones/{zone}/map', 'zones.map')
        ->name('zones.map');

    /*
    |--------------------------------------------------------------------------
    | Task Categories
    |--------------------------------------------------------------------------
    */
    Route::livewire('/task-categories', 'task-categories.index')
        ->name('task-categories.index');

    Route::livewire('/task-categories/create', 'task-categories.create')
        ->name('task-categories.create');

    Route::livewire('/task-categories/{taskCategory}/edit', 'task-categories.edit')
        ->name('task-categories.edit');

    /*
    |--------------------------------------------------------------------------
    | Farm Work Logs
    |--------------------------------------------------------------------------
    */
    Route::livewire('/farm-work-logs', 'farm-work-logs.index')
        ->name('farm-work-logs.index');

    Route::livewire('/farm-work-logs/create', 'farm-work-logs.create')
        ->name('farm-work-logs.create');

    Route::livewire('/farm-work-logs/{farmWorkLog}/edit', 'farm-work-logs.edit')
        ->name('farm-work-logs.edit');

    Route::livewire('/farm-work-logs/{farmWorkLog}/map', 'farm-work-logs.map')
        ->name('farm-work-logs.map');

    /*
    |--------------------------------------------------------------------------
    | GPS Tracking + Driver Work Actions
    |--------------------------------------------------------------------------
    */
    Route::post('/driver-gps-tracks', [DriverGpsTrackController::class, 'store'])
        ->name('driver-gps-tracks.store');

    Route::post('/driver-work-actions', [DriverWorkActionController::class, 'store'])
        ->name('driver-work-actions.store');

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    Route::livewire('/roles', 'roles.index')
        ->name('roles.index');

    Route::livewire('/roles/create', 'roles.create')
        ->name('roles.create');

    Route::livewire('/roles/{role}/edit', 'roles.edit')
        ->name('roles.edit');

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    Route::livewire('/users', 'users.index')
        ->name('users.index');

    Route::livewire('/users/create', 'users.create')
        ->name('users.create');

    Route::livewire('/users/{user}/edit', 'users.edit')
        ->name('users.edit');

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::livewire('/reports/fuel', 'reports.fuel-report')
        ->name('reports.fuel');

    Route::livewire('/reports/tractors', 'reports.tractor-report')
        ->name('reports.tractors');

    Route::livewire('/reports/drivers', 'reports.driver-report')
        ->name('reports.drivers');

    Route::livewire('/reports/zones', 'reports.zone-report')
        ->name('reports.zones');

    // Stock Fuel
    Route::livewire('/stock-fuel', 'stock-fuel.index')
        ->name('stock-fuel.index');

    Route::livewire('/stock-fuel/create', 'stock-fuel.create')
        ->name('stock-fuel.create');

    Route::livewire('/stock-fuel/history', 'stock-fuel.history')
        ->name('stock-fuel.history');
        
    Route::livewire('/stock-fuel/{fuelStock}/edit', 'stock-fuel.edit')
        ->name('stock-fuel.edit');

    Route::livewire('/sidebar-settings', 'settings.sidebar-settings')
        ->name('sidebar-settings.index');

    Route::get('/farm-work-logs/export/csv', [FarmWorkLogExportController::class, 'csv'])
        ->name('farm-work-logs.export.csv');

    Route::get('/farm-work-logs/export/excel', [FarmWorkLogExportController::class, 'excel'])
        ->name('farm-work-logs.export.excel');
    Route::get('/language/{locale}', [LanguageController::class, 'switch'])
        ->name('language.switch');

    Route::get('/reports/task-category-summary', [TaskCategorySummaryReportController::class, 'index'])
        ->name('reports.task-category-summary');

    Route::get('/reports/task-category-summary/export/csv', [TaskCategorySummaryReportController::class, 'exportCsv'])
        ->name('reports.task-category-summary.export.csv');

    Route::get('/reports/task-category-summary/export/excel', [TaskCategorySummaryReportController::class, 'exportExcel'])
    ->name('reports.task-category-summary.export.excel');
});

require __DIR__.'/auth.php';