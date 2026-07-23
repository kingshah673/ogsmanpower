<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaFlow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function searchCountry(): BelongsTo
    {
        return $this->belongsTo(SearchCountry::class, 'search_country_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(VisaFlowStep::class)->orderBy('sort_order');
    }

    public function activeSteps(): HasMany
    {
        return $this->steps()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->where('publish_status', 'published')
                ->orWhereNull('publish_status');
        });
    }

    public function isPublished(): bool
    {
        return ($this->publish_status ?? 'published') === 'published';
    }

    public function publish(): void
    {
        $version = (int) ($this->version ?: 0);
        if ($this->isPublished()) {
            $version = $version + 1;
        } else {
            $version = max(1, $version);
        }

        $this->update([
            'publish_status' => 'published',
            'version' => $version,
            'is_active' => true,
        ]);
    }

    public function markDraft(): void
    {
        $this->update(['publish_status' => 'draft']);
    }
}
