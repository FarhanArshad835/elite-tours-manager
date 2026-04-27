<?php
/**
 * Plugin Name:   Elite Tours Manager
 * Description:   Content management panel for Elite Tours Ireland website. Last updated: April 2026.
 * Version:       1.2.10
 * Author:        Elite Tours Ireland
 * Text Domain:   elite-tours-manager
 * GitHub Plugin URI: FarhanArshad835/elite-tours-manager
 * Primary Branch:    main
 */

defined( 'ABSPATH' ) || exit;

define( 'ETM_VERSION', '1.6.0' );

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

// ── Auto-create pages (runs once) ─────────────────────────────────────────────
if ( get_option( 'etm_pages_created_v2' ) !== 'done' ) {
    add_action( 'init', function () {
        $pages = [
            [ 'title' => 'Bespoke Tours',  'slug' => 'bespoke-tours',  'template' => 'page-bespoke-tours.php' ],
            [ 'title' => 'Golf Tours',      'slug' => 'golf-tours',     'template' => 'page-golf-tours.php' ],
            [ 'title' => 'Experiences',     'slug' => 'experiences',    'template' => 'page-experiences.php' ],
            [ 'title' => 'Accommodation',   'slug' => 'accommodation',  'template' => 'page-accommodation.php' ],
            [ 'title' => 'About Us',        'slug' => 'about-us',       'template' => 'page-about-us.php' ],
            [ 'title' => 'Blog',            'slug' => 'blog',           'template' => 'page-blog.php' ],
            [ 'title' => 'Contact',         'slug' => 'contact',        'template' => 'page-contact.php' ],
            [ 'title' => 'Wishlist',        'slug' => 'wishlist',       'template' => 'page-wishlist.php' ],
        ];

        foreach ( $pages as $p ) {
            // Skip if a page with this slug already exists
            $existing = get_page_by_path( $p['slug'] );
            if ( $existing ) {
                // Just ensure the template is set
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

        update_option( 'etm_pages_created_v2', 'done' );
    } );
}

// CPTs (must load on front-end too so single-experience.php template resolves)
require_once ETM_PATH . 'includes/cpt-experience.php';
require_once ETM_PATH . 'includes/contact-form.php';

// Load admin panel
if ( is_admin() ) {
    require_once ETM_PATH . 'includes/admin/class-admin-menus.php';
    require_once ETM_PATH . 'includes/admin/pages/site-settings.php';
    require_once ETM_PATH . 'includes/admin/pages/homepage.php';
    require_once ETM_PATH . 'includes/admin/pages/experiences.php';
    require_once ETM_PATH . 'includes/admin/pages/hotels.php';
    require_once ETM_PATH . 'includes/admin/pages/itineraries.php';
    require_once ETM_PATH . 'includes/admin/pages/funnel-leads.php';
    require_once ETM_PATH . 'includes/admin/pages/seed-content.php';
}
