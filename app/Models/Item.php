<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'thumbnail',
        'serial_number',
        'asset_tag',
        'purchase_date',
        'purchase_cost',
        'warranty_expiry',
        'status',
        'total_quantity',
        'category_id',
        'custom_attributes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
        'custom_attributes' => 'array',
    ];

    /**
     * Get the category that owns the item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the loans for the item.
     */
    public function loans(): BelongsToMany
    {
        return $this->belongsToMany(Loan::class, 'loan_items')
            ->withPivot(['quantity', 'serial_numbers', 'condition_before', 'condition_after', 'status'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include available items.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    /**
     * Check if the item is available for loan.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Get the total quantity of the item available for loan.
     * This can be overridden in child classes for different inventory systems.
     * 
     * @return int
     */
    public function getAvailableQuantity(): int
    {
        if (!$this->isAvailable()) {
            return 0;
        }

        // Calculate the total quantity borrowed of this item in active loans
        $borrowedQuantity = $this->loans()
            ->whereIn('loans.status', ['pending', 'active', 'overdue'])
            ->sum('loan_items.quantity');

        // Assuming there's a total_quantity field or method
        // If not, override in a custom implementation or set a default
        $totalQuantity = $this->total_quantity ?? 1;

        return max(0, $totalQuantity - $borrowedQuantity);
    }

    /**
     * Check if the item is currently being loaned out (has active loans).
     * This may differ from the item's status field as we're checking the actual loan_items.
     *
     * @return bool
     */
    public function isCurrentlyLoaned(): bool
    {
        return $this->loans()
            ->whereIn('loans.status', ['active', 'overdue', 'pending'])
            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned'])
            ->exists();
    }

    /**
     * Get the total quantity of this item that is currently borrowed.
     * 
     * @return int
     */
    public function borrowedQuantity(): int
    {
        return $this->loans()
            ->whereIn('loans.status', ['active', 'pending', 'overdue'])
            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned'])
            ->sum('loan_items.quantity');
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Normalize status to lowercase before saving
            if ($item->status) {
                $item->status = strtolower($item->status);
            }
        });
    }
}
