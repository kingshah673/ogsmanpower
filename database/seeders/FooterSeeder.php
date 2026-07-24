<?php

namespace Database\Seeders;

use App\Models\FooterItem;
use App\Models\FooterPanel;
use Illuminate\Database\Seeder;

/**
 * Seeds OGS-style footer panels. Safe to re-run only when empty.
 * php artisan db:seed --class=FooterSeeder
 */
class FooterSeeder extends Seeder
{
    public function run(): void
    {
        if (FooterPanel::query()->exists()) {
            $this->command?->info('Footer panels already exist — skipping FooterSeeder.');
            return;
        }

        $panels = [
            [
                'title' => 'Top Links',
                'items' => [
                    ['type' => 'link', 'label' => 'Faq', 'url' => '/faq'],
                    ['type' => 'link', 'label' => 'Privacy', 'url' => '/privacy-policy'],
                    ['type' => 'link', 'label' => 'Contact Us', 'url' => '/contact'],
                    ['type' => 'link', 'label' => 'About', 'url' => '/about'],
                ],
            ],
            [
                'title' => 'Jobs By Industry',
                'items' => [
                    ['type' => 'link', 'label' => 'Drilling Oil & Gas', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'Information Technology', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'Accounts, Finance and Business', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'Engineering & Technology', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'Construction Consultants', 'url' => '/jobs'],
                ],
            ],
            [
                'title' => 'Jobs By Location',
                'items' => [
                    ['type' => 'link', 'label' => 'Pakistan', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'Saudi Arabia', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'Australia', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'USA', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'UK', 'url' => '/jobs'],
                ],
            ],
            [
                'title' => 'Job Seekers',
                'items' => [
                    ['type' => 'link', 'label' => 'Search job', 'url' => '/jobs'],
                    ['type' => 'link', 'label' => 'Create Account', 'url' => '/register?type=seeker'],
                    ['type' => 'link', 'label' => 'Recent Jobs', 'url' => '/jobs'],
                ],
            ],
            [
                'title' => 'Employers',
                'items' => [
                    ['type' => 'link', 'label' => 'Register as Employer', 'url' => '/register?type=employer'],
                    ['type' => 'link', 'label' => 'Post Job', 'url' => '/company/create/job'],
                    ['type' => 'link', 'label' => 'Search Resume', 'url' => '/candidates'],
                ],
            ],
        ];

        foreach ($panels as $panelOrder => $panelData) {
            $panel = FooterPanel::create([
                'title' => $panelData['title'],
                'sort_order' => $panelOrder,
                'is_active' => true,
            ]);

            foreach ($panelData['items'] as $itemOrder => $item) {
                FooterItem::create(array_merge([
                    'footer_panel_id' => $panel->id,
                    'sort_order' => $itemOrder,
                    'is_active' => true,
                ], $item));
            }
        }
    }
}
