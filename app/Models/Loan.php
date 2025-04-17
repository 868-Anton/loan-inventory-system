<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;

/**
 * The Loan model represents a loan record in the system.
 * 
 * This model handles equipment/item loans with the following key features:
 * 
 * - Can be associated with either registered users or guests
 * - Tracks loan dates, due dates and return dates
 * - Automatically sets due date to 1 month after loan date if not specified
 * - Supports soft deletes for maintaining loan history
 * - Stores loan documentation (signatures and vouchers)
 * - Department data is fetched from external API
 * 
 * Key attributes:
 * @property string $loan_number      Unique identifier for the loan
 * @property int    $user_id          ID of the user who took the loan (if not guest)
 * @property int    $department_id    Department ID from external API
 * @property bool   $is_guest         Whether the loan is for a guest user
 * @property string $guest_name       Name of guest borrower (if guest loan)
 * @property string $guest_email      Email of guest borrower
 * @property string $guest_phone      Phone number of guest borrower
 * @property string $guest_id         ID number of guest borrower
 * @property \DateTime $loan_date     When the item was loaned out
 * @property \DateTime $due_date      When the item should be returned
 * @property \DateTime $return_date   When the item was actually returned
 * @property string $notes            Additional notes about the loan
 * @property string $status           Current status of the loan
 * @property string $signature        Digital signature for the loan
 * @property string $voucher_path     Path to stored loan voucher document
 * 
 * Relationships:
 * @property-read \App\Models\User|null $user The user who created this loan
 * @property-read \App\Models\Department|null $department The department associated with this loan
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Item[] $items The items in this loan
 * 
 * Note: Department information is retrieved from an external API using the department_id.
 * Implement an API service/client to fetch department details when needed.
 */

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_number',
        'user_id',
        'department_id',
        'is_guest',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_id',
        'loan_date',
        'due_date',
        'return_date',
        'notes',
        'status',
        'signature',
        'voucher_path',
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'is_guest' => 'boolean',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($loan) {
            // Set due_date to one month after loan_date if not provided
            if ($loan->loan_date && !$loan->due_date) {
                $loan->due_date = Carbon::parse($loan->loan_date)->addMonth();
            }
        });

        static::created(function ($loan) {
            // Generate and save voucher when loan is created
            $loan->generateVoucher();
        });
    }

    /**
     * Get the user who created this loan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department associated with this loan.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the items for the loan.
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'loan_items')
            ->withPivot(['quantity', 'serial_numbers', 'condition_before', 'condition_after', 'status'])
            ->withTimestamps();
    }

    /**
     * Check if a loan is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->return_date) {
            return false;
        }

        return Carbon::now()->gt($this->due_date);
    }

    /**
     * Get the borrower name (user or guest)
     */
    public function getBorrowerName(): string
    {
        if ($this->is_guest) {
            return $this->guest_name;
        }

        return $this->user?->name ?? 'Unknown';
    }

    /**
     * Get the borrower email (user or guest)
     */
    public function getBorrowerEmail(): ?string
    {
        if ($this->is_guest) {
            return $this->guest_email;
        }

        return $this->user?->email ?? null;
    }

    /**
     * Generate a PDF voucher for the loan
     * 
     * @return string|null Path to the generated voucher
     */
    public function generateVoucher(): ?string
    {
        // Don't generate if no items are attached
        if ($this->items->isEmpty()) {
            return null;
        }

        // Create PDF using Laravel DomPDF package
        $pdf = PdfFacade::loadView('loans.voucher', [
            'loan' => $this,
            'items' => $this->items,
            'borrower_name' => $this->getBorrowerName(),
            'borrower_email' => $this->getBorrowerEmail(),
        ]);

        // Save PDF to storage
        $fileName = 'loan_voucher_' . $this->loan_number . '.pdf';
        $path = 'vouchers/' . $fileName;

        Storage::put('public/' . $path, $pdf->output());

        // Update the voucher_path field
        $this->voucher_path = $path;
        $this->save();

        return $path;
    }

    /**
     * Get full URL for the voucher
     * 
     * @return string|null URL to the voucher
     */
    public function getVoucherUrl(): ?string
    {
        if (!$this->voucher_path) {
            return null;
        }

        return Storage::url($this->voucher_path);
    }

    /**
     * Mark a loan as returned and update all associated items
     * 
     * @param string|null $notes Additional notes about the return
     * @return bool Whether the return was processed successfully
     */
    public function markAsReturned(?string $notes = null): bool
    {
        if ($this->status === 'returned') {
            return false; // Already returned
        }

        // Update loan status and return date
        $this->return_date = now();
        $this->status = 'returned';

        if ($notes) {
            $this->notes = $this->notes ? $this->notes . "\n\nReturn notes: " . $notes : "Return notes: " . $notes;
        }

        // Update all associated items statuses in the pivot table
        $this->items()->updateExistingPivot(
            $this->items->pluck('id')->toArray(),
            ['status' => 'returned']
        );

        // For each item, check if it's in any other active loans
        // If not, set it back to 'available'
        foreach ($this->items as $item) {
            $stillOnLoan = $item->loans()
                ->where('loans.id', '!=', $this->id)
                ->whereIn('loans.status', ['active', 'pending', 'overdue'])
                ->exists();

            if (!$stillOnLoan) {
                $item->update(['status' => 'available']);
            }
        }

        return $this->save();
    }
}
