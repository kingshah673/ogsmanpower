<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FooterItemController extends Controller
{
    /** Add an item (link / heading / text / image) to a panel. */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['is_active']       = $request->boolean('is_active');
        $data['sort_order']      = (int) FooterItem::where('footer_panel_id', $data['footer_panel_id'])
                                                   ->max('sort_order') + 1;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('footer', 'public');
        }

        unset($data['image']);

        FooterItem::create($data);
        $this->clearCache();

        return back()->with('status', 'Footer item added.');
    }

    /** Update an existing item. */
    public function update(Request $request, FooterItem $item)
    {
        $data = $this->validated($request);

        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['is_active']       = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $this->deleteImage($item);
            $data['image_path'] = $request->file('image')->store('footer', 'public');
        }

        unset($data['image']);

        $item->update($data);
        $this->clearCache();

        return back()->with('status', 'Footer item updated.');
    }

    /** Delete an item (and its image, if any). */
    public function destroy(FooterItem $item)
    {
        $this->deleteImage($item);
        $item->delete();
        $this->clearCache();

        return back()->with('status', 'Footer item deleted.');
    }

    /**
     * Persist item order after a drag & drop. Also handles moving an item
     * to a different panel.
     * Expects: { panel_id: 2, ids: [9, 5, 7] }
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'panel_id' => ['required', 'integer', 'exists:footer_panels,id'],
            'ids'      => ['present', 'array'],
            'ids.*'    => ['integer', 'exists:footer_items,id'],
        ]);

        foreach (array_values($validated['ids']) as $order => $id) {
            FooterItem::whereKey($id)->update([
                'sort_order'      => $order,
                'footer_panel_id' => $validated['panel_id'],
            ]);
        }

        $this->clearCache();

        return response()->json(['ok' => true]);
    }

    /* ---------------------------------------------------------------------
     | Helpers
     |--------------------------------------------------------------------*/

    private function validated(Request $request): array
    {
        return $request->validate([
            'footer_panel_id' => ['required', 'integer', 'exists:footer_panels,id'],
            'type'            => ['required', 'in:link,heading,text,image'],
            'label'           => ['nullable', 'string', 'max:255'],
            'url'             => ['nullable', 'string', 'max:2048'],
            'content'         => ['nullable', 'string'],
            'image'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:2048'],
        ]);
    }

    private function deleteImage(FooterItem $item): void
    {
        if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
            Storage::disk('public')->delete($item->image_path);
        }
    }

    private function clearCache(): void
    {
        Cache::forget('footer_panels');
    }
}
