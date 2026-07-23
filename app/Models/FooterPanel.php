<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterPanel extends Model
{
    protected $fillable = [
        'title',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /** All items in this panel, ordered. */
    public function items(): HasMany
    {
        return $this->hasMany(FooterItem::class)->orderBy('sort_order');
    }

    /** Only the items that should be visible on the website. */
    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    /* ---------------------------------------------------------------------
     | Query scopes
     |--------------------------------------------------------------------*/

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
