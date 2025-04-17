<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryItemsController extends Controller
{
    /**
     * Display the items in a specific category.
     *
     * @param  \App\Models\Category  $category
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function show(Category $category, Request $request)
    {
        // Get filter parameter
        $filter = $request->query('filter');

        // Set up the base query
        $query = $category->items();

        // Apply filter if specified
        if ($filter === 'borrowed') {
            $query->where(function ($query) {
                $query->where('status', 'borrowed')
                    ->orWhereHas('loans', function ($loanQuery) {
                        $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                            ->whereRaw('loan_items.status = "loaned"');
                    });
            });
        } elseif ($filter === 'available') {
            // Filter only available items
            $query->where('status', 'available');
        }

        // Get paginated results with optimized query
        $items = $query->paginate(10);

        // Pass filter to view for UI adjustments
        return view('categories.items', [
            'category' => $category,
            'items' => $items,
            'filter' => $filter,
        ]);
    }
}
