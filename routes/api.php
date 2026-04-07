<?php

use App\Http\Controllers\Api\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::post('/applications', [ApplicationController::class, 'store']);
Route::post('/applications/procedure', [StoredProcedureApplicationController::class, 'store']);