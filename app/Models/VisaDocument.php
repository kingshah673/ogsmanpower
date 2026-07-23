<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaDocument extends Model
{
    protected $table = 'visa_documents';

    protected $fillable = [
        'case_id',
        'document_name',
        'file_path',
        'status',
        'remarks',
        'uploaded_by',
        'verified_by'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function case()
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}