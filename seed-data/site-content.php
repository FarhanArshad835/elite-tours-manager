<?php
/**
 * Site content seed data — homepage editorial copy, experience cards array,
 * and experience taxonomies. Returns an associative array of option_name =>
 * option_value, intended to be passed to update_option() by the seeder.
 *
 * Captured from the primary local development site on 2026-04-27. Image IDs
 * are intentionally 0 — they're populated separately by the experience
 * seeder's image-import step.
 */

defined( 'ABSPATH' ) || exit;

return [

    'et_homepage_settings' => [
        'hero_label'                => 'ELITE TOURS IRELAND · SINCE 1973',
        'hero_headline'             => 'Ireland,Experienced Properly.',
        'hero_subheading'           => 'Bespoke private journeys, tailored to you, delivered with genuine Irish care.',
        'hero_cta_primary'          => 'Visit the Emerald Isle',
        'hero_cta_secondary'        => 'Explore Our Tours',
        'hero_video_url'            => '',
        'hero_image_id'             => '',
        'hero_proof_text'           => 'Ireland\'s Highest-Rated Tour Provider on TripAdvisor',

        'trust_ta_sub'              => '5-Star Rated',
        'trust_failte_sub'          => 'Approved Partner',
        'trust_asta_sub'            => 'Member',
        'trust_iagto_sub'           => 'Golf Tourism',
        'trust_since_label'         => 'Since 1973',
        'trust_since_sub'           => '50+ years experience',
        'trust_failte_logo_id'      => '',
        'trust_asta_logo_id'        => '',
        'trust_iagto_logo_id'       => '',

        'stats_1_icon' => '', 'stats_1_label' => '', 'stats_1_desc' => '',
        'stats_2_icon' => '', 'stats_2_label' => '', 'stats_2_desc' => '',
        'stats_3_icon' => '', 'stats_3_label' => '', 'stats_3_desc' => '',
        'stats_4_icon' => '', 'stats_4_label' => '', 'stats_4_desc' => '',

        'intro_label'               => 'Who We Are',
        'intro_heading'             => 'More Than a Tour.A Deeper Connection to Ireland.',
        'intro_body'                => 'We offer a range of Private Tours for families and small groups to the most beautiful parts of Ireland from Shannon Airport, Limerick, Dublin, Cork, Killarney and Galway. These tours are designed to be highly personable giving each guest a unique Irish experience and trip across Ireland. It is about creating the perfect trip to Ireland for you and making sure you get a true Irish experience.',
        'intro_cta_text'            => 'The Elite Tours Story & About Us',
        'intro_cta_url'             => '/about-us/',
        'intro_badge_num'           => '50+',
        'intro_badge_text'          => 'Years ofExperience',
        'intro_image_id'            => '',

        'offer_1_label'             => 'Bespoke Private Tours',
        'offer_1_heading'           => 'Ireland,Built Around You.',
        'offer_1_desc'              => 'Deeply personal, privately guided journeys. Ancestry, culture, heritage, whiskey, scenic routes. No fixed itineraries. Everything designed from scratch, around the people taking it.',
        'offer_1_cta_text'          => 'Explore Bespoke Tours',
        'offer_1_cta_url'           => '/bespoke-tours/',
        'offer_1_image_id'          => '',

        'offer_2_label'             => 'Golf Tours',
        'offer_2_heading'           => 'Play Ireland\'s Greatest Courses.',
        'offer_2_desc'              => 'Fully managed golf journeys across Ireland\'s most iconic links, with priority access, private chauffeur, and Ray\'s personal hosting standard throughout.',
        'offer_2_cta_text'          => 'Explore Golf Tours',
        'offer_2_cta_url'           => '/golf-tours/',
        'offer_2_image_id'          => '',

        'process_label'             => 'The Process',
        'process_heading'           => 'Your Journey, From First Conversation to Final Day.',
        'process_cta_text'          => 'Start Planning Your Journey',
        'process_cta_url'           => '/contact/',
        'step_1_num' => '01', 'step_1_title' => 'We Listen',                       'step_1_desc' => 'Tell us who you are, what matters to you, and what you\'re hoping to feel. No forms. A real conversation.',
        'step_2_num' => '02', 'step_2_title' => 'We Design',                       'step_2_desc' => 'We create a bespoke itinerary built entirely around you. Your interests, your family, your pace.',
        'step_3_num' => '03', 'step_3_title' => 'We Handle Everything',            'step_3_desc' => 'From accommodation to access, transfers to timing, every detail is managed, so you don\'t have to think about a thing.',
        'step_4_num' => '04', 'step_4_title' => 'You Experience Ireland Properly', 'step_4_desc' => 'Arrive as a visitor. Leave with a deeper connection to Ireland, and often, a lifelong friend.',

        'exp_label'                 => '',
        'exp_heading'               => 'Every Journey Is Different. Here\'s Where Yours Might Begin.',
        'exp_1_label' => '', 'exp_1_title' => '', 'exp_1_desc' => '', 'exp_1_url' => '', 'exp_1_image_id' => '',
        'exp_2_label' => '', 'exp_2_title' => '', 'exp_2_desc' => '', 'exp_2_url' => '', 'exp_2_image_id' => '',
        'exp_3_label' => '', 'exp_3_title' => '', 'exp_3_desc' => '', 'exp_3_url' => '', 'exp_3_image_id' => '',
        'exp_4_label' => '', 'exp_4_title' => '', 'exp_4_desc' => '', 'exp_4_url' => '', 'exp_4_image_id' => '',
        'exp_5_label' => '', 'exp_5_title' => '', 'exp_5_desc' => '', 'exp_5_url' => '', 'exp_5_image_id' => '',
        'exp_6_label' => '', 'exp_6_title' => '', 'exp_6_desc' => '', 'exp_6_url' => '', 'exp_6_image_id' => '',

        'testimonials_label'        => 'Client Stories',
        'testimonials_heading'      => 'What Our Clients Say',
        'testimonials_sub'          => 'These are not reviews. These are stories.',
        't_1_name'                  => 'Beth G.',
        't_1_origin'                => 'TripAdvisor',
        't_1_quote'                 => 'Ray went above and beyond and completely transformed our trip from good to simply amazing. He took time to know us and customize a really special tour that was perfectly suited to our family. I cannot imagine trying to explore Ireland without him.',
        't_2_name'                  => 'Margaret B.',
        't_2_origin'                => 'TripAdvisor',
        't_2_quote'                 => 'Ray is more than a driver. He\'s a storyteller, a guide, and now, a dear friend. Whether we were at the Cliffs of Moher, winding through the Gap of Dunloe, or soaking in the charm of Cobh, Ray brought each place to life in a way only someone deeply connected to Ireland could.',
        't_3_name'                  => 'Ellie M.',
        't_3_origin'                => 'Boston',
        't_3_quote'                 => 'By the end of the trip, it felt like we were saying goodbye to a friend rather than a driver. Ray\'s insider tips led us away from the typical tourist crowds and gave us a more authentic experience. He is truly a gem, and we can\'t recommend him highly enough.',

        'founder_label'             => 'Plan Your Journey',
        'founder_heading'           => 'Start PlanningYour Journey.',
        'founder_body'              => 'Every journey is tailored to you, designed with care, local insight, and a deep understanding of Ireland.',
        'founder_quote'             => 'I\'ve spent decades helping people experience Ireland in a truly personal way.',
        'founder_cite'              => 'Raphael Mulally, Founder, Elite Tours Ireland',
        'founder_cta_text'          => 'Plan Your Journey',
        'founder_cta_url'           => '/contact/',
        'founder_image_id'          => '',

        'section_intro_visible'        => '1',
        'section_offers_visible'       => '1',
        'section_process_visible'      => '1',
        'section_experiences_visible'  => '1',
        'section_testimonials_visible' => '1',
        'section_founder-cta_visible'  => '1',
        'section_order'                => '["intro","offers","process","experiences","testimonials","founder-cta"]',
    ],

    'et_experiences' => [
        [
            'label'    => 'Ancestry, Culture & Scenery',
            'title'    => 'Bespoke Private Tour of Ireland',
            'desc'     => 'A fully bespoke private tour of Ireland, crafted around your interests, ancestry, and pace.',
            'url'      => '/bespoke-tours/',
            'type'     => 'bespoke',
            'duration' => 'bespoke',
            'image_id' => 0,
        ],
        [
            'label'    => 'Ancestry & Roots',
            'title'    => 'Trace Your Irish Heritage',
            'desc'     => 'Trace your Irish heritage with depth, dignity, and personal connection.',
            'type'     => 'photography',
            'duration' => 'bespoke',
            'url'      => '/bespoke-tours/',
            'image_id' => 0,
        ],
        [
            'label'    => 'Whiskey & Culture',
            'title'    => 'Ireland\'s Craft Distilleries',
            'desc'     => 'Ireland\'s craft distilleries and rich cultural story, privately curated.',
            'type'     => 'culinary',
            'duration' => '6-10',
            'url'      => '/experiences/',
            'image_id' => 0,
        ],
    ],

    'et_experience_taxonomies' => [
        'types' => [
            'photography' => 'Photography',
            'culinary'    => 'Culinary',
            'bespoke'     => 'Bespoke',
            'golf'        => 'Golf',
            'adventure'   => 'Adventure',
            'family'      => 'Family',
        ],
        'durations' => [
            'bespoke' => 'Bespoke',
            '6-10'    => '6-10 Days',
            '11-15'   => '11-15 Days',
        ],
    ],
];
