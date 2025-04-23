<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'sort_order',
        'color',
        'icon',
        'slug',
        'custom_fields',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the items in this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the count of available items in this category.
     * An item is considered available if:
     * 1. Its status is 'available'
     * 2. AND it's not currently part of any active loan
     * 
     * @return int
     */
    public function getAvailableItemsCount(): int
    {
        return $this->items()
            ->where(function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['available'])
                    ->whereDoesntHave('loans', function ($loanQuery) {
                        $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned']);
                    });
            })
            ->count();
    }

    /**
     * Get the count of borrowed items in this category.
     * An item is considered borrowed if it has active loans in the loan_items pivot table
     * or if its status is set to 'borrowed'.
     * 
     * @return int
     */
    public function getBorrowedItemsCount(): int
    {
        return $this->items()
            ->where(function ($query) {
                // Items that are marked as borrowed in their status
                $query->where('status', 'borrowed')
                    // OR items that have active loans
                    ->orWhereHas('loans', function ($loanQuery) {
                        $loanQuery->whereIn('loans.status', ['active', 'overdue', 'pending'])
                            ->whereRaw('LOWER(loan_items.status) = ?', ['loaned']);
                    });
            })
            ->count();
    }

    /**
     * Get the total count of all items in this category.
     * 
     * @return int
     */
    public function totalQuantity(): int
    {
        return $this->items()->count();
    }

    /**
     * Get the total count of items that are currently borrowed in this category.
     * 
     * @return int
     */
    public function totalBorrowed(): int
    {
        return $this->getBorrowedItemsCount();
    }

    /**
     * Get the total count of items that are available in this category.
     * 
     * @return int
     */
    public function totalAvailable(): int
    {
        return $this->getAvailableItemsCount();
    }
}
