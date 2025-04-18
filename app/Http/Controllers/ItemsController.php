<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
  /**
   * Display a specific item.
   *
   * @param  \App\Models\Item  $item
   * @return \Illuminate\View\View
   */
  public function show(Item $item)
  {
    // Load relationships that might be needed for the view
    $item->load(['category', 'loans' => function ($query) {
      $query->whereIn('loans.status', ['active', 'pending', 'overdue'])
        ->with('user');
    }]);

    return view('items.show', compact('item'));
  }
}
