<?php
/**
 * Plugin Name:   Elite Tours Manager
 * Description:   Content management panel for Elite Tours Ireland website. Last updated: April 2026.
 * Version:       1.2.26
 * Author:        Elite Tours Ireland
 * Text Domain:   elite-tours-manager
 * GitHub Plugin URI: FarhanArshad835/elite-tours-manager
 * Primary Branch:    main
 */

defined( 'ABSPATH' ) || exit;

define( 'ETM_VERSION', '1.12.1' );

// ── One-time migration: clear stale homepage settings so fresh defaults apply ─
if ( get_option( 'etm_migration_v130' ) !== 'done' ) {
    delete_option( 'et_homepage_settings' );
    update_option( 'etm_migration_v130', 'done' );
}

// ── One-time migration v1.5.0: Bespoke rename + intro CTA copy + flagship card ─
if ( get_option( 'etm_migration_v150' ) !== 'done' ) {

    // 1. Intro CTA copy: "Meet Our Story" → "The Elite Tours Story & About Us"
    $home = get_option( 'et_homepage_settings', [] );
    if ( is_array( $home ) && isset( $home['intro_cta_text'] ) && $home['intro_cta_text'] === 'Meet Our Story' ) {
        $home['intro_cta_text'] = 'The Elite Tours Story & About Us';
        update_option( 'et_homepage_settings', $home );
    }

    // 2. Experience taxonomy: rename "tailormade" → "bespoke" (preserve position)
    $tax = get_option( 'et_experience_taxonomies', [] );
    if ( ! empty( $tax['types'] ) && is_array( $tax['types'] ) && isset( $tax['types']['tailormade'] ) ) {
        $new_types = [];
        foreach ( $tax['types'] as $k => $v ) {
            if ( $k === 'tailormade' ) {
                if ( ! isset( $tax['types']['bespoke'] ) ) {
                    $new_types['bespoke'] = 'Bespoke';
                }
            } else {
                $new_types[ $k ] = $v;
            }
        }
        $tax['types'] = $new_types;
        update_option( 'et_experience_taxonomies', $tax );
    }

    // 3. Experience cards: re-tag tailormade → bespoke + ensure flagship card is first
    $exps = get_option( 'et_experiences', [] );
    if ( is_array( $exps ) ) {
        foreach ( $exps as &$e ) {
            if ( isset( $e['type'] ) && $e['type'] === 'tailormade' ) {
                $e['type'] = 'bespoke';
            }
        }
        unset( $e );

        $flagship_title = 'Bespoke Private Tour of Ireland';
        $existing_idx   = null;
        foreach ( $exps as $i => $e ) {
            if ( ( $e['title'] ?? '' ) === $flagship_title ) {
                $existing_idx = $i;
                break;
            }
        }
        if ( $existing_idx !== null && $existing_idx !== 0 ) {
            $card = $exps[ $existing_idx ];
            array_splice( $exps, $existing_idx, 1 );
            array_unshift( $exps, $card );
        } elseif ( $existing_idx === null ) {
            array_unshift( $exps, [
                'label'    => 'Ancestry, Culture & Scenery',
                'title'    => $flagship_title,
                'desc'     => 'A fully bespoke private tour of Ireland, crafted around your interests, ancestry, and pace.',
                'url'      => '/bespoke-tours/',
                'type'     => 'bespoke',
                'duration' => 'bespoke',
                'image_id' => 0,
            ] );
        }
        update_option( 'et_experiences', $exps );
    }

    update_option( 'etm_migration_v150', 'done' );
}

