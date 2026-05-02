<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $credit_note_id
 * @property int|null $product_id
 * @property int $account_id
 * @property string $description
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property numeric $tax_percent
 * @property numeric $line_total
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CreditNote $creditNote
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Account $account
 *
 * @mixin \Eloquent
 */
class CreditNoteItem extends Model
{
    /** @use HasFactory<\Database\Factories\CreditNoteItemFactory> */
    use BuildsAttributeCasts, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'credit_note_id',
        'product_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'tax_percent',
        'line_total',
        'notes',
    ];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->decimalCasts([
                'quantity',
                'unit_price',
                'tax_percent',
                'line_total',
            ]),
        ];
    }
}
