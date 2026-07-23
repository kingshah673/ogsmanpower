<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PassportOcrLog extends Model
{
    protected $table = 'passport_ocr_logs';

    protected $guarded = [];

    protected $casts = [
        'extracted_fields'  => 'array',
        'existing_db_fields'=> 'array',
        'conflicts'         => 'array',
        'confirmed_at'      => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function document()
    {
        return $this->belongsTo(CandidateDocument::class, 'document_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
