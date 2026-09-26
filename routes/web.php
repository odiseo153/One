<?php

use App\Modules\Complaint\Adapters\Controllers\ComplaintController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('/quejas', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/quejas', [ComplaintController::class, 'store'])->name('complaints.store');
Route::get('/quejas/track', [ComplaintController::class, 'track'])->name('complaints.track');
Route::get('/quejas/{tracking_code}', [ComplaintController::class, 'show'])->name('complaints.show')
    ->where('tracking_code', '[A-Za-z0-9-]+');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
