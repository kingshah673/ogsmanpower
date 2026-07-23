<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmployerVerificationDocumentType extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'help_text',
        'is_required',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(CompanyVerificationDocumentAssignment::class, 'document_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public static function slugFromLabel(string $label): string
    {
        $base = Str::slug($label, '_');
        $base = $base !== '' ? $base : 'document';

        $slug = $base;
        $counter = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'_'.$counter;
            $counter++;
        }

        return $slug;
    }
}
