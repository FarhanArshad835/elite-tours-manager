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
        $this->log[] = 'Done.';
        return $this->log;
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
        $files = [
            'hero'           => '22.jpg',
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

        if ( $img['0.png'] )  set_post_thumbnail( $id_heritage,     $img['0.png'] );
        if ( $img['29.png'] ) set_post_thumbnail( $id_distilleries, $img['29.png'] );

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
                  'image_id' => $img['25.png'] ],
                [ 'pillar' => 'Craft', 'title' => 'The people who shape what is in the glass.',
                  'body' => 'Master distillers, head blenders, a still-room foreman in Cork. We secure private time with the people who matter most, never with marketers.',
                  'image_id' => $img['29.png'] ],
                [ 'pillar' => 'The Cask', 'title' => 'What the wood gives, and how it gives it.',
                  'body' => 'A morning at a working cooperage in Co. Tipperary. The smell of the kiln. The slow lean of the staves. The whiskey is shaped here as much as in the still.',
                  'image_id' => $img['28.png'] ],
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
