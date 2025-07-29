<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    protected $fillable = [
        'bank_account_id',
        'name',
        'description',
        'amount',
        'date',
        'frequency',
        'start_date',
        'end_date',
        'category',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
