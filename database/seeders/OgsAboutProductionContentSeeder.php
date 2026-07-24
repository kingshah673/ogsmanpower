<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Syncs production https://ogsmanpower.com/about content into About CMS tables.
 * Editable afterwards via /admin/about.
 *
 * php artisan db:seed --class=OgsAboutProductionContentSeeder
 */
class OgsAboutProductionContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $html_list = static function (array $items): string {
            $lis = implode('', array_map(static fn ($i) => '<li>' . $i . '</li>', $items));

            return '<ul>' . $lis . '</ul>';
        };

        $html_p = static function (string $text): string {
            return '<p>' . $text . '</p>';
        };

        $html_h4 = static function (string $text): string {
            return '<h4>' . $text . '</h4>';
        };

        // Keep body below using $html_list / $html_p / $html_h4 closures.

// ─── Hero ───────────────────────────────────────────────────────────────────
if (Schema::hasTable('about_hero')) {
    $hero = [
        'badge_text' => '',
        'headline' => 'Reliable Global Manpower Solutions',
        'subheadline' => '15+ Years | Pakistan · UAE · UK',
        'pill_1' => 'Licensed OEP',
        'pill_2' => '15+ Years Experience',
        'pill_3' => 'Global Workforce Supply',
        'stat_1_val' => '15+',
        'stat_1_lbl' => 'Years Experience',
        'stat_2_val' => '10K+',
        'stat_2_lbl' => 'Workers Deployed',
        'stat_3_val' => '200+',
        'stat_3_lbl' => 'Clients',
        'is_active' => 1,
        'updated_at' => $now,
    ];
    if (DB::table('about_hero')->exists()) {
        DB::table('about_hero')->where('id', DB::table('about_hero')->value('id'))->update($hero);
    } else {
        DB::table('about_hero')->insert($hero + ['id' => 1]);
    }
    echo "hero ok\n";
}

// ─── Story ──────────────────────────────────────────────────────────────────
if (Schema::hasTable('about_story')) {
    $mission = implode("\n", [
        'Build long-term partnerships with employers',
        'Deliver reliable and skilled manpower solutions',
        'Empower candidates with global career opportunities',
        'Maintain integrity, transparency, and professionalism in every placement',
    ]);

    $story = [
        'section_label' => 'About OGS Manpower',
        'headline' => '',
        'quote' => '',
        'body_1' => 'OGS Manpower, a part of OGS Group of Companies, is a government-licensed overseas recruitment agency based in Pakistan, specializing in providing reliable manpower solutions to international employers.',
        'body_2' => 'Established in 2010, OGS has built a strong reputation in global recruitment by delivering skilled, semi-skilled, and professional workforce across multiple industries including construction, oil & gas, engineering, hospitality, and facility management.',
        'body_3' => 'OGS Manpower is a legally registered entity under the Government of Pakistan (License No. 2978/RWP) and operates under strict compliance with international recruitment standards. We are committed to connecting global employers with qualified talent through a transparent, ethical, and efficient recruitment process.',
        'license_text' => 'License No. 2978/RWP · Government of Pakistan',
        'card_1_num' => '15+',
        'card_1_lbl' => 'Years of Excellence',
        'card_1_desc' => 'Serving global employers with pre-screened, verified talent since 2010.',
        'card_2_num' => '5+',
        'card_2_lbl' => 'Countries Served',
        'card_2_desc' => 'Active presence in Pakistan, UAE & UK with global workforce supply.',
        'is_active' => 1,
        'updated_at' => $now,
    ];
    if (Schema::hasColumn('about_story', 'mission')) {
        $story['mission'] = $mission;
    }

    if (DB::table('about_story')->exists()) {
        DB::table('about_story')->where('id', DB::table('about_story')->value('id'))->update($story);
    } else {
        DB::table('about_story')->insert($story + ['id' => 1]);
    }
    echo "story ok\n";
}

