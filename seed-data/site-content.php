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
            'label'    => '11–15 Days · Fully Bespoke',
            'title'    => 'The Signature Ireland Journey',
            'desc'     => 'A privately curated journey through Ireland — Dublin & Ancient Ireland, the Atlantic Edge, and the Quiet North. Fully bespoke, hosted by Ray.',
            'url'      => '/experiences/signature-ireland-journey/',
            'type'     => 'bespoke',
            'duration' => '11-15',
            'image_id' => 0,
        ],
        [
            'label'    => '6–10 Days · Fully Bespoke',
            'title'    => 'The Essence of Ireland Experience',
            'desc'     => 'A refined version of the full experience for those with less time. Ireland\'s very best, without unnecessary movement.',
            'url'      => '/experiences/essence-of-ireland/',
            'type'     => 'bespoke',
            'duration' => '6-10',
            'image_id' => 0,
        ],
    ],

    // ── Regions of Ireland (Phase 5) ─────────────────────────
    // Each entry has the static data only. The seeder's seed_regions() method
    // imports the image_filename into the Media Library and merges the resulting
    // attachment ID back as 'image_id'. Render in page-experiences.php.
    'et_regions' => [
        [
            'slug'           => 'dublin-and-ancient-ireland',
            'title'          => 'Dublin & Ancient Ireland',
            'eyebrow'        => 'The Foundations',
            'blurb'          => 'Begin with the foundations of Ireland — its history, heritage, and deep-rooted connection to the world. Ancient landmarks, storytelling, and cultural context that set the tone for everything that follows.',
            'highlights'     => [
                'EPIC Museum & the real Irish emigration story',
                'Trinity College, Christ Church & Dublinia',
                'Viking walking tour with Brendan',
            ],
            'image_filename' => 'dublin-trinity-campanile.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature & Bespoke',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'cork-and-kinsale',
            'title'          => 'Cork & Kinsale',
            'eyebrow'        => 'A Softer, Coastal South',
            'blurb'          => 'Colourful towns, harbourside life, and rich maritime history — with moments of reflection, exploration, and understated luxury.',
            'highlights'     => [
                'Kinsale coastal stays — Actons, Perryville, Trident',
                'Cobh private walking tour with Michael Martin (Titanic & Lusitania storytelling)',
                'English Market in Cork & Cobh\'s historic harbour',
            ],
            'image_filename' => 'kinsale-colourful-houses.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature, Essence & Bespoke',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'kerry-and-dingle',
            'title'          => 'Kerry & Dingle',
            'eyebrow'        => 'Cinematic Coast',
            'blurb'          => 'One of the most visually striking parts of the country — winding coastal roads, dramatic scenery, and small towns full of character. Where Ireland feels raw, cinematic, and alive.',
            'highlights'     => [
                'Ring of Kerry & Daniel O\'Connell at Derrynane',
                'Slea Head Drive on the Dingle Peninsula',
                'Foxy John\'s pub & a Páidí Ó Sé\'s Irish coffee',
            ],
            'image_filename' => 'gap-of-dunloe.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature, Essence & Bespoke',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'south-and-west',
            'title'          => 'South & West (Limerick / Adare)',
            'eyebrow'        => 'Castles & The Atlantic Edge',
            'blurb'          => 'Castles, landscapes, and iconic experiences blend seamlessly with quieter, lesser-known stops. This is where the journey begins to shift — from seeing Ireland to truly feeling it.',
            'highlights'     => [
                'King John\'s Castle, Limerick — and Adare village',
                'Bunratty Castle medieval banquet (or Dromoland Castle stay)',
                'Cliffs of Moher, done properly via Doolin (not the bus tour)',
            ],
            'image_filename' => 'king-johns-castle-limerick.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature, Essence & Bespoke',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'galway',
            'title'          => 'Galway',
            'eyebrow'        => 'Music, Energy & Storytelling',
            'blurb'          => 'Where Ireland comes alive — music, history, and atmosphere. Often described as Ireland\'s most vibrant city, Galway draws people in and rarely lets go.',
            'highlights'     => [
                'Private walking tour — medieval Galway & Spanish Arch',
                'The Spanish Armada & origins of the "Black Irish"',
                'Glenlo Abbey — Orient Express dining experience',
            ],
            'image_filename' => 'galway-cathedral-river.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature & Essence',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'connemara',
            'title'          => 'Connemara',
            'eyebrow'        => 'Wild & Untouched',
            'blurb'          => 'The Ireland people imagine — rugged, quiet, and breathtaking. Wild, open, and untouched, with space to slow down.',
            'highlights'     => [
                'Sky Road — panoramic Atlantic views',
                'Abbeyglen Castle evening (live music, storytelling, sing-song)',
                'Ballynahinch Castle (fishing heritage, presidential history)',
            ],
            'image_filename' => 'kylemore-abbey-reflection.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature & Essence',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'mayo-and-ashford',
            'title'          => 'Mayo & Ashford',
            'eyebrow'        => 'Luxury Meets Authenticity',
            'blurb'          => 'World-class estates paired with genuine local experiences — often where the most memorable moments happen. Where world-class luxury meets real Irish character.',
            'highlights'     => [
                'Ashford Castle — one of the world\'s top hotels',
                'Falconry, horse riding & estate activities',
                'Ray\'s signature off-itinerary pub experience (Irish coffee, Guinness, live music)',
            ],
            'image_filename' => 'ashford-castle-fountain.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature Journey',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'sligo',
            'title'          => 'Sligo',
            'eyebrow'        => 'Poetry & Landscape',
            'blurb'          => 'Quiet, reflective Ireland — rich in poetry and natural beauty. The country of Yeats, walked slowly.',
            'highlights'     => [
                'Yeats Country drive beneath Benbulben',
                'Drumcliffe — W.B. Yeats\' resting place',
                'Coastal viewpoints, waterfalls & Strandhill',
            ],
            'image_filename' => 'sligo-benbulben.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature Journey',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'donegal',
            'title'          => 'Donegal',
            'eyebrow'        => 'Ireland at Its Most Raw',
            'blurb'          => 'Untouched, dramatic, and often missed — a true hidden Ireland. Untamed coastlines and powerful scenery that feel a world away from the expected.',
            'highlights'     => [
                'Slieve League Cliffs (higher than Moher, far less crowded)',
                'Wild Atlantic coastline drives',
                'Harvey\'s Point — renowned food & service',
            ],
            'image_filename' => 'donegal-atlantic-coast.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature Journey',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'derry-and-causeway',
            'title'          => 'Derry & The Causeway Coast',
            'eyebrow'        => 'History, Myth & Cinema',
            'blurb'          => 'A deeper understanding of Ireland\'s modern history meets some of the country\'s most unique landscapes. Where myth, geology, and film collide.',
            'highlights'     => [
                'Walk the historic Derry city walls — with Bloody Sunday context',
                'Giant\'s Causeway & the Dark Hedges (Game of Thrones)',
                'Bushmills Distillery & the Causeway coastal drive',
            ],
            'image_filename' => 'giants-causeway-basalt.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature Journey',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
        [
            'slug'           => 'belfast',
            'title'          => 'Belfast',
            'eyebrow'        => 'Modern, Complex & Compelling',
            'blurb'          => 'A city shaped by its past, now full of character, culture, and contrast. A powerful, modern finish to the Wild Atlantic Journey.',
            'highlights'     => [
                'Black taxi political tour — murals, history, storytelling',
                'Titanic Quarter & Titanic Belfast museum',
                'The Merchant Hotel & Cathedral Quarter culture',
            ],
            'image_filename' => 'belfast-titanic.jpg',
            'image_id'       => 0,
            'tour_link_text' => 'Featured in: Signature Journey',
            'tour_link_url'  => '/experiences/signature-ireland-journey/',
        ],
    ],

    // ── Hotels & Accommodation (Phase 4) ──────────────────────
    // 22 entries from the client's Accommodation Collection PDF, mapped to
    // the existing 3-category schema (castle / boutique / coastal). Image
    // IDs are 0 — seeded as text-only first; client to provide hotel
    // exteriors in a follow-up phase. Render in page-accommodation.php.
    'et_hotels' => [
        // Flagship Castle & Estate
        [ 'name' => 'Ashford Castle',          'location' => 'Co. Mayo',         'desc' => 'A seven-star-level estate experience and one of the best hotels in the world. Falconry, horse riding, and full estate activities — paired with Ray\'s signature off-itinerary moments at local pubs nearby.',     'category' => 'castle',   'url' => '', 'image_filename' => 'ashford-castle-fountain.jpg',     'image_id' => 0 ],
        [ 'name' => 'Dromoland Castle',        'location' => 'Co. Clare',        'desc' => 'A 16th-century castle set on 450 acres of parkland — a strong luxury anchor in the West and an ideal early stop in any Bespoke itinerary.',                                                                       'category' => 'castle',   'url' => '', 'image_filename' => 'hotel-dromoland-castle.jpg',      'image_id' => 0 ],
        [ 'name' => 'Ballynahinch Castle',     'location' => 'Connemara',        'desc' => 'Known for its fishing heritage and presidential history — American presidents have stayed here. Quiet, riverside, and steeped in story.',                                                                            'category' => 'castle',   'url' => '', 'image_filename' => 'hotel-ballynahinch-castle.jpg',   'image_id' => 0 ],
        [ 'name' => 'Lough Eske Castle',       'location' => 'Co. Donegal',      'desc' => 'A 5-star castle experience in the North-West. Lakeside privacy, dramatic scenery, and a refined base for exploring Donegal.',                                                                                       'category' => 'castle',   'url' => '', 'image_filename' => 'hotel-lough-eske-castle.jpg',     'image_id' => 0 ],
        [ 'name' => 'Glenlo Abbey',            'location' => 'Co. Galway',       'desc' => 'Famed for its Orient Express dining carriage — a unique culinary setting, paired with refined accommodation just outside Galway city.',                                                                              'category' => 'castle',   'url' => '', 'image_filename' => 'hotel-glenlo-abbey.jpg',          'image_id' => 0 ],
        [ 'name' => 'Abbeyglen Castle',        'location' => 'Connemara',        'desc' => 'One of the most memorable stays in Ireland — interactive dining, live music, storytelling, and Irish dancers. High-energy and unforgettable; Ireland with the volume up.',                                          'category' => 'castle',   'url' => '', 'image_filename' => 'hotel-abbeyglen-castle.jpg',      'image_id' => 0 ],

        // Iconic City & Manor (Boutique tier)
        [ 'name' => 'The Shelbourne',          'location' => 'Dublin',           'desc' => 'Dublin\'s most established 5-star city stay. Iconic, central, and steeped in Irish history.',                                                                                                                       'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-shelbourne-dublin.jpg',    'image_id' => 0 ],
        [ 'name' => 'The Merrion',             'location' => 'Dublin',           'desc' => 'Five-star city stay across four restored Georgian townhouses, with a celebrated art collection and refined dining.',                                                                                                'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-merrion-dublin.jpg',       'image_id' => 0 ],
        [ 'name' => 'The Merchant Hotel',      'location' => 'Belfast',          'desc' => 'The leading luxury hotel in Belfast. Distinctive, storied, and superbly located in the Cathedral Quarter.',                                                                                                          'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-merchant-belfast.jpg',     'image_id' => 0 ],
        [ 'name' => 'Hayfield Manor',          'location' => 'Cork',             'desc' => 'One of Ireland\'s finest 5-star hotels — boutique, intimate, and consistently high-end. The perfect Cork city base, with warmth and polish.',                                                                          'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-hayfield-manor.jpg',       'image_id' => 0 ],
        [ 'name' => 'The Hawthorn Hotel',      'location' => 'Co. Galway',       'desc' => 'A newer 5-star property — ideal for modern luxury and partnership programmes. Stylish, considered, and well-located.',                                                                                                'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-hawthorn-galway.jpg',      'image_id' => 0 ],
        [ 'name' => 'Bushmills Inn',           'location' => 'Northern Ireland', 'desc' => 'Strong character, tied to whiskey heritage. A quiet base near the Causeway Coast and Bushmills Distillery.',                                                                                                          'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-bushmills-inn.jpg',        'image_id' => 0 ],
        [ 'name' => 'Europa Hotel',            'location' => 'Belfast',          'desc' => 'Historic, story-rich, and memorable — one of Belfast\'s most recognised properties, with a long place in the city\'s past and present.',                                                                              'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-europa-belfast.jpg',       'image_id' => 0 ],
        [ 'name' => 'Westport (Curated 4-Star)','location' => 'Co. Mayo',        'desc' => 'A handpicked Westport stay chosen for comfort, charm, and location — selected fresh per journey based on availability and fit.',                                                                                       'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-westport-manor.jpg',       'image_id' => 0 ],
        [ 'name' => 'Derry City Hotel',        'location' => 'Co. Derry',        'desc' => 'A practical, well-located base for exploring Derry\'s walls and the Bogside.',                                                                                                                                          'category' => 'boutique', 'url' => '', 'image_filename' => 'hotel-derry-city.jpg',           'image_id' => 0 ],

        // Luxury Coastal & Scenic
        [ 'name' => 'Sheen Falls Lodge',       'location' => 'Kenmare, Kerry',   'desc' => 'A luxury countryside retreat with a waterfall setting — privacy, calm, and an ideal transition into the Ring of Kerry and the Southwest.',                                                                            'category' => 'coastal',  'url' => '', 'image_filename' => 'hotel-sheen-falls-kenmare.jpg',  'image_id' => 0 ],
        [ 'name' => 'Aghadoe Heights',         'location' => 'Killarney, Kerry', 'desc' => 'Scenic Killarney luxury — lake views, calm, and an ideal anchor for the South-West.',                                                                                                                                  'category' => 'coastal',  'url' => '', 'image_filename' => 'hotel-aghadoe-heights.jpg',       'image_id' => 0 ],
        [ 'name' => 'The Europe Hotel',        'location' => 'Killarney, Kerry', 'desc' => 'Five-star lakeside in Killarney — big, polished, and well-positioned for the Kerry section of any journey.',                                                                                                          'category' => 'coastal',  'url' => '', 'image_filename' => 'hotel-europe-killarney.jpg',      'image_id' => 0 ],
        [ 'name' => "Harvey's Point",          'location' => 'Co. Donegal',      'desc' => 'Renowned for food and warmth — often outperforming traditional 5-star stays. A standout Donegal experience by Lough Eske.',                                                                                            'category' => 'coastal',  'url' => '', 'image_filename' => 'hotel-harveys-point-donegal.jpg', 'image_id' => 0 ],
        [ 'name' => 'Kinsale — Curated Stays', 'location' => 'Co. Cork',         'desc' => 'We hand-pick the right Kinsale stay for each journey — Actons Hotel (waterfront, central), Perryville House (boutique, elevated), or Trident (harbour views). The exact choice depends on your journey and the season.', 'category' => 'coastal',  'url' => '', 'image_filename' => 'kinsale-colourful-houses.jpg',    'image_id' => 0 ],
        [ 'name' => 'Fishing Lodges',          'location' => 'Connemara',        'desc' => 'Fresh-catch dining, fireside atmosphere, and a remote, authentic setting. A different kind of stay — where the day ends with a rod and a fire.',                                                                       'category' => 'coastal',  'url' => '', 'image_filename' => 'hotel-fishing-lodges.jpg',        'image_id' => 0 ],
        [ 'name' => 'Private Estates & Residences','location' => 'Cork / Kerry / Connemara','desc' => 'A collection of private homes and estates across Ireland — fully serviced luxury residences with private chefs, full staff (optional), tailored dining, and complete privacy. For travellers who want space, privacy, and a more personal way to experience Ireland.', 'category' => 'castle',   'url' => '', 'image_filename' => 'hotel-private-estates.jpg',       'image_id' => 0 ],
    ],

    // ── Sample Itineraries (Phase 6) ──────────────────────────
    // 3 bespoke + 3 golf samples, region-level summaries (not day-by-day,
    // per client's "do not go into full detail" instruction). Bespoke
    // samples render on /bespoke-tours/, golf on /golf-tours/. Stored in
    // et_itineraries; admin can edit via Elite Tours > Itineraries.
    'et_itineraries' => [
        // Bespoke
        [
            'name'       => 'The Classic Signature',
            'meta'       => '13 days · Privately hosted · Spring–Autumn',
            'route'      => 'Dublin → Cork & Kinsale → Kerry & Dingle → Galway & Connemara → Mayo & Ashford → Sligo → Donegal → Causeway → Belfast',
            'highlights' => [
                'Cobh private walking tour with Michael Martin (Titanic & Lusitania storytelling)',
                'Slea Head Drive on the Dingle Peninsula',
                'Ashford Castle stay — falconry, horse riding, full estate experiences',
                'Slieve League Cliffs in Donegal — quieter than Moher, far less crowded',
                'Giant\'s Causeway and the Dark Hedges',
                'Black taxi political tour finish in Belfast',
                "Ray's signature off-itinerary pub experience along the way",
            ],
            'type'       => 'bespoke',
        ],
        [
            'name'       => 'The Essence — South & West',
            'meta'       => '8 days · Privately hosted · Year-round',
            'route'      => 'Dublin → Cork & Kinsale → Kerry & Dingle → Cliffs of Moher (via Doolin) → Galway → Dublin',
            'highlights' => [
                'Kinsale waterfront stay (Actons / Perryville House / Trident)',
                'Ring of Kerry & Daniel O\'Connell at Derrynane',
                'Cliffs of Moher — done properly via Doolin, not the bus',
                'Glenlo Abbey Orient Express dining experience',
                "Foxy John's pub & Páidí Ó Sé's Irish coffees",
            ],
            'type'       => 'bespoke',
        ],
        [
            'name'       => 'The Heritage Trace',
            'meta'       => '5–8 days · Genealogist research included · Year-round',
            'route'      => 'Dublin (genealogist briefing) → ancestral region (varies) → quiet return to Dublin',
            'highlights' => [
                'Professional genealogist file prepared months before you arrive',
                'Walk the parish, townland, schoolhouse, and church on foot',
                'Cousin introductions where the line is still living',
                'A bound family file — parish records, maps, photographs — to take home',
                'Quiet, slow pacing — the most personal journey we design',
            ],
            'type'       => 'bespoke',
        ],

        // Golf
        [
            'name'       => 'The Wild Atlantic Links',
            'meta'       => '8 days · 5 rounds · April–October',
            'route'      => 'Shannon → Lahinch → Doonbeg → Old Head of Kinsale → Tralee → Ballybunion → Dromoland Castle finish',
            'highlights' => [
                'Lahinch Golf Club — links golf at its finest, overlooking the Atlantic',
                'Old Head of Kinsale — one of the most spectacular settings in world golf',
                'Doonbeg — Greg Norman links on the Atlantic dunes',
                'Ballybunion — championship links on the Wild Atlantic Way',
                'Tralee — Arnold Palmer\'s first European links design',
                'Anchored by Dromoland Castle, paired with an Adare Manor finish',
            ],
            'type'       => 'golf',
        ],
        [
            'name'       => 'The Royal Tour',
            'meta'       => '10 days · 7 rounds · Top-100 courses · April–October',
            'route'      => 'Belfast → Royal County Down → Royal Portrush → Adare Manor → The K Club → Old Head of Kinsale → Lahinch',
            'highlights' => [
                'Royal County Down — consistently in the world\'s top 10',
                'Royal Portrush — Open Championship venue, North Coast drama',
                'Adare Manor — Ryder Cup 2027 host venue',
                'The K Club — twice Ryder Cup host',
                'Old Head of Kinsale — clifftop drama at its peak',
                'Lahinch — pure links character to finish',
            ],
            'type'       => 'golf',
        ],
        [
            'name'       => "The Connoisseur's Week",
            'meta'       => '6 days · 4 rounds · Boutique pacing · Year-round',
            'route'      => 'Shannon → Adare Manor → Old Head of Kinsale → Lahinch → Doonbeg → Shannon',
            'highlights' => [
                'Adare Manor as the anchor stay (Ryder Cup 2027 host)',
                'Old Head of Kinsale clifftop morning',
                'Lahinch links afternoon',
                'Doonbeg Greg Norman coastal session',
                'Long lunches, no rush — golf as a slow craft, not a checklist',
            ],
            'type'       => 'golf',
        ],
    ],

    // ── Key Experiences (Phase 2 — client's "Key experiences to build into
    // the website" list from Full list of experiences.txt). Renders on
    // /experiences/ as a featured grid below the 11 region cards. Each entry
    // is a small card: image + name + region tag + short blurb + optional CTA.
    'et_key_experiences' => [
        [ 'name' => 'Midleton Distillery',           'region' => 'Co. Cork',           'desc' => "Ireland's most famous distillery — single-pot still tradition, after-hours tasting access, the home of Jameson and Redbreast.",                                                          'image_filename' => 'distillery-barrels-irish.jpg', 'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Kinsale Memorial Garden',       'region' => 'Co. Cork',           'desc' => 'A quiet seafront garden remembering the Lusitania — a reflective pause in the colourful harbour town.',                                                                                  'image_filename' => 'kinsale-colourful-houses.jpg', 'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Charles Fort',                  'region' => 'Co. Cork',           'desc' => 'A 17th-century star-shaped fort on the Kinsale headland, with sweeping views of the harbour mouth.',                                                                                       'image_filename' => '16.png',                       'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Old Head of Kinsale',           'region' => 'Co. Cork',           'desc' => 'A peninsula jutting into the Atlantic — one of the most spectacular settings in world golf, walked privately at the right time of day.',                                                    'image_filename' => 'links-golf-coast.jpg',         'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Sheen Falls',                   'region' => 'Kenmare, Co. Kerry', 'desc' => 'Waterfall, lodge, and a quiet riverside walk before crossing into the Ring of Kerry.',                                                                                                    'image_filename' => 'muckross-lake-view.jpg',       'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Ring of Kerry',                 'region' => 'Co. Kerry',          'desc' => 'The classic Kerry loop — done at the right pace, in the right direction, stopping where the buses do not.',                                                                              'image_filename' => 'gap-of-dunloe.jpg',            'image_id' => 0, 'url' => '' ],
        [ 'name' => 'A fairy fort, a wish set',      'region' => 'Co. Kerry',          'desc' => 'A signature Ray moment — a quiet stop at a hidden ringfort, a stone placed, a wish made the old way.',                                                                                    'image_filename' => '23.png',                       'image_id' => 0, 'url' => '' ],
        [ 'name' => "Daniel O'Connell & Derrynane",  'region' => 'Co. Kerry',          'desc' => "The Liberator's family home and the Atlantic strand below it — a personal walk through 19th-century Irish history.",                                                                     'image_filename' => 'coastal-road-fog.jpg',         'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Slea Head Drive',               'region' => 'Dingle, Co. Kerry',  'desc' => 'The Dingle Peninsula loop — beehive huts, sea cliffs, the mainland edge of Europe.',                                                                                                       'image_filename' => '22.jpg',                       'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Dingle Peninsula',              'region' => 'Co. Kerry',          'desc' => "Cinematic, atmospheric, and full of character — Ray's favourite stretch of Ireland.",                                                                                                      'image_filename' => '17.png',                       'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Conor Pass scenic drive',       'region' => 'Co. Kerry',          'desc' => "Ireland's highest mountain pass — a slow, dramatic crossing with panoramic Atlantic views.",                                                                                              'image_filename' => 'conor-pass-panorama.jpg',      'image_id' => 0, 'url' => '' ],
        [ 'name' => "Foxy John's pub",               'region' => 'Dingle, Co. Kerry',  'desc' => 'Half pub, half hardware shop — pull a Guinness, buy a hammer. The kind of evening you cannot plan.',                                                                                      'image_filename' => '29.png',                       'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Limerick City & Adare',         'region' => 'Co. Limerick',       'desc' => "King John's Castle on the Shannon, then the thatched-cottage village of Adare — and Adare Manor next door.",                                                                                'image_filename' => 'king-johns-castle-limerick.jpg','image_id' => 0, 'url' => '' ],
        [ 'name' => 'Cliffs of Moher (via Doolin)',  'region' => 'Co. Clare',          'desc' => "Done properly — entering from Doolin, walking the rim before the buses arrive, looking *up* at the cliffs from the Doolin ferry below.",                                                  'image_filename' => 'cliffs-of-moher.jpg',          'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Dromoland Castle',              'region' => 'Co. Clare',          'desc' => 'A 16th-century estate on 450 acres of parkland — a strong luxury anchor early in the journey.',                                                                                            'image_filename' => 'hotel-dromoland-castle.jpg',   'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Galway',                        'region' => 'Co. Galway',         'desc' => 'Music, culture, energy — the most vibrant city on the island.',                                                                                                                              'image_filename' => 'galway-cathedral-river.jpg',   'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Connemara',                     'region' => 'Co. Galway',         'desc' => 'Wild, open, and untouched — the Ireland people imagine.',                                                                                                                                    'image_filename' => 'kylemore-abbey-reflection.jpg','image_id' => 0, 'url' => '' ],
        [ 'name' => 'Ashford Castle',                'region' => 'Co. Mayo',           'desc' => 'A seven-star-level estate experience and one of the best hotels in the world — falconry, horse riding, and Ray\'s off-itinerary pub finish.',                                              'image_filename' => 'ashford-castle-fountain.jpg',  'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Donegal Atlantic coastline',    'region' => 'Co. Donegal',        'desc' => "Untamed, dramatic, often missed — Slieve League cliffs higher than Moher, far less crowded.",                                                                                              'image_filename' => 'donegal-atlantic-coast.jpg',   'image_id' => 0, 'url' => '' ],
        [ 'name' => "Giant's Causeway",              'region' => 'Co. Antrim',         'desc' => 'Basalt columns, Atlantic spray, the geological story of Ireland told in stone.',                                                                                                            'image_filename' => 'giants-causeway-basalt.jpg',   'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Black Taxi political tour',     'region' => 'Belfast',            'desc' => "A privately hosted black-taxi tour through the murals and the history — a powerful, unflinching introduction to the city's past.",                                                          'image_filename' => 'hotel-europa-belfast.jpg',     'image_id' => 0, 'url' => '' ],
        [ 'name' => 'Titanic Quarter',               'region' => 'Belfast',            'desc' => "The shipyard where it was built, told properly. Titanic Belfast museum + the slipway where she was launched.",                                                                              'image_filename' => 'belfast-titanic.jpg',          'image_id' => 0, 'url' => '' ],
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
