<?php

use App\Http\Controllers\ApplicationController;
use Illuminate\Support\Facades\Route;

Route::post('/applications', [ApplicationController::class, 'store']);