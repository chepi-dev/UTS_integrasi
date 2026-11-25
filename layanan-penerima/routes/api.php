<?php

use App\Http\Controllers\JurnalPembukuanController;
use App\Http\Middleware\VerifyPemilikToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(VerifyPemilikToken::class)->group(function () {
    Route::get('/pembukuan', [JurnalPembukuanController::class, 'index']);
});