// ─── Features (12 production cards) ─────────────────────────────────────────
$features = [
    [
        'sort_order' => 1,
        'icon_emoji' => '👔',
        'icon_bg_color' => '#E8F5E9',
        'title' => 'CEO Leadership',
        'teaser' => '25+ years in HR, recruitment, AI, and global workforce development.',
        'badge_tags' => 'City & Guilds UK,Licensed OEP,AI & IR 4.0,Oil & Gas,TVET Specialist,GIZ Consultant',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('Abdul Basit Malik brings over <strong>25 years of expertise</strong> in Recruitment, Career Counselling, Labour Market Research, Business Migration Analysis, AI, and Industrial Revolution 4.0. Beginning in 1991 with the Pakistan Air Force\'s Directorate of Recruitment, Training &amp; Publicity, he built a methodical, results-driven approach to human capital development that today powers OGS Manpower.')
            . $html_h4('Current Roles &amp; Certifications')
            . $html_list([
                'Authorized Overseas Employment Promoter — OGS Manpower (License No. MPD/2978/RWP)',
                'Certified Chief Master Trainer &amp; Lead Assessor — City and Guilds UK',
                'Chairman, "Pakistan Digital Transformation Vision 2025" at RCCI',
                'Former Chairman HRD &amp; Overseas Pakistani — RCCI',
                'Accreditation Expert — National Accreditation Council for TVS (NAC-TVS)',
                'Certified Career Counselor — Quality International Study Abroad Network UK',
                'Expert Curriculum Developer — Competency Based Training &amp; Assessment (CBT &amp; A)',
                'GIZ Freelance TVET Consultant — Training Package Material Developer',
                'Member, Pakistan Overseas Employment Promoters Association',
            ])
            . $html_h4('Academic Qualifications')
            . $html_list([
                'Master in Computer Science (Final Stage)',
                'Bachelor in Computer Applications',
                'Associate Engineering Diploma — Electronics &amp; Telecommunications',
                'USAID Small &amp; Medium Enterprises Training',
                'Labour Market Need Assessment for Demand Driven TVET',
                'Career Counseling &amp; Vocational Guidance Skills Certification',
                'New Dimensions of TVET with Innovation &amp; Technopreneurship',
            ])
            . $html_h4('Core Expertise Areas')
            . $html_p('Oil &amp; Gas · Alternative Energy · Green Skills · Sustainable Development · AI &amp; IR 4.0 · Civil Defence · Trade Testing · Curriculum Design · Aptitude, Psychological &amp; Intelligent Assessment · RPL · Event Management · SEO &amp; Digital Marketing. Published author in leading Pakistani English and Urdu national newspapers.'),
    ],
    [
        'sort_order' => 2,
        'icon_emoji' => '🏆',
        'icon_bg_color' => '#FFF8E1',
        'title' => '15+ Years Experience',
        'teaser' => 'OGS Group has been delivering complete HR solutions globally since 2010.',
        'badge_tags' => 'Est. 2010,Govt. Licensed,15-Year Milestone,End-to-End HR',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('Since 2010, OGS Group of Companies has built an unmatched track record in international recruitment. With <strong>15+ years of continuous operation</strong>, we have evolved into a fully integrated global HR solutions provider — far beyond a traditional staffing agency.')
            . $html_h4('What 15+ Years Means for You')
            . $html_list([
                'Deep understanding of international compliance, visa, and immigration frameworks',
                'Established relationships with embassies, government bodies, and trade partners',
                'Battle-tested recruitment processes refined through thousands of successful deployments',
                'Industry-specific expertise across 6+ major global sectors',
                'Proven crisis management and rapid-deployment capabilities for urgent requirements',
                '15-year milestone ceremony held at OGS Pakistan HQ — a testament to longevity and credibility',
            ])
            . $html_h4('Complete HR Solutions Portfolio')
            . $html_list([
                'Talent sourcing, skills assessment, and trade testing',
                'Documentation, visa processing, and compliance management',
                'Ticketing, deployment logistics, and post-placement support',
                'HR consultancy, student advisory, and travel &amp; tourism services',
            ])
            . $html_p('OGS Group is <strong>fully capable of providing end-to-end human resource solutions</strong> — all under one trusted roof, eliminating the need for multiple vendors.'),
    ],
    [
        'sort_order' => 3,
        'icon_emoji' => '🌍',
        'icon_bg_color' => '#E3F2FD',
        'title' => '100+ Clients',
        'teaser' => 'Trusted by 100+ clients across the Gulf region and international markets.',
        'badge_tags' => '100+ Clients,GCC Markets,Repeat Business,Confidentiality Assured',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('OGS has proudly established long-standing partnerships with <strong>over 100 clients</strong> across the Gulf region and international markets, reflecting credibility, reliability, and consistent delivery of high-quality workforce solutions.')
            . $html_h4('Industries Served Across Our Client Base')
            . $html_list([
                'Construction, Oil &amp; Gas, and Engineering',
                'Healthcare and Medical Services',
                'Hospitality, Tourism, and Facility Management',
                'Logistics, Manufacturing, and Information Technology',
            ])
            . $html_h4('Why Clients Choose to Stay')
            . $html_p('The strength of OGS lies in <strong>enduring relationships</strong> built on professionalism, transparency, and performance. Through a client-centric approach, OGS delivers tailored recruitment solutions aligned with each organization\'s specific operational and cultural requirements — resulting in a high rate of repeat business and long-term GCC collaborations.')
            . $html_h4('Our Confidentiality Policy')
            . $html_p('To protect client privacy and uphold professional standards, OGS follows a strict non-disclosure policy. Specific client names and project details are shared only upon request with appropriate consent — reinforcing trust and professionalism at every level.')
            . $html_p('<strong>For HR managers, this client portfolio signals proven expertise, operational reliability, and capacity to manage large-scale recruitment assignments with precision.</strong>'),
    ],
    [
        'sort_order' => 4,
        'icon_emoji' => '⭐',
        'icon_bg_color' => '#FFF3E0',
        'title' => 'Strong Reputation',
        'teaser' => '15 years of credibility and recognition in the global HR business community.',
        'badge_tags' => '15-Year Milestone,Ethical Recruitment,Brand Credibility,Strategic HR Partner',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('Established in 2010, OGS has grown into a <strong>recognized and trusted name</strong> in the global HR and recruitment industry. The 15-year milestone ceremony at OGS Pakistan HQ reflects not just longevity, but a journey of credibility, growth, and strong positioning within the international HR business community.')
            . $html_h4('What Our Brand Delivers to HR Managers')
            . $html_list([
                'Government-licensed and compliant — trusted by regulatory authorities internationally',
                'Consistent brand promise: "Right talent, at the right time, every time"',
                'Recognized specialist in oil &amp; gas, construction, and technical trades globally',
                'Strong and growing LinkedIn, Facebook, and Instagram presence',
                'Contributions to workforce development through training and skill enhancement',
                'Structured recruitment: sourcing → screening → documentation → deployment',
            ])
            . $html_h4('More Than Recruitment')
            . $html_p('OGS is a <strong>strategic HR solutions provider</strong> committed to long-term partnerships. Its reputation is driven by proven performance, ethical standards, and a deep understanding of global workforce demands. For HR managers across different countries, OGS represents confidence, consistency, and a trusted gateway to skilled manpower from Pakistan and beyond.'),
    ],
    [
        'sort_order' => 5,
        'icon_emoji' => '💻',
        'icon_bg_color' => '#E8EAF6',
        'title' => 'Job Portal',
        'teaser' => 'AI-powered recruitment platform with smart filters and full lifecycle tracking.',
        'badge_tags' => 'AI-Powered,Real-Time Tracking,70% Faster Hiring,Global Scale',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('<strong>Stop wasting time on unqualified applicants and slow hiring processes.</strong> With the OGS Manpower Talent Portal, you gain direct access to a verified, job-ready global workforce — all in one powerful, intelligent platform.')
            . $html_h4('Smart Candidate Filtering')
            . $html_list([
                'Skills &amp; Trade · Country &amp; City · Experience Level',
                'Age, Gender, and Job Category filters',
                'No chaos. No guesswork. Just precision hiring.',
            ])
            . $html_h4('Complete Hiring System — Not Just a Job Board')
            . $html_list([
                'Track candidates: Application → Shortlisting → Interview → Selection → Deployment',
                'Manage contracts, documentation, and visa processing in one place',
                'Collaborate with agencies, consultants, and partners globally',
                'Real-time dashboards for full candidate progress visibility',
            ])
            . $html_h4('AI-Powered Recruitment Advantage')
            . $html_list([
                'Identify top candidates instantly with AI-driven recommendations',
                'Reduce hiring time by up to 70% through intelligent automation',
                'Eliminate irrelevant applications automatically',
                'Smart matching based on your exact job specifications',
            ])
            . $html_h4('Total Control — From Application to Deployment')
            . $html_p('Selection → Documentation → Visa → Ticketing → Deployment — all tracked in one system. Whether hiring 5 or 5,000 workers, OGS provides a structured, verified pipeline with full visibility and secure document handling.'),
    ],
    [
        'sort_order' => 6,
        'icon_emoji' => '📂',
        'icon_bg_color' => '#FCE4EC',
        'title' => 'Verified CV Bank',
        'teaser' => 'Verified, deployment-ready candidates with zero documentation errors.',
        'badge_tags' => 'Zero Doc Errors,Visa-Ready,Pre-Verified,Fast Shortlisting',
        'cta_text' => 'Register as Seeker →',
        'modal_body' => $html_p('Unlike common job portals where documentation inconsistencies cause costly visa rejections and delays, the OGS CV Data Bank is built around <strong>recruitment-grade accuracy</strong>. Every candidate profile is verified before it ever reaches an HR manager\'s desk.')
            . $html_h4('What Sets Our Data Bank Apart')
            . $html_list([
                '<strong>Exact Name Matching:</strong> Verified across passport, certificates, and all official documents',
                '<strong>Valid Passports:</strong> Expiry checked against international deployment requirements',
                '<strong>Complete Documentation:</strong> Qualifications, experience letters, and IDs organized and reviewed',
                '<strong>Pre-Verified Credentials:</strong> Structured vetting for authenticity before listing',
                '<strong>Deployment Readiness:</strong> Many candidates cleared for medical, trade test, and orientation',
            ])
            . $html_h4('Advanced Search Capabilities')
            . $html_p('Filter candidates by industry, job title, qualifications, experience level, and deployment readiness. Our technology-driven system enhances transparency and operational efficiency — making recruitment seamless and dependable.')
            . $html_h4('The Business Case for HR Managers')
            . $html_p('Accurate documentation means <strong>no visa rejections, no costly delays, no administrative losses</strong>. OGS delivers verified talent with precision, reliability, and global compliance — protecting your time, reputation, and budget from day one.'),
    ],
    [
        'sort_order' => 7,
        'icon_emoji' => '✈️',
        'icon_bg_color' => '#E0F7FA',
        'title' => 'Travel Services',
        'teaser' => 'Complete travel and ticketing solutions for smooth workforce mobility worldwide.',
        'badge_tags' => 'Group Ticketing,Visa Coordination,Corporate Travel,Umrah Services',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('OGS Travel and Tourism is the dedicated workforce mobility division of the OGS Group, specializing in comprehensive travel and ticketing solutions designed specifically for <strong>international manpower deployment</strong> and corporate travel management.')
            . $html_h4('Core Travel Services')
            . $html_list([
                'Group and individual worker ticketing for international deployment',
                'Cost-effective airline bookings with preferred carrier agreements',
                'Visa facilitation and travel documentation coordination',
                'Corporate travel management for HR managers and client representatives',
                'Emergency travel arrangements and last-minute deployment support',
                'Transit assistance and layover management across major global hubs',
            ])
            . $html_h4('Strategic Value for HR Managers')
            . $html_p('By integrating travel services within the OGS recruitment system, HR managers benefit from a seamless transition from candidate selection to physical arrival. This eliminates the complexity of coordinating with multiple vendors, reduces costs, and ensures candidates arrive on time, on budget, and fully documented.')
            . $html_h4('Umrah &amp; Religious Travel')
            . $html_p('OGS also offers Umrah and religious travel packages, demonstrating a commitment to holistic service delivery and community support beyond pure recruitment.'),
    ],
    [
        'sort_order' => 8,
        'icon_emoji' => '🎓',
        'icon_bg_color' => '#F3E5F5',
        'title' => 'Student Consultancy',
        'teaser' => 'Building tomorrow workforce through admissions, scholarships, and career guidance.',
        'badge_tags' => 'UK Admissions,Scholarships,Career Guidance,Future Workforce',
        'cta_text' => 'Register as Seeker →',
        'modal_body' => $html_p('OGS Student Consultancy is a strategic initiative dedicated to <strong>developing the future global workforce</strong>. Beyond immediate recruitment, OGS nurtures tomorrow\'s talent by guiding students toward educational pathways that align with international employer demands.')
            . $html_h4('Study Destinations &amp; Fields Supported')
            . $html_list([
                'United Kingdom, Europe, Middle East, and other global destinations',
                'Internationally recognized academic and vocational programs',
                'High-demand fields: Healthcare, Engineering, IT, Hospitality, Construction, Business Management',
            ])
            . $html_h4('Our Student Consultancy Services')
            . $html_list([
                'University and college admissions guidance and application support',
                'Scholarship identification, eligibility, and application management',
                'Career planning, skill development, and professional roadmaps',
                'Language proficiency preparation and cultural orientation programs',
                'Bridging the gap between education and employment',
            ])
            . $html_h4('Strategic Value for Employers')
            . $html_p('For HR managers, this initiative provides access to a <strong>pre-prepared, continuously growing talent pipeline</strong> trained in line with industry requirements. This reduces recruitment risks, shortens onboarding time, and enhances employee retention — making OGS a valuable long-term workforce planning partner.'),
    ],
    [
        'sort_order' => 9,
        'icon_emoji' => '📡',
        'icon_bg_color' => '#EFEBE9',
        'title' => 'HR News Channel',
        'teaser' => 'Dedicated platform delivering verified HR news and global workforce intelligence',
        'badge_tags' => 'HR Intelligence,Labour Law Updates,Market Analysis,Thought Leadership',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('The HR International News Channel is a pioneering initiative of the OGS Group, established to provide <strong>authentic, timely, and insightful information</strong> about the global human resource industry — a trusted intelligence platform for HR managers, recruiters, and employers worldwide.')
            . $html_h4('What the Channel Covers')
            . $html_list([
                'Global job market trends and sector-specific workforce demand forecasts',
                'International recruitment practices and industry best standards',
                'Labour laws, immigration regulations, and policy updates by region',
                'Workforce mobility and cross-border employment frameworks',
                'Emerging employment opportunities and regional market intelligence',
                'Ethical recruitment and international compliance best practices',
            ])
            . $html_h4('Why HR Managers Subscribe')
            . $html_list([
                'Every piece of information carefully reviewed for accuracy and relevance',
                'Expert interviews and real-time updates from verified global sources',
                'Rapidly growing presence across LinkedIn, Facebook, and social platforms',
                'Empowers data-driven recruitment strategy and talent market anticipation',
            ])
            . $html_h4('OGS as an Industry Thought Leader')
            . $html_p('By launching this platform, OGS extends its role from recruitment agency to <strong>knowledge partner and global HR thought leader</strong> — contributing to a more informed, ethical, and efficient international labour market.'),
    ],
    [
        'sort_order' => 10,
        'icon_emoji' => '📱',
        'icon_bg_color' => '#E8F5E9',
        'title' => 'Social Media Reach',
        'teaser' => 'Massive global digital presence enabling rapid talent sourcing and branding.',
        'badge_tags' => 'LinkedIn,Facebook,Instagram,Global Reach,Verified Pipeline',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('OGS has established a powerful and dynamic social media presence serving as a <strong>strategic platform for global HR networking and rapid talent acquisition</strong> across LinkedIn, Facebook, Instagram, and other leading digital channels.')
            . $html_h4('Our Global Talent Sourcing Reach')
            . $html_list([
                'Pakistan, India, Nepal, Sri Lanka, Philippines, Bangladesh',
                'South African nations, Egypt, Lebanon, and international markets',
                'Skilled, semi-skilled, and professional candidates continuously updated',
            ])
            . $html_h4('Why Social Media Accelerates Your Hiring')
            . $html_list([
                'Instant dissemination of job opportunities to a highly engaged global audience',
                'Rapid candidate responses resulting in a larger, highly relevant applicant pool',
                'Industry-specific targeted campaigns for precise talent attraction',
                'Faster shortlisting capability for urgent manpower requirements',
            ])
            . $html_h4('Integrated with Our Verified Recruitment System')
            . $html_p('Candidates sourced through social channels are funnelled directly into OGS\'s verified CV databank where they undergo <strong>full documentation screening</strong> before reaching HR managers. Social speed combined with recruitment-grade verification — the best of both worlds.'),
    ],
    [
        'sort_order' => 11,
        'icon_emoji' => '🖥️',
        'icon_bg_color' => '#E3F2FD',
        'title' => 'Digital Interview Lab',
        'teaser' => 'HD video interview facility eliminating geography as a hiring barrier.',
        'badge_tags' => 'HD Video Lab,No Travel Required,Bulk Interviews,Fast Decisions',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('OGS operates a state-of-the-art <strong>Online Interview Digitally Modern Equipped Lab</strong>, enabling HR managers to conduct real-time interviews with candidates from anywhere in the world — eliminating geography as a hiring barrier and significantly reducing recruitment time and costs.')
            . $html_h4('Facility Specifications')
            . $html_list([
                'High-speed internet connectivity for uninterrupted global sessions',
                'High-definition video conferencing and professional audio-visual equipment',
                'Secure, distraction-free, professionally designed interview environment',
                'Dedicated technical coordination team for full session management',
            ])
            . $html_h4('What This Delivers for HR Managers')
            . $html_list([
                'Interview candidates in Pakistan without travel or relocation expenses',
                'Quick, confident decision-making with full candidate visibility',
                'Support for bulk recruitment and urgent manpower requirements at scale',
                'Access to a wider pool of pre-screened, verified candidates on demand',
            ])
            . $html_h4('Interview Today. Deploy Faster.')
            . $html_p('This modern digital infrastructure accelerates the entire hiring process — from initial interview to final selection — while maintaining transparency, professionalism, and the highest standards of candidate presentation. No compromise on quality. No geographic limitations.'),
    ],
    [
        'sort_order' => 12,
        'icon_emoji' => '🔧',
        'icon_bg_color' => '#FFF3E0',
        'title' => 'Trade Test Centre',
        'teaser' => 'Skills certification ensuring only qualified, job-ready workers are deployed.',
        'badge_tags' => 'Certified Assessors,Welding & Electrical,Oil & Gas Trades,Employer-Specific Testing',
        'cta_text' => 'Register as Employer →',
        'modal_body' => $html_p('OGS operates a professionally managed <strong>Trade Test Centre</strong> designed to evaluate and certify practical candidate skills in accordance with international industry standards — ensuring only qualified, job-ready workers are deployed to your organization.')
            . $html_h4('Trades Assessed at Our Centre')
            . $html_list([
                'Welding and Fabrication',
                'Electrical and Plumbing',
                'Masonry and Carpentry',
                'Mechanical and Heavy Equipment Operation',
                'Oil &amp; Gas Technical Skills',
                'Other specialized skilled professions accommodated on request',
            ])
            . $html_h4('Assessment Process &amp; Facilities')
            . $html_list([
                'Modern tools, machinery, and simulated real-world work environments',
                'Experienced and internationally certified assessors',
                'Standardized testing procedures with employer-specific criteria',
                'Detailed written evaluation reports provided per candidate',
            ])
            . $html_h4('The Business Impact for HR Managers')
            . $html_p('Pre-deployment trade testing <strong>minimizes recruitment risks, reduces onboarding time</strong>, and enhances workforce productivity from Day 1. When a candidate arrives certified by OGS Trade Test Centre, you can deploy with complete confidence — knowing their verified skills match your operational requirements precisely.'),
    ],
];

