<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'balance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Get the singleton instance of the master account.
     */
    public static function getInstance(): self
    {
        return self::firstOrCreate(['id' => 1], ['balance' => 0]);
    }

    /**
     * Add funds to the master account.
     */
    public function addFunds(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Deduct funds from the master account.
     */
    public function deductFunds(float $amount): bool
    {
        if ($this->balance >= $amount) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }
}
