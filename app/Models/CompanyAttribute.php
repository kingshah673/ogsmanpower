<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyAttribute extends Model
{
    use HasFactory;
     protected $fillable = [
        'company_id',
        'section',
        'attribute_name',
        'input_type',
        'attribute_value',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];
    public function comapny_id()
    {
        return $this->belongsTo(Company::class);
    }
}
