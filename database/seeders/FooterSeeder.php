<?php

namespace Database\Seeders;

use App\Models\FooterItem;
use App\Models\FooterPanel;
use Illuminate\Database\Seeder;

/**
 * Optional: gives you 4 ready-made panels so you can see the footer
 * straight away. Run with:  php artisan db:seed --class=FooterSeeder
 */
class FooterSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            [
                'title' => 'About Us',
                'items' => [
                    ['type' => 'text', 'content' => 'We build great products and help our customers succeed every single day.'],
                    ['type' => 'link', 'label' => 'Our Story', 'url' => '/about'],
                    ['type' => 'link', 'label' => 'Careers',   'url' => '/careers'],
                ],
            ],
            [
                'title' => 'Quick Links',
                'items' => [
                    ['type' => 'link', 'label' => 'Home',     'url' => '/'],
                    ['type' => 'link', 'label' => 'Services', 'url' => '/services'],
                    ['type' => 'link', 'label' => 'Pricing',  'url' => '/pricing'],
                    ['type' => 'link', 'label' => 'Blog',     'url' => '/blog'],
                ],
            ],
            [
                'title' => 'Support',
                'items' => [
                    ['type' => 'link', 'label' => 'Help Center', 'url' => '/help'],
                    ['type' => 'link', 'label' => 'Contact',     'url' => '/contact'],
                    ['type' => 'link', 'label' => 'FAQ',         'url' => '/faq'],
                    ['type' => 'link', 'label' => 'Privacy Policy', 'url' => '/privacy'],
                ],
            ],
            [
                'title' => 'Get In Touch',
                'items' => [
                    ['type' => 'heading', 'label' => 'Head Office'],
                    ['type' => 'text', 'content' => "123 Example Street\nCity, Country"],
                    ['type' => 'text', 'content' => 'hello@example.com'],
                ],
            ],
        ];

        foreach ($panels as $panelOrder => $panelData) {
            $panel = FooterPanel::create([
                'title'      => $panelData['title'],
                'sort_order' => $panelOrder,
                'is_active'  => true,
            ]);

            foreach ($panelData['items'] as $itemOrder => $item) {
                FooterItem::create(array_merge([
                    'footer_panel_id' => $panel->id,
                    'sort_order'      => $itemOrder,
                    'is_active'       => true,
                ], $item));
            }
        }
    }
}