// ── One-time migration v1.6.0: et_experiences array → experience CPT posts ────
// Runs on init (priority 20) so the CPT is registered first (priority 10).
// Idempotent — skips entries already mapped or whose slug already exists.
if ( get_option( 'etm_migration_v160' ) !== 'done' ) {
    add_action( 'init', function () {
        if ( get_option( 'etm_migration_v160' ) === 'done' ) return;
        if ( ! post_type_exists( 'experience' ) ) return;

        $exps = get_option( 'et_experiences', [] );
        $map  = get_option( 'et_experience_cpt_map', [] );
        if ( ! is_array( $exps ) ) $exps = [];
        if ( ! is_array( $map ) )  $map  = [];

        foreach ( $exps as $exp ) {
            $title = $exp['title'] ?? '';
            if ( $title === '' ) continue;
            $slug  = sanitize_title( $title );
            if ( $slug === '' ) continue;

            // Already mapped?
            if ( ! empty( $map[ $slug ] ) && get_post_status( $map[ $slug ] ) ) continue;

            // CPT post with this slug already exists (manually created)? — link to it
            $existing = get_page_by_path( $slug, OBJECT, 'experience' );
            if ( $existing ) {
                $map[ $slug ] = (int) $existing->ID;
                continue;
            }

            // Create the CPT post
            $post_id = wp_insert_post( [
                'post_type'    => 'experience',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_excerpt' => sanitize_text_field( $exp['desc'] ?? '' ),
                'post_content' => '', // Sean fills in the blurb body later
            ] );
            if ( ! $post_id || is_wp_error( $post_id ) ) continue;

            // Featured image (from the array's image_id, if present)
            $image_id = absint( $exp['image_id'] ?? 0 );
            if ( $image_id ) {
                set_post_thumbnail( $post_id, $image_id );
            }

            // Seed funnel meta from the array entry
            update_post_meta( $post_id, '_etm_eyebrow',         sanitize_text_field( $exp['label']    ?? '' ) );
            update_post_meta( $post_id, '_etm_legacy_url',      esc_url_raw(         $exp['url']      ?? '' ) );
            update_post_meta( $post_id, '_etm_legacy_type',     sanitize_key(        $exp['type']     ?? '' ) );
            update_post_meta( $post_id, '_etm_legacy_duration', sanitize_key(        $exp['duration'] ?? '' ) );

            $map[ $slug ] = (int) $post_id;
        }

        update_option( 'et_experience_cpt_map', $map );
        update_option( 'etm_migration_v160', 'done' );
    }, 20 );
}
// ── One-time migration v1.7.0: seed et_golf_courses (Phase 1 CMS consolidation) ──
// Lifts the 11 hardcoded courses out of page-golf-tours.php into a wp_option so
// the Golf Tours and Experiences pages can both read from a single source of
// truth. Idempotent — only runs if et_golf_courses isn't already populated.
if ( get_option( 'etm_migration_v170' ) !== 'done' ) {
    if ( ! get_option( 'et_golf_courses' ) ) {
        update_option( 'et_golf_courses', [
            [ 'name' => 'Old Head of Kinsale',           'location' => 'Co. Cork',     'desc' => 'A peninsula jutting into the Atlantic — one of the most spectacular settings in world golf.',                'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Lahinch Golf Club',             'location' => 'Co. Clare',    'desc' => 'Links golf at its finest, overlooking the Atlantic. One of Ireland\'s most beloved courses.',                  'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Doonbeg (Trump International)', 'location' => 'Co. Clare',    'desc' => 'A Greg Norman links design carved into Atlantic dunes — modern drama on the Wild Atlantic Way.',              'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Royal County Down',             'location' => 'Co. Down',     'desc' => 'Consistently in the world\'s top 10. A links masterpiece beneath the Mourne Mountains.',                       'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Royal Portrush',                'location' => 'Co. Antrim',   'desc' => 'Open Championship venue. Drama, dunes, and the North Coast at its best.',                                      'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Adare Manor',                   'location' => 'Co. Limerick', 'desc' => 'Ryder Cup 2027 host venue. A neo-Gothic estate paired with a championship parkland course.',                  'url' => '', 'image_id' => 0 ],
            [ 'name' => 'The K Club',                    'location' => 'Co. Kildare',  'desc' => 'Twice Ryder Cup host. Refined parkland golf, an easy add-on from Dublin.',                                     'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Ballybunion Links',             'location' => 'Co. Kerry',    'desc' => 'Championship links on the Wild Atlantic Way. A bucket-list course for every serious golfer.',                'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Tralee Golf Club',              'location' => 'Co. Kerry',    'desc' => 'Arnold Palmer\'s first European links design — coastal, elevated, unforgettable.',                            'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Waterville Golf Links',         'location' => 'Co. Kerry',    'desc' => 'Remote, stunning, and unforgettable — bucket-list links on the Ring of Kerry.',                              'url' => '', 'image_id' => 0 ],
            [ 'name' => 'Portmarnock',                   'location' => 'Co. Dublin',   'desc' => 'A legendary championship links course north of Dublin. The capital\'s flagship.',                            'url' => '', 'image_id' => 0 ],
        ] );
    }
    update_option( 'etm_migration_v170', 'done' );
}

