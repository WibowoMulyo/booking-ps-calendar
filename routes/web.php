<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('calendar');
});

Route::get('/bookings', [BookingController::class, 'fetchBookings'])->name('bookings.fetch');
Route::post('/checkout', [BookingController::class, 'checkout'])->name('checkout');