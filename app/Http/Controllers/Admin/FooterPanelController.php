<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FooterPanelController extends Controller
{
    /** The footer manager screen. */
    public function index()
    {
        $panels = FooterPanel::with('items')->ordered()->get();

        return view('backend.footer.index', compact('panels'));
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

    /** Drop the cached footer so the website shows changes immediately. */
    private function clearCache(): void
    {
        Cache::forget('footer_panels');
    }
}
