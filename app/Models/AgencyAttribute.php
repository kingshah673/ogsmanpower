<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyAttribute extends Model
{
    use HasFactory;
     protected $fillable = [
        'agency_id',
        'attribute_name',
        'input_type',
        'attribute_value',
        'is_required',
        'is_active'
    ];
    public function agency_id()
    {
        return $this->belongsTo(Agency::class);
    }
}
