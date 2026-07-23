<?php

use App\Models\MenuSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('menu_settings')) {
            return;
        }

        $items = [
            [
                'en_title' => 'About Us',
                'url' => '/about',
                'order' => 10,
            ],
            [
                'en_title' => 'Contact Us',
                'url' => '/contact',
                'order' => 11,
            ],
            [
                'en_title' => 'Privacy Policy',
                'url' => '/privacy-policy',
                'order' => 12,
            ],
        ];

        foreach ($items as $item) {
            if (MenuSettings::where('url', $item['url'])->exists()) {
                continue;
            }

            $menu = MenuSettings::create([
                'url' => $item['url'],
                'status' => true,
                'default' => false,
                'order' => $item['order'],
                'for' => json_encode(['public']),
            ]);

            $menu->translateOrNew('en')->title = $item['en_title'];
            $menu->save();
        }

        Cache::forget('menu_lists');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('menu_settings')) {
            return;
        }

        MenuSettings::whereIn('url', ['/about', '/contact', '/privacy-policy'])
            ->where('default', false)
            ->each(function (MenuSettings $menu) {
                $menu->delete();
            });

        Cache::forget('menu_lists');
    }
};
