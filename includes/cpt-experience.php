<?php
/**
 * Custom Post Type: experience
 * Powers the per-experience funnel pages (V2 editorial layout).
 * URLs: /experiences/{slug}/
 */
defined( 'ABSPATH' ) || exit;

// ── CPT registration ────────────────────────────────────────────────────────
add_action( 'init', function () {
    register_post_type( 'experience', [
        'labels' => [
            'name'               => 'Sample Itineraries',
            'singular_name'      => 'Sample Itinerary',
            'add_new'            => 'Add Sample Itinerary',
            'add_new_item'       => 'Add New Sample Itinerary',
            'edit_item'          => 'Edit Sample Itinerary',
            'new_item'           => 'New Sample Itinerary',
            'view_item'          => 'View Sample Itinerary',
            'search_items'       => 'Search Sample Itineraries',
            'not_found'          => 'No sample itineraries found',
            'not_found_in_trash' => 'No sample itineraries in trash',
            'menu_name'          => 'Sample Itineraries',
        ],
        'public'              => true,
        'has_archive'         => false,
        'rewrite'             => [ 'slug' => 'experiences', 'with_front' => false ],
        'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
        'menu_icon'           => 'dashicons-palmtree',
        'show_in_menu'        => 'elite-tours',
        'show_in_rest'        => true,
        'capability_type'     => 'post',
        'hierarchical'        => false,
    ] );
} );

// ── Flush rewrite rules once per template version ──────────────────────────
if ( get_option( 'etm_rewrite_v200' ) !== 'done' ) {
    add_action( 'init', function () {
        flush_rewrite_rules();
        update_option( 'etm_rewrite_v200', 'done' );
    }, 99 );
}

// ── Enqueue Media Library on experience edit screens ──────────────────────
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    global $post;
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
    if ( ! $post || $post->post_type !== 'experience' ) return;
    wp_enqueue_media();
} );

// ── Meta box ──────────────────────────────────────────────────────────────
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'etm_experience_funnel',
        'Funnel Page Fields (V2 — Editorial Layout)',
        'etm_render_experience_funnel_meta',
        'experience',
        'normal',
        'high'
    );
} );

/**
 * Tiny helpers used inside the meta-box.
 */
function etm_field_text( string $name, string $value, string $placeholder = '' ): void {
    printf(
        '<input type="text" name="%s" value="%s" placeholder="%s" class="widefat">',
        esc_attr( $name ),
        esc_attr( $value ),
        esc_attr( $placeholder )
    );
}
function etm_field_url( string $name, string $value, string $placeholder = '' ): void {
    printf(
        '<input type="url" name="%s" value="%s" placeholder="%s" class="widefat">',
        esc_attr( $name ),
        esc_attr( $value ),
        esc_attr( $placeholder )
    );
}
function etm_field_textarea( string $name, string $value, string $placeholder = '', int $rows = 3 ): void {
    printf(
        '<textarea name="%s" placeholder="%s" rows="%d" class="widefat">%s</textarea>',
        esc_attr( $name ),
        esc_attr( $placeholder ),
        $rows,
        esc_textarea( $value )
    );
}
function etm_field_image( string $name, int $attach_id ): void {
    $url = $attach_id ? wp_get_attachment_image_url( $attach_id, 'thumbnail' ) : '';
    ?>
    <div class="etm-image-field">
        <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo (int) $attach_id; ?>" class="etm-image-id">
        <div class="etm-image-preview">
            <?php if ( $url ) : ?>
                <img src="<?php echo esc_url( $url ); ?>" alt="">
            <?php else : ?>
                <span class="etm-image-empty">No image selected</span>
            <?php endif; ?>
        </div>
        <button type="button" class="button etm-image-pick">Choose / change image</button>
        <button type="button" class="button etm-image-clear">Remove</button>
    </div>
    <?php
}

