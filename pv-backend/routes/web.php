<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation (HTML view)
Route::get('/docs', function () {
    return view('api-docs-v2');
});
