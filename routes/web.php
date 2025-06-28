<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;

Route::get('/', function () {
    return view('legacy-index');
});

Route::get('/lb_overlay/{z}/{x}/{y}.png', [MapController::class, 'overlay']);
Route::get('/lb_map/{z}/{x}/{y}.png', [MapController::class, 'map']);

