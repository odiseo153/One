<?php

use App\Modules\Province\Adapters\Controllers\ProvinceController;
use Illuminate\Support\Facades\Route;

Route::apiResource('provinces', ProvinceController::class);