if (Schema::hasTable('about_features')) {
    DB::table('about_features')->delete();
    foreach ($features as $f) {
        DB::table('about_features')->insert($f + [
            'icon_svg_url' => null,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    echo 'features ok: ' . count($features) . "\n";
}

// ─── Metrics (OGS Journey counters) ─────────────────────────────────────────
if (Schema::hasTable('about_metrics')) {
    DB::table('about_metrics')->delete();
    $metrics = [
        ['sort_order' => 1, 'icon' => '🏆', 'value' => '15+', 'label' => 'Years Experience'],
        ['sort_order' => 2, 'icon' => '👷', 'value' => '10K+', 'label' => 'Workers Deployed'],
        ['sort_order' => 3, 'icon' => '🏢', 'value' => '200+', 'label' => 'Clients'],
        ['sort_order' => 4, 'icon' => '🌍', 'value' => '5+', 'label' => 'Countries Served'],
    ];
    foreach ($metrics as $m) {
        DB::table('about_metrics')->insert($m + ['is_active' => 1, 'updated_at' => $now]);
    }
    echo "metrics ok\n";
}

// ─── Industries ─────────────────────────────────────────────────────────────
if (Schema::hasTable('about_industries')) {
    DB::table('about_industries')->delete();
    $industries = [
        ['Construction', 'icons/construction.png'],
        ['Oil & Gas', 'icons/oil-gas.png'],
        ['Hospitality', 'icons/hospitality.png'],
        ['Facility', 'icons/facility.png'],
        ['Security', 'icons/security.png'],
        ['Engineering', 'icons/engineering.png'],
    ];
    $i = 1;
    foreach ($industries as [$name, $icon]) {
        DB::table('about_industries')->insert([
            'sort_order' => $i++,
            'icon' => $icon,
            'name' => $name,
            'description' => null,
            'is_active' => 1,
            'updated_at' => $now,
        ]);
    }
    echo "industries ok\n";
}

// ─── CEO ────────────────────────────────────────────────────────────────────
if (Schema::hasTable('about_ceo')) {
    $ceo = [
        'name' => 'Abdul Basit Malik',
        'title' => 'CEO & Founder',
        'location' => 'Pakistan · UAE · UK',
        'photo_url' => null,
        'experience' => '25+',
        'quote' => 'At OGS Manpower, we are committed to delivering reliable, skilled, and pre-screened manpower solutions to global employers. With over 15 years of experience and operations across Pakistan, UAE, and the UK, we focus on quality, transparency, and long-term partnerships. Our goal is to support business growth while creating better career opportunities worldwide.',
        'bio' => '25+ Years · HR Leadership · Global Recruitment Expert · AI & IR 4.0',
        'tags' => 'OEP,Recruitment,AI,TVET',
        'creds' => json_encode([
            'Licensed Overseas Employment Promoter (2978/RWP)',
            'City & Guilds UK — Chief Master Trainer & Lead Assessor',
            'Chairman, Pakistan Digital Transformation Vision 2025 (RCCI)',
        ]),
        'is_active' => 1,
        'updated_at' => $now,
    ];
    if (DB::table('about_ceo')->exists()) {
        DB::table('about_ceo')->where('id', DB::table('about_ceo')->value('id'))->update($ceo);
    } else {
        DB::table('about_ceo')->insert($ceo + ['id' => 1]);
    }
    echo "ceo ok\n";
}

// ─── Offices ────────────────────────────────────────────────────────────────
if (Schema::hasTable('about_offices')) {
    DB::table('about_offices')->delete();
    $offices = [
        ['sort_order' => 1, 'flag' => '🇦🇪', 'country' => 'UAE', 'city' => 'Dubai'],
        ['sort_order' => 2, 'flag' => '🇵🇰', 'country' => 'Pakistan', 'city' => 'Rawalpindi'],
        ['sort_order' => 3, 'flag' => '🇬🇧', 'country' => 'United Kingdom', 'city' => 'London'],
    ];
    foreach ($offices as $o) {
        DB::table('about_offices')->insert($o + [
            'description' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'is_active' => 1,
            'updated_at' => $now,
        ]);
    }
    echo "offices ok\n";
}

// ─── Social links ───────────────────────────────────────────────────────────
if (Schema::hasTable('about_social_links')) {
    DB::table('about_social_links')->delete();
    $socials = [
        ['WhatsApp Group', '💬', 'https://chat.whatsapp.com/G0YIKjgkSy90j9bIN1LuDU?mode=gi_t'],
        ['Facebook', '📘', 'https://www.facebook.com/ogs.official'],
        ['TikTok', '🎵', 'https://www.tiktok.com/@ogs.manpower'],
        ['Instagram', '📸', 'https://www.instagram.com/ogsmanpower'],
        ['WhatsApp Channel', '💬', 'https://whatsapp.com/channel/0029VaCbduB9Gv7RhaugxS0Z'],
        ['X (Twitter)', '🐦', 'https://x.com/ogsmanpower'],
        ['LinkedIn', '💼', 'https://www.linkedin.com/company/ogsmanpower/'],
        ['YouTube', '▶️', 'https://youtube.com/@ogsgroupofficial'],
    ];
    $i = 1;
    foreach ($socials as [$platform, $icon, $url]) {
        DB::table('about_social_links')->insert([
            'sort_order' => $i++,
            'platform' => $platform,
            'icon' => $icon,
            'url' => $url,
            'is_active' => 1,
            'updated_at' => $now,
        ]);
    }
    echo "socials ok\n";
}

// ─── Config section titles ──────────────────────────────────────────────────
if (Schema::hasTable('about_config')) {
    $cfg = [
        'features_label' => 'Why Choose OGS',
        'features_title' => 'Why Employers Trust OGS',
        'features_intro' => 'Click any card below to explore our strengths in detail.',
        'journey_title' => 'OGS Journey',
        'global_title' => 'Our Global Presence',
        'portal_title' => 'Our Candidate Portal',
        'portal_subtitle' => 'Find Pre-Screened Candidates Instantly',
        'industries_title' => 'Industries We Serve',
        'connect_title' => 'Connect With OGS Manpower',
        'connect_subtitle' => 'Follow OGS across our channels for updates, opportunities, and community news.',
        'join_title' => 'Register with OGS Manpower',
        'site_email' => 'info@ogsmanpower.com',
    ];
    foreach ($cfg as $key => $value) {
        DB::table('about_config')->updateOrInsert(
            ['cfg_key' => $key],
            ['cfg_value' => $value, 'updated_at' => $now]
        );
    }
    echo "config ok\n";
}

// Clear about page caches
$keys = [
    'about_page_hero',
    'about_page_story',
    'about_page_features',
    'about_page_metrics',
    'about_page_industries',
    'about_page_ceo',
    'about_page_videos',
    'about_page_offices',
    'about_page_socials',
    'about_page_config',
];
foreach ($keys as $key) {
    Cache::forget($key);
}
echo "cache cleared\n";

echo "DONE\n";

    }
}

