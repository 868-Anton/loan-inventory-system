<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'item_id',
        'quantity',
        'serial_numbers',
        'condition_before',
        'condition_after',
        'condition_tags',
        'return_notes',
        'status',
        'returned_at',
        'returned_by',
        'condition_assessed_at',
    ];

    protected $casts = [
        'serial_numbers' => 'array',
        'condition_tags' => 'array',
        'returned_at' => 'datetime',
        'condition_assessed_at' => 'datetime',
    ];

    /**
     * Get the loan that owns the loan item.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the item that is loaned.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Check if the loan item has been returned
     */
    public function isReturned(): bool
    {
        return !is_null($this->returned_at);
    }

    /**
     * Check if the loan item has condition assessment
     */
    public function hasConditionAssessment(): bool
    {
        return !is_null($this->condition_assessed_at);
    }

    /**
     * Get the condition status as a readable string
     */
    public function getConditionStatusAttribute(): string
    {
        if ($this->isReturned()) {
            if ($this->hasConditionAssessment()) {
                return 'Assessed';
            }
            return 'Returned';
        }
        return 'Loaned';
    }
}
