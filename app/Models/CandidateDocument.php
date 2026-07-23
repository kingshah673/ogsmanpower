<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'candidate_id',
        'file_reference',
        'document_type',
        'source_channel',
        'original_name',
        'mime_type',
        'file_size',
        'message_id',
        'is_primary',
        // Legacy single-record document image fields used by documentUpdate()
        'passport_image',
        'cnic_front',
        'cnic_back',
        'police_character_certificate',
        'medical',
        'navtec_report',
        'license_image',
        'review_status',
        'review_notes',
        'medical_expiry_date',
        'police_certificate_expiry_date',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'review_status' => 'array',
        'review_notes' => 'array',
        'medical_expiry_date' => 'date',
        'police_certificate_expiry_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    /** Document keys tracked on the checklist, mapped to their file column. */
    public const CHECKLIST_KEYS = [
        'passport_image' => 'Passport',
        'cnic_front' => 'CNIC / ID (Front)',
        'cnic_back' => 'CNIC / ID (Back)',
        'police_character_certificate' => 'Police Character Certificate',
        'medical' => 'Medical Certificate',
        'navtec_report' => 'Trade / Skill Test Report',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function passportOcrLogs()
    {
        return $this->hasMany(PassportOcrLog::class, 'document_id');
    }

    /** Pending/approved/rejected status for a checklist doc key (defaults to pending once uploaded). */
    public function statusFor(string $key): ?string
    {
        if (! filled($this->{$key} ?? null)) {
            return null;
        }

        return $this->review_status[$key] ?? 'pending';
    }

    public function noteFor(string $key): ?string
    {
        return $this->review_notes[$key] ?? null;
    }

    public function expiryFor(string $key): ?\Illuminate\Support\Carbon
    {
        return match ($key) {
            'medical' => $this->medical_expiry_date,
            'police_character_certificate' => $this->police_certificate_expiry_date,
            default => null,
        };
    }
}
