<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ItemsController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request): View
  {
    $query = Item::with(['category', 'loans' => function ($query) {
      $query->whereIn('status', ['active', 'pending', 'overdue']);
    }]);

    // Apply search filter
    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('serial_number', 'like', "%{$search}%")
          ->orWhere('asset_tag', 'like', "%{$search}%");
      });
    }

    // Apply status filter
    if ($request->filled('status') && $request->input('status') !== 'all') {
      $status = $request->input('status');
      if ($status === 'borrowed') {
        $query->where(function ($q) {
          $q->where('status', 'borrowed')
            ->orWhereHas('loans', function ($loanQuery) {
              $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                ->where('loan_items.status', 'loaned');
            });
        });
      } elseif ($status === 'available') {
        $query->where(function ($q) {
          $q->where('status', 'available')
            ->whereDoesntHave('loans', function ($loanQuery) {
              $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                ->where('loan_items.status', 'loaned');
            });
        });
      } else {
        $query->where('status', $status);
      }
    }

    // Apply category filter
    if ($request->filled('category_id')) {
      $query->where('category_id', $request->input('category_id'));
    }

    $items = $query->orderBy('sort_order')->orderBy('name')->paginate(15);
    $categories = Category::orderBy('name')->get();

    return view('items.index', compact('items', 'categories'));
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create(): View
  {
    $categories = Category::orderBy('name')->get();
    $statuses = ['available', 'under_repair', 'lost', 'retired'];

    return view('items.create', compact('categories', 'statuses'));
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'category_id' => 'nullable|exists:categories,id',
      'status' => 'required|in:available,under_repair,lost,retired',
      'description' => 'nullable|string',
      'serial_number' => 'nullable|string|max:255|unique:items,serial_number',
      'asset_tag' => 'nullable|string|max:255|unique:items,asset_tag',
      'purchase_date' => 'nullable|date',
      'purchase_cost' => 'nullable|numeric|min:0',
      'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date',
      'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($request->hasFile('thumbnail')) {
      $validated['thumbnail'] = $request->file('thumbnail')->store('item-thumbnails', 'public');
    }

    Item::create($validated);

    return redirect()->route('items.index')->with('success', 'Item created successfully.');
  }

  /**
   * Display the specified resource.
   */
  public function show(Item $item): View
  {
    $item->load(['category', 'loans' => function ($query) {
      $query->whereIn('status', ['active', 'pending', 'overdue'])
        ->with('user');
    }]);

    return view('items.show', compact('item'));
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(Item $item): View
  {
    $categories = Category::orderBy('name')->get();
    $statuses = ['available', 'under_repair', 'lost', 'retired'];

    return view('items.edit', compact('item', 'categories', 'statuses'));
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Item $item): RedirectResponse
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'category_id' => 'nullable|exists:categories,id',
      'status' => 'required|in:available,under_repair,lost,retired',
      'description' => 'nullable|string',
      'serial_number' => 'nullable|string|max:255|unique:items,serial_number,' . $item->id,
      'asset_tag' => 'nullable|string|max:255|unique:items,asset_tag,' . $item->id,
      'purchase_date' => 'nullable|date',
      'purchase_cost' => 'nullable|numeric|min:0',
      'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date',
      'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($request->hasFile('thumbnail')) {
      // Delete old thumbnail if exists
      if ($item->thumbnail) {
        Storage::disk('public')->delete($item->thumbnail);
      }
      $validated['thumbnail'] = $request->file('thumbnail')->store('item-thumbnails', 'public');
    }

    $item->update($validated);

    return redirect()->route('items.show', $item)->with('success', 'Item updated successfully.');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Item $item): RedirectResponse
  {
    // Check if item is currently loaned
    if ($item->isCurrentlyLoaned()) {
      return back()->with('error', 'Cannot delete item that is currently loaned out.');
    }

    // Delete thumbnail if exists
    if ($item->thumbnail) {
      Storage::disk('public')->delete($item->thumbnail);
    }

    $item->delete();

    return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
  }

  /**
   * Display item details in a modal view.
   */
  public function viewModal(Item $item): JsonResponse
  {
    $item->load(['category', 'loans' => function ($query) {
      $query->whereIn('status', ['active', 'pending', 'overdue'])
        ->with('user');
    }]);

    $html = view('items.modal', compact('item'))->render();

    return response()->json([
      'html' => $html,
      'item' => $item
    ]);
  }

  public function returnItem(Request $request, Loan $loan): JsonResponse
  {
    try {
      $itemId = $request->input('item_id');
      $conditionTags = $request->input('condition_tags', []);
      $returnNotes = $request->input('return_notes');
      $returnedBy = \Filament\Facades\Filament::auth()->user()?->name ?? 'System';

      // Validate that the item exists in this loan
      $item = $loan->items()->where('item_id', $itemId)->first();
      if (!$item) {
        return response()->json([
          'success' => false,
          'message' => 'Item not found in this loan'
        ], 404);
      }

      // Return the item
      $result = $loan->returnItem($itemId, $conditionTags, $returnNotes, $returnedBy);

      if ($result) {
        return response()->json([
          'success' => true,
          'message' => 'Item returned successfully'
        ]);
      } else {
        return response()->json([
          'success' => false,
          'message' => 'Failed to return item'
        ], 500);
      }
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
      ], 500);
    }
  }
}
