<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceAdjustment extends Model
{
    protected $fillable = [
        'bank_account_id',
        'adjustment_date', 
        'actual_balance',
        'description',
        'is_active',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'actual_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
