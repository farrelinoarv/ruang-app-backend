<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'campaign_id',
        'user_id',
        'donor_name',
        'is_anonymous',
        'amount',
        'message',
        'payment_method',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'payment_status',
        'transaction_ref',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
    ];

    /**
     * Get the campaign that was donated to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user who made the donation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the donation is successful.
     */
    public function getIsSuccessAttribute(): bool
    {
        return $this->payment_status === 'success';
    }

    /**
     * Get the display name for the donor.
     * Returns "Seseorang" if anonymous, otherwise returns the donor's name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->is_anonymous ? 'Seseorang' : $this->donor_name;
    }
}
