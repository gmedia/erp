<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FiscalYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function coaVersions(): HasMany
    {
        return $this->hasMany(CoaVersion::class);
    }

    public function activeCoaVersion(): HasOne
    {
        return $this->hasOne(CoaVersion::class)->where('status', 'active');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Check if fiscal year is open for transactions
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Scope to get open fiscal years
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope to get current fiscal year based on date
     */
    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }
}
