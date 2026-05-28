<?php

namespace App\Models;

use Database\Factories\CoaVersionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $fiscal_year_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Account> $accounts
 * @property-read int|null $accounts_count
 * @property-read FiscalYear|null $fiscalYear
 *
 * @method static \Database\Factories\CoaVersionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion whereFiscalYearId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoaVersion whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CoaVersion extends Model
{
    /** @use HasFactory<CoaVersionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'fiscal_year_id',
        'status',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
