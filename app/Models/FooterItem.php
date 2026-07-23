<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FooterItem extends Model
{
    protected $fillable = [
        'footer_panel_id',
        'type',
        'label',
        'url',
        'content',
        'image_path',
        'open_in_new_tab',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
    ];

    /** The panel this item belongs to. */
    public function panel(): BelongsTo
    {
        return $this->belongsTo(FooterPanel::class, 'footer_panel_id');
    }

    /** Public URL for the uploaded image (null when there is none). */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }
}
