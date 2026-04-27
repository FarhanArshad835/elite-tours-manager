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
        'hero_headline'             => 'Ireland,done properly.',
        'hero_subheading'           => 'Privately hosted journeys through Ireland — designed around you, guided by local expertise, and delivered with a level of care that turns travel into something far more meaningful.',
        'hero_cta_primary'          => 'Begin Your Journey',
        'hero_cta_secondary'        => 'Explore Our Tours',
        'hero_video_url'            => '',
        'hero_image_id'             => '',
        'hero_proof_text'           => 'Ireland\'s Highest-Rated Tour Provider on TripAdvisor · 50+ years of local relationships',

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
        'intro_heading'             => 'Most people visit Ireland.Very few experience it properly.',
        'intro_body'                => 'Elite Tours is not a tour company — it is a privately hosted experience of Ireland, led by Ray and built on more than fifty years of relationships across the country. Where the buses don\'t go, who the tourists don\'t meet, the right entrance, the right pub, the right person. Every journey is tailored end-to-end around the people travelling, with the flexibility to slow down where it matters and move on where it doesn\'t. This is Ireland through Ray — and the Ireland most people never see.',
        'intro_cta_text'            => 'The Elite Tours Story & About Us',
        'intro_cta_url'             => '/about-us/',
        'intro_badge_num'           => '50+',
        'intro_badge_text'          => 'Years ofRelationships',
        'intro_image_id'            => '',

        'offer_1_label'             => 'Bespoke Private Tours',
        'offer_1_heading'           => 'Ireland,built around you.',
        'offer_1_desc'              => 'Deeply personal, privately hosted journeys — 6 to 15 days, fully bespoke. Ancestry, culture, heritage, whiskey, scenic routes. No fixed itineraries, no group buses. Just Ireland, designed from scratch, around the people taking it.',
        'offer_1_cta_text'          => 'Explore Bespoke Tours',
        'offer_1_cta_url'           => '/bespoke-tours/',
        'offer_1_image_id'          => '',

        'offer_2_label'             => 'Golf Tours',
        'offer_2_heading'           => 'Play Ireland\'s greatest courses.',
        'offer_2_desc'              => 'Fully managed golf journeys across Ireland\'s most iconic links — Old Head, Lahinch, Doonbeg, Royal County Down. Priority tee times, private chauffeur, hand-picked accommodation, and Ray\'s personal hosting standard throughout.',
        'offer_2_cta_text'          => 'Explore Golf Tours',
        'offer_2_cta_url'           => '/golf-tours/',
        'offer_2_image_id'          => '',

        'process_label'             => 'The Process',
        'process_heading'           => 'Your journey begins with a conversation, not a schedule.',
        'process_cta_text'          => 'Begin Your First Conversation',
        'process_cta_url'           => '/contact/',
        'step_1_num' => '01', 'step_1_title' => 'The First Conversation',          'step_1_desc' => 'An hour on the phone with Ray. Setting expectations, understanding what brought you to Ireland, finding the personal connection. This is not logistics — it is emotional anchoring.',
        'step_2_num' => '02', 'step_2_title' => 'A Draft Itinerary',                'step_2_desc' => 'Within ten days you receive a written itinerary — routes, houses, experiences, and the reasoning behind every choice. Every line is yours to revise.',
        'step_3_num' => '03', 'step_3_title' => 'Quiet Preparation',                'step_3_desc' => 'Once you approve the shape, we book everything ourselves. A printed leather wallet arrives by post, two weeks before you leave.',
        'step_4_num' => '04', 'step_4_title' => 'Ireland, Properly',                'step_4_desc' => 'Ray meets you at Shannon, Dublin or Belfast. From that moment until you leave, you carry nothing but your camera. The trip adapts in real time — if something captures your attention, we stay; if it doesn\'t, we move on.',

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

        'founder_label'             => 'Begin Your Journey',
        'founder_heading'           => 'Ireland,through Ray.',
        'founder_body'              => 'The product is not the route, the hotels, or the itinerary. It is Ray\'s perspective, his relationships, his storytelling, and his instinct — built across more than fifty years on these roads. Every Bespoke is personally designed by Ray. Every conversation begins with him.',
        'founder_quote'             => 'I\'ve spent decades helping people experience Ireland in a truly personal way. The most memorable moments are usually the ones you never see coming.',
        'founder_cite'              => 'Raphael Mulally, Founder, Elite Tours Ireland',
        'founder_cta_text'          => 'Begin Your First Conversation',
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
