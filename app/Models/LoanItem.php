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
        'status',
        'returned_at',
    ];

    protected $casts = [
        'serial_numbers' => 'array',
        'returned_at' => 'datetime',
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
}
