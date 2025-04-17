<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/loans/{loan}/voucher', function (App\Models\Loan $loan) {
    return response()->file(storage_path('app/public/' . $loan->voucher_path));
})->name('loans.voucher');

Route::get('/categories/{category}/items', [\App\Http\Controllers\CategoryItemsController::class, 'show'])->name('categories.items');

// Add a new route to redirect to loan creation with prefilled item
Route::get('/loan-item/{item}', function (App\Models\Item $item) {
    return redirect()->to(
        route('filament.admin.resources.loans.create') .
            '?prefill[items][0][item_id]=' . $item->id .
            '&prefill[items][0][quantity]=1'
    );
})->name('loan.item');
