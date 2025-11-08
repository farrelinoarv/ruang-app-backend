<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// for testing
Route::get('/test-ci-cd', function () {
    return 'This is a test route!';
});