// ── One-time migration v1.8.0: seed editorial arrays for Bespoke / Golf / Accommodation / Contact pages ──
// Lifts hardcoded arrays out of page templates into wp_options so the new
// "Page Content" admin can manage them. Idempotent — only seeds options that
// don't already exist.
if ( get_option( 'etm_migration_v180' ) !== 'done' ) {

    // et_bespoke_journey_types deliberately removed — the 6-tile "Where Would
    // You Like to Begin?" section it powered was a carryover from early site
    // building, not in the client's content brief. The /bespoke-tours/
    // template no longer renders it (Two ways to travel + Duration Breakdown
    // already cover the bespoke decision flow). Existing data on live is
    // left untouched; the option simply isn't read anywhere any more.

    if ( ! get_option( 'et_bespoke_durations' ) ) {
        update_option( 'et_bespoke_durations', [
            [ 'num' => '6-10',  'title' => 'Days',    'desc' => 'A focused, deeply personal Ireland experience. Two to three regions, unhurried pace, time to truly settle in.' ],
            [ 'num' => '11-15', 'title' => 'Days',    'desc' => 'A comprehensive journey, west coast to east coast, with time to breathe in every region.' ],
            [ 'num' => '?',     'title' => 'Bespoke', 'desc' => "We'll design whatever length works best for your group. Tell us your dates and we'll build around them." ],
        ] );
    }

    if ( ! get_option( 'et_bespoke_includes' ) ) {
        update_option( 'et_bespoke_includes', [
            [ 'num' => '01', 'title' => 'Private Chauffeur',         'desc' => 'Door-to-door throughout your journey. Premium vehicles. No shared transfers.' ],
            [ 'num' => '02', 'title' => 'Custom Itinerary',          'desc' => 'Designed from scratch after your consultation. Built for you, nobody else.' ],
            [ 'num' => '03', 'title' => 'All Logistics',             'desc' => 'Accommodation, reservations, access, timing. All managed. You think about nothing.' ],
            [ 'num' => '04', 'title' => "Ray's Personal Standard",   'desc' => 'Every journey is shaped and overseen by Ray Mulally personally.' ],
        ] );
    }

    if ( ! get_option( 'et_golf_pillars' ) ) {
        update_option( 'et_golf_pillars', [
            [ 'num' => '01', 'title' => 'Golf-Led Personalisation', 'desc' => 'Built around you, not a pre-set route. Playing level, bucket list courses vs hidden gems, preferred pace, group dynamic. All considered before a single tee time is booked.' ],
            [ 'num' => '02', 'title' => "Ray's Personal Hosting",   'desc' => 'Every golf journey is personally overseen by Ray. Someone who knows the game, knows the country, and knows how to host properly. Present without intruding.' ],
            [ 'num' => '03', 'title' => 'Seamless Logistics',       'desc' => 'Tee time scheduling, private chauffeur between courses, club transport and handling, pre/post round timing. You never think about where to be or when to leave.' ],
            [ 'num' => '04', 'title' => 'Priority Course Access',   'desc' => "Ireland's top courses are seasonal and highly booked. We know when and how to secure the rounds that matter, through established relationships and strategic booking windows." ],
            [ 'num' => '05', 'title' => 'Curation Beyond Golf',     'desc' => 'Handpicked luxury accommodation near courses. Whiskey tastings. Coastal drives. Private dining. Post-round pub evenings. The full Ireland experience, built around the game.' ],
        ] );
    }

    if ( ! get_option( 'et_accommodation_category_intros' ) ) {
        update_option( 'et_accommodation_category_intros', [
            [ 'key' => 'castle',   'label' => 'Castle & Estate Hotels',   'title' => 'Sleep inside history.',              'desc' => 'Ashford, Dromoland, Ballynahinch, Lough Eske, Glenlo Abbey, Abbeyglen — and Private Estates by request. The flagship 5-star tier that anchors every premium itinerary.', 'image_id' => 0 ],
            [ 'key' => 'boutique', 'label' => 'Boutique & Country House', 'title' => 'Where authenticity balances luxury.', 'desc' => "Handpicked iconic city stays (Shelbourne, Merrion, Merchant) and high-end character hotels (Hayfield Manor, Bushmills Inn, Harvey's Point) where the welcome is as warm as the fire.", 'image_id' => 0 ],
            [ 'key' => 'coastal',  'label' => 'Luxury Coastal & Scenic',  'title' => 'Wake up to the Atlantic.',           'desc' => 'Sheen Falls Lodge, Aghadoe Heights, Kinsale curated stays, Fishing Lodges — properties chosen for their setting as much as their service.', 'image_id' => 0 ],
        ] );
    }

    if ( ! get_option( 'et_contact_interests' ) ) {
        update_option( 'et_contact_interests', [
            'Ancestry', 'Heritage & History', 'Whiskey & Culture', 'Golf', 'Scenic Ireland', 'Family Journey', 'Something Else',
        ] );
    }

    update_option( 'etm_migration_v180', 'done' );
}

