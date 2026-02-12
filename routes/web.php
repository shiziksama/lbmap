<?php

use App\Http\Controllers\InterpreterController;
use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('legacy-index');
});

Route::get('/lb_overlay/{z}/{x}/{y}.png', [MapController::class, 'overlay']);
Route::get('/lb_map/{z}/{x}/{y}.png', [MapController::class, 'map']);

Route::match(['post', 'options'], '/interpreter', [InterpreterController::class, 'handle']);
