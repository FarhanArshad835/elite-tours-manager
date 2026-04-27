<?php
/**
 * Experience content seeder for Elite Tours Manager.
 *
 * Bundles the four one-shot seed scripts that previously lived under
 * .claude/ (seed-bespoke.php, seed-bespoke-images.php,
 * seed-bespoke-highlights.php, seed-other-experiences.php) into a single
 * idempotent class that ships with the plugin and can be triggered from
 * the WordPress admin (no WP-CLI / SSH required).
 *
 * Source images are read from {plugin}/seed-data/images/ so the seeder
 * works identically on local, staging, and the live site.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seeder version. Bump on every meaningful change to what gets seeded
 * (new images, new option keys, new steps). The number is shown in the
 * Seed Content admin page header so the live site can be checked at a glance.
 */
if ( ! defined( 'ETM_SEEDER_VERSION' ) ) define( 'ETM_SEEDER_VERSION', 10 );

class ETM_Experience_Seeder {

    /** @var string Absolute path to the bundled seed images. */
    private $img_dir;

    /** @var string[] Captured log lines from the current run. */
    private $log = [];

    public function __construct() {
        $this->img_dir = rtrim( ETM_PATH, '/\\' ) . '/seed-data/images';
    }

    /**
     * Run every seeder in order. Returns the captured log lines.
     *
     * @return string[]
     */
    public function run(): array {
        $this->log = [];

        if ( ! is_dir( $this->img_dir ) ) {
            $this->log[] = "ERROR: seed image directory not found: {$this->img_dir}";
            return $this->log;
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $this->log[] = '── Step 1: Bespoke Private Tour of Ireland — post + meta ──';
        $this->seed_bespoke();

        $this->log[] = '';
        $this->log[] = '── Step 2: Bespoke — images ──';
        $this->seed_bespoke_images();

        $this->log[] = '';
        $this->log[] = '── Step 3: Bespoke — highlights with images ──';
        $this->seed_bespoke_highlights();

        $this->log[] = '';
        $this->log[] = '── Step 4: Heritage + Distilleries — posts, meta, images ──';
        $this->seed_other_experiences();

        $this->log[] = '';
        $this->log[] = '── Step 5: Signature Journey + Essence — Bespoke tour products ──';
        $this->seed_signature_and_essence();

        $this->log[] = '';
        $this->log[] = '── Step 6: Homepage settings, experience cards, taxonomies ──';
        $this->seed_site_content();

        $this->log[] = '';
        $this->log[] = '── Step 7: Homepage hero / intro / offer / founder images ──';
        $this->seed_homepage_images();

        $this->log[] = '';
        $this->log[] = '── Step 8: Regions of Ireland — image attachments ──';
        $this->seed_region_images();

        $this->log[] = '';
        $this->log[] = '── Step 9: Hotel image attachments ──';
        $this->seed_hotel_images();

        $this->log[] = '';
        $this->log[] = 'Done.';
        return $this->log;
    }

    // ─── Step 5: Signature Journey + Essence Experience ──────────

    /**
     * Two new Bespoke tour products from the client's 2026-04-27 brief drop:
     *   - The Signature Ireland Journey  (11–15 days)
     *   - The Essence of Ireland Experience (6–10 days)
     *
     * Both follow the existing 7-section funnel template (hero / highlights /
     * story / pillars / process / CTA / similar) and reuse hero images already
     * bundled in seed-data/images/. They are added alongside the existing
     * Bespoke Private Tour, Heritage, and Distilleries — so the published
     * Experience CPT count goes 3 → 5 after this step.
     */
    private function seed_signature_and_essence(): void {
        $id_bespoke      = (int) ( get_page_by_path( 'bespoke-private-tour-of-ireland', OBJECT, 'experience' )->ID ?? 0 );
        $id_heritage     = (int) ( get_page_by_path( 'trace-your-irish-heritage',       OBJECT, 'experience' )->ID ?? 0 );
        $id_distilleries = (int) ( get_page_by_path( 'irelands-craft-distilleries',     OBJECT, 'experience' )->ID ?? 0 );

        $hero_signature_id = $this->seed_image( 'coastal-road-fog.jpg' );
        $hero_essence_id   = $this->seed_image( 'gap-of-dunloe.jpg' );
        $kylemore_id       = $this->seed_image( 'kylemore-abbey-reflection.jpg' );
        $muckross_id       = $this->seed_image( 'muckross-lake-view.jpg' );
        $cathedral_id      = $this->seed_image( 'galway-cathedral-river.jpg' );
        $links_id          = $this->seed_image( 'links-golf-coast.jpg' );
        $belfast_id        = $this->seed_image( 'belfast-titanic.jpg' );
        $sligo_id          = $this->seed_image( 'sligo-benbulben.jpg' );
        $causeway_30_id    = $this->seed_image( '30.png' );
        $cottage_23_id     = $this->seed_image( '23.png' );
        $portrait_id       = $this->seed_image( 'Raphell mulaly image.jpeg' );

        // ─── The Signature Ireland Journey (11–15 days) ─────────
        $id_signature = $this->get_or_create_post(
            'signature-ireland-journey',
            'The Signature Ireland Journey.',
            'A privately curated 11–15 day journey through Ireland — fully bespoke, hosted by Ray, designed around you.'
        );
        if ( $id_signature && $hero_signature_id ) set_post_thumbnail( $id_signature, $hero_signature_id );

        $signature_meta = [
            '_etm_eyebrow'                     => 'An Elite Tours Experience · 11–15 Days · Fully Bespoke',
            '_etm_hero_title_em'               => 'Signature Ireland Journey',
            '_etm_hero_deck'                   => 'A privately curated journey through Ireland, designed around you, guided by local expertise, and delivered with a level of care that turns travel into something far more meaningful.',
            '_etm_hero_breadcrumb'             => [ 'Bespoke Tours', 'Privately Hosted', 'The Signature Journey' ],
            '_etm_hero_meta_strip'             => [ '11–15 Days', 'Privately Hosted', 'Fully Bespoke' ],
            '_etm_hero_aside_text'             => 'This is not a fixed tour. From the moment you arrive, Ireland unfolds in layers — history, culture, landscape, and atmosphere — each carefully introduced, never rushed.',
            '_etm_hero_aside_facts'            => [
                [ 'label' => 'Length', 'value' => '11–15 days' ],
                [ 'label' => 'Pace',   'value' => 'Slow & considered' ],
            ],
            '_etm_hero_cta_primary'            => 'Begin Your Journey',
            '_etm_hero_cta_primary_url'        => '#et-exp-cta',
            '_etm_hero_cta_secondary'          => 'Speak to a Designer',
            '_etm_hero_cta_secondary_url'      => '#et-exp-cta',

            '_etm_highlights_heading'          => 'Highlights.',
            '_etm_highlights_intro'            => 'Four things every Signature Journey shares — each carefully held in place across the eleven to fifteen days you are with us.',
            '_etm_highlights'                  => [
                [ 'title' => 'A privately hosted journey, end-to-end',
                  'desc'  => 'Ray meets you at the airport and stays with you to the final day. No coach changes, no group rotations, no handoffs — one journey, one host, the whole way.',
                  'image_id' => $hero_signature_id ],
                [ 'title' => 'Ireland in layers, not in lists',
                  'desc'  => 'From vibrant cities to quiet coastal villages, world-class estates to hidden local spots — each part of the country is introduced at the right pace, with the right context.',
                  'image_id' => $cathedral_id ],
                [ 'title' => 'The full Wild Atlantic Journey',
                  'desc'  => 'Galway through Belfast, with Connemara, Mayo & Ashford, Sligo, Donegal and the Causeway Coast between. The most rugged stretches, the quietest beaches, the longest evenings.',
                  'image_id' => $sligo_id ],
                [ 'title' => "The moments you don't see coming",
                  'desc'  => 'Local introductions, off-itinerary stops, golden moments built from Ray\'s relationships across the country. Never planned in advance, never shared publicly.',
                  'image_id' => $cottage_23_id ],
            ],

            '_etm_story_heading_part1'         => 'Ireland,',
            '_etm_story_heading_part2'         => 'properly experienced.',
            '_etm_story_plate'                 => 'Plate 1 of 4 · The Whole Island',
            '_etm_story_lede'                  => 'You move effortlessly from vibrant cities to quiet coastal villages, from world-class estates to hidden local spots, experiencing the country in a way that feels both elevated and deeply authentic.',
            '_etm_story_para1'                 => 'Every journey is different. Every experience is intentional. And the most memorable moments are often the ones you never see coming — golden moments created through local relationships, instinct, and decades of experience that turn a great trip into something unforgettable.',
            '_etm_story_para2'                 => 'If something captures your attention, we stay. If something doesn\'t, we move on. That is the difference between a tour and a truly private journey.',
            '_etm_story_image_main'            => $muckross_id,
            '_etm_story_image_accent'          => $kylemore_id,
            '_etm_story_people_label'          => 'Your People for the Journey',
            '_etm_story_people'                => [
                [ 'name' => 'Raphael Mulally', 'alt' => '',
                  'role' => 'Founder & Personal Host',
                  'note' => 'Personally hosts every Signature Journey, end-to-end. Fifty-two years on these roads.' ],
            ],

            '_etm_pillars_heading_part1'       => 'Foundations, the Atlantic',
            '_etm_pillars_heading_part2'       => '& the Quiet North.',
            '_etm_pillars_subheading'          => 'Three movements, woven across two weeks.',
            '_etm_pillars_intro'               => 'The Signature Journey is built across three thematic movements. The proportions shift with your interests — some travellers spend a week in the south, others lean hard into the Atlantic. We listen, then design accordingly.',
            '_etm_pillars'                     => [
                [ 'pillar' => 'Foundations', 'title' => 'History, heritage & the south.',
                  'body' => 'Dublin & Ancient Ireland to set the foundations — landmarks, storytelling, and cultural context. Cork & Kinsale for colourful coastal towns and rich maritime history. Kerry & Dingle for cinematic coastal roads and small towns full of character.',
                  'image_id' => $cathedral_id ],
                [ 'pillar' => 'The Atlantic Edge', 'title' => 'Galway, Connemara & the wild west.',
                  'body' => 'Galway — Ireland at its most vibrant. Connemara — wild, open, and untouched. Mayo & Ashford — refined balance of luxury and authenticity. Sligo — a quieter, more reflective Ireland rich in poetry and atmosphere.',
                  'image_id' => $sligo_id ],
                [ 'pillar' => 'The Quiet North', 'title' => 'Donegal, Derry, the Causeway & Belfast.',
                  'body' => 'Donegal — dramatic, remote, often overlooked. Derry & The Causeway Coast — history, perspective, and natural wonder meet. Belfast — a powerful, modern finish to the journey, full of character and contrast.',
                  'image_id' => $belfast_id ],
            ],

            '_etm_process_card_eyebrow'        => 'The Method',
            '_etm_process_card_title'          => 'How a Signature Journey is Built',
            '_etm_process_card_subtitle'       => 'four conversations, then two weeks of Ireland',
            '_etm_process_steps'               => [
                [ 'number' => '01', 'title' => 'A first conversation',
                  'body' => 'An hour on the phone with Ray. Setting expectations, understanding what brought you to Ireland, and finding the personal connection that anchors the whole journey.' ],
                [ 'number' => '02', 'title' => 'A draft itinerary',
                  'body' => 'Within ten days, a written itinerary lands — routes, houses, experiences, and the reasoning behind every choice. Every line is yours to revise.' ],
                [ 'number' => '03', 'title' => 'Quiet preparation',
                  'body' => 'Once you approve the shape, we book everything ourselves. A printed leather wallet arrives by post two weeks before you leave.' ],
                [ 'number' => '04', 'title' => 'Ireland, properly',
                  'body' => 'Ray meets you at Shannon, Dublin or Belfast. From that moment until you leave, you carry nothing but your camera. The journey adapts in real time.' ],
            ],
            '_etm_process_aside_heading_part1' => 'Designed around',
            '_etm_process_aside_heading_part2' => 'your pace.',
            '_etm_process_aside_body'          => 'Nothing is fixed. The journey adapts in real time — if a place captures you, we stay. If something falls flat, we move on. That is the difference between a tour and a truly private journey.',
            '_etm_process_image_1'             => $hero_signature_id,
            '_etm_process_image_2'             => $kylemore_id,
            '_etm_process_facts'               => [
                [ 'label' => 'From',   'value' => 'On request' ],
                [ 'label' => 'Length', 'value' => '11–15 days' ],
                [ 'label' => 'Group',  'value' => '2 – 8' ],
            ],

            '_etm_cta_heading_part1'           => 'Every journey',
            '_etm_cta_heading_part2'           => 'begins with',
            '_etm_cta_heading_part3'           => 'a conversation.',
            '_etm_cta_body'                    => 'Tell us what brought you to Ireland — a name, a region, a curiosity, a feeling — and we will write back within a working day.',
            '_etm_cta_phone'                   => '+353 87 345 2874',
            '_etm_cta_email'                   => 'concierge@elitetours.ie',
            '_etm_cta_portrait'                => $portrait_id,
            '_etm_cta_quote'                   => 'From the moment you arrive, Ireland unfolds in layers — history, culture, landscape, and atmosphere — each carefully introduced, never rushed.',
            '_etm_cta_quote_attribution'       => 'Raphael Mulally · Founder, Elite Tours',

            '_etm_similar_heading_part1'       => 'Other ways',
            '_etm_similar_heading_part2'       => 'to experience Ireland.',
            '_etm_similar_view_all_text'       => 'View all experiences →',
            '_etm_similar_view_all_url'        => home_url( '/experiences/' ),
            '_etm_similar_ids'                 => array_filter( [ $id_bespoke, $id_heritage, $id_distilleries ] ),

            '_etm_card_meta'                   => '11–15 days · Fully bespoke · Privately hosted',
        ];
        if ( $id_signature ) {
            foreach ( $signature_meta as $k => $v ) update_post_meta( $id_signature, $k, $v );
            $this->log[] = "Seeded Signature Journey (post #{$id_signature}) — " . count( $signature_meta ) . " fields";
        }

        // ─── The Essence of Ireland Experience (6–10 days) ──────
        $id_essence = $this->get_or_create_post(
            'essence-of-ireland',
            'The Essence of Ireland Experience.',
            'A 6–10 day refined Bespoke journey — Ireland\'s most beautiful landscapes and meaningful history, without unnecessary movement.'
        );
        if ( $id_essence && $hero_essence_id ) set_post_thumbnail( $id_essence, $hero_essence_id );

        $essence_meta = [
            '_etm_eyebrow'                     => 'An Elite Tours Experience · 6–10 Days · Fully Bespoke',
            '_etm_hero_title_em'               => 'Essence of Ireland',
            '_etm_hero_deck'                   => 'For those with less time, this journey captures the very best of Ireland — without ever feeling rushed.',
            '_etm_hero_breadcrumb'             => [ 'Bespoke Tours', 'Privately Hosted', 'The Essence Experience' ],
            '_etm_hero_meta_strip'             => [ '6–10 Days', 'Privately Hosted', 'Fully Bespoke' ],
            '_etm_hero_aside_text'             => 'This is not a condensed tour. It is a refined version of the full experience — built around the right things, properly, within the time you have.',
            '_etm_hero_aside_facts'            => [
                [ 'label' => 'Length', 'value' => '6–10 days' ],
                [ 'label' => 'Pace',   'value' => 'Curated & calm' ],
            ],
            '_etm_hero_cta_primary'            => 'Begin Your Journey',
            '_etm_hero_cta_primary_url'        => '#et-exp-cta',
            '_etm_hero_cta_secondary'          => 'Speak to a Designer',
            '_etm_hero_cta_secondary_url'      => '#et-exp-cta',

            '_etm_highlights_heading'          => 'Highlights.',
            '_etm_highlights_intro'            => 'Four things every Essence Experience shares — Ireland\'s very best, delivered without overwhelm.',
            '_etm_highlights'                  => [
                [ 'title' => "Ireland's most beautiful landscapes",
                  'desc'  => 'Iconic cliffs, scenic routes, dramatic coastlines — chosen for distinction rather than fame.',
                  'image_id' => $hero_essence_id ],
                [ 'title' => 'Its most meaningful history',
                  'desc'  => 'The stories that anchor the country — told properly, by the people who keep them.',
                  'image_id' => $cathedral_id ],
                [ 'title' => 'Its most character-filled towns and cities',
                  'desc'  => 'Vibrant streets, harbourside life, music and atmosphere — places where Ireland feels alive.',
                  'image_id' => $kylemore_id ],
                [ 'title' => 'Without unnecessary movement or overwhelm',
                  'desc'  => 'Slow lunches, late starts, long evenings. Curated days that feel effortless rather than crammed.',
                  'image_id' => $muckross_id ],
            ],

            '_etm_story_heading_part1'         => 'A refined version',
            '_etm_story_heading_part2'         => 'of the full experience.',
            '_etm_story_plate'                 => 'Plate 1 of 3 · A Carefully Selected Blend',
            '_etm_story_lede'                  => 'For those with less time, this journey captures the very best of Ireland — without ever feeling rushed. Rather than trying to see everything, we focus on experiencing the right things, properly.',
            '_etm_story_para1'                 => 'No two 6–10 day journeys are the same. Some clients choose to immerse themselves in the South & West — coastal landscapes, historic towns, Ireland\'s most scenic routes. Others lean towards the West & North — wilder, more rugged, rich in story and contrast. And for many, the journey becomes a carefully selected blend of both.',
            '_etm_story_para2'                 => 'Our role is to guide that decision — and build a route that feels effortless, balanced, and complete.',
            '_etm_story_image_main'            => $hero_essence_id,
            '_etm_story_image_accent'          => $muckross_id,
            '_etm_story_people_label'          => 'Your People for the Journey',
            '_etm_story_people'                => [
                [ 'name' => 'Raphael Mulally', 'alt' => '',
                  'role' => 'Founder & Personal Host',
                  'note' => 'Personally designs every Essence Experience. The shape may be shorter — the care is the same.' ],
            ],

            '_etm_pillars_heading_part1'       => 'South & West, West & North,',
            '_etm_pillars_heading_part2'       => 'or a careful blend.',
            '_etm_pillars_subheading'          => 'Three route options, all hand-built around your time.',
            '_etm_pillars_intro'               => 'A 6–10 day journey usually leans into one of three shapes. Tell us which speaks to you and we\'ll build accordingly — or let us help you choose.',
            '_etm_pillars'                     => [
                [ 'pillar' => 'The South & West', 'title' => 'Coastal towns, castles & iconic scenery.',
                  'body' => 'Cork, Kinsale, Kerry, Dingle. Some of the country\'s most beautiful coastlines and most photographed routes — paced so they actually land. Ideal for first-time visitors.',
                  'image_id' => $hero_essence_id ],
                [ 'pillar' => 'The West & North', 'title' => 'Wilder, less-travelled Ireland.',
                  'body' => 'Galway, Connemara, Mayo, Sligo, Donegal. Dramatic coastlines, deeper history, quieter beauty. Ideal for returning travellers, or anyone wanting a less-photographed version of the country.',
                  'image_id' => $sligo_id ],
                [ 'pillar' => 'A Selected Blend', 'title' => 'A little of both, perfectly paced.',
                  'body' => 'For most travellers, the right answer is some of each — Cork & Kinsale, Kerry, then Galway and Connemara, finishing in the south or west. We listen first, then draw the route.',
                  'image_id' => $kylemore_id ],
            ],

            '_etm_process_card_eyebrow'        => 'The Method',
            '_etm_process_card_title'          => 'How an Essence Journey is Built',
            '_etm_process_card_subtitle'       => 'three conversations, then a careful week',
            '_etm_process_steps'               => [
                [ 'number' => '01', 'title' => 'A first conversation',
                  'body' => 'An hour on the phone with Ray. We discuss your timeframe, your interests, and the type of experience you want.' ],
                [ 'number' => '02', 'title' => 'The shape, drawn',
                  'body' => 'Within seven days you receive a draft itinerary — South & West, West & North, or a blend. Every choice is reasoned and revisable.' ],
                [ 'number' => '03', 'title' => 'Quiet preparation',
                  'body' => 'We book the houses, the access, the introductions. Your printed wallet arrives by post a week before you leave.' ],
                [ 'number' => '04', 'title' => 'Ireland, properly',
                  'body' => 'Ray meets you at the airport. From that moment until you leave, you simply travel. The route adapts as you go.' ],
            ],
            '_etm_process_aside_heading_part1' => 'The right things,',
            '_etm_process_aside_heading_part2' => 'properly, in the time you have.',
            '_etm_process_aside_body'          => 'Whether you have six days or ten, the goal remains the same: to experience Ireland properly, effortlessly, and without compromise.',
            '_etm_process_image_1'             => $links_id,
            '_etm_process_image_2'             => $causeway_30_id,
            '_etm_process_facts'               => [
                [ 'label' => 'From',   'value' => 'On request' ],
                [ 'label' => 'Length', 'value' => '6–10 days' ],
                [ 'label' => 'Group',  'value' => '2 – 8' ],
            ],

            '_etm_cta_heading_part1'           => 'Six days,',
            '_etm_cta_heading_part2'           => 'ten days,',
            '_etm_cta_heading_part3'           => 'or somewhere between.',
            '_etm_cta_body'                    => 'Tell us how much time you have and what you\'d hope to feel. We\'ll design the rest.',
            '_etm_cta_phone'                   => '+353 87 345 2874',
            '_etm_cta_email'                   => 'concierge@elitetours.ie',
            '_etm_cta_portrait'                => $portrait_id,
            '_etm_cta_quote'                   => 'Rather than trying to see everything, we focus on experiencing the right things, properly. That is the whole idea.',
            '_etm_cta_quote_attribution'       => 'Raphael Mulally · Founder, Elite Tours',

            '_etm_similar_heading_part1'       => 'Other ways',
            '_etm_similar_heading_part2'       => 'to experience Ireland.',
            '_etm_similar_view_all_text'       => 'View all experiences →',
            '_etm_similar_view_all_url'        => home_url( '/experiences/' ),
            '_etm_similar_ids'                 => array_filter( [ $id_signature, $id_bespoke, $id_heritage ] ),

            '_etm_card_meta'                   => '6–10 days · Fully bespoke · Privately hosted',
        ];
        if ( $id_essence ) {
            foreach ( $essence_meta as $k => $v ) update_post_meta( $id_essence, $k, $v );
            $this->log[] = "Seeded Essence Experience (post #{$id_essence}) — " . count( $essence_meta ) . " fields";
        }

        // Cross-link the original Bespoke entry to the two new tour products.
        if ( $id_bespoke && $id_signature && $id_essence ) {
            update_post_meta( $id_bespoke, '_etm_similar_ids', [ $id_signature, $id_essence, $id_heritage, $id_distilleries ] );
            $this->log[] = "Updated Bespoke similar links to include Signature + Essence";
        }
    }

    // ─── Step 9: Hotels image_id slots ───────────────────────────

    /**
     * Same pattern as seed_region_images: each hotel in et_hotels carries an
     * 'image_filename' string set by seed_site_content. This imports each
     * filename into the Media Library and merges the resulting attachment ID
     * back as 'image_id' so the page-accommodation template renders proper
     * cards instead of the generic-castle fallback.
     */
    private function seed_hotel_images(): void {
        $hotels = get_option( 'et_hotels', [] );
        if ( ! is_array( $hotels ) || empty( $hotels ) ) {
            $this->log[] = "et_hotels option is empty — skipping hotel images";
            return;
        }
        $wired = 0;
        foreach ( $hotels as $i => $hotel ) {
            $filename = $hotel['image_filename'] ?? '';
            if ( $filename === '' ) continue;
            $id = $this->seed_image( $filename );
            if ( $id ) {
                $hotels[ $i ]['image_id'] = $id;
                $wired++;
            }
        }
        update_option( 'et_hotels', $hotels );
        $this->log[] = "Wired image IDs for {$wired} of " . count( $hotels ) . " hotels";
    }

    // ─── Step 8: Regions image_id slots ──────────────────────────

    /**
     * The 11 region cards on the Experiences page each carry an
     * 'image_filename' string in et_regions (set by seed_site_content from
     * site-content.php). This method imports each filename into the Media
     * Library and merges the resulting attachment ID back as 'image_id' so
     * the page-experiences template can render full-bleed Pexels-grade
     * heroes per card.
     */
    private function seed_region_images(): void {
        $regions = get_option( 'et_regions', [] );
        if ( ! is_array( $regions ) || empty( $regions ) ) {
            $this->log[] = "et_regions option is empty — skipping region images";
            return;
        }
        $wired = 0;
        foreach ( $regions as $i => $region ) {
            $filename = $region['image_filename'] ?? '';
            if ( $filename === '' ) continue;
            $id = $this->seed_image( $filename );
            if ( $id ) {
                $regions[ $i ]['image_id'] = $id;
                $wired++;
            }
        }
        update_option( 'et_regions', $regions );
        $this->log[] = "Wired image IDs for {$wired} of " . count( $regions ) . " regions";
    }

    // ─── Step 7: Homepage image_id slots ─────────────────────────

    /**
     * Imports the bundled homepage hero/intro/offer/founder images and merges
     * their attachment IDs into et_homepage_settings. Runs *after*
     * seed_site_content() so it never gets clobbered by the editorial-copy seed.
     *
     * Image-to-slot mapping:
     *   hero_image_id       → coastal-road-fog.jpg       (4K, full-bleed safe)
     *   intro_image_id      → muckross-lake-view.jpg     (1200×900)
     *   offer_1_image_id    → gap-of-dunloe.jpg          (Bespoke offer block)
     *   offer_2_image_id    → links-golf-coast.jpg       (Golf offer block)
     *   founder_image_id    → Raphell mulaly image.jpeg  (reused from CTA portrait)
     */
    private function seed_homepage_images(): void {
        $hero_id     = $this->seed_image( 'coastal-road-fog.jpg' );
        $intro_id    = $this->seed_image( 'muckross-lake-view.jpg' );
        $offer_1_id  = $this->seed_image( 'gap-of-dunloe.jpg' );
        $offer_2_id  = $this->seed_image( 'links-golf-coast.jpg' );
        $founder_id  = $this->seed_image( 'Raphell mulaly image.jpeg' );

        $home = get_option( 'et_homepage_settings', [] );
        if ( ! is_array( $home ) ) $home = [];

        $home['hero_image_id']    = $hero_id    ?: '';
        $home['intro_image_id']   = $intro_id   ?: '';
        $home['offer_1_image_id'] = $offer_1_id ?: '';
        $home['offer_2_image_id'] = $offer_2_id ?: '';
        $home['founder_image_id'] = $founder_id ?: '';

        update_option( 'et_homepage_settings', $home );
        $this->log[] = "Wired homepage image IDs: hero={$hero_id} intro={$intro_id} offer_1={$offer_1_id} offer_2={$offer_2_id} founder={$founder_id}";

        // Also mirror founder image into Site Settings (et_site_settings.founder_image_id)
        $site = get_option( 'et_site_settings', [] );
        if ( ! is_array( $site ) ) $site = [];
        $site['founder_image_id'] = $founder_id ?: '';
        update_option( 'et_site_settings', $site );
    }

    // ─── Step 5: Homepage / experience-cards / taxonomies wp_options ────

    /**
     * Imports the bundled site-content.php into wp_options. Overwrites any
     * existing values for the keys it sets — safe because the keys it touches
     * (et_homepage_settings, et_experiences, et_experience_taxonomies) are
     * managed entirely through this plugin's admin screens.
     */
    private function seed_site_content(): void {
        $file = rtrim( ETM_PATH, '/\\' ) . '/seed-data/site-content.php';
        if ( ! file_exists( $file ) ) {
            $this->log[] = "MISSING: site-content.php";
            return;
        }
        $payload = include $file;
        if ( ! is_array( $payload ) ) {
            $this->log[] = "site-content.php did not return an array";
            return;
        }
        foreach ( $payload as $option_name => $value ) {
            update_option( $option_name, $value );
            $count = is_array( $value ) ? count( $value ) : 1;
            $this->log[] = "Set {$option_name} ({$count} keys)";
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Imports a bundled image into the Media Library (or returns the existing
     * attachment ID if it has already been imported). Idempotent via the
     * `_etm_seed_source` post meta key.
     */
    private function seed_image( string $filename ): int {
        $abs = $this->img_dir . '/' . $filename;
        if ( ! file_exists( $abs ) ) {
            $this->log[] = "MISSING: $filename";
            return 0;
        }

        $existing = get_posts( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'meta_key'       => '_etm_seed_source',
            'meta_value'     => $filename,
            'fields'         => 'ids',
        ] );
        if ( ! empty( $existing ) ) {
            $this->log[] = "Reusing #{$existing[0]}  ←  $filename";
            return (int) $existing[0];
        }

        $upload_dir = wp_upload_dir();
        $tmp = trailingslashit( $upload_dir['path'] ) . '_seed_tmp_' . $filename;
        if ( ! @copy( $abs, $tmp ) ) {
            $this->log[] = "FAIL copy: $filename";
            return 0;
        }
        $id = media_handle_sideload( [ 'name' => $filename, 'tmp_name' => $tmp ], 0 );
        if ( is_wp_error( $id ) ) {
            @unlink( $tmp );
            $this->log[] = "FAIL: " . $id->get_error_message();
            return 0;
        }
        update_post_meta( $id, '_etm_seed_source', $filename );
        $this->log[] = "Imported #{$id}  ←  $filename";
        return (int) $id;
    }

    private function get_or_create_post( string $slug, string $title, string $excerpt ): int {
        $existing = get_page_by_path( $slug, OBJECT, 'experience' );
        if ( $existing ) {
            wp_update_post( [
                'ID'           => $existing->ID,
                'post_title'   => $title,
                'post_status'  => 'publish',
                'post_excerpt' => $excerpt,
            ] );
            $this->log[] = "Updated existing post #{$existing->ID} — $slug";
            return (int) $existing->ID;
        }
        $post_id = wp_insert_post( [
            'post_type'    => 'experience',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_excerpt' => $excerpt,
            'post_content' => '',
        ] );
        if ( ! $post_id || is_wp_error( $post_id ) ) {
            $this->log[] = "FAIL creating post: $slug";
            return 0;
        }
        $this->log[] = "Created post #{$post_id} — $slug";
        return (int) $post_id;
    }

    // ─── Step 1: Bespoke post + meta ─────────────────────────

    private function seed_bespoke(): void {
        $post_id = $this->get_or_create_post(
            'bespoke-private-tour-of-ireland',
            'The Bespoke Private Tour of Ireland.',
            'Crafted around your interests, ancestry, and pace.'
        );
        if ( ! $post_id ) return;

        $meta = [
            '_etm_eyebrow'                     => 'An Elite Tours Experience · Ancestry, Culture & Scenery',
            '_etm_hero_title_em'               => 'Private Tour',
            '_etm_hero_deck'                   => 'Crafted around your interests, ancestry, and pace.',
            '_etm_hero_breadcrumb'             => [ 'Tailored Experiences', 'Ancestry, Culture & Scenery', 'The Bespoke Private Tour' ],
            '_etm_hero_meta_strip'             => [ 'The Whole Island', 'Privately Guided', 'Designed Around You' ],
            '_etm_hero_aside_text'             => 'A fully bespoke private tour of Ireland — designed end-to-end around the people travelling, with every road, room, and meeting chosen by hand.',
            '_etm_hero_aside_facts'            => [
                [ 'label' => 'Length', 'value' => '6 – 15 days' ],
                [ 'label' => 'Season', 'value' => 'Year-round' ],
            ],
            '_etm_hero_cta_primary'            => 'Begin Your Journey',
            '_etm_hero_cta_primary_url'        => '#et-exp-cta',
            '_etm_hero_cta_secondary'          => 'Speak to a Designer',
            '_etm_hero_cta_secondary_url'      => '#et-exp-cta',

            '_etm_highlights_number'           => '01',
            '_etm_highlights_label'            => 'The Experience at a Glance',
            '_etm_highlights_heading'          => 'Highlights',
            '_etm_highlights'                  => [
                [ 'title' => 'A private chauffeur-guide',
                  'desc'  => 'One person. The whole journey. Drivers who are also storytellers, fluent in the country they cross.' ],
                [ 'title' => 'Ancestry traced & visited',
                  'desc'  => 'Professional genealogists prepare your file before you arrive. Parish, townland, and — where we can — the cousins still on the land.' ],
                [ 'title' => 'Manor & castle accommodations',
                  'desc'  => 'Hand-picked houses chosen for character, not stars. Most evenings are spent somewhere with a name and a history.' ],
                [ 'title' => 'Quiet, exclusive access',
                  'desc'  => "Doors that are usually closed — a private after-hours visit, a poet at a table, a tasting before the distillery opens." ],
            ],

            '_etm_story_number'                => '02',
            '_etm_story_label'                 => 'The Story',
            '_etm_story_heading_part1'         => 'Ireland,',
            '_etm_story_heading_part2'         => 'your way.',
            '_etm_story_plate'                 => 'Plate 1 of 4 · The Western Counties',
            '_etm_story_lede'                  => 'The Bespoke is the journey we are best known for — built end-to-end around the people travelling. Where you go, how long you stay, and the rhythm of each day are decided in conversation with us, never lifted from a brochure.',
            '_etm_story_para1'                 => "Most begin with a single thread — a great-grandmother's parish in Mayo, a love of the western seaboard, a curiosity about the language, a quiet need to slow down. From there, we build outward: the route, the houses you sleep in, the people you meet, the meals at the end of each day.",
            '_etm_story_para2'                 => 'No two of these journeys have ever been the same. None ever will be.',
            '_etm_story_people_label'          => 'Your People for the Journey',
            '_etm_story_people'                => [
                [ 'name' => 'Raphael Mulally', 'alt' => '',
                  'role' => 'Founder & Lead Designer',
                  'note' => 'Personally designs every Bespoke. Fifty-two years on these roads.' ],
                [ 'name' => 'Sean & Niamh', 'alt' => '',
                  'role' => 'Chauffeur-Guides',
                  'note' => 'Drivers who are first historians, second hosts, third drivers.' ],
            ],

            '_etm_pillars_number'              => '03',
            '_etm_pillars_label'               => 'The Three Threads',
            '_etm_pillars_heading_part1'       => 'Ancestry, Culture',
            '_etm_pillars_heading_part2'       => '& Scenery.',
            '_etm_pillars_subheading'          => 'Three threads, woven to your weight.',
            '_etm_pillars_intro'               => 'Almost every Bespoke draws on these three pillars — but the proportions are entirely yours. Some travellers want a week of ancestry; others come for the landscape and stay for the music. We listen, then design accordingly.',
            '_etm_pillars'                     => [
                [ 'pillar' => 'Ancestry', 'title' => 'Find your people, then sit with them.',
                  'body' => 'We work with professional genealogists in Dublin and Belfast to trace your line, then plan parish visits, graveyard walks, and afternoons with the cousins still on the land.',
                  'image_id' => 0 ],
                [ 'pillar' => 'Culture', 'title' => 'Stories told by the people who keep them.',
                  'body' => 'Private fiddle sessions, a poet at a table in Kerry, a workshop with a Kilkenny stonemason. Culture is delivered through encounter, never through a coach window.',
                  'image_id' => 0 ],
                [ 'pillar' => 'Scenery', 'title' => 'The country, slowly, and from the right angles.',
                  'body' => "Roads that aren't on the maps, viewpoints reached before the buses, and the patience to wait for the light. Our drivers know exactly where to stop.",
                  'image_id' => 0 ],
            ],

            '_etm_process_number'              => '04',
            '_etm_process_label'               => 'The Process',
            '_etm_process_card_eyebrow'        => 'The Method',
            '_etm_process_card_title'          => 'How a Journey is Built',
            '_etm_process_card_subtitle'       => 'four conversations, then a week of Ireland',
            '_etm_process_steps'               => [
                [ 'number' => '01', 'title' => 'A first conversation',
                  'body' => 'An hour on the phone with a designer. We ask about who you are, what brought you to Ireland, and what would make the trip mean something.' ],
                [ 'number' => '02', 'title' => 'A draft itinerary',
                  'body' => 'Within ten days you receive a written itinerary — routes, houses, experiences, and the reasoning behind each choice. Every line is yours to revise.' ],
                [ 'number' => '03', 'title' => 'Quiet preparation',
                  'body' => 'Once you approve the shape, we book everything ourselves. A printed leather wallet arrives by post, two weeks before you leave.' ],
                [ 'number' => '04', 'title' => 'Ireland, properly',
                  'body' => 'Your driver-host meets you at Shannon, Dublin or Belfast. From that moment until you leave, you carry nothing but your camera.' ],
            ],
            '_etm_process_aside_heading_part1' => 'Built in four',
            '_etm_process_aside_heading_part2' => 'quiet conversations.',
            '_etm_process_aside_body'          => "We do not begin with a route. We begin with a phone call. The journey is shaped slowly, on your terms — and we carry every detail so you don't have to.",
            '_etm_process_facts'               => [
                [ 'label' => 'From',   'value' => '€1,650 / day' ],
                [ 'label' => 'Length', 'value' => '6 – 15 days' ],
                [ 'label' => 'Group',  'value' => '2 – 8' ],
            ],

            '_etm_cta_number'                  => '05',
            '_etm_cta_label'                   => 'Tailoring This Journey',
            '_etm_cta_heading_part1'           => 'We are',
            '_etm_cta_heading_part2'           => 'experience designers,',
            '_etm_cta_heading_part3'           => 'not tour operators.',
            '_etm_cta_body'                    => 'Every Bespoke is built around a quiet conversation. Tell us what brought you to Ireland — a name, a county, a curiosity — and we will write back within a working day.',
            '_etm_cta_phone'                   => '+353 87 345 2874',
            '_etm_cta_email'                   => 'concierge@elitetours.ie',
            '_etm_cta_quote'                   => "I've spent decades helping people experience Ireland in a truly personal way. The Bespoke is the journey closest to my heart.",
            '_etm_cta_quote_attribution'       => 'Raphael Mulally · Founder, Elite Tours',

            '_etm_similar_number'              => '06',
            '_etm_similar_label'               => 'You May Also Consider',
            '_etm_similar_heading_part1'       => 'Other experiences,',
            '_etm_similar_heading_part2'       => 'other quiet days.',
            '_etm_similar_view_all_text'       => 'View all experiences →',
            '_etm_similar_view_all_url'        => home_url( '/experiences/' ),

            '_etm_card_meta'                   => 'Multi-day · 6 – 15 nights',
        ];
        foreach ( $meta as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }
        $this->log[] = "Wrote " . count( $meta ) . " meta fields to post #{$post_id}.";
    }

    // ─── Step 2: Bespoke images ──────────────────────────────

    private function seed_bespoke_images(): void {
        // Hero is upgraded to a hi-res TripAdvisor shot (Gap of Dunloe, 1100×1467)
        // because the previous 22.jpg was 788×800 — too soft on a full-bleed hero.
        $files = [
            'hero'           => 'gap-of-dunloe.jpg',
            'story_main'     => '0.png',
            'story_accent'   => '23.png',
            'pillar_culture' => '29.png',
            'pillar_scenery' => '16.png',
            'process_1'      => '36.png',
            'raphael'        => 'Raphell mulaly image.jpeg',
        ];
        $ids = [];
        foreach ( $files as $slot => $filename ) {
            $ids[ $slot ] = $this->seed_image( $filename );
        }

        $post = get_page_by_path( 'bespoke-private-tour-of-ireland', OBJECT, 'experience' );
        if ( ! $post ) {
            $this->log[] = "Bespoke post not found.";
            return;
        }
        $post_id = (int) $post->ID;

        if ( $ids['hero'] ) {
            set_post_thumbnail( $post_id, $ids['hero'] );
            $this->log[] = "Set featured image: #{$ids['hero']}";
        }
        update_post_meta( $post_id, '_etm_story_image_main',   $ids['story_main']   ?: 0 );
        update_post_meta( $post_id, '_etm_story_image_accent', $ids['story_accent'] ?: 0 );

        $pillars = get_post_meta( $post_id, '_etm_pillars', true );
        if ( is_array( $pillars ) && count( $pillars ) >= 3 ) {
            $pillars[0]['image_id'] = $ids['story_accent']   ?: 0;
            $pillars[1]['image_id'] = $ids['pillar_culture'] ?: 0;
            $pillars[2]['image_id'] = $ids['pillar_scenery'] ?: 0;
            update_post_meta( $post_id, '_etm_pillars', $pillars );
            $this->log[] = "Patched pillar images.";
        }

        update_post_meta( $post_id, '_etm_process_image_1', $ids['process_1']  ?: 0 );
        update_post_meta( $post_id, '_etm_process_image_2', $ids['story_main'] ?: 0 );
        update_post_meta( $post_id, '_etm_cta_portrait',    $ids['raphael']    ?: 0 );
    }

    // ─── Step 3: Bespoke highlights ──────────────────────────

    private function seed_bespoke_highlights(): void {
        $id_cliffsAlt   = $this->seed_image( '16.png' );
        $id_cottage     = $this->seed_image( '23.png' );
        $id_manor       = $this->seed_image( '34.png' );
        $id_drawingRoom = $this->seed_image( '7.png'  );

        $post = get_page_by_path( 'bespoke-private-tour-of-ireland', OBJECT, 'experience' );
        if ( ! $post ) {
            $this->log[] = "Bespoke post not found.";
            return;
        }
        $post_id = (int) $post->ID;

        update_post_meta( $post_id, '_etm_highlights_heading', 'Highlights.' );
        update_post_meta( $post_id, '_etm_highlights_intro',   'Four things every Bespoke shares — held quietly in the background while the country reveals itself.' );

        $highlights = [
            [ 'title' => 'A private chauffeur-guide',
              'desc'  => 'One person. The whole journey. Drivers who are also storytellers, fluent in the country they cross.',
              'image_id' => $id_cliffsAlt ],
            [ 'title' => 'Ancestry traced & visited',
              'desc'  => 'Professional genealogists prepare your file before you arrive. Parish, townland, and — where we can — the cousins still on the land.',
              'image_id' => $id_cottage ],
            [ 'title' => 'Manor & castle accommodations',
              'desc'  => 'Hand-picked houses chosen for character, not stars. Most evenings are spent somewhere with a name and a history.',
              'image_id' => $id_manor ],
            [ 'title' => 'Quiet, exclusive access',
              'desc'  => "Doors that are usually closed — a private after-hours visit, a poet at a table, a tasting before the distillery opens.",
              'image_id' => $id_drawingRoom ],
        ];
        update_post_meta( $post_id, '_etm_highlights', $highlights );
        $this->log[] = "Patched highlights on post #{$post_id}";
    }

    // ─── Step 4: Heritage + Distilleries ─────────────────────

    private function seed_other_experiences(): void {
        $img_files = [
            '0.png','7.png','9.png','10.png','16.png','17.png','22.jpg','23.png',
            '25.png','26.png','27.png','28.png','29.png','30.png','33.png','34.png','36.png',
            'Raphell mulaly image.jpeg',
            // Hi-res whiskey/distillery shots (Pexels) for Distilleries hero + pillars.
            'distillery-barrels-irish.jpg',
            'whiskey-casks-warehouse.jpg',
            'copper-still-closeup.jpg',
        ];
        $img = [];
        foreach ( $img_files as $f ) {
            $img[ $f ] = $this->seed_image( $f );
        }

        $id_bespoke      = (int) ( get_page_by_path( 'bespoke-private-tour-of-ireland', OBJECT, 'experience' )->ID ?? 0 );
        $id_heritage     = $this->get_or_create_post(
            'trace-your-irish-heritage',
            'Trace Your Irish Heritage.',
            'A guided return to the parish, townland, and people your family came from.'
        );
        $id_distilleries = $this->get_or_create_post(
            'irelands-craft-distilleries',
            "Ireland's Craft Distilleries.",
            'A privately guided journey through the houses, the people, and the practice behind a quiet renaissance in Irish whiskey.'
        );

        if ( ! $id_heritage || ! $id_distilleries ) {
            $this->log[] = "Could not create Heritage / Distilleries posts.";
            return;
        }

        // Heritage post-thumbnail is upgraded to the hi-res Kylemore Abbey
        // reflection (1200×675) — the existing 0.png is 736×981 which softens
        // at full hero scale. Distilleries is upgraded to a hi-res whiskey
        // cask warehouse shot (1920×3410, Pexels) — the existing 29.png pub
        // corner was 600×900 and tonally pub-leaning rather than craft-leaning;
        // the warehouse better matches the "houses, people, and the practice"
        // copy of the experience.
        $kylemore_hires    = $this->seed_image( 'kylemore-abbey-reflection.jpg' );
        $whiskey_warehouse = $img['whiskey-casks-warehouse.jpg'] ?? 0;
        if ( $kylemore_hires )    set_post_thumbnail( $id_heritage,     $kylemore_hires );
        if ( $whiskey_warehouse ) set_post_thumbnail( $id_distilleries, $whiskey_warehouse );

        $heritage_meta = [
            '_etm_eyebrow'                     => 'An Elite Tours Experience · Ancestry & Roots',
            '_etm_hero_title_em'               => 'Heritage',
            '_etm_hero_deck'                   => 'A guided return to the parish, townland, and people your family came from.',
            '_etm_hero_breadcrumb'             => [ 'Tailored Experiences', 'Ancestry & Roots', 'Trace Your Irish Heritage' ],
            '_etm_hero_meta_strip'             => [ 'The Whole Island', 'Privately Researched', 'Quietly Met' ],
            '_etm_hero_aside_text'             => "A bespoke journey through your family's Ireland — researched in advance with professional genealogists, then walked together at your own pace.",
            '_etm_hero_aside_facts'            => [
                [ 'label' => 'Length', 'value' => '5 – 8 days' ],
                [ 'label' => 'Season', 'value' => 'Year-round' ],
            ],
            '_etm_hero_cta_primary'            => 'Begin Your Search',
            '_etm_hero_cta_primary_url'        => '#et-exp-cta',
            '_etm_hero_cta_secondary'          => 'Speak to a Genealogist',
            '_etm_hero_cta_secondary_url'      => '#et-exp-cta',

            '_etm_highlights_heading'          => 'Highlights.',
            '_etm_highlights_intro'            => 'Four things every Heritage journey shares — held quietly in the background while the country reveals itself.',
            '_etm_highlights'                  => [
                [ 'title' => "A genealogist's file, prepared",
                  'desc'  => 'We commission a professional Dublin or Belfast genealogist to research your line before you arrive — often months in advance.',
                  'image_id' => $img['9.png'] ],
                [ 'title' => 'The townland, walked slowly',
                  'desc'  => 'We find where your name belonged, and bring you there. The lane, the church, the field, the gravestone.',
                  'image_id' => $img['23.png'] ],
                [ 'title' => 'Cousins, where they exist',
                  'desc'  => 'Where the line is still living, we make the introduction. Tea is poured. Photographs are shared. No strangers leave that house.',
                  'image_id' => $img['17.png'] ],
                [ 'title' => 'Documents to take home',
                  'desc'  => "A bound family file — parish records, maps, photographs, and the genealogist's notes — yours to keep, designed to be kept.",
                  'image_id' => $img['33.png'] ],
            ],

            '_etm_story_heading_part1'         => 'Coming home,',
            '_etm_story_heading_part2'         => 'to a name.',
            '_etm_story_plate'                 => 'Plate 1 of 4 · The Western Counties',
            '_etm_story_lede'                  => 'For many of our guests, an Irish journey is not a holiday — it is a return to a name on a parish register, a townland on a worn family map, or a feeling that something here is theirs.',
            '_etm_story_para1'                 => 'Heritage is the most personal journey we design. It begins months before you fly, in the quiet work of a Dublin or Belfast genealogist tracing your line through baptismal records, land transfers, and the long, careful papers of the country.',
            '_etm_story_para2'                 => 'By the time you arrive, the work has been done. What remains is the walk.',
            '_etm_story_image_main'            => $img['9.png'],
            '_etm_story_image_accent'          => $img['10.png'],
            '_etm_story_people_label'          => 'Your People for the Journey',
            '_etm_story_people'                => [
                [ 'name' => 'Raphael Mulally', 'alt' => '',
                  'role' => 'Founder & Lead Designer',
                  'note' => 'Personally oversees every Heritage file. Fifty-two years on these roads.' ],
                [ 'name' => 'Áine & Conor', 'alt' => '',
                  'role' => 'Genealogists, Dublin & Belfast',
                  'note' => 'Forty years between them. Quiet specialists in parish, land, and famine-era records.' ],
            ],

            '_etm_pillars_heading_part1'       => 'Research, Walk',
            '_etm_pillars_heading_part2'       => '& Belong.',
            '_etm_pillars_subheading'          => 'Three movements, gently sequenced.',
            '_etm_pillars_intro'               => 'Every Heritage journey draws on these three movements, in this order — research before you arrive, walking the country with a guide, and where possible, the welcome of cousins still on the land.',
            '_etm_pillars'                     => [
                [ 'pillar' => 'Research', 'title' => "The genealogist's quiet preparation.",
                  'body' => 'Months before you fly, a professional genealogist in Dublin or Belfast traces your line through parish registers, land records, and gravestone surveys. The file arrives with you.',
                  'image_id' => $img['33.png'] ],
                [ 'pillar' => 'Walk', 'title' => 'The country, on the ground your family stood on.',
                  'body' => 'With your driver-host, we walk the parish, the townland, the schoolhouse, the church. We keep it slow. The smallest detail is sometimes the most affecting.',
                  'image_id' => $img['23.png'] ],
                [ 'pillar' => 'Belong', 'title' => 'Where the line is still living, the welcome is real.',
                  'body' => 'Where second cousins remain on the land, we make the introduction in advance. The kettle goes on. Photographs come out. The visit is on their terms, never ours.',
                  'image_id' => $img['17.png'] ],
            ],

            '_etm_process_card_eyebrow'        => 'The Method',
            '_etm_process_card_title'          => 'How a Heritage Journey is Built',
            '_etm_process_card_subtitle'       => 'two months of research, one week in Ireland',
            '_etm_process_steps'               => [
                [ 'number' => '01', 'title' => 'A first conversation',
                  'body' => "An hour by phone with a designer. Tell us what you know — names, towns, dates — and what you'd hope to feel." ],
                [ 'number' => '02', 'title' => "The genealogist's brief",
                  'body' => 'We commission a professional researcher with expertise in your county. Within four weeks, you receive a written file and a recommended itinerary.' ],
                [ 'number' => '03', 'title' => 'Quiet preparation',
                  'body' => 'Once you approve, we book everything — driver-host, accommodation, parish access, family introductions where possible.' ],
                [ 'number' => '04', 'title' => 'Ireland, properly',
                  'body' => 'Your driver meets you at Shannon, Dublin or Belfast. The file travels with you. From that moment, the work is done.' ],
            ],
            '_etm_process_aside_heading_part1' => 'Built in two',
            '_etm_process_aside_heading_part2' => 'phases of work.',
            '_etm_process_aside_body'          => 'Heritage cannot be rushed. We give the genealogist time, then we give you the country. By the time you arrive, every parish has been mapped, every family confirmed, every door asked about in advance.',
            '_etm_process_image_1'             => $img['9.png'],
            '_etm_process_image_2'             => $img['0.png'],
            '_etm_process_facts'               => [
                [ 'label' => 'From',   'value' => '€1,850 / day' ],
                [ 'label' => 'Length', 'value' => '5 – 8 days'  ],
                [ 'label' => 'Group',  'value' => '2 – 6'       ],
            ],

            '_etm_cta_heading_part1'           => 'We are',
            '_etm_cta_heading_part2'           => 'story-keepers,',
            '_etm_cta_heading_part3'           => 'as much as designers.',
            '_etm_cta_body'                    => "Tell us a name, a county, a half-remembered story — even a great-grandmother's first name and a port of departure. We will write back within a working day.",
            '_etm_cta_phone'                   => '+353 87 345 2874',
            '_etm_cta_email'                   => 'concierge@elitetours.ie',
            '_etm_cta_portrait'                => $img['Raphell mulaly image.jpeg'],
            '_etm_cta_quote'                   => 'There is something we do here that we do nowhere else: we walk people back to where they belong. It is the part of this work I love most.',
            '_etm_cta_quote_attribution'       => 'Raphael Mulally · Founder, Elite Tours',

            '_etm_similar_heading_part1'       => 'Other experiences,',
            '_etm_similar_heading_part2'       => 'other quiet days.',
            '_etm_similar_view_all_text'       => 'View all experiences →',
            '_etm_similar_view_all_url'        => home_url( '/experiences/' ),
            '_etm_similar_ids'                 => array_filter( [ $id_bespoke, $id_distilleries ] ),

            '_etm_card_meta'                   => 'Multi-day · 5 – 8 nights',
        ];
        foreach ( $heritage_meta as $k => $v ) update_post_meta( $id_heritage, $k, $v );
        $this->log[] = "Seeded Heritage (post #{$id_heritage}) — " . count( $heritage_meta ) . " fields";

        $distilleries_meta = [
            '_etm_eyebrow'                     => 'An Elite Tours Experience · Whiskey & Culture',
            '_etm_hero_title_em'               => 'Craft Distilleries',
            '_etm_hero_deck'                   => 'A privately guided journey through the houses, the people, and the practice behind a quiet renaissance in Irish whiskey.',
            '_etm_hero_breadcrumb'             => [ 'Tailored Experiences', 'Whiskey & Culture', "Ireland's Craft Distilleries" ],
            '_etm_hero_meta_strip'             => [ 'From Cork to Antrim', 'Privately Hosted', 'Designed to Slow' ],
            '_etm_hero_aside_text'             => 'A five-day private journey through six craft distilleries, three private tastings, and one cooperage — at the rhythm of a long, slow drink.',
            '_etm_hero_aside_facts'            => [
                [ 'label' => 'Length', 'value' => '5 – 7 days'  ],
                [ 'label' => 'Season', 'value' => 'Year-round' ],
            ],
            '_etm_hero_cta_primary'            => 'Plan Your Pilgrimage',
            '_etm_hero_cta_primary_url'        => '#et-exp-cta',
            '_etm_hero_cta_secondary'          => 'Speak to a Designer',
            '_etm_hero_cta_secondary_url'      => '#et-exp-cta',

            '_etm_highlights_heading'          => 'Highlights.',
            '_etm_highlights_intro'            => 'Four things every Distilleries journey shares — chosen for distinction rather than fame.',
            '_etm_highlights'                  => [
                [ 'title' => 'Six distilleries, three intimate',
                  'desc'  => 'Three of the houses we visit are still small enough to be opened by the people who run them. The other three are quietly storied.',
                  'image_id' => $img['25.png'] ],
                [ 'title' => 'Private tastings, hosted',
                  'desc'  => 'At each distillery, we secure a private flight before opening — guided not by a brand ambassador, but by a master distiller or blender.',
                  'image_id' => $img['26.png'] ],
                [ 'title' => 'A cooper at his bench',
                  'desc'  => 'One half-day at a working cooperage. Hands on the staves. Conversation with the man who builds the casks the whiskey will live in.',
                  'image_id' => $img['27.png'] ],
                [ 'title' => 'Tables that match the glass',
                  'desc'  => 'Every dinner is paired — a gastropub in West Cork, a tasting menu in Galway, a long Dublin lunch with a serious cellar.',
                  'image_id' => $img['28.png'] ],
            ],

            '_etm_story_heading_part1'         => 'A glass,',
            '_etm_story_heading_part2'         => 'carefully kept.',
            '_etm_story_plate'                 => 'Plate 1 of 4 · From Cork to Antrim',
            '_etm_story_lede'                  => 'Ireland once had two hundred working distilleries; for much of the last century, fewer than three remained. What is happening now is a quiet correction — small houses, careful hands, drinks made for keeping.',
            '_etm_story_para1'                 => 'We design this journey for travellers who already love whiskey and want to spend a week with the people making it. The route runs the country diagonally, from the Atlantic coast of Cork to the Antrim glens, with stops chosen for distinction rather than fame.',
            '_etm_story_para2'                 => 'It is, in the gentlest sense, a pilgrimage.',
            '_etm_story_image_main'            => $img['26.png'],
            '_etm_story_image_accent'          => $img['30.png'],
            '_etm_story_people_label'          => 'Your People for the Journey',
            '_etm_story_people'                => [
                [ 'name' => 'Raphael Mulally', 'alt' => '',
                  'role' => 'Founder & Lead Designer',
                  'note' => 'Designs every Distilleries route personally. A long-time student of the practice.' ],
                [ 'name' => 'Niall', 'alt' => 'Whiskey writer & host',
                  'role' => 'Whiskey Companion',
                  'note' => 'Two decades inside the trade. Hosts the private tastings on the road.' ],
            ],

            '_etm_pillars_heading_part1'       => 'Heritage, Craft',
            '_etm_pillars_heading_part2'       => '& the Cask.',
            '_etm_pillars_subheading'          => 'Three threads, distilled carefully.',
            '_etm_pillars_intro'               => 'Every Craft Distilleries journey draws on these three threads — the long heritage of Irish whiskey, the hands shaping it now, and the quiet patience of the cask.',
            '_etm_pillars'                     => [
                [ 'pillar' => 'Heritage', 'title' => 'The two-hundred-year tradition, retold.',
                  'body' => 'We visit three houses with century-old archives — Bushmills, Kilbeggan, and a smaller mill outside Dingle — to understand where the practice began before tasting where it is going.',
                  'image_id' => $img['distillery-barrels-irish.jpg'] ?? $img['25.png'] ],
                [ 'pillar' => 'Craft', 'title' => 'The people who shape what is in the glass.',
                  'body' => 'Master distillers, head blenders, a still-room foreman in Cork. We secure private time with the people who matter most, never with marketers.',
                  'image_id' => $img['copper-still-closeup.jpg'] ?? $img['29.png'] ],
                [ 'pillar' => 'The Cask', 'title' => 'What the wood gives, and how it gives it.',
                  'body' => 'A morning at a working cooperage in Co. Tipperary. The smell of the kiln. The slow lean of the staves. The whiskey is shaped here as much as in the still.',
                  'image_id' => $img['whiskey-casks-warehouse.jpg'] ?? $img['28.png'] ],
            ],

            '_etm_process_card_eyebrow'        => 'The Method',
            '_etm_process_card_title'          => 'How a Whiskey Journey is Built',
            '_etm_process_card_subtitle'       => 'five days, six distilleries, one quiet rhythm',
            '_etm_process_steps'               => [
                [ 'number' => '01', 'title' => 'A first conversation',
                  'body' => 'An hour by phone. Tell us your favourite expression, your tolerance for travel, and the kind of dinners you most enjoy.' ],
                [ 'number' => '02', 'title' => 'The route, drawn',
                  'body' => 'Within ten days you receive a written itinerary — six distilleries, three private hosts, the cooperage, and the meals at the end of each day.' ],
                [ 'number' => '03', 'title' => 'Quiet preparation',
                  'body' => 'Once you approve, we secure the private slots. Most of these visits cannot be booked online; we have spent years building the relationships that allow them.' ],
                [ 'number' => '04', 'title' => 'Ireland, properly',
                  'body' => 'Your driver meets you in Cork or Dublin. The itinerary travels with you in a leather wallet. Each evening ends with a long dinner.' ],
            ],
            '_etm_process_aside_heading_part1' => 'Built across',
            '_etm_process_aside_heading_part2' => 'five careful days.',
            '_etm_process_aside_body'          => 'Whiskey rewards patience. The journey is paced for it — late starts, slow lunches, no more than two distilleries in a single day. Every tasting is preceded by the walk that earned it.',
            '_etm_process_image_1'             => $img['27.png'],
            '_etm_process_image_2'             => $img['30.png'],
            '_etm_process_facts'               => [
                [ 'label' => 'From',   'value' => '€1,950 / day' ],
                [ 'label' => 'Length', 'value' => '5 – 7 days'  ],
                [ 'label' => 'Group',  'value' => '2 – 6'       ],
            ],

            '_etm_cta_heading_part1'           => 'We are',
            '_etm_cta_heading_part2'           => 'whiskey companions,',
            '_etm_cta_heading_part3'           => 'not tour guides.',
            '_etm_cta_body'                    => "Tell us how you take your whiskey — and what you'd hope to learn over five days. We will write back within a working day with the names of houses we'd take you to first.",
            '_etm_cta_phone'                   => '+353 87 345 2874',
            '_etm_cta_email'                   => 'concierge@elitetours.ie',
            '_etm_cta_portrait'                => $img['Raphell mulaly image.jpeg'],
            '_etm_cta_quote'                   => 'Whiskey is one of the most patient drinks we make. The way to learn it is to spend time with the people who give it that patience.',
            '_etm_cta_quote_attribution'       => 'Raphael Mulally · Founder, Elite Tours',

            '_etm_similar_heading_part1'       => 'Other experiences,',
            '_etm_similar_heading_part2'       => 'other quiet days.',
            '_etm_similar_view_all_text'       => 'View all experiences →',
            '_etm_similar_view_all_url'        => home_url( '/experiences/' ),
            '_etm_similar_ids'                 => array_filter( [ $id_bespoke, $id_heritage ] ),

            '_etm_card_meta'                   => 'Multi-day · 5 – 7 nights',
        ];
        foreach ( $distilleries_meta as $k => $v ) update_post_meta( $id_distilleries, $k, $v );
        $this->log[] = "Seeded Distilleries (post #{$id_distilleries}) — " . count( $distilleries_meta ) . " fields";

        if ( $id_bespoke ) {
            update_post_meta( $id_bespoke, '_etm_similar_ids', [ $id_heritage, $id_distilleries ] );
            $this->log[] = "Updated Bespoke similar links";
        }
    }
}