// ── One-time migration v1.9.0: seed About Us editorial arrays + page strings ──
// Lifts hardcoded About Us arrays and disclaimer/note strings into wp_options
// so the "Page Content" admin can manage them. Idempotent — only seeds options
// that don't already exist.
if ( get_option( 'etm_migration_v190' ) !== 'done' ) {

    if ( ! get_option( 'et_about_dna' ) ) {
        update_option( 'et_about_dna', [
            [ 'title' => 'Hosted Ireland, not guided',
              'desc'  => "You aren't guided through Ireland — you are personally hosted. Ray is not a driver; he is the Irish connection. The trip is not scheduled — it is felt and adapted in real time." ],
            [ 'title' => 'Insider Ireland, not tourist Ireland',
              'desc'  => "No Guinness Storehouse. No gift-shop stops. The right entrance, the right pub, the right person — every time. Where the buses don't go. Who the tourists don't meet." ],
            [ 'title' => 'Emotion-led, not location-led',
              'desc'  => "Most tours sell Cliffs of Moher and Ring of Kerry. Ray delivers the silence moment that follows them — goosebumps, pride in heritage, a feeling that you've gone from observer to participant." ],
            [ 'title' => 'Ray <em>is</em> the product',
              'desc'  => 'Fifty-plus years on these roads, an unmatched book of relationships, an ear for what each guest actually wants to feel. The unfair advantage. No Ray — no Elite Tours.' ],
            [ 'title' => 'Controlled luxury — not sterilised',
              'desc'  => 'Five-star castles paired with the right village pub at the end of the day. Premium without losing soul. Authentic without losing comfort. The sweet spot most luxury operators miss.' ],
        ] );
    }

    if ( ! get_option( 'et_about_signature_moments' ) ) {
        update_option( 'et_about_signature_moments', [
            [ 'title' => 'The First Conversation',
              'desc'  => 'Sitting down with Ray at the start of the journey — setting expectations, understanding dreams, finding the personal connection. This is not logistics. This is emotional anchoring. <em>Your journey begins with a conversation, not a schedule.</em>' ],
            [ 'title' => 'Silence Moments',
              'desc'  => "The cliffs from the right angle (the Doolin ferry under them, not the bus-tour viewing platform). The Dingle Peninsula viewpoint before Inch Beach. O'Connor's Pass overlooking Tralee Bay. Donegal coastline. The kind of moments you remember for the rest of your life." ],
            [ 'title' => 'Local Immersion',
              'desc'  => "Sean's Bar in Athlone — the oldest pub in Ireland — for whiskey storytelling. Kane's Bar with a 12-year and the view. Foxy John's in Dingle. The Ivy Bar in Doolin for chowder. The kind of stops where you stop feeling like a tourist." ],
            [ 'title' => 'Story-Driven History',
              'desc'  => "EPIC Centre for the real Irish emigration story (not the Guinness version). The Derry walking tour with Bloody Sunday context. The Cobh Titanic & Lusitania truth. Clonmacnoise — the High Kings' burial ground. The visits where you finally understand Ireland." ],
            [ 'title' => 'Ray Knows Everyone',
              'desc'  => 'Private walking tours with Michael Martin in Cobh and Brian Kelly in Dublin. Shop owners, locals, characters introduced by name. Access most travellers will never get on their own. Status, but the kind built on relationships, not bookings.' ],
            [ 'title' => 'Done Properly',
              'desc'  => "Entering the Cliffs of Moher the right way. Avoiding the tourist-bus timing. Macroom backroads. The Kerry coastline that isn't on the postcards. The expert authority that comes from doing this for fifty-plus years on the same roads." ],
        ] );
    }

    if ( ! get_option( 'et_about_compare' ) ) {
        update_option( 'et_about_compare', [
            [ 'left' => 'Group tours that move people around in coaches',                  'right' => 'A privately hosted journey, end-to-end, designed around you' ],
            [ 'left' => 'Drivers who get you from A to B',                                  'right' => 'An Irish host who tells the stories, opens the doors, and stays with you the whole way' ],
            [ 'left' => 'The Guinness Storehouse and the gift-shop circuit',               'right' => "EPIC Museum, Sean's Bar, the after-hours visits — the Ireland most travellers never see" ],
            [ 'left' => 'Cold, corporate luxury — five-star and sterilised',               'right' => 'Five-star where it counts, paired with the right village pub at the end of the day' ],
            [ 'left' => 'Fixed itineraries, set in stone',                                 'right' => 'Built from a conversation, designed from scratch every time, adaptable in real time' ],
            [ 'left' => 'Volume over meaning',                                             'right' => 'Meaning above everything' ],
        ] );
    }

    if ( ! get_option( 'et_page_strings' ) ) {
        update_option( 'et_page_strings', [
            'bespoke_itinerary_disclaimer' => 'These are starting points. Your journey will be designed around you.',
            'golf_itinerary_disclaimer'    => 'All itineraries designed around the group. These are starting points only.',
            'golf_availability_note'       => "Availability at Ireland's top courses is limited, especially in peak season. We secure access through established relationships. Speak to us early.",
            'accommodation_trust_quote'    => "We have built relationships with Ireland's finest hotels over many years. This means preferred rooms, priority availability, and a personal welcome — not just a reservation. Many of the places we use are not widely known, and some are not publicly marketed in the traditional way.",
            'about_origin_story'           => "For many visitors, a trip to Ireland is one of the most meaningful journeys of their lives — a connection to ancestry, a long-held dream, a trip planned for years. Yet so often, the experience falls short. Rushed itineraries. Group buses. The Guinness Storehouse. Volume over meaning.\n\nElite Tours was built to be the alternative. Founded by Raphael Mulally — Ray — on decades of experience and a deep pride in the country, every journey is shaped by a single belief: clients deserve more than a tour. They deserve to feel completely looked after, understood, and genuinely connected to Ireland itself.\n\nWe are not a big operation. We don't want to be. We are a small, carefully run company that delivers an exceptional level of personal service, because that is the only way we know how to work — and the only way Ireland is properly understood.\n\n*The Ireland most people never see. Done properly.*",
        ] );
    }

    update_option( 'etm_migration_v190', 'done' );
}

