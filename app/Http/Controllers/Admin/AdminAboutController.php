<?php
// app/Http/Controllers/Admin/AdminAboutController.php
// CareerWorkforce.com — Admin Backend Controller

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminAboutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('about_hero');
    }

    private function setupRequiredResponse()
    {
        return back()->with(
            'error',
            'About page database tables are missing on this server. Run: php artisan migrate --force && php artisan db:seed --class=AboutPageSeeder --force'
        );
    }

    // ── DASHBOARD ────────────────────────────────────────────
    public function dashboard()
    {
        if (! $this->tablesReady()) {
            return view('backend.about.dashboard', [
                'hero' => null,
                'story' => null,
                'features' => collect(),
                'metrics' => collect(),
                'industries' => collect(),
                'ceo' => null,
                'videos' => collect(),
                'config' => collect(),
                'about_tables_missing' => true,
            ]);
        }

        return view('backend.about.dashboard', [
            'hero'      => DB::table('about_hero')->first(),
            'story'     => DB::table('about_story')->first(),
            'features'  => DB::table('about_features')->orderBy('sort_order')->get(),
            'metrics'   => DB::table('about_metrics')->orderBy('sort_order')->get(),
            'industries'=> DB::table('about_industries')->orderBy('sort_order')->get(),
            'ceo'       => DB::table('about_ceo')->first(),
            'videos'    => DB::table('about_videos')->orderBy('sort_order')->get(),
            'config'    => DB::table('about_config')->pluck('cfg_value', 'cfg_key'),
            'about_tables_missing' => false,
        ]);
    }

    // ── HERO ─────────────────────────────────────────────────
    public function updateHero(Request $request)
    {
        if (! $this->tablesReady()) {
            return $this->setupRequiredResponse();
        }

        $validated = $request->validate([
            'badge_text'  => 'required|max:200',
            'headline'    => 'required|max:300',
            'subheadline' => 'nullable',
            'pill_1'      => 'nullable|max:100',
            'pill_2'      => 'nullable|max:100',
            'pill_3'      => 'nullable|max:100',
            'stat_1_val'  => 'nullable|max:20',
            'stat_1_lbl'  => 'nullable|max:80',
            'stat_2_val'  => 'nullable|max:20',
            'stat_2_lbl'  => 'nullable|max:80',
            'stat_3_val'  => 'nullable|max:20',
            'stat_3_lbl'  => 'nullable|max:80',
        ]);
        DB::table('about_hero')->where('id', 1)->update($validated);
        $this->clearCache();
        return back()->with('success', 'Hero section updated successfully!');
    }

    // ── STORY ────────────────────────────────────────────────
    public function updateStory(Request $request)
    {
        $validated = $request->validate([
            'headline'     => 'required|max:300',
            'quote'        => 'nullable',
            'body_1'       => 'nullable',
            'body_2'       => 'nullable',
            'body_3'       => 'nullable',
            'license_text' => 'nullable|max:200',
            'card_1_num'   => 'nullable|max:20',
            'card_1_lbl'   => 'nullable|max:100',
            'card_1_desc'  => 'nullable|max:300',
            'card_2_num'   => 'nullable|max:20',
            'card_2_lbl'   => 'nullable|max:100',
            'card_2_desc'  => 'nullable|max:300',
        ]);
        DB::table('about_story')->where('id', 1)->update($validated);
        $this->clearCache();
        return back()->with('success', 'Story section updated!');
    }

    // ── FEATURES ─────────────────────────────────────────────
    public function storeFeature(Request $request)
    {
        $validated = $request->validate([
            'sort_order'   => 'required|integer',
            'icon_emoji'   => 'required|max:20',
            'icon_bg_color'=> 'required|max:20',
            'title'        => 'required|max:200',
            'teaser'       => 'required|max:300',
            'modal_body'   => 'required',
            'badge_tags'   => 'nullable|max:500',
            'cta_text'     => 'nullable|max:100',
        ]);
        DB::table('about_features')->insert($validated + ['is_active' => 1]);
        $this->clearCache();
        return back()->with('success', 'Feature card added!');
    }

    public function updateFeature(Request $request, int $id)
    {
        $validated = $request->validate([
            'sort_order'   => 'required|integer',
            'icon_emoji'   => 'required|max:20',
            'icon_bg_color'=> 'required|max:20',
            'title'        => 'required|max:200',
            'teaser'       => 'required|max:300',
            'modal_body'   => 'required',
            'badge_tags'   => 'nullable|max:500',
            'cta_text'     => 'nullable|max:100',
            'is_active'    => 'boolean',
        ]);
        DB::table('about_features')->where('id', $id)->update($validated);
        $this->clearCache();
        return back()->with('success', 'Feature updated!');
    }

    public function deleteFeature(int $id)
    {
        DB::table('about_features')->where('id', $id)->delete();
        $this->clearCache();
        return back()->with('success', 'Feature deleted.');
    }

    // ── METRICS ──────────────────────────────────────────────
    public function updateMetrics(Request $request)
    {
        $metrics = $request->input('metrics', []);
        foreach ($metrics as $id => $data) {
            DB::table('about_metrics')->where('id', $id)->update([
                'value'      => $data['value'] ?? '',
                'label'      => $data['label'] ?? '',
                'icon'       => $data['icon']  ?? '📊',
                'is_active'  => isset($data['is_active']) ? 1 : 0,
            ]);
        }
        $this->clearCache();
        return back()->with('success', 'Metrics updated!');
    }

    // ── INDUSTRIES ───────────────────────────────────────────
    public function updateIndustry(Request $request, int $id)
    {
        $validated = $request->validate([
            'icon'        => 'required|max:20',
            'name'        => 'required|max:100',
            'description' => 'nullable|max:300',
            'is_active'   => 'boolean',
        ]);
        DB::table('about_industries')->where('id', $id)->update($validated);
        $this->clearCache();
        return back()->with('success', 'Industry updated!');
    }

    // ── CEO ──────────────────────────────────────────────────
    public function updateCeo(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|max:200',
            'title'      => 'required|max:200',
            'location'   => 'nullable|max:200',
            'experience' => 'nullable|max:50',
            'quote'      => 'required',
            'bio'        => 'nullable',
            'tags'       => 'nullable|max:500',
            'creds'      => 'nullable',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('about/ceo', 'public');
            $validated['photo_url'] = '/storage/' . $path;
        }

        // Encode creds as JSON if array
        if (!empty($validated['creds'])) {
            $credsArray = array_filter(array_map('trim', explode("\n", $validated['creds'])));
            $validated['creds'] = json_encode(array_values($credsArray));
        }

        DB::table('about_ceo')->where('id', 1)->update($validated);
        $this->clearCache();
        return back()->with('success', 'CEO profile updated!');
    }

    // ── VIDEOS ───────────────────────────────────────────────
    public function storeVideo(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|max:300',
            'description' => 'nullable|max:500',
            'video_type'  => 'required|in:youtube,vimeo,upload',
            'video_url'   => 'required|max:500',
            'thumbnail'   => 'nullable|max:500',
            'duration'    => 'nullable|max:20',
            'sort_order'  => 'integer',
        ]);

        // Handle file upload
        if ($request->hasFile('video_file') && $validated['video_type'] === 'upload') {
            $path = $request->file('video_file')->store('about/videos', 'public');
            $validated['video_url'] = '/storage/' . $path;
        }

        DB::table('about_videos')->insert($validated + ['is_active' => 1]);
        $this->clearCache();
        return back()->with('success', 'Video added!');
    }

    public function deleteVideo(int $id)
    {
        DB::table('about_videos')->where('id', $id)->delete();
        $this->clearCache();
        return back()->with('success', 'Video removed.');
    }

    // ── SOCIAL LINKS ─────────────────────────────────────────
    public function updateSocial(Request $request)
    {
        $links = $request->input('links', []);
        foreach ($links as $id => $data) {
            DB::table('about_social_links')->where('id', $id)->update([
                'url'       => $data['url'] ?? '#',
                'is_active' => isset($data['is_active']) ? 1 : 0,
            ]);
        }
        $this->clearCache();
        return back()->with('success', 'Social links updated!');
    }

    // ── SITE CONFIG ──────────────────────────────────────────
    public function updateConfig(Request $request)
    {
        $config = $request->input('config', []);
        foreach ($config as $key => $value) {
            DB::table('about_config')
              ->where('cfg_key', $key)
              ->update(['cfg_value' => $value]);
        }
        $this->clearCache();
        return back()->with('success', 'Configuration saved!');
    }

    // ── CACHE CLEAR ──────────────────────────────────────────
    private function clearCache(): void
    {
        Cache::forget('about_page_data');
    }

    public function clearCacheManual()
    {
        $this->clearCache();
        return back()->with('success', 'Cache cleared — changes are now live!');
    }
}
