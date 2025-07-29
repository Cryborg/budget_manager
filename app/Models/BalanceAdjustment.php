<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bank_account_id
 * @property \Illuminate\Support\Carbon $adjustment_date
 * @property float $actual_balance
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BalanceAdjustment extends Model
{
    use HasFactory, HasUserScope;

    protected $fillable = [
        'bank_account_id',
        'adjustment_date',
        'actual_balance',
        'description',
        'is_active',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'actual_balance' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Filtrer par utilisateur via la relation bankAccount
     */
    protected static function applyUserScopeFilter(Builder $builder, int $userId): void
    {
        $builder->whereHas('bankAccount', function (Builder $query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
