<?php

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasFactory, HasUserScope;

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'name',
        'description',
        'amount',
        'date',
        'frequency',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Filtrer par utilisateur via les relations fromAccount/toAccount
     * Un transfert est visible si l'utilisateur possède au moins un des deux comptes
     */
    protected static function applyUserScopeFilter(Builder $builder, int $userId): void
    {
        $builder->where(function (Builder $query) use ($userId) {
            $query->whereHas('fromAccount', function (Builder $subQuery) use ($userId) {
                $subQuery->where('user_id', $userId);
            })
                ->orWhereHas('toAccount', function (Builder $subQuery) use ($userId) {
                    $subQuery->where('user_id', $userId);
                });
        });
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_account_id');
    }
}