// ── One-time migration v1.10.0: seed Page Heroes + Bottom CTAs ──────────────
// Lifts hardcoded hero blocks and bottom CTA sections from the page templates
// into wp_options keyed by page slug. Idempotent.
if ( get_option( 'etm_migration_v1100' ) !== 'done' ) {

    if ( ! get_option( 'et_page_heroes' ) ) {
        update_option( 'et_page_heroes', [
            'bespoke-tours' => [
                'eyebrow'        => '',
                'title'          => 'Your Ireland.<br>Built Around You.',
                'subtitle'       => 'No fixed routes. No templates. Every journey designed from scratch, for you.',
                'cta_text'       => 'Begin Your First Conversation',
                'cta_url'        => '/contact/',
                'image_id'       => 0,
                'image_filename' => 'winding-road.jpg',
            ],
            'golf-tours' => [
                'eyebrow'        => '',
                'title'          => "Play Ireland's<br>greatest courses.",
                'subtitle'       => "Old Head, Lahinch, Doonbeg, Royal County Down, Adare Manor — fully managed, privately hosted, with Ray's standard of care across every round, transfer, and evening.",
                'cta_text'       => 'Begin Your First Conversation',
                'cta_url'        => '/contact/',
                'image_id'       => 0,
                'image_filename' => 'golf-coastal.jpg',
            ],
            'experiences' => [
                'eyebrow'        => '',
                'title'          => 'Ireland in Eleven Regions.<br>One Carefully Designed Journey.',
                'subtitle'       => "From Dublin's foundations to the Causeway Coast, each region of Ireland brings its own character — its own people, landscapes, and stories. Below is the country we travel.",
                'cta_text'       => '',
                'cta_url'        => '',
                'image_id'       => 0,
                'image_filename' => 'irish-pub.jpg',
            ],
            'accommodation' => [
                'eyebrow'        => '',
                'title'          => 'Where you stay,<br>chosen for how it feels.',
                'subtitle'       => 'Accommodation throughout your journey is carefully selected to reflect both the standard of experience and the character of Ireland itself. From Ashford Castle to handpicked Kinsale stays, each location is chosen for how it contributes to the journey — not just for its star rating.',
                'cta_text'       => '',
                'cta_url'        => '',
                'image_id'       => 0,
                'image_filename' => 'gothic-castle.jpg',
            ],
            'about-us' => [
                'eyebrow'        => '',
                'title'          => 'Ireland, through Ray.',
                'subtitle'       => 'Elite Tours is not a tour company. It is a privately hosted experience of Ireland — built on more than fifty years of relationships across the country, and led personally by Ray himself.',
                'cta_text'       => '',
                'cta_url'        => '',
                'image_id'       => 0,
                'image_filename' => 'kylemore-abbey.jpg',
            ],
            'contact' => [
                'eyebrow'        => '',
                'title'          => 'Start Your Journey Here',
                'subtitle'       => "There are no fixed packages. No automated quote tools. Just a real conversation about who you are and what you'd love to experience in Ireland.",
                'cta_text'       => '',
                'cta_url'        => '',
                'image_id'       => 0,
                'image_filename' => '',
            ],
        ] );
    }

    if ( ! get_option( 'et_page_ctas' ) ) {
        update_option( 'et_page_ctas', [
            'bespoke-tours' => [
                'title'    => 'Ready to Begin?',
                'subtitle' => "Tell us who you are and what you're looking for. We'll come back to you personally, usually within 24 hours, with the start of your journey.",
                'cta_text' => 'Begin Your First Conversation',
                'cta_url'  => '/contact/',
            ],
            'golf-tours' => [
                'title'    => "Let's plan your golf journey.",
                'subtitle' => "Ireland's top courses book out early, especially in peak season — and Ryder Cup-host venues like Adare Manor and Royal County Down even earlier. The earlier you speak to us, the better we can secure the rounds that matter most.",
                'cta_text' => 'Begin Your First Conversation',
                'cta_url'  => '/contact/',
            ],
            'experiences' => [
                'title'    => "Don't See What You're Looking For?",
                'subtitle' => "We design experiences from scratch. Tell us what interests you and we'll build something entirely around it.",
                'cta_text' => 'Speak to Us',
                'cta_url'  => '/contact/',
            ],
            'accommodation' => [
                'title'    => 'All accommodation handled for you.',
                'subtitle' => 'Every stay across your Bespoke journey is selected, booked, and looked after by us — paired carefully so the rhythm of the trip flows from one to the next.',
                'cta_text' => 'Begin Your First Conversation',
                'cta_url'  => '/contact/',
            ],
            'about-us' => [
                'title'    => 'Every journey begins with a conversation.',
                'subtitle' => "Tell us a name, a region, a curiosity, a feeling — we'll write back within a working day.",
                'cta_text' => 'Begin Your First Conversation',
                'cta_url'  => '/contact/',
            ],
        ] );
    }

    update_option( 'etm_migration_v1100', 'done' );
}

