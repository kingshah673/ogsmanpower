<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIUsageLog extends Model
{
    protected $table = 'ai_usage_logs';
    protected $fillable = [

        'user_id',

        'module',

        'model',

        'prompt_tokens',

        'completion_tokens',

        'total_tokens',

        'cost',

        'prompt',

        'response'
    ];
}