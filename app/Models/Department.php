<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sort_order',
        'description',
        'location',
        'contact_person',
        'contact_email',
        'contact_phone',
    ];

    /**
     * Get the users belonging to this department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the loans associated with this department.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
