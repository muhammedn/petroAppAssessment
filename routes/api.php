<?php

use App\Http\Controllers\TransferEventController;
use Illuminate\Support\Facades\Route;

Route::post('/transfers', [TransferEventController::class, 'store']);
Route::get('/stations/{station_id}/summary', [TransferEventController::class, 'summary']);