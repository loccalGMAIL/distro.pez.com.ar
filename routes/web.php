<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard/login')->name('home');
