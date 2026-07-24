<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms;
use App\Models\FooterPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FooterPanelController extends Controller
{
    /** The footer manager screen. */
    public function index()
    {
        $panels = FooterPanel::with('items')->ordered()->get();
        $cms = Cms::first();

        return view('backend.footer.index', compact('panels', 'cms'));
    }

    /** Create a new panel (column). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active']   = $request->boolean('is_active');
        $data['sort_order']  = (int) FooterPanel::max('sort_order') + 1;

        FooterPanel::create($data);
        $this->clearCache();

        return back()->with('status', 'Footer panel created.');
    }

    /** Update an existing panel. */
    public function update(Request $request, FooterPanel $panel)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $panel->update($data);
        $this->clearCache();

        return back()->with('status', 'Footer panel updated.');
    }

    /** Delete a panel (its items are removed automatically). */
    public function destroy(FooterPanel $panel)
    {
        $panel->delete();
        $this->clearCache();

        return back()->with('status', 'Footer panel deleted.');
    }

    /**
     * Persist a new panel order after a drag & drop.
     * Expects: { ids: [3, 1, 2] }  (panel ids in their new order)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:footer_panels,id'],
        ]);

        foreach (array_values($validated['ids']) as $order => $id) {
            FooterPanel::whereKey($id)->update(['sort_order' => $order]);
        }

        $this->clearCache();

        return response()->json(['ok' => true]);
    }

    /** Update footer colors, copyright, and badge settings. */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'footer_bg_color' => ['nullable', 'string', 'max:20'],
            'footer_text_color' => ['nullable', 'string', 'max:20'],
            'footer_accent_color' => ['nullable', 'string', 'max:20'],
            'footer_copyright' => ['nullable', 'string', 'max:500'],
            'footer_powered_by' => ['nullable', 'string', 'max:255'],
            'footer_badge_position' => ['nullable', 'in:left,right,center'],
            'footer_badge_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:2048'],
        ]);

        $cms = Cms::firstOrFail();
        $cms->footer_bg_color = $data['footer_bg_color'] ?? $cms->footer_bg_color;
        $cms->footer_text_color = $data['footer_text_color'] ?? $cms->footer_text_color;
        $cms->footer_accent_color = $data['footer_accent_color'] ?? $cms->footer_accent_color;
        $cms->footer_copyright = $data['footer_copyright'] ?? $cms->footer_copyright;
        $cms->footer_powered_by = $data['footer_powered_by'] ?? $cms->footer_powered_by;
        $cms->footer_badge_position = $data['footer_badge_position'] ?? $cms->footer_badge_position;
        $cms->footer_badge_enabled = $request->boolean('footer_badge_enabled');

        if ($request->hasFile('footer_badge_image')) {
            if ($cms->footer_badge_image && Storage::disk('public')->exists($cms->footer_badge_image)) {
                Storage::disk('public')->delete($cms->footer_badge_image);
            }
            $cms->footer_badge_image = $request->file('footer_badge_image')->store('footer', 'public');
        }

        $cms->save();
        Cache::forget('cms_setting');
        Cache::forget('cms');
        $this->clearCache();

        return back()->with('status', 'Footer settings updated.');
    }

    /** Drop the cached footer so the website shows changes immediately. */
    private function clearCache(): void
    {
        Cache::forget('footer_panels');
    }
}
