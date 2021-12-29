<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Nagad payment
Route::post('/nagad-payment', [App\Http\Controllers\payment\NagadController::class, 'paymentWithNagad'])->name('nagad.payment');
Route::get('/nagad-payment-varify', [App\Http\Controllers\payment\NagadController::class, 'varifyNagadPayment'])->name('nagad.varify');
Route::get('/nagad-payment-success', [App\Http\Controllers\payment\NagadController::class, 'nagadSuccess'])->name('nagad.success');
