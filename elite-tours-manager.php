<?php
/**
 * Plugin Name:   Elite Tours Manager
 * Description:   Content management panel for Elite Tours Ireland website.
 * Version:       1.0.2
 * Author:        Elite Tours Ireland
 * Text Domain:   elite-tours-manager
 * GitHub Plugin URI: FarhanArshad835/elite-tours-manager
 * Primary Branch:    main
 */

defined( 'ABSPATH' ) || exit;

define( 'ETM_VERSION', '1.0.2' );
define( 'ETM_PATH',    plugin_dir_path( __FILE__ ) );
define( 'ETM_URL',     plugin_dir_url( __FILE__ ) );

// Load admin panel
if ( is_admin() ) {
    require_once ETM_PATH . 'includes/admin/class-admin-menus.php';
    require_once ETM_PATH . 'includes/admin/pages/site-settings.php';
    require_once ETM_PATH . 'includes/admin/pages/homepage.php';
}
