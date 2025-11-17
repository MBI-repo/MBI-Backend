<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/jaye-testing', function () {
    return view('jaye-testing');
});
Route::get('/femi-testing', function () {
    return view('femi-testing');
});
