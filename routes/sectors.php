<?php

use App\Modules\Sector\Adapters\Controllers\SectorController;
use Illuminate\Support\Facades\Route;

Route::apiResource('sectors', SectorController::class);
