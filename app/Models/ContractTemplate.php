<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    protected $fillable = [
        'title',
        'content',
        'category',
        'status',
        'created_by'
    ];
}