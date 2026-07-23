<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIKnowledgeBase extends Model
{
    protected $table = 'ai_knowledge_base';
    protected $fillable = [

        'category',

        'intent',

        'question',

        'answer',

        'keywords',

        'status'
    ];
}