function etm_render_experience_funnel_meta( WP_Post $post ): void {
    wp_nonce_field( 'etm_experience_funnel', 'etm_experience_funnel_nonce' );

    $get = function( string $key, $default = '' ) use ( $post ) {
        $v = get_post_meta( $post->ID, '_etm_' . $key, true );
        return $v === '' ? $default : $v;
    };
    $get_arr = function( string $key ) use ( $post ): array {
        $v = get_post_meta( $post->ID, '_etm_' . $key, true );
        return is_array( $v ) ? $v : [];
    };

    // Existing experience posts (for the linked-experience and similar selectors).
    $other_experiences = get_posts( [
        'post_type'      => 'experience',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'exclude'        => [ $post->ID ],
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );

    $highlights      = $get_arr( 'highlights' );
    if ( empty( $highlights ) ) $highlights = [ [ 'title' => '', 'desc' => '', 'image_id' => 0 ] ];

    $story_people    = $get_arr( 'story_people' );
    if ( empty( $story_people ) ) $story_people = [ [ 'name' => '', 'alt' => '', 'role' => '', 'note' => '' ] ];

    $pillars         = $get_arr( 'pillars' );
    if ( empty( $pillars ) ) $pillars = [ [ 'pillar' => '', 'title' => '', 'body' => '', 'image_id' => 0 ] ];

    $process_steps   = $get_arr( 'process_steps' );
    if ( empty( $process_steps ) ) $process_steps = [ [ 'number' => '', 'title' => '', 'body' => '' ] ];

    $process_facts   = $get_arr( 'process_facts' );
    if ( empty( $process_facts ) ) $process_facts = [ [ 'label' => '', 'value' => '' ] ];

    $hero_aside_facts = $get_arr( 'hero_aside_facts' );
    if ( empty( $hero_aside_facts ) ) $hero_aside_facts = [ [ 'label' => '', 'value' => '' ] ];

    $hero_breadcrumb = $get_arr( 'hero_breadcrumb' );
    $hero_meta_strip = $get_arr( 'hero_meta_strip' );
    $similar_ids     = $get_arr( 'similar_ids' );

    ?>
    <style>
        .etm-cpt details { background:#fff; border:1px solid #c3c4c7; margin:16px 0; padding:0; border-radius:4px; }
        .etm-cpt details > summary { cursor:pointer; padding:14px 18px; font-size:14px; font-weight:600; background:#f6f7f7; border-bottom:1px solid #c3c4c7; user-select:none; }
        .etm-cpt details[open] > summary { background:#1a4f31; color:#fff; }
        .etm-cpt details > .etm-cpt-body { padding:18px; }
        .etm-cpt-row { margin: 0 0 14px; }
        .etm-cpt-row > label { display:block; font-weight:600; margin-bottom:6px; font-size:13px; }
        .etm-cpt-row .etm-cpt-help { color:#6c7079; font-size:12px; margin:4px 0 0; }
        .etm-cpt-grid { display:grid; gap:12px; grid-template-columns:1fr 1fr; }
        .etm-cpt-grid-3 { display:grid; gap:12px; grid-template-columns:1fr 1fr 1fr; }
        .etm-repeater { background:#f6f7f7; border:1px solid #dcdcde; border-radius:4px; padding:12px; margin-bottom:8px; position:relative; }
        .etm-repeater__remove { position:absolute; top:8px; right:8px; background:#dc3232; color:#fff; border:none; padding:4px 10px; cursor:pointer; border-radius:3px; font-size:11px; }
        .etm-repeater__add { margin-top:8px; }
        .etm-image-field { display:flex; align-items:flex-start; gap:12px; flex-wrap:wrap; }
        .etm-image-preview { width:96px; height:96px; background:#f0f0f1; border:1px solid #dcdcde; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .etm-image-preview img { width:100%; height:100%; object-fit:cover; }
        .etm-image-empty { font-size:11px; color:#6c7079; padding:4px 8px; text-align:center; }
        .etm-checkbox-list { background:#f6f7f7; border:1px solid #dcdcde; padding:10px; max-height:240px; overflow-y:auto; border-radius:4px; }
        .etm-checkbox-list label { display:block; padding:3px 0; font-weight:400; font-size:13px; }
    </style>

    <div class="etm-cpt">

    <p style="background:#fffbe6;border-left:4px solid #c4a265;padding:10px 14px;margin:0 0 16px;">
        Each section below corresponds to a block on the funnel page. Empty sections are skipped on the front-end. Hero image = the post's <strong>Featured Image</strong>.
    </p>

    <!-- ─── HERO ──────────────────────────────────────────── -->
    <details open>
        <summary>1 · Hero</summary>
        <div class="etm-cpt-body">

            <div class="etm-cpt-row">
                <label>Eyebrow Label <span class="etm-cpt-help">(small uppercase line above the title)</span></label>
                <?php etm_field_text( 'etm_eyebrow', $get( 'eyebrow' ), 'AN ELITE TOURS EXPERIENCE · ANCESTRY, CULTURE & SCENERY' ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Italic Fragment of Title <span class="etm-cpt-help">(must match a phrase in the post title — that phrase will render in italic gold)</span></label>
                <?php etm_field_text( 'etm_hero_title_em', $get( 'hero_title_em' ), 'Private Tour' ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Deck (sub-line under title) <span class="etm-cpt-help">(if the post has an Excerpt, that wins instead)</span></label>
                <?php etm_field_text( 'etm_hero_deck', $get( 'hero_deck' ), 'Crafted around your interests, ancestry, and pace.' ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Breadcrumb (top-left strip) <span class="etm-cpt-help">(one item per line — last line is highlighted)</span></label>
                <?php etm_field_textarea( 'etm_hero_breadcrumb', implode( "\n", array_map( 'strval', $hero_breadcrumb ) ), "Tailored Experiences\nAncestry, Culture & Scenery\nThe Bespoke Private Tour", 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Top-Right Meta Strip <span class="etm-cpt-help">(one item per line — joined with dot separators)</span></label>
                <?php etm_field_textarea( 'etm_hero_meta_strip', implode( "\n", array_map( 'strval', $hero_meta_strip ) ), "The Whole Island\nPrivately Guided\nDesigned Around You", 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Aside Paragraph (right column under hero)</label>
                <?php etm_field_textarea( 'etm_hero_aside_text', $get( 'hero_aside_text' ), 'A fully bespoke private tour of Ireland — designed end-to-end around the people travelling…', 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Aside Facts (under the paragraph)</label>
                <div class="etm-repeater-list" data-repeater="hero_aside_facts">
                    <?php foreach ( $hero_aside_facts as $i => $fact ) : ?>
                        <div class="etm-repeater">
                            <button type="button" class="etm-repeater__remove">×</button>
                            <div class="etm-cpt-grid">
                                <div><input type="text" name="etm_hero_aside_facts[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $fact['label'] ?? '' ); ?>" placeholder="Label (e.g. Length)" class="widefat"></div>
                                <div><input type="text" name="etm_hero_aside_facts[<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $fact['value'] ?? '' ); ?>" placeholder="Value (e.g. 6 – 15 days)" class="widefat"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button etm-repeater__add" data-repeater-add="hero_aside_facts">+ Add fact</button>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Primary CTA Text</label>
                    <?php etm_field_text( 'etm_hero_cta_primary', $get( 'hero_cta_primary' ), 'Begin Your Journey' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Primary CTA URL <span class="etm-cpt-help">(blank = scroll to contact form)</span></label>
                    <?php etm_field_url( 'etm_hero_cta_primary_url', $get( 'hero_cta_primary_url' ), '#et-exp-cta' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Secondary CTA Text</label>
                    <?php etm_field_text( 'etm_hero_cta_secondary', $get( 'hero_cta_secondary' ), 'Speak to a Designer' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Secondary CTA URL</label>
                    <?php etm_field_url( 'etm_hero_cta_secondary_url', $get( 'hero_cta_secondary_url' ), '#et-exp-cta' ); ?>
                </div>
            </div>

        </div>
    </details>

    <!-- ─── HIGHLIGHTS ─────────────────────────────────────── -->
    <details>
        <summary>2 · Highlights</summary>
        <div class="etm-cpt-body">

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Section Number</label>
                    <?php etm_field_text( 'etm_highlights_number', $get( 'highlights_number' ), '01' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Section Label</label>
                    <?php etm_field_text( 'etm_highlights_label', $get( 'highlights_label' ), 'The Experience at a Glance' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Heading</label>
                    <?php etm_field_text( 'etm_highlights_heading', $get( 'highlights_heading' ), 'Highlights.' ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Intro Paragraph (right of heading)</label>
                <?php etm_field_textarea( 'etm_highlights_intro', $get( 'highlights_intro' ), 'Four things every Bespoke shares — held quietly in the background while the country reveals itself.', 2 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Highlights <span class="etm-cpt-help">(4 items recommended — auto-numbered 01, 02, 03, 04…)</span></label>
                <div class="etm-repeater-list" data-repeater="highlights">
                    <?php foreach ( $highlights as $i => $h ) : ?>
                        <div class="etm-repeater">
                            <button type="button" class="etm-repeater__remove">×</button>
                            <input type="text" name="etm_highlights[<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr( $h['title'] ?? '' ); ?>" placeholder="Highlight title" class="widefat" style="margin-bottom:4px;">
                            <input type="text" name="etm_highlights[<?php echo (int) $i; ?>][desc]"  value="<?php echo esc_attr( $h['desc'] ?? '' );  ?>" placeholder="One-line description" class="widefat" style="margin-bottom:6px;">
                            <div>
                                <label style="font-weight:600;font-size:12px;">Image:</label>
                                <?php etm_field_image( 'etm_highlights[' . (int) $i . '][image_id]', (int) ( $h['image_id'] ?? 0 ) ); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button etm-repeater__add" data-repeater-add="highlights">+ Add highlight</button>
            </div>

        </div>
    </details>

    <!-- ─── STORY ──────────────────────────────────────────── -->
    <details>
        <summary>3 · Story</summary>
        <div class="etm-cpt-body">

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Section Number</label>
                    <?php etm_field_text( 'etm_story_number', $get( 'story_number' ), '02' ); ?>
                </div>
                <div class="etm-cpt-row" style="grid-column:span 2;">
                    <label>Section Label</label>
                    <?php etm_field_text( 'etm_story_label', $get( 'story_label' ), 'The Story' ); ?>
                </div>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Heading — Part 1</label>
                    <?php etm_field_text( 'etm_story_heading_part1', $get( 'story_heading_part1' ), 'Ireland,' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Heading — Part 2 (italic gold)</label>
                    <?php etm_field_text( 'etm_story_heading_part2', $get( 'story_heading_part2' ), 'your way.' ); ?>
                </div>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Main Image</label>
                    <?php etm_field_image( 'etm_story_image_main', (int) $get( 'story_image_main' ) ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Accent Image (small overlay)</label>
                    <?php etm_field_image( 'etm_story_image_accent', (int) $get( 'story_image_accent' ) ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Plate Caption (rotated label, optional)</label>
                <?php etm_field_text( 'etm_story_plate', $get( 'story_plate' ), 'Plate 1 of 4 · The Western Counties' ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Lede Paragraph (large serif)</label>
                <?php etm_field_textarea( 'etm_story_lede', $get( 'story_lede' ), 'The Bespoke is the journey we are best known for…', 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Paragraph 1</label>
                <?php etm_field_textarea( 'etm_story_para1', $get( 'story_para1' ), 'Most begin with a single thread…', 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Paragraph 2</label>
                <?php etm_field_textarea( 'etm_story_para2', $get( 'story_para2' ), 'No two of these journeys have ever been the same.', 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>"Your People" Section Label</label>
                <?php etm_field_text( 'etm_story_people_label', $get( 'story_people_label' ), 'Your People for the Journey' ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>People Cards <span class="etm-cpt-help">(2 recommended)</span></label>
                <div class="etm-repeater-list" data-repeater="story_people">
                    <?php foreach ( $story_people as $i => $p ) : ?>
                        <div class="etm-repeater">
                            <button type="button" class="etm-repeater__remove">×</button>
                            <div class="etm-cpt-grid">
                                <input type="text" name="etm_story_people[<?php echo (int) $i; ?>][name]" value="<?php echo esc_attr( $p['name'] ?? '' ); ?>" placeholder="Name (e.g. Raphael Mulally)" class="widefat">
                                <input type="text" name="etm_story_people[<?php echo (int) $i; ?>][alt]"  value="<?php echo esc_attr( $p['alt']  ?? '' ); ?>" placeholder="Alt line (italic, e.g. or Niamh Flannelly)" class="widefat">
                            </div>
                            <input type="text" name="etm_story_people[<?php echo (int) $i; ?>][role]" value="<?php echo esc_attr( $p['role'] ?? '' ); ?>" placeholder="Role (e.g. Founder & Lead Designer)" class="widefat" style="margin-top:6px;">
                            <textarea name="etm_story_people[<?php echo (int) $i; ?>][note]" placeholder="One-line note about them" rows="2" class="widefat" style="margin-top:6px;"><?php echo esc_textarea( $p['note'] ?? '' ); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button etm-repeater__add" data-repeater-add="story_people">+ Add person</button>
            </div>

        </div>
    </details>

    <!-- ─── PILLARS ────────────────────────────────────────── -->
    <details>
        <summary>4 · Pillars (Three Threads)</summary>
        <div class="etm-cpt-body">

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Section Number</label>
                    <?php etm_field_text( 'etm_pillars_number', $get( 'pillars_number' ), '03' ); ?>
                </div>
                <div class="etm-cpt-row" style="grid-column:span 2;">
                    <label>Section Label</label>
                    <?php etm_field_text( 'etm_pillars_label', $get( 'pillars_label' ), 'The Three Threads' ); ?>
                </div>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Heading — Part 1</label>
                    <?php etm_field_text( 'etm_pillars_heading_part1', $get( 'pillars_heading_part1' ), 'Ancestry, Culture' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Heading — Part 2 (italic)</label>
                    <?php etm_field_text( 'etm_pillars_heading_part2', $get( 'pillars_heading_part2' ), '& Scenery.' ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Italic Sub-heading (gold)</label>
                <?php etm_field_text( 'etm_pillars_subheading', $get( 'pillars_subheading' ), 'Three threads, woven to your weight.' ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Intro Paragraph</label>
                <?php etm_field_textarea( 'etm_pillars_intro', $get( 'pillars_intro' ), 'Almost every Bespoke draws on these three pillars…', 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Pillars <span class="etm-cpt-help">(3 recommended — auto-numbered I, II, III)</span></label>
                <div class="etm-repeater-list" data-repeater="pillars">
                    <?php foreach ( $pillars as $i => $p ) : ?>
                        <div class="etm-repeater">
                            <button type="button" class="etm-repeater__remove">×</button>
                            <div class="etm-cpt-grid">
                                <input type="text" name="etm_pillars[<?php echo (int) $i; ?>][pillar]" value="<?php echo esc_attr( $p['pillar'] ?? '' ); ?>" placeholder="Pillar (e.g. Ancestry)" class="widefat">
                                <input type="text" name="etm_pillars[<?php echo (int) $i; ?>][title]"  value="<?php echo esc_attr( $p['title']  ?? '' ); ?>" placeholder="Title (e.g. Find your people…)" class="widefat">
                            </div>
                            <textarea name="etm_pillars[<?php echo (int) $i; ?>][body]" placeholder="Body (2–3 sentences)" rows="3" class="widefat" style="margin-top:6px;"><?php echo esc_textarea( $p['body'] ?? '' ); ?></textarea>
                            <div style="margin-top:6px;">
                                <label style="font-weight:600;font-size:12px;">Image:</label>
                                <?php etm_field_image( 'etm_pillars[' . (int) $i . '][image_id]', (int) ( $p['image_id'] ?? 0 ) ); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button etm-repeater__add" data-repeater-add="pillars">+ Add pillar</button>
            </div>

        </div>
    </details>

    <!-- ─── PROCESS ────────────────────────────────────────── -->
    <details>
        <summary>5 · Process (Method)</summary>
        <div class="etm-cpt-body">

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Section Number</label>
                    <?php etm_field_text( 'etm_process_number', $get( 'process_number' ), '04' ); ?>
                </div>
                <div class="etm-cpt-row" style="grid-column:span 2;">
                    <label>Section Label</label>
                    <?php etm_field_text( 'etm_process_label', $get( 'process_label' ), 'The Process' ); ?>
                </div>
            </div>

            <h4 style="margin:18px 0 8px;border-bottom:1px solid #dcdcde;padding-bottom:6px;">Method Card (left)</h4>

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Card Eyebrow</label>
                    <?php etm_field_text( 'etm_process_card_eyebrow', $get( 'process_card_eyebrow' ), 'The Method' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Card Title</label>
                    <?php etm_field_text( 'etm_process_card_title', $get( 'process_card_title' ), 'How a Journey is Built' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Card Subtitle (italic gold)</label>
                    <?php etm_field_text( 'etm_process_card_subtitle', $get( 'process_card_subtitle' ), 'four conversations, then a week of Ireland' ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Process Steps <span class="etm-cpt-help">(4 recommended)</span></label>
                <div class="etm-repeater-list" data-repeater="process_steps">
                    <?php foreach ( $process_steps as $i => $s ) : ?>
                        <div class="etm-repeater">
                            <button type="button" class="etm-repeater__remove">×</button>
                            <div class="etm-cpt-grid-3">
                                <input type="text" name="etm_process_steps[<?php echo (int) $i; ?>][number]" value="<?php echo esc_attr( $s['number'] ?? '' ); ?>" placeholder="Number (e.g. 01)" class="widefat">
                                <input type="text" name="etm_process_steps[<?php echo (int) $i; ?>][title]"  value="<?php echo esc_attr( $s['title']  ?? '' ); ?>" placeholder="Title (e.g. A first conversation)" class="widefat" style="grid-column:span 2;">
                            </div>
                            <textarea name="etm_process_steps[<?php echo (int) $i; ?>][body]" placeholder="2–3 sentences" rows="2" class="widefat" style="margin-top:6px;"><?php echo esc_textarea( $s['body'] ?? '' ); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button etm-repeater__add" data-repeater-add="process_steps">+ Add step</button>
            </div>

            <h4 style="margin:18px 0 8px;border-bottom:1px solid #dcdcde;padding-bottom:6px;">Right Column</h4>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Heading — Part 1</label>
                    <?php etm_field_text( 'etm_process_aside_heading_part1', $get( 'process_aside_heading_part1' ), 'Built in four' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Heading — Part 2 (italic)</label>
                    <?php etm_field_text( 'etm_process_aside_heading_part2', $get( 'process_aside_heading_part2' ), 'quiet conversations.' ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Body</label>
                <?php etm_field_textarea( 'etm_process_aside_body', $get( 'process_aside_body' ), 'We do not begin with a route. We begin with a phone call…', 3 ); ?>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Image 1</label>
                    <?php etm_field_image( 'etm_process_image_1', (int) $get( 'process_image_1' ) ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Image 2</label>
                    <?php etm_field_image( 'etm_process_image_2', (int) $get( 'process_image_2' ) ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Facts (From / Length / Group, etc.)</label>
                <div class="etm-repeater-list" data-repeater="process_facts">
                    <?php foreach ( $process_facts as $i => $fact ) : ?>
                        <div class="etm-repeater">
                            <button type="button" class="etm-repeater__remove">×</button>
                            <div class="etm-cpt-grid">
                                <input type="text" name="etm_process_facts[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $fact['label'] ?? '' ); ?>" placeholder="Label (e.g. From)" class="widefat">
                                <input type="text" name="etm_process_facts[<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $fact['value'] ?? '' ); ?>" placeholder="Value (e.g. €1,650 / day)" class="widefat">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button etm-repeater__add" data-repeater-add="process_facts">+ Add fact</button>
            </div>

        </div>
    </details>

    <!-- ─── CTA ────────────────────────────────────────────── -->
    <details>
        <summary>6 · CTA (Founder + Form)</summary>
        <div class="etm-cpt-body">

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Section Number</label>
                    <?php etm_field_text( 'etm_cta_number', $get( 'cta_number' ), '05' ); ?>
                </div>
                <div class="etm-cpt-row" style="grid-column:span 2;">
                    <label>Section Label</label>
                    <?php etm_field_text( 'etm_cta_label', $get( 'cta_label' ), 'Tailoring This Journey' ); ?>
                </div>
            </div>

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Heading — Part 1</label>
                    <?php etm_field_text( 'etm_cta_heading_part1', $get( 'cta_heading_part1' ), 'We are' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Heading — Part 2 (italic)</label>
                    <?php etm_field_text( 'etm_cta_heading_part2', $get( 'cta_heading_part2' ), 'experience designers,' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Heading — Part 3</label>
                    <?php etm_field_text( 'etm_cta_heading_part3', $get( 'cta_heading_part3' ), 'not tour operators.' ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Body</label>
                <?php etm_field_textarea( 'etm_cta_body', $get( 'cta_body' ), 'Every Bespoke is built around a quiet conversation…', 3 ); ?>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Phone</label>
                    <?php etm_field_text( 'etm_cta_phone', $get( 'cta_phone' ), '+353 87 345 2874' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Email</label>
                    <?php etm_field_text( 'etm_cta_email', $get( 'cta_email' ), 'concierge@elitetours.ie' ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Founder Portrait</label>
                <?php etm_field_image( 'etm_cta_portrait', (int) $get( 'cta_portrait' ) ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Quote (over the portrait)</label>
                <?php etm_field_textarea( 'etm_cta_quote', $get( 'cta_quote' ), 'I\'ve spent decades helping people experience Ireland in a truly personal way…', 3 ); ?>
            </div>

            <div class="etm-cpt-row">
                <label>Quote Attribution</label>
                <?php etm_field_text( 'etm_cta_quote_attribution', $get( 'cta_quote_attribution' ), 'Raphael Mulally · Founder, Elite Tours' ); ?>
            </div>

        </div>
    </details>

    <!-- ─── SIMILAR ────────────────────────────────────────── -->
    <details>
        <summary>7 · Similar Experiences</summary>
        <div class="etm-cpt-body">

            <div class="etm-cpt-grid-3">
                <div class="etm-cpt-row">
                    <label>Section Number</label>
                    <?php etm_field_text( 'etm_similar_number', $get( 'similar_number' ), '06' ); ?>
                </div>
                <div class="etm-cpt-row" style="grid-column:span 2;">
                    <label>Section Label</label>
                    <?php etm_field_text( 'etm_similar_label', $get( 'similar_label' ), 'You May Also Consider' ); ?>
                </div>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>Heading — Part 1</label>
                    <?php etm_field_text( 'etm_similar_heading_part1', $get( 'similar_heading_part1' ), 'Other experiences,' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>Heading — Part 2 (italic)</label>
                    <?php etm_field_text( 'etm_similar_heading_part2', $get( 'similar_heading_part2' ), 'other quiet days.' ); ?>
                </div>
            </div>

            <div class="etm-cpt-grid">
                <div class="etm-cpt-row">
                    <label>"View All" Link Text</label>
                    <?php etm_field_text( 'etm_similar_view_all_text', $get( 'similar_view_all_text' ), 'View all experiences →' ); ?>
                </div>
                <div class="etm-cpt-row">
                    <label>"View All" Link URL</label>
                    <?php etm_field_url( 'etm_similar_view_all_url', $get( 'similar_view_all_url' ), home_url( '/experiences/' ) ); ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Pick 3 Related Experiences <span class="etm-cpt-help">(if none picked, the latest 3 other experiences are shown automatically)</span></label>
                <div class="etm-checkbox-list">
                    <?php if ( empty( $other_experiences ) ) : ?>
                        <em>No other experiences yet — publish more to use this.</em>
                    <?php else : ?>
                        <?php foreach ( $other_experiences as $opt ) :
                            $checked = in_array( (int) $opt->ID, array_map( 'intval', $similar_ids ), true );
                        ?>
                            <label>
                                <input type="checkbox" name="etm_similar_ids[]" value="<?php echo (int) $opt->ID; ?>" <?php checked( $checked ); ?>>
                                <?php echo esc_html( $opt->post_title ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="etm-cpt-row">
                <label>Card Sub-meta <span class="etm-cpt-help">(small italic line shown when this experience appears as a card on other pages — e.g. "Multi-day · 6–15 nights")</span></label>
                <?php etm_field_text( 'etm_card_meta', $get( 'card_meta' ), 'Multi-day · 6–15 nights' ); ?>
            </div>

        </div>
    </details>

    </div>

    <script>
    ( function () {
        // ── Repeater add/remove ────────────────────────────────
        document.querySelectorAll( '[data-repeater-add]' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                const list = document.querySelector( '[data-repeater="' + btn.dataset.repeaterAdd + '"]' );
                if ( ! list ) return;
                const first = list.querySelector( '.etm-repeater' );
                if ( ! first ) return;
                const clone = first.cloneNode( true );
                clone.querySelectorAll( 'input, textarea' ).forEach( function ( el ) {
                    if ( el.type === 'hidden' ) el.value = 0;
                    else el.value = '';
                } );
                clone.querySelectorAll( '.etm-image-preview' ).forEach( function ( prev ) {
                    prev.innerHTML = '<span class="etm-image-empty">No image selected</span>';
                } );
                // Reindex names: …[0][title] → …[N][title]
                const newIndex = list.querySelectorAll( '.etm-repeater' ).length;
                clone.querySelectorAll( '[name]' ).forEach( function ( el ) {
                    el.name = el.name.replace( /\[\d+\]/, '[' + newIndex + ']' );
                } );
                list.appendChild( clone );
            } );
        } );

        document.addEventListener( 'click', function ( e ) {
            if ( e.target.matches( '.etm-repeater__remove' ) ) {
                const row  = e.target.closest( '.etm-repeater' );
                const list = row && row.parentElement;
                if ( list && list.querySelectorAll( '.etm-repeater' ).length > 1 ) {
                    row.remove();
                } else if ( row ) {
                    // Last row — clear instead of remove so we always have one.
                    row.querySelectorAll( 'input, textarea' ).forEach( function ( el ) {
                        if ( el.type === 'hidden' ) el.value = 0; else el.value = '';
                    } );
                    row.querySelectorAll( '.etm-image-preview' ).forEach( function ( prev ) {
                        prev.innerHTML = '<span class="etm-image-empty">No image selected</span>';
                    } );
                }
            }
        } );

        // ── Image picker ───────────────────────────────────────
        document.addEventListener( 'click', function ( e ) {
            if ( e.target.matches( '.etm-image-pick' ) ) {
                e.preventDefault();
                const wrap   = e.target.closest( '.etm-image-field' );
                const input  = wrap.querySelector( '.etm-image-id' );
                const preview= wrap.querySelector( '.etm-image-preview' );
                const frame = wp.media( {
                    title: 'Select image',
                    button: { text: 'Use this image' },
                    multiple: false,
                } );
                frame.on( 'select', function () {
                    const att = frame.state().get( 'selection' ).first().toJSON();
                    input.value = att.id;
                    const url = ( att.sizes && att.sizes.thumbnail ) ? att.sizes.thumbnail.url : att.url;
                    preview.innerHTML = '<img src="' + url + '" alt="">';
                } );
                frame.open();
            }
            if ( e.target.matches( '.etm-image-clear' ) ) {
                e.preventDefault();
                const wrap   = e.target.closest( '.etm-image-field' );
                wrap.querySelector( '.etm-image-id' ).value = 0;
                wrap.querySelector( '.etm-image-preview' ).innerHTML = '<span class="etm-image-empty">No image selected</span>';
            }
        } );
    } )();
    </script>
    <?php
}

// ── Save meta ───────────────────────────────────────────────────────────────
add_action( 'save_post_experience', function ( int $post_id ) {
    if ( ! isset( $_POST['etm_experience_funnel_nonce'] ) ) return;
    if ( ! wp_verify_nonce( wp_unslash( $_POST['etm_experience_funnel_nonce'] ), 'etm_experience_funnel' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // ── Plain text/URL fields ────────────────────────────────
    $text_fields = [
        'eyebrow',
        'hero_title_em', 'hero_deck',
        'hero_aside_text',
        'hero_cta_primary',     'hero_cta_secondary',
        'highlights_label',     'highlights_number', 'highlights_heading',
        'highlights_intro',
        'story_label',          'story_number',
        'story_heading_part1',  'story_heading_part2',
        'story_plate',          'story_lede',
        'story_para1',          'story_para2',
        'story_people_label',
        'pillars_label',        'pillars_number',
        'pillars_heading_part1','pillars_heading_part2',
        'pillars_subheading',   'pillars_intro',
        'process_label',        'process_number',
        'process_card_eyebrow', 'process_card_title', 'process_card_subtitle',
        'process_aside_heading_part1', 'process_aside_heading_part2',
        'process_aside_body',
        'cta_label',            'cta_number',
        'cta_heading_part1',    'cta_heading_part2', 'cta_heading_part3',
        'cta_body',             'cta_phone', 'cta_email',
        'cta_quote',            'cta_quote_attribution',
        'similar_label',        'similar_number',
        'similar_heading_part1','similar_heading_part2',
        'similar_view_all_text',
        'card_meta',
    ];
    foreach ( $text_fields as $f ) {
        update_post_meta(
            $post_id,
            '_etm_' . $f,
            sanitize_textarea_field( wp_unslash( $_POST[ 'etm_' . $f ] ?? '' ) )
        );
    }

    $url_fields = [
        'hero_cta_primary_url', 'hero_cta_secondary_url',
        'similar_view_all_url',
    ];
    foreach ( $url_fields as $f ) {
        update_post_meta(
            $post_id,
            '_etm_' . $f,
            esc_url_raw( wp_unslash( $_POST[ 'etm_' . $f ] ?? '' ) )
        );
    }

    // ── Attachment IDs (single image fields) ─────────────────
    $img_fields = [
        'story_image_main',
        'story_image_accent',
        'process_image_1',
        'process_image_2',
        'cta_portrait',
    ];
    foreach ( $img_fields as $f ) {
        update_post_meta( $post_id, '_etm_' . $f, absint( $_POST[ 'etm_' . $f ] ?? 0 ) );
    }

    // ── Hero breadcrumb / meta strip — newline-separated → array ─
    foreach ( [ 'hero_breadcrumb', 'hero_meta_strip' ] as $f ) {
        $raw   = wp_unslash( $_POST[ 'etm_' . $f ] ?? '' );
        $lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\n|\r/', (string) $raw ) ) ) );
        $clean = array_map( 'sanitize_text_field', $lines );
        update_post_meta( $post_id, '_etm_' . $f, $clean );
    }

    // ── Hero aside facts (label/value repeater) ──────────────
    $hero_facts = $_POST['etm_hero_aside_facts'] ?? [];
    $hf_clean   = [];
    if ( is_array( $hero_facts ) ) {
        foreach ( $hero_facts as $row ) {
            $l = sanitize_text_field( wp_unslash( $row['label'] ?? '' ) );
            $v = sanitize_text_field( wp_unslash( $row['value'] ?? '' ) );
            if ( $l === '' && $v === '' ) continue;
            $hf_clean[] = [ 'label' => $l, 'value' => $v ];
        }
    }
    update_post_meta( $post_id, '_etm_hero_aside_facts', $hf_clean );

    // ── Highlights (title/desc/image_id repeater) ────────────
    $hl_in    = $_POST['etm_highlights'] ?? [];
    $hl_clean = [];
    if ( is_array( $hl_in ) ) {
        foreach ( $hl_in as $row ) {
            $t   = sanitize_text_field( wp_unslash( $row['title'] ?? '' ) );
            $d   = sanitize_text_field( wp_unslash( $row['desc']  ?? '' ) );
            $img = absint( $row['image_id'] ?? 0 );
            if ( $t === '' && $d === '' && $img === 0 ) continue;
            $hl_clean[] = [ 'title' => $t, 'desc' => $d, 'image_id' => $img ];
        }
    }
    update_post_meta( $post_id, '_etm_highlights', $hl_clean );

    // ── Story people (name/alt/role/note repeater) ───────────
    $sp_in    = $_POST['etm_story_people'] ?? [];
    $sp_clean = [];
    if ( is_array( $sp_in ) ) {
        foreach ( $sp_in as $row ) {
            $n = sanitize_text_field(     wp_unslash( $row['name'] ?? '' ) );
            $a = sanitize_text_field(     wp_unslash( $row['alt']  ?? '' ) );
            $r = sanitize_text_field(     wp_unslash( $row['role'] ?? '' ) );
            $note = sanitize_textarea_field( wp_unslash( $row['note'] ?? '' ) );
            if ( $n === '' && $a === '' && $r === '' && $note === '' ) continue;
            $sp_clean[] = [ 'name' => $n, 'alt' => $a, 'role' => $r, 'note' => $note ];
        }
    }
    update_post_meta( $post_id, '_etm_story_people', $sp_clean );

    // ── Pillars (pillar/title/body/image_id repeater) ────────
    $p_in    = $_POST['etm_pillars'] ?? [];
    $p_clean = [];
    if ( is_array( $p_in ) ) {
        foreach ( $p_in as $row ) {
            $pl  = sanitize_text_field(     wp_unslash( $row['pillar'] ?? '' ) );
            $t   = sanitize_text_field(     wp_unslash( $row['title']  ?? '' ) );
            $b   = sanitize_textarea_field( wp_unslash( $row['body']   ?? '' ) );
            $img = absint( $row['image_id'] ?? 0 );
            if ( $pl === '' && $t === '' && $b === '' && $img === 0 ) continue;
            $p_clean[] = [ 'pillar' => $pl, 'title' => $t, 'body' => $b, 'image_id' => $img ];
        }
    }
    update_post_meta( $post_id, '_etm_pillars', $p_clean );

    // ── Process steps (number/title/body repeater) ───────────
    $ps_in    = $_POST['etm_process_steps'] ?? [];
    $ps_clean = [];
    if ( is_array( $ps_in ) ) {
        foreach ( $ps_in as $row ) {
            $n = sanitize_text_field(     wp_unslash( $row['number'] ?? '' ) );
            $t = sanitize_text_field(     wp_unslash( $row['title']  ?? '' ) );
            $b = sanitize_textarea_field( wp_unslash( $row['body']   ?? '' ) );
            if ( $n === '' && $t === '' && $b === '' ) continue;
            $ps_clean[] = [ 'number' => $n, 'title' => $t, 'body' => $b ];
        }
    }
    update_post_meta( $post_id, '_etm_process_steps', $ps_clean );

    // ── Process facts (label/value repeater) ─────────────────
    $pf_in    = $_POST['etm_process_facts'] ?? [];
    $pf_clean = [];
    if ( is_array( $pf_in ) ) {
        foreach ( $pf_in as $row ) {
            $l = sanitize_text_field( wp_unslash( $row['label'] ?? '' ) );
            $v = sanitize_text_field( wp_unslash( $row['value'] ?? '' ) );
            if ( $l === '' && $v === '' ) continue;
            $pf_clean[] = [ 'label' => $l, 'value' => $v ];
        }
    }
    update_post_meta( $post_id, '_etm_process_facts', $pf_clean );

    // ── Similar experiences (multi-checkbox) ─────────────────
    $sim_in = $_POST['etm_similar_ids'] ?? [];
    $sim_clean = is_array( $sim_in ) ? array_values( array_filter( array_map( 'absint', $sim_in ) ) ) : [];
    update_post_meta( $post_id, '_etm_similar_ids', $sim_clean );
} );

// ── Helper: get all funnel data for a post (consumed by theme template) ────
if ( ! function_exists( 'etm_get_experience_funnel' ) ) {
    function etm_get_experience_funnel( int $post_id ): array {
        $get = function ( string $key, $default = '' ) use ( $post_id ) {
            $v = get_post_meta( $post_id, '_etm_' . $key, true );
            return $v === '' ? $default : $v;
        };
        $get_arr = function ( string $key ) use ( $post_id ): array {
            $v = get_post_meta( $post_id, '_etm_' . $key, true );
            return is_array( $v ) ? $v : [];
        };

        return [
            // Hero
            'eyebrow'                  => (string) $get( 'eyebrow' ),
            'hero_title_em'            => (string) $get( 'hero_title_em' ),
            'hero_deck'                => (string) $get( 'hero_deck' ),
            'hero_breadcrumb'          => $get_arr( 'hero_breadcrumb' ),
            'hero_meta_strip'          => $get_arr( 'hero_meta_strip' ),
            'hero_aside_text'          => (string) $get( 'hero_aside_text' ),
            'hero_aside_facts'         => $get_arr( 'hero_aside_facts' ),
            'hero_cta_primary'         => (string) $get( 'hero_cta_primary' ),
            'hero_cta_primary_url'     => (string) $get( 'hero_cta_primary_url' ),
            'hero_cta_secondary'       => (string) $get( 'hero_cta_secondary' ),
            'hero_cta_secondary_url'   => (string) $get( 'hero_cta_secondary_url' ),

            // Highlights
            'highlights'               => $get_arr( 'highlights' ),
            'highlights_label'         => (string) $get( 'highlights_label' ),
            'highlights_number'        => (string) $get( 'highlights_number' ),
            'highlights_heading'       => (string) $get( 'highlights_heading' ),
            'highlights_intro'         => (string) $get( 'highlights_intro' ),

            // Story
            'story_label'              => (string) $get( 'story_label' ),
            'story_number'             => (string) $get( 'story_number' ),
            'story_heading_part1'      => (string) $get( 'story_heading_part1' ),
            'story_heading_part2'      => (string) $get( 'story_heading_part2' ),
            'story_image_main'         => (int) $get( 'story_image_main', 0 ),
            'story_image_accent'       => (int) $get( 'story_image_accent', 0 ),
            'story_plate'              => (string) $get( 'story_plate' ),
            'story_lede'               => (string) $get( 'story_lede' ),
            'story_para1'              => (string) $get( 'story_para1' ),
            'story_para2'              => (string) $get( 'story_para2' ),
            'story_people_label'       => (string) $get( 'story_people_label' ),
            'story_people'             => $get_arr( 'story_people' ),

            // Pillars
            'pillars_label'            => (string) $get( 'pillars_label' ),
            'pillars_number'           => (string) $get( 'pillars_number' ),
            'pillars_heading_part1'    => (string) $get( 'pillars_heading_part1' ),
            'pillars_heading_part2'    => (string) $get( 'pillars_heading_part2' ),
            'pillars_subheading'       => (string) $get( 'pillars_subheading' ),
            'pillars_intro'            => (string) $get( 'pillars_intro' ),
            'pillars'                  => $get_arr( 'pillars' ),

            // Process
            'process_label'                  => (string) $get( 'process_label' ),
            'process_number'                 => (string) $get( 'process_number' ),
            'process_card_eyebrow'           => (string) $get( 'process_card_eyebrow' ),
            'process_card_title'             => (string) $get( 'process_card_title' ),
            'process_card_subtitle'          => (string) $get( 'process_card_subtitle' ),
            'process_steps'                  => $get_arr( 'process_steps' ),
            'process_aside_heading_part1'    => (string) $get( 'process_aside_heading_part1' ),
            'process_aside_heading_part2'    => (string) $get( 'process_aside_heading_part2' ),
            'process_aside_body'             => (string) $get( 'process_aside_body' ),
            'process_image_1'                => (int) $get( 'process_image_1', 0 ),
            'process_image_2'                => (int) $get( 'process_image_2', 0 ),
            'process_facts'                  => $get_arr( 'process_facts' ),

            // CTA
            'cta_label'              => (string) $get( 'cta_label' ),
            'cta_number'             => (string) $get( 'cta_number' ),
            'cta_heading_part1'      => (string) $get( 'cta_heading_part1' ),
            'cta_heading_part2'      => (string) $get( 'cta_heading_part2' ),
            'cta_heading_part3'      => (string) $get( 'cta_heading_part3' ),
            'cta_body'               => (string) $get( 'cta_body' ),
            'cta_phone'              => (string) $get( 'cta_phone' ),
            'cta_email'              => (string) $get( 'cta_email' ),
            'cta_portrait'           => (int) $get( 'cta_portrait', 0 ),
            'cta_quote'              => (string) $get( 'cta_quote' ),
            'cta_quote_attribution'  => (string) $get( 'cta_quote_attribution' ),

            // Similar
            'similar_label'              => (string) $get( 'similar_label' ),
            'similar_number'             => (string) $get( 'similar_number' ),
            'similar_heading_part1'      => (string) $get( 'similar_heading_part1' ),
            'similar_heading_part2'      => (string) $get( 'similar_heading_part2' ),
            'similar_view_all_text'      => (string) $get( 'similar_view_all_text' ),
            'similar_view_all_url'       => (string) $get( 'similar_view_all_url' ),
            'similar_ids'                => $get_arr( 'similar_ids' ),
        ];
    }
}
