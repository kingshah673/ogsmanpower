<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyAttributeTranslation extends Model
{
    use HasFactory;
    protected $fillable = [
        'agency_id',
        'job_id',
        'agency_attribute_id',
        'attribute_value',

    ];
}
