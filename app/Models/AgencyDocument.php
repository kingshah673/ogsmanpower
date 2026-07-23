<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyDocument extends Model
{
    protected $fillable = [
        'agency_id',
        'document_type',
        'file_path',
        'status',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}