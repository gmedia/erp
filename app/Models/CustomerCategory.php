<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCategory extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
