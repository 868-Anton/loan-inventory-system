<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
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
            // Filter borrowed items - include both:
            // 1. Items whose status is explicitly "borrowed"
            // 2. Items that have active loans in the loan_items pivot table
            $query->where(function ($query) {
                $query->where('status', 'borrowed')
                    ->orWhereHas('loans', function ($loanQuery) {
                        $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned']);
                    });
            });
        } elseif ($filter === 'available') {
            // Filter only truly available items (status is available AND not in any active loan)
            $query->where(function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['available'])
                    ->whereDoesntHave('loans', function ($loanQuery) {
                        $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned']);
                    });
            });
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

    /**
     * Display detailed information for a specific item.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewItem(Item $item)
    {
        // Load relationships that might be needed for the view
        $item->load(['category', 'loans' => function ($query) {
            $query->whereIn('status', ['active', 'pending', 'overdue'])
                ->with('user');
        }]);

        // Return item data as JSON
        return response()->json([
            'item' => $item,
            'activeLoans' => $item->loans->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'loan_number' => $loan->loan_number,
                    'borrower' => $loan->getBorrowerName(),
                    'status' => $loan->status,
                    'due_date' => $loan->due_date ? $loan->due_date->format('Y-m-d') : null,
                ];
            }),
            'html' => view('items.modal', compact('item'))->render()
        ]);
    }
}
