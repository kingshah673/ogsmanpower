<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    // Logo files that are committed to git (public/uploads/app/logo/)
    // and will be present on production after git pull.
    const DARK_LOGO    = 'uploads/app/logo/FxZOotJ0Aoe1fCMCJ10Ck1FCjAVvwk8t1XfX5Y74.png';
    const LIGHT_LOGO   = 'uploads/app/logo/WidwdkKuKFejkk0Rb7wbwYJEVE4ZygTq6Bi7xrSd.png';
    const FAVICON      = 'uploads/app/logo/PxEcoG3FR8WS9ZNOcz5PW7SMXAuFZ4kGTLkoYbdx.jpg';

    public function up(): void
    {
        // 1. Fix logo paths in settings table.
        //    Only overwrite if null or still pointing to an absolute server path
        //    (starts with '/' which means a broken upload from before the helper fix).
        $setting = DB::table('settings')->first();

        if ($setting) {
            $updates = [];

            if (is_null($setting->dark_logo) || str_starts_with($setting->dark_logo, '/')) {
                $updates['dark_logo'] = self::DARK_LOGO;
            }
            if (is_null($setting->light_logo) || str_starts_with($setting->light_logo, '/')) {
                $updates['light_logo'] = self::LIGHT_LOGO;
            }
            if (is_null($setting->favicon_image) || str_starts_with($setting->favicon_image, '/')) {
                $updates['favicon_image'] = self::FAVICON;
            }

            if (! empty($updates)) {
                DB::table('settings')->where('id', $setting->id)->update($updates);
            }
        }

        // 2. Fix SEO title for the home page.
        $homeSeo = DB::table('seos')->where('page_slug', 'home')->first();
        if ($homeSeo) {
            DB::table('seo_page_contents')
                ->where('page_id', $homeSeo->id)
                ->where('language_code', 'en')
                ->update(['title' => 'Welcome To Career Workforce']);
        }

        // 3. Clear cached settings so the new logo shows immediately.
        Cache::forget('setting');
        Cache::forget('setting_data');
    }

    public function down(): void
    {
        // Restore Jobpilot defaults (null triggers the SVG fallback in the model)
        DB::table('settings')->update([
            'dark_logo'    => null,
            'light_logo'   => null,
            'favicon_image' => null,
        ]);

        $homeSeo = DB::table('seos')->where('page_slug', 'home')->first();
        if ($homeSeo) {
            DB::table('seo_page_contents')
                ->where('page_id', $homeSeo->id)
                ->where('language_code', 'en')
                ->update(['title' => 'Welcome To Jobpilot']);
        }
    }
};
