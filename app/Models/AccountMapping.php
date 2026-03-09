<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $source_account_id
 * @property int $target_account_id
 * @property string $type
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $sourceAccount
 * @property-read \App\Models\Account $targetAccount
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping whereSourceAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping whereTargetAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountMapping whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AccountMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_account_id',
        'target_account_id',
        'type',
        'notes',
    ];

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function targetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'target_account_id');
    }
}
