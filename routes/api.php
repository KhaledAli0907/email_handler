<?php

use App\Http\Controllers\Api\EmailController;
use Illuminate\Support\Facades\Route;

Route::get('emails', [EmailController::class, 'index']);
Route::get('/emails/{id}', [EmailController::class, 'show']);

Route::post('/send-email', [EmailController::class, 'sendEmail']);
Route::post('/reply-email/{id}', [EmailController::class, 'replyEmail']);
Route::post('/forward-email/{id}', [EmailController::class, 'forwardEmail']);

