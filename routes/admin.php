<?php

use App\Http\Controllers\Admin\MunicipalityController;
use App\Http\Controllers\Admin\ProjectReportController;
use App\Http\Controllers\Admin\RegisteredBusinessController;
use App\Http\Controllers\Admin\SectorController;
use App\Http\Controllers\Admin\SectorMapController;
use App\Http\Controllers\Admin\UserController;
use App\Modules\Complaint\Adapters\Controllers\Admin\ComplaintController;
use App\Modules\Project\Adapters\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::patch('complaints/{complaint}/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');
    Route::patch('complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('complaints.status');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/reports', [ProjectReportController::class, 'index'])->name('projects.reports.index');
    Route::get('projects/reports/export', [ProjectReportController::class, 'export'])->name('projects.reports.export');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}/updates/create', [ProjectController::class, 'updateCreate'])->name('projects.updates.create');
    Route::post('projects/{project}/updates', [ProjectController::class, 'storeUpdate'])->name('projects.updates.store');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::match(['put', 'patch'], 'projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
    Route::post('projects/{project}/users', [ProjectController::class, 'storeAssignment'])->name('projects.assign');
    Route::delete('projects/{project}/users/{projectUser}', [ProjectController::class, 'destroyAssignment'])->name('projects.unassign');
    Route::post('projects/{project}/milestones', [ProjectController::class, 'storeMilestone'])->name('projects.milestones.store');
    Route::patch('projects/{project}/milestones/{milestone}/status', [ProjectController::class, 'updateMilestoneStatus'])->name('projects.milestones.status');
    Route::delete('projects/{project}/milestones/{milestone}', [ProjectController::class, 'destroyMilestone'])->name('projects.milestones.destroy');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('municipalities', [MunicipalityController::class, 'index'])->name('municipalities.index');
    Route::post('municipalities', [MunicipalityController::class, 'store'])->name('municipalities.store');
    Route::match(['put', 'patch'], 'municipalities/{municipality}', [MunicipalityController::class, 'update'])->name('municipalities.update');
    Route::delete('municipalities/{municipality}', [MunicipalityController::class, 'destroy'])->name('municipalities.destroy');

    Route::get('sectors', [SectorController::class, 'index'])->name('sectors.index');
    Route::post('sectors', [SectorController::class, 'store'])->name('sectors.store');
    Route::match(['put', 'patch'], 'sectors/{sector}', [SectorController::class, 'update'])->name('sectors.update');
    Route::delete('sectors/{sector}', [SectorController::class, 'destroy'])->name('sectors.destroy');

    Route::get('registered-businesses', [RegisteredBusinessController::class, 'index'])->name('registered-businesses.index');

    Route::get('sector-map', [SectorMapController::class, 'index'])->name('sector-map.index');
    Route::get('business-categories', [SectorMapController::class, 'categories'])->name('business-categories.index');
    Route::get('sector-map/businesses', [SectorMapController::class, 'businesses'])->name('sector-map.businesses');
    Route::get('sector-map/places', [SectorMapController::class, 'places'])->name('sector-map.places');
    Route::post('sector-map/businesses', [SectorMapController::class, 'store'])->name('sector-map.store');
    Route::match(['put', 'patch'], 'sector-map/businesses/{business}', [SectorMapController::class, 'update'])->name('sector-map.update');
    Route::patch('sector-map/businesses/{business}/status', [SectorMapController::class, 'updateStatus'])->name('sector-map.update-status');
});
