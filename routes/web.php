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

    Route::get('/language/{locale}', [LanguageController::class, 'switch'])
        ->name('language.switch');
});

Route::middleware(['auth', 'setLocale'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::livewire('/dashboard', 'dashboard.index')
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Stock Fuel
    |--------------------------------------------------------------------------
    */
    Route::livewire('/stock-fuel', 'stock-fuel.index')
        ->middleware('permission:stock_fuel.view')
        ->name('stock-fuel.index');

    Route::livewire('/stock-fuel/create', 'stock-fuel.create')
        ->middleware('permission:stock_fuel.create')
        ->name('stock-fuel.create');

    Route::livewire('/stock-fuel/history', 'stock-fuel.history')
        ->middleware('permission:stock_fuel.history')
        ->name('stock-fuel.history');

    Route::livewire('/stock-fuel/{fuelStock}/edit', 'stock-fuel.edit')
        ->middleware('permission:stock_fuel.edit')
        ->name('stock-fuel.edit');

    /*
    |--------------------------------------------------------------------------
    | Farm Work Logs
    |--------------------------------------------------------------------------
    */
    Route::livewire('/farm-work-logs', 'farm-work-logs.index')
        ->middleware('permission:work_logs.view')
        ->name('farm-work-logs.index');

    Route::livewire('/farm-work-logs/create', 'farm-work-logs.create')
        ->middleware('permission:work_logs.create')
        ->name('farm-work-logs.create');

    Route::livewire('/farm-work-logs/{farmWorkLog}/edit', 'farm-work-logs.edit')
        ->middleware('permission:work_logs.edit')
        ->name('farm-work-logs.edit');

    Route::livewire('/farm-work-logs/{farmWorkLog}/map', 'farm-work-logs.map')
        ->middleware('permission:work_logs.map')
        ->name('farm-work-logs.map');

    Route::get('/farm-work-logs/export/csv', [FarmWorkLogExportController::class, 'csv'])
        ->middleware('permission:work_logs.export')
        ->name('farm-work-logs.export.csv');

    Route::get('/farm-work-logs/export/excel', [FarmWorkLogExportController::class, 'excel'])
        ->middleware('permission:work_logs.export')
        ->name('farm-work-logs.export.excel');

    /*
    |--------------------------------------------------------------------------
    | Driver GPS + Work Actions
    |--------------------------------------------------------------------------
    */
    Route::post('/driver-gps-tracks', [DriverGpsTrackController::class, 'store'])
        ->middleware('permission:work_logs.map')
        ->name('driver-gps-tracks.store');

    Route::post('/driver-work-actions', [DriverWorkActionController::class, 'store'])
        ->middleware('permission:work_logs.map')
        ->name('driver-work-actions.store');

    /*
    |--------------------------------------------------------------------------
    | Tractors
    |--------------------------------------------------------------------------
    */
    Route::livewire('/tractors', 'tractors.index')
        ->middleware('permission:tractors.view')
        ->name('tractors.index');

    Route::livewire('/tractors/create', 'tractors.create')
        ->middleware('permission:tractors.create')
        ->name('tractors.create');

    Route::livewire('/tractors/{tractor}/edit', 'tractors.edit')
        ->middleware('permission:tractors.edit')
        ->name('tractors.edit');

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */
    Route::livewire('/drivers', 'drivers.index')
        ->middleware('permission:drivers.view')
        ->name('drivers.index');

    Route::livewire('/drivers/create', 'drivers.create')
        ->middleware('permission:drivers.create')
        ->name('drivers.create');

    Route::livewire('/drivers/{driver}/edit', 'drivers.edit')
        ->middleware('permission:drivers.edit')
        ->name('drivers.edit');

    /*
    |--------------------------------------------------------------------------
    | Zones
    |--------------------------------------------------------------------------
    */
    Route::livewire('/zones', 'zones.index')
        ->middleware('permission:zones.view')
        ->name('zones.index');

    Route::livewire('/zones/create', 'zones.create')
        ->middleware('permission:zones.create')
        ->name('zones.create');

    Route::livewire('/zones/{zone}/edit', 'zones.edit')
        ->middleware('permission:zones.edit')
        ->name('zones.edit');

    Route::livewire('/zones/{zone}/map', 'zones.map')
        ->middleware('permission:zones.map')
        ->name('zones.map');

    /*
    |--------------------------------------------------------------------------
    | Task Categories
    |--------------------------------------------------------------------------
    */
    Route::livewire('/task-categories', 'task-categories.index')
        ->middleware('permission:task_categories.view')
        ->name('task-categories.index');

    Route::livewire('/task-categories/create', 'task-categories.create')
        ->middleware('permission:task_categories.create')
        ->name('task-categories.create');

    Route::livewire('/task-categories/{taskCategory}/edit', 'task-categories.edit')
        ->middleware('permission:task_categories.edit')
        ->name('task-categories.edit');

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::livewire('/reports/fuel', 'reports.fuel-report')
        ->middleware('permission:reports.fuel')
        ->name('reports.fuel');

    Route::livewire('/reports/tractors', 'reports.tractor-report')
        ->middleware('permission:reports.tractors')
        ->name('reports.tractors');

    Route::livewire('/reports/drivers', 'reports.driver-report')
        ->middleware('permission:reports.drivers')
        ->name('reports.drivers');

    Route::livewire('/reports/zones', 'reports.zone-report')
        ->middleware('permission:reports.zones')
        ->name('reports.zones');

    Route::livewire('/reports/task-category-summary', 'reports.task-category-summary')
        ->middleware('permission:reports.task_category_summary')
        ->name('reports.task-category-summary');

    Route::get('/reports/task-category-summary/export/excel', [TaskCategorySummaryReportController::class, 'exportExcel'])
        ->middleware('permission:reports.export')
        ->name('reports.task-category-summary.export.excel');

    Route::get('/reports/task-category-summary/export/csv', [TaskCategorySummaryReportController::class, 'exportCsv'])
        ->middleware('permission:reports.export')
        ->name('reports.task-category-summary.export.csv');

    /*
    |--------------------------------------------------------------------------
    | Sidebar Settings
    |--------------------------------------------------------------------------
    */
    Route::livewire('/sidebar-settings', 'settings.sidebar-settings')
        ->middleware('permission:sidebar_settings.view')
        ->name('sidebar-settings.index');

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    Route::livewire('/users', 'users.index')
        ->middleware('permission:users.view')
        ->name('users.index');

    Route::livewire('/users/create', 'users.create')
        ->middleware('permission:users.create')
        ->name('users.create');

    Route::livewire('/users/{user}/edit', 'users.edit')
        ->middleware('permission:users.edit')
        ->name('users.edit');

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    Route::livewire('/roles', 'roles.index')
        ->middleware('permission:roles.view')
        ->name('roles.index');

    Route::livewire('/roles/create', 'roles.create')
        ->middleware('permission:roles.create')
        ->name('roles.create');

    Route::livewire('/roles/{role}/edit', 'roles.edit')
        ->middleware('permission:roles.edit')
        ->name('roles.edit');
});

require __DIR__.'/auth.php';