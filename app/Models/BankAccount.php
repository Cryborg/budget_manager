<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class BankAccount extends Model
{
    use HasFactory, HasUserScope;

    protected $fillable = [
        'user_id',
        'bank_id',
        'name',
        'type',
        'current_balance',
        'initial_balance',
        'account_number',
        'is_active',
        'blocked_at',
    ];

    protected $casts = [
        'current_balance' => 'float',
        'initial_balance' => 'float',
        'is_active' => 'boolean',
        'blocked_at' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (Auth::check() && ! $model->user_id) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    public function balanceAdjustments(): HasMany
    {
        return $this->hasMany(BalanceAdjustment::class);
    }

    /**
     * Vérifie si le compte est actuellement bloqué
     */
    public function isBlocked(): bool
    {
        return $this->blocked_at !== null && $this->blocked_at->isAfter(now()->startOfDay());
    }

    /**
     * Vérifie si le compte est disponible pour les transactions
     */
    public function isAvailableForTransactions(): bool
    {
        return $this->is_active && ! $this->isBlocked();
    }
}
