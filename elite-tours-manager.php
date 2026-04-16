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

// Load admin panel
if ( is_admin() ) {
    require_once ETM_PATH . 'includes/admin/class-admin-menus.php';
    require_once ETM_PATH . 'includes/admin/pages/site-settings.php';
    require_once ETM_PATH . 'includes/admin/pages/homepage.php';
}
