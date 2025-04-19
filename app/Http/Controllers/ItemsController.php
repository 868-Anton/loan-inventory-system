<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemsController extends Controller
{
  /**
   * Display a listing of items.
   *
   * @return \Illuminate\View\View
   */
  public function index(Request $request)
  {
    $query = Item::query()->with('category');

    // Filter by status if provided
    if ($request->has('status') && $request->status !== 'all') {
      if ($request->status === 'borrowed') {
        // Filter items that have active loans in the loan_items pivot table
        $query->whereHas('loans', function ($loanQuery) {
          $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned']);
        });
      } else if ($request->status === 'available') {
        // Filter only truly available items (status is available AND not in any active loan)
        $query->where(function ($query) {
          $query->whereRaw('LOWER(status) = ?', ['available'])
            ->whereDoesntHave('loans', function ($loanQuery) {
              $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                ->whereRaw('LOWER(loan_items.status) = ?', ['loaned']);
            });
        });
      } else {
        $query->where('status', $request->status);
      }
    }

    // Filter by category if provided
    if ($request->has('category_id') && $request->category_id) {
      $query->where('category_id', $request->category_id);
    }

    // Search by name, serial number, or asset tag
    if ($request->has('search') && $request->search) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('serial_number', 'like', "%{$search}%")
          ->orWhere('asset_tag', 'like', "%{$search}%");
      });
    }

    $items = $query->latest()->paginate(10);
    $categories = Category::all();

    return view('items.index', compact('items', 'categories'));
  }

  /**
   * Show the form for creating a new item.
   *
   * @return \Illuminate\View\View
   */
  public function create()
  {
    $categories = Category::all();
    $statuses = ['available', 'borrowed', 'under_repair', 'lost'];

    return view('items.create', compact('categories', 'statuses'));
  }

  /**
   * Store a newly created item.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
      'serial_number' => 'nullable|string|max:255|unique:items',
      'asset_tag' => 'nullable|string|max:255|unique:items',
      'purchase_date' => 'nullable|date',
      'purchase_cost' => 'nullable|numeric|min:0',
      'warranty_expiry' => 'nullable|date',
      'status' => ['required', Rule::in(['available', 'borrowed', 'under_repair', 'lost'])],
      'total_quantity' => 'required|integer|min:1',
      'category_id' => 'nullable|exists:categories,id',
      'thumbnail' => 'nullable|image|max:2048', // 2MB max
      'custom_attributes' => 'nullable|array',
    ]);

    // Handle file upload if provided
    if ($request->hasFile('thumbnail')) {
      $path = $request->file('thumbnail')->store('thumbnails', 'public');
      $validated['thumbnail'] = $path;
    }

    $item = Item::create($validated);

    return redirect()->route('items.show', $item)
      ->with('success', 'Item created successfully.');
  }

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

  /**
   * Show the form for editing an item.
   *
   * @param  \App\Models\Item  $item
   * @return \Illuminate\View\View
   */
  public function edit(Item $item)
  {
    $categories = Category::all();
    $statuses = ['available', 'borrowed', 'under_repair', 'lost'];

    return view('items.edit', compact('item', 'categories', 'statuses'));
  }

  /**
   * Update the specified item.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\Item  $item
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(Request $request, Item $item)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
      'serial_number' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('items')->ignore($item->id),
      ],
      'asset_tag' => [
        'nullable',
        'string',
        'max:255',
        Rule::unique('items')->ignore($item->id),
      ],
      'purchase_date' => 'nullable|date',
      'purchase_cost' => 'nullable|numeric|min:0',
      'warranty_expiry' => 'nullable|date',
      'status' => ['required', Rule::in(['available', 'borrowed', 'under_repair', 'lost'])],
      'total_quantity' => 'required|integer|min:1',
      'category_id' => 'nullable|exists:categories,id',
      'thumbnail' => 'nullable|image|max:2048', // 2MB max
      'custom_attributes' => 'nullable|array',
    ]);

    // Handle file upload if provided
    if ($request->hasFile('thumbnail')) {
      // Delete old thumbnail if exists
      if ($item->thumbnail) {
        Storage::disk('public')->delete($item->thumbnail);
      }

      $path = $request->file('thumbnail')->store('thumbnails', 'public');
      $validated['thumbnail'] = $path;
    }

    $item->update($validated);

    return redirect()->route('items.show', $item)
      ->with('success', 'Item updated successfully.');
  }

  /**
   * Remove the specified item.
   *
   * @param  \App\Models\Item  $item
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy(Item $item)
  {
    // Check if item is currently loaned
    $isLoaned = $item->loans()
      ->whereIn('loans.status', ['active', 'pending', 'overdue'])
      ->exists();

    if ($isLoaned) {
      return back()->with('error', 'Cannot delete item that is currently loaned out.');
    }

    // Delete thumbnail if exists
    if ($item->thumbnail) {
      Storage::disk('public')->delete($item->thumbnail);
    }

    $item->delete();

    return redirect()->route('items.index')
      ->with('success', 'Item deleted successfully.');
  }

  /**
   * Return item details for modal view via AJAX.
   *
   * @param  \App\Models\Item  $item
   * @return \Illuminate\Http\JsonResponse
   */
  public function viewModal(Item $item)
  {
    // Load relationships that might be needed for the view
    $item->load(['category', 'loans' => function ($query) {
      $query->whereIn('loans.status', ['active', 'pending', 'overdue'])
        ->with('user');
    }]);

    // Render the item modal partial view
    $html = view('items.modal', compact('item'))->render();

    return response()->json([
      'success' => true,
      'html' => $html,
      'item' => $item
    ]);
  }
}
