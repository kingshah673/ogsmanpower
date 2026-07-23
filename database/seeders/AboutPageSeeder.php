<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('about_hero')) {
            return;
        }

        $now = now();

        // Upsert OGS Manpower branding while preserving admin-edited layout sections when already seeded.
        if (DB::table('about_hero')->exists()) {
            DB::table('about_hero')->where('id', 1)->update([
                'badge_text' => 'OGS Manpower · Established 2010 · Pakistan · UAE · UK',
                'headline' => 'Your Gateway to a <em>Global Career</em> with <em>OGS Manpower</em>',
                'subheadline' => 'OGS Manpower (CareerWorkforce) connects verified professionals with employers across the Gulf, UK, Europe and beyond — powered by an AI-assisted recruitment platform.',
                'pill_1' => 'Licensed OEP Agency',
                'pill_2' => 'OGS Manpower Network',
                'pill_3' => 'AI-Powered Platform',
                'updated_at' => $now,
            ]);
        } else {
            DB::table('about_hero')->insert([
                'id' => 1,
                'badge_text' => 'OGS Manpower · Established 2010 · Pakistan · UAE · UK',
                'headline' => 'Your Gateway to a <em>Global Career</em> with <em>OGS Manpower</em>',
                'subheadline' => 'OGS Manpower (CareerWorkforce) connects verified professionals with employers across the Gulf, UK, Europe and beyond — powered by an AI-assisted recruitment platform.',
                'pill_1' => 'Licensed OEP Agency',
                'pill_2' => 'OGS Manpower Network',
                'pill_3' => 'AI-Powered Platform',
                'stat_1_val' => '15+',
                'stat_1_lbl' => 'Years Experience',
                'stat_2_val' => '10K+',
                'stat_2_lbl' => 'Workers Deployed',
                'stat_3_val' => '100+',
                'stat_3_lbl' => 'Global Clients',
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('about_story') && DB::table('about_story')->exists()) {
            DB::table('about_story')->where('id', 1)->update([
                'section_label' => 'Our Story',
                'headline' => "OGS Manpower — Pakistan's Trusted Global Recruitment Partner",
                'quote' => 'Every career opportunity deserves a global stage — we build that stage.',
                'body_1' => 'OGS Manpower / CareerWorkforce connects verified talent with employers worldwide through a modern, AI-assisted hiring platform.',
                'body_2' => 'From sourcing and screening to deployment support, we help HR teams hire faster with confidence.',
                'body_3' => 'Register today to post jobs, manage applicants, and grow your workforce with OGS Manpower.',
                'license_text' => 'Govt. of Pakistan · License No. MPD/2978/RWP',
                'updated_at' => $now,
            ]);

            return;
        }

        if (DB::table('about_story')->exists()) {
            return;
        }

        DB::table('about_story')->insert([
            'id' => 1,
            'section_label' => 'Our Story',
            'headline' => "OGS Manpower — Pakistan's Trusted Global Recruitment Partner",
            'quote' => 'Every career opportunity deserves a global stage — we build that stage.',
            'body_1' => 'OGS Manpower / CareerWorkforce connects verified talent with employers worldwide through a modern, AI-assisted hiring platform.',
            'body_2' => 'From sourcing and screening to deployment support, we help HR teams hire faster with confidence.',
            'body_3' => 'Register today to post jobs, manage applicants, and grow your workforce with OGS Manpower.',
            'license_text' => 'Govt. of Pakistan · License No. MPD/2978/RWP',
            'card_1_num' => '15+',
            'card_1_lbl' => 'Years of Excellence',
            'card_1_desc' => 'Serving global employers with pre-screened, verified talent since 2010.',
            'card_2_num' => '5+',
            'card_2_lbl' => 'Countries Served',
            'card_2_desc' => 'Active offices in Pakistan, UAE & UK with sourcing from 15+ nations.',
            'is_active' => 1,
            'updated_at' => $now,
        ]);

        // Continue original feature/seed inserts when tables were empty
        if (DB::table('about_hero')->where('id', 1)->exists() && Schema::hasTable('about_features') && DB::table('about_features')->exists()) {
            return;
        }

        $now = now();

        // Legacy early-return path replaced above — keep remaining inserts from original seeder body.
        $features = [
            ['AI Matching', 'Smart candidate-job matching', '🤖'],
            ['Verified Profiles', 'Pre-screened candidates', '✅'],
            ['Fast Hiring', 'Reduce time-to-hire', '⚡'],
            ['Global Reach', 'Gulf, UK & Europe', '🌍'],
            ['Employer Dashboard', 'Manage jobs & applicants', '📊'],
            ['Mobile Friendly', 'Hire from anywhere', '📱'],
            ['Secure Data', 'Privacy-first platform', '🔒'],
            ['24/7 Support', 'Dedicated assistance', '💬'],
            ['Job Promotion', 'Boost visibility', '🚀'],
            ['Agency Network', 'Trusted partners', '🤝'],
            ['Document Checks', 'Credential verification', '📄'],
            ['Interview Tools', 'Streamlined shortlisting', '🎯'],
            ['Analytics', 'Hiring insights', '📈'],
            ['Multilingual', 'English & Arabic ready', '🌐'],
            ['Free Registration', 'Start at no cost', '🆓'],
            ['Licensed OEP', 'Compliant recruitment', '🏛'],
        ];

        foreach ($features as $i => [$title, $teaser, $icon]) {
            DB::table('about_features')->insert([
                'sort_order' => $i + 1,
                'icon_emoji' => $icon,
                'icon_bg_color' => '#E8F5E9',
                'title' => $title,
                'teaser' => $teaser,
                'modal_body' => '<p>'.$teaser.'</p>',
                'badge_tags' => 'CareerWorkforce',
                'cta_text' => 'Register Now →',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $metrics = [
            ['15+', 'Years Experience', '🏆'],
            ['10K+', 'Workers Deployed', '👷'],
            ['100+', 'Global Clients', '🏢'],
            ['16', 'Platform Features', '⭐'],
            ['5+', 'Countries', '🌍'],
            ['24/7', 'Support', '💬'],
        ];

        foreach ($metrics as $i => [$value, $label, $icon]) {
            DB::table('about_metrics')->insert([
                'sort_order' => $i + 1,
                'value' => $value,
                'label' => $label,
                'icon' => $icon,
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }

        $industries = [
            ['🏗', 'Construction'],
            ['🏥', 'Healthcare'],
            ['🏭', 'Manufacturing'],
            ['🛒', 'Retail'],
            ['🍽', 'Hospitality'],
            ['🚚', 'Logistics'],
            ['💻', 'IT & Telecom'],
            ['🔧', 'Technical Trades'],
            ['🧹', 'Facility Services'],
        ];

        foreach ($industries as $i => [$icon, $name]) {
            DB::table('about_industries')->insert([
                'sort_order' => $i + 1,
                'icon' => $icon,
                'name' => $name,
                'description' => 'Recruitment solutions for '.$name,
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }

        DB::table('about_ceo')->insert([
            'id' => 1,
            'name' => 'Leadership Team',
            'title' => 'OGS Manpower / CareerWorkforce',
            'location' => 'Pakistan · UAE · UK',
            'experience' => '25+',
            'quote' => 'We connect talent with opportunity — locally and globally.',
            'bio' => 'Our leadership team brings decades of recruitment and workforce deployment experience.',
            'tags' => 'OEP,Recruitment,Workforce',
            'creds' => json_encode(['Licensed Overseas Employment Promoter', 'City & Guilds UK partner']),
            'is_active' => 1,
            'updated_at' => $now,
        ]);

        DB::table('about_videos')->insert([
            [
                'sort_order' => 1,
                'title' => 'Platform Overview',
                'description' => 'See how CareerWorkforce works',
                'video_type' => 'youtube',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'duration' => '3:00',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $offices = [
            ['🇵🇰', 'Pakistan', 'Islamabad'],
            ['🇦🇪', 'UAE', 'Dubai'],
            ['🇬🇧', 'United Kingdom', 'London'],
        ];

        foreach ($offices as $i => [$flag, $country, $city]) {
            DB::table('about_offices')->insert([
                'sort_order' => $i + 1,
                'flag' => $flag,
                'country' => $country,
                'city' => $city,
                'description' => $city.' office',
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }

        $socials = [
            ['Facebook', '📘'],
            ['Instagram', '📸'],
            ['LinkedIn', '💼'],
            ['YouTube', '▶️'],
            ['Twitter', '🐦'],
            ['WhatsApp', '💬'],
            ['TikTok', '🎵'],
            ['Email', '✉️'],
        ];

        foreach ($socials as $i => [$platform, $icon]) {
            DB::table('about_social_links')->insert([
                'sort_order' => $i + 1,
                'platform' => $platform,
                'icon' => $icon,
                'url' => '#',
                'is_active' => 1,
                'updated_at' => $now,
            ]);
        }

        $config = [
            'whatsapp_number' => '923001234567',
            'email_address' => 'info@careerworkforce.com',
            'register_url' => '/register',
            'google_analytics_id' => '',
            'footer_copyright' => '© '.date('Y').' CareerWorkforce.com — All rights reserved.',
            'site_name' => 'CareerWorkforce',
            'support_phone' => '',
            'meta_description' => 'Global recruitment platform powered by OGS Manpower',
        ];

        foreach ($config as $key => $value) {
            DB::table('about_config')->insert([
                'cfg_key' => $key,
                'cfg_value' => $value,
                'updated_at' => $now,
            ]);
        }
    }
}
