<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/reset-password/{token}', function ($token, Request $request) {
    return 'Reset token: ' . $token . ' for ' . $request->query('email');
})->name('password.reset');

Route::get('/jaye-testing', function () {
    return view('jaye-testing');
});
Route::get('/femi-testing', function () {
    return view('femi-testing');
});
