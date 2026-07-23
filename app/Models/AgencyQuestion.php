<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyQuestion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'required' => 'boolean',
        'reuse' => 'boolean',
    ];
}
