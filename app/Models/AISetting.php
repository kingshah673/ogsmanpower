<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AISetting extends Model
{
    protected $table = 'ai_settings';
    protected $fillable = [

        'key',

        'value'
    ];
}