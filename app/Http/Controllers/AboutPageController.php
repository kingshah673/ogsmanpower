<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AboutPageController extends Controller
{
    // Cache duration in seconds (10 minutes)
    private const CACHE_TTL = 600;

    public function index()
    {
        if (! Schema::hasTable('about_hero')) {
            return view('frontend.pages.about.index', $this->emptyAboutData());
        }

        $data = Cache::remember('about_page_data', self::CACHE_TTL, function () {
            return [
                'hero'         => DB::table('about_hero')->where('is_active', 1)->first(),
                'story'        => DB::table('about_story')->where('is_active', 1)->first(),
                'features'     => DB::table('about_features')
                                    ->where('is_active', 1)
                                    ->orderBy('sort_order')
                                    ->get(),
                'metrics'      => DB::table('about_metrics')
                                    ->where('is_active', 1)
                                    ->orderBy('sort_order')
                                    ->get(),
                'industries'   => DB::table('about_industries')
                                    ->where('is_active', 1)
                                    ->orderBy('sort_order')
                                    ->get(),
                'ceo'          => DB::table('about_ceo')->where('is_active', 1)->first(),
                'videos'       => DB::table('about_videos')
                                    ->where('is_active', 1)
                                    ->orderBy('sort_order')
                                    ->get(),
                'offices'      => DB::table('about_offices')
                                    ->where('is_active', 1)
                                    ->orderBy('sort_order')
                                    ->get(),
                'social_links' => DB::table('about_social_links')
                                    ->where('is_active', 1)
                                    ->orderBy('sort_order')
                                    ->get(),
                'config'       => DB::table('about_config')
                                    ->pluck('cfg_value', 'cfg_key'),
            ];
        });

        $defaults = $this->emptyAboutData();
        if (empty($data['hero'])) {
            $data['hero'] = $defaults['hero'];
        }
        if (empty($data['story'])) {
            $data['story'] = $defaults['story'];
        }

        // Decode CEO creds JSON
        if ($data['ceo'] && $data['ceo']->creds) {
            $data['ceo']->creds_array = json_decode($data['ceo']->creds, true) ?? [];
        }

       return view('frontend.pages.about.index', $data);
    }

    private function emptyAboutData(): array
    {
        $story = (object) [
            'headline' => null,
            'quote' => null,
            'body_1' => 'CareerWorkforce connects verified job seekers with employers and recruitment partners worldwide.',
            'body_2' => null,
            'body_3' => null,
            'license_text' => null,
            'card_1_num' => null,
            'card_1_lbl' => null,
            'card_1_desc' => null,
            'card_2_num' => null,
            'card_2_lbl' => null,
            'card_2_desc' => null,
            'is_active' => 1,
        ];

        $hero = (object) [
            'badge_text' => null,
            'headline' => 'About OGS Manpower',
            'subheadline' => null,
            'pill_1' => null,
            'pill_2' => null,
            'pill_3' => null,
            'stat_1_val' => null,
            'stat_1_lbl' => null,
            'stat_2_val' => null,
            'stat_2_lbl' => null,
            'stat_3_val' => null,
            'stat_3_lbl' => null,
            'is_active' => 1,
        ];

        return [
            'hero' => $hero,
            'story' => $story,
            'features' => collect(),
            'metrics' => collect(),
            'industries' => collect(),
            'ceo' => null,
            'videos' => collect(),
            'offices' => collect(),
            'social_links' => collect(),
            'config' => collect(),
        ];
    }

    // API endpoint for admin live preview
    public function apiData()
    {
        Cache::forget('about_page_data');
        return response()->json(['status' => 'cache_cleared']);
    }
}
