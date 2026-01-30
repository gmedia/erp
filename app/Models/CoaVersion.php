<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoaVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fiscal_year_id',
        'status',
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
