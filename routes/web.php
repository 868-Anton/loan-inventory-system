<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\CategoryItemsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/loans/{loan}/voucher', function (App\Models\Loan $loan) {
    return response()->file(storage_path('app/public/' . $loan->voucher_path));
})->name('loans.voucher');

Route::get('/categories/{category}/items', [CategoryItemsController::class, 'show'])->name('categories.items');

// RESTful route for showing item details
Route::get('/items/{item}', [ItemsController::class, 'show'])->name('items.show');

// Add a new route to redirect to loan creation with prefilled item
Route::get('/loan-item/{item}', function (App\Models\Item $item) {
    return redirect()->to(
        route('filament.admin.resources.loans.create') .
            '?prefill[items][0][item_id]=' . $item->id .
            '&prefill[items][0][quantity]=1'
    );
})->name('loan.item');
