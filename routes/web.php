<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/loans/{loan}/voucher', function (App\Models\Loan $loan) {
    return response()->file(storage_path('app/public/' . $loan->voucher_path));
})->name('loans.voucher');

Route::get('/categories/{category}/items', [\App\Http\Controllers\CategoryItemsController::class, 'show'])->name('categories.items');