// ── One-time migration v1.11.0: seed page editorial story blocks ─────────────
// Adds new keys to et_page_strings (Bespoke / Golf philosophy blocks +
// About Us Founder Feature). Idempotent — only adds keys that aren't set.
if ( get_option( 'etm_migration_v1110' ) !== 'done' ) {

    $strings = get_option( 'et_page_strings', [] );
    if ( ! is_array( $strings ) ) $strings = [];

    $defaults = [
        'bespoke_philosophy_title' => 'This Is Not a Tour Package',
        'bespoke_philosophy_body'  => "Most companies offer you a list of itineraries and ask you to choose. We don't.\n\nEvery Elite Tours journey begins with a conversation about who you are, what brought you to Ireland, and what you want to feel when you leave. Then we build it entirely around you.\n\nNo two tours are the same. That is not a marketing line. It is simply how we work.\n\nFrom ancestry searches in County Mayo to whiskey tastings on the Dingle Peninsula, every experience is chosen, sequenced, and delivered for the specific people taking it.",

        'golf_philosophy_title'      => 'This Is Not a Golf Package',
        'golf_philosophy_body'       => "We don't hand you a list of courses and ask you to pick three.\n\nWe design a golf experience around the golfer. Your handicap, your bucket list courses, your pace, your group dynamic. Every tee time, every transfer, every detail is managed. You simply show up and play.\n\nThis is what separates Elite Tours from every other golf operator in Ireland.",
        'golf_philosophy_blockquote' => 'The best golf trip of your life, without having to think about anything.',

        'about_founder_title'             => 'Raphael Mulally',
        'about_founder_subtitle'          => 'Founder, host & the Irish connection',
        'about_founder_body'              => "The product is not the route, the hotels, or the itinerary. It is Ray's perspective, his relationships, his storytelling, and his instinct — built across more than fifty years on these roads.\n\nRay knows everyone. Shop owners, local guides, the publican who'll open for a private after-hours visit, the cousin still on the family land. He is — to use his own word — a chameleon: equally at home pouring whiskey in a Donegal bar and seating clients at a long Dublin lunch. Clients are not processed; they are personally hosted, from the first conversation to the last goodbye.\n\nEvery Bespoke is designed by Ray himself. He still drives. He still tells the stories. He still sings, when the moment calls for it. **No Ray, no Elite Tours.** That has been the deal from the beginning, and it is what makes this company impossible to copy.",
        'about_founder_quote'             => "I've spent decades helping people experience Ireland in a truly personal way. The most memorable moments are usually the ones you never see coming.",
        'about_founder_quote_attribution' => 'Raphael Mulally · Founder, Elite Tours Ireland',
    ];

    foreach ( $defaults as $k => $v ) {
        if ( ! isset( $strings[ $k ] ) || $strings[ $k ] === '' ) {
            $strings[ $k ] = $v;
        }
    }
    update_option( 'et_page_strings', $strings );

    update_option( 'etm_migration_v1110', 'done' );
}

