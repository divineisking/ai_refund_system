<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('CustomerChat');
});

Route::get('/admin', function () {
    return Inertia::render('AdminDashboard');
});
