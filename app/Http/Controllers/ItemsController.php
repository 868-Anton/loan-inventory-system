<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ItemsController extends Controller
{
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
