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
        'sort_order',
        'description',
        'thumbnail',
        'serial_number',
        'asset_tag',
        'purchase_date',
        'purchase_cost',
        'warranty_expiry',
        'status',
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
            ->withPivot([
                'quantity',
                'serial_numbers',
                'condition_before',
                'condition_after',
                'condition_tags',
                'return_notes',
                'status',
                'returned_at',
                'returned_by',
                'condition_assessed_at'
            ])
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
     * Check if the item is currently being loaned out.
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
     * Get all items in a specific category.
     * 
     * @param int $categoryId
     * @return Builder
     */
    public static function inCategory(int $categoryId): Builder
    {
        return self::where('category_id', $categoryId);
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
