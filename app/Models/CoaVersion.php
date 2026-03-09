<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int|null $fiscal_year_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Account> $accounts
 * @property-read int|null $accounts_count
 * @property-read \App\Models\FiscalYear|null $fiscalYear
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
    use HasFactory;

    protected $fillable = [
        'name',
        'fiscal_year_id',
        'status',
    ];

    public function fiscalYear(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Account::class);
    }
}
