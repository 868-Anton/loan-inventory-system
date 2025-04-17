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
     * @return \Illuminate\View\View
     */
    public function show(Category $category)
    {
        // Eager load the items relationship
        $category->load('items');

        return view('categories.items', [
            'category' => $category,
            'items' => $category->items()->paginate(10), // Paginate for better performance
        ]);
    }
}
