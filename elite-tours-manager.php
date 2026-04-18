<?php
/**
 * Plugin Name:   Elite Tours Manager
 * Description:   Content management panel for Elite Tours Ireland website. Last updated: April 2026.
 * Version:       1.2.0
 * Author:        Elite Tours Ireland
 * Text Domain:   elite-tours-manager
 * GitHub Plugin URI: FarhanArshad835/elite-tours-manager
 * Primary Branch:    main
 */

defined( 'ABSPATH' ) || exit;

define( 'ETM_VERSION', '1.3.0' );

// ── One-time migration: clear stale homepage settings so fresh defaults apply ─
if ( get_option( 'etm_migration_v130' ) !== 'done' ) {
    delete_option( 'et_homepage_settings' );
    update_option( 'etm_migration_v130', 'done' );
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

// Load admin panel
if ( is_admin() ) {
    require_once ETM_PATH . 'includes/admin/class-admin-menus.php';
    require_once ETM_PATH . 'includes/admin/pages/site-settings.php';
    require_once ETM_PATH . 'includes/admin/pages/homepage.php';
    require_once ETM_PATH . 'includes/admin/pages/experiences.php';
}
