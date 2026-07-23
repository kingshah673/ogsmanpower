<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIWorkflow extends Model
{
    protected $fillable = [

        'name',

        'trigger',

        'conditions',

        'actions',

        'status'
    ];

    protected $casts = [

        'conditions' => 'array',

        'actions' => 'array'
    ];
}