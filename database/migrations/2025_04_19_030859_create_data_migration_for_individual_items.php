<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Item;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all items with deprecated_total_quantity > 1
        $items = DB::table('items')
            ->where('deprecated_total_quantity', '>', 1)
            ->get();

        foreach ($items as $item) {
            // For each item with quantity > 1, create additional individual items
            for ($i = 2; $i <= $item->deprecated_total_quantity; $i++) {
                $newSerialNumber = $item->serial_number ? $item->serial_number . '-' . $i : null;
                $newAssetTag = $item->asset_tag ? $item->asset_tag . '-' . $i : null;

                // Insert a new record for each additional quantity
                DB::table('items')->insert([
                    'name' => $item->name,
                    'description' => $item->description,
                    'thumbnail' => $item->thumbnail,
                    'serial_number' => $newSerialNumber,
                    'asset_tag' => $newAssetTag,
                    'purchase_date' => $item->purchase_date,
                    'purchase_cost' => $item->purchase_cost,
                    'warranty_expiry' => $item->warranty_expiry,
                    'status' => $item->status,
                    'deprecated_total_quantity' => 1,
                    'category_id' => $item->category_id,
                    'custom_attributes' => $item->custom_attributes,
                    'created_at' => $item->created_at,
                    'updated_at' => now(),
                ]);
            }

            // Update the original item to have quantity = 1
            DB::table('items')
                ->where('id', $item->id)
                ->update([
                    'deprecated_total_quantity' => 1,
                    'updated_at' => now()
                ]);
        }

        // Now handle the loan_items table
        $loanItems = DB::table('loan_items')
            ->where('deprecated_quantity', '>', 1)
            ->get();

        foreach ($loanItems as $loanItem) {
            $item = DB::table('items')->where('id', $loanItem->item_id)->first();
            $quantity = $loanItem->deprecated_quantity;

            // The original loan_item record will represent 1 item
            DB::table('loan_items')
                ->where('id', $loanItem->id)
                ->update([
                    'deprecated_quantity' => 1
                ]);

            // Find similar items to attach to this loan (same category, name, etc.)
            $similarItems = DB::table('items')
                ->where('id', '!=', $loanItem->item_id)
                ->where('name', $item->name)
                ->where('category_id', $item->category_id)
                ->where('status', 'available')
                ->limit($quantity - 1)
                ->get();

            // For each remaining quantity, create a new loan_items record
            foreach ($similarItems as $index => $similarItem) {
                if ($index < $quantity - 1) {
                    DB::table('loan_items')->insert([
                        'loan_id' => $loanItem->loan_id,
                        'item_id' => $similarItem->id,
                        'deprecated_quantity' => 1,
                        'serial_numbers' => null,
                        'condition_before' => $loanItem->condition_before,
                        'condition_after' => $loanItem->condition_after,
                        'status' => $loanItem->status,
                        'created_at' => $loanItem->created_at,
                        'updated_at' => now(),
                    ]);

                    // Update the similar item status if needed
                    if ($loanItem->status === 'loaned') {
                        DB::table('items')
                            ->where('id', $similarItem->id)
                            ->update([
                                'status' => 'borrowed',
                                'updated_at' => now()
                            ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     * NOTE: This cannot be truly reversed as we create new records.
     */
    public function down(): void
    {
        // We cannot fully reverse this migration as it creates new records
        // This is just a placeholder
    }
};
