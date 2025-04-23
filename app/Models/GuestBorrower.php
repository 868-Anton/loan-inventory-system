<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestBorrower extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'id_number',
        'organization',
        'notes'
    ];

    /**
     * Get all loans associated with this guest borrower.
     */
    public function loans(): MorphMany
    {
        return $this->morphMany(Loan::class, 'borrower');
    }
}
