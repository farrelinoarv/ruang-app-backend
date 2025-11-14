<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
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
        'requested_amount',
        'destination_bank',
        'destination_account',
        'destination_name',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'payout_id',
        'payout_status',
        'proof_file',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the campaign associated with this withdrawal request.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user who requested the withdrawal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who reviewed this withdrawal request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Check if the withdrawal request is approved.
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the withdrawal request is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }
}
