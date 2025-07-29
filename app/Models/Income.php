<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bank_account_id
 * @property string $name
 * @property string|null $description
 * @property float $amount
 * @property \Illuminate\Support\Carbon $date
 * @property string $frequency
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $category
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Income extends Model
{
    use HasFactory;

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
        'amount' => 'float',
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
