<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobAiTemplate extends Model
{
    protected $fillable = [
        'job_title',
        'description',
        'skills',
        'tags',
        'min_salary',
        'max_salary',
        'experience'
    ];

    protected $casts = [
        'skills' => 'array',
        'tags' => 'array'
    ];
}