// ── One-time migration v1.12.0: restore <br> in homepage headings ──
// The pre-fix homepage admin save handler ran sanitize_text_field on heading
// fields, which strips ALL HTML tags including <br>. So any heading the user
// edited via admin lost its line breaks ("Ireland.<br>Very" → "Ireland.Very").
// This migration restores <br> in known headings if their stored value
// matches the stripped pattern but lacks the line break.
if ( get_option( 'etm_migration_v1120' ) !== 'done' ) {
    $hp = get_option( 'et_homepage_settings', [] );
    if ( is_array( $hp ) ) {
        // Map of heading key → known stripped pattern → fixed pattern with <br>.
        // Only patches if the current value matches the stripped form exactly,
        // so we don't overwrite custom user edits that already include <br>.
        $patches = [
            'intro_heading'        => [
                'Most people visit Ireland.Very few experience it properly.' => 'Most people visit Ireland.<br>Very few experience it properly.',
                'More Than a Tour.A Deeper Connection to Ireland.'           => 'More Than a Tour.<br>A Deeper Connection to Ireland.',
            ],
            'intro_badge_text'     => [
                'Years ofRelationships' => 'Years of<br>Relationships',
                'Years ofExperience'    => 'Years of<br>Experience',
            ],
        ];
        foreach ( $patches as $key => $map ) {
            if ( isset( $hp[ $key ] ) && isset( $map[ $hp[ $key ] ] ) ) {
                $hp[ $key ] = $map[ $hp[ $key ] ];
            }
        }
        update_option( 'et_homepage_settings', $hp );
    }
    update_option( 'etm_migration_v1120', 'done' );
}

define( 'ETM_PATH',    plugin_dir_path( __FILE__ ) );
define( 'ETM_URL',     plugin_dir_url( __FILE__ ) );

// ── Admin: append live deploy timestamp to plugin row ───────────────────────
add_filter( 'plugin_row_meta', function ( array $meta, string $file ): array {
    if ( $file === plugin_basename( __FILE__ ) ) {
        $ts     = filemtime( __FILE__ );
        $meta[] = 'Deployed: <strong>' . gmdate( 'j M Y, H:i', $ts ) . ' UTC</strong>';
    }
    return $meta;
}, 10, 2 );

// ── Auto-create pages (runs once per version) ────────────────────────────────
// v3 (Phase 8) adds Privacy Policy + Terms & Conditions pages.
if ( get_option( 'etm_pages_created_v3' ) !== 'done' ) {
    add_action( 'init', function () {
        $pages = [
            [ 'title' => 'Bespoke Tours',           'slug' => 'bespoke-tours',          'template' => 'page-bespoke-tours.php' ],
            [ 'title' => 'Golf Tours',              'slug' => 'golf-tours',             'template' => 'page-golf-tours.php' ],
            [ 'title' => 'Experiences',             'slug' => 'experiences',            'template' => 'page-experiences.php' ],
            [ 'title' => 'Accommodation',           'slug' => 'accommodation',          'template' => 'page-accommodation.php' ],
            [ 'title' => 'About Us',                'slug' => 'about-us',               'template' => 'page-about-us.php' ],
            [ 'title' => 'Blog',                    'slug' => 'blog',                   'template' => 'page-blog.php' ],
            [ 'title' => 'Contact',                 'slug' => 'contact',                'template' => 'page-contact.php' ],
            [ 'title' => 'Wishlist',                'slug' => 'wishlist',               'template' => 'page-wishlist.php' ],
            [ 'title' => 'Privacy Policy',          'slug' => 'privacy-policy',         'template' => 'page-privacy-policy.php' ],
            [ 'title' => 'Terms & Conditions',      'slug' => 'terms-and-conditions',   'template' => 'page-terms-and-conditions.php' ],
        ];

        foreach ( $pages as $p ) {
            $existing = get_page_by_path( $p['slug'] );
            if ( $existing ) {
                update_post_meta( $existing->ID, '_wp_page_template', $p['template'] );
                continue;
            }

            $page_id = wp_insert_post( [
                'post_title'   => $p['title'],
                'post_name'    => $p['slug'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
            ] );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_post_meta( $page_id, '_wp_page_template', $p['template'] );
            }
        }

        update_option( 'etm_pages_created_v3', 'done' );
    } );
}

// CPTs (must load on front-end too so single-experience.php template resolves)
require_once ETM_PATH . 'includes/cpt-experience.php';
require_once ETM_PATH . 'includes/contact-form.php';

// Page Heroes / CTAs — always loaded so the front-end render helpers
// (etm_render_page_hero / etm_render_page_cta) are available to themes.
// The admin UI render and AJAX handlers inside this file are no-op on
// the front-end (the menu callback isn't registered, ajax actions only
// fire on admin-ajax.php).
require_once ETM_PATH . 'includes/admin/pages/page-heroes.php';

// Load admin panel
if ( is_admin() ) {
    require_once ETM_PATH . 'includes/admin/lucide-icons.php';
    require_once ETM_PATH . 'includes/admin/class-admin-menus.php';
    require_once ETM_PATH . 'includes/admin/pages/site-settings.php';
    require_once ETM_PATH . 'includes/admin/pages/homepage.php';
    // experiences.php (legacy et_experiences option editor) and itineraries.php
    // (et_itineraries option editor) intentionally not loaded — both replaced
    // by the renamed Experience CPT ("Sample Itineraries") which is the single
    // source of truth for tour products.
    require_once ETM_PATH . 'includes/admin/pages/hotels.php';
    require_once ETM_PATH . 'includes/admin/pages/regions.php';
    require_once ETM_PATH . 'includes/admin/pages/key-experiences.php';
    require_once ETM_PATH . 'includes/admin/pages/golf-courses.php';
    require_once ETM_PATH . 'includes/admin/pages/page-content.php';
    require_once ETM_PATH . 'includes/admin/pages/funnel-leads.php';
    require_once ETM_PATH . 'includes/admin/pages/seed-content.php';
}
