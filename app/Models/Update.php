<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Update extends Model
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
        'title',
        'content',
        'media_path',
    ];

    /**
     * Get the campaign this update belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user who created this update.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
