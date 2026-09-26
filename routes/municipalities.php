<?php

use App\Modules\Municipality\Adapters\Controllers\MunicipalityController;
use Illuminate\Support\Facades\Route;

Route::apiResource('municipalities', MunicipalityController::class);
