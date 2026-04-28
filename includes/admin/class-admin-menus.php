<?php
defined( 'ABSPATH' ) || exit;

class ETM_Admin_Menus {

    public function __construct() {
        add_action( 'admin_menu',       [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'admin_footer_text',   [ $this, 'hide_wp_footer' ] );
        add_filter( 'update_footer',       [ $this, 'hide_wp_footer' ], 99 );
    }

    public function register_menus(): void {

        // Top-level menu
        add_menu_page(
            'Elite Tours',
            'Elite Tours',
            'manage_options',
            'elite-tours',
            [ $this, 'dashboard_page' ],
            'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#ffffff"><text y="16" font-size="16" font-family="Georgia,serif" font-weight="700">ET</text></svg>'
            ),
            3
        );

        // Sub-pages
        add_submenu_page( 'elite-tours', 'Site Settings', 'Site Settings', 'manage_options', 'et-site-settings',   'etm_site_settings_page' );
        add_submenu_page( 'elite-tours', 'Homepage',      'Homepage',      'manage_options', 'et-homepage',        'etm_homepage_page' );
        add_submenu_page( 'elite-tours', 'Experiences',   'Experiences',   'manage_options', 'et-experiences',     'etm_experiences_page' );
        add_submenu_page( 'elite-tours', 'Hotels',         'Hotels',        'manage_options', 'et-hotels',          'etm_hotels_page' );
        add_submenu_page( 'elite-tours', 'Regions',        'Regions',       'manage_options', 'et-regions',         'etm_regions_page' );
        add_submenu_page( 'elite-tours', 'Key Experiences','Key Experiences','manage_options','et-key-experiences','etm_key_experiences_page' );
        add_submenu_page( 'elite-tours', 'Golf Courses',   'Golf Courses',  'manage_options', 'et-golf-courses',    'etm_golf_courses_page' );
        add_submenu_page( 'elite-tours', 'Itineraries',    'Itineraries',   'manage_options', 'et-itineraries',     'etm_itineraries_page' );
        add_submenu_page( 'elite-tours', 'Page Content',   'Page Content',  'manage_options', 'et-page-content',    'etm_page_content_page' );
        add_submenu_page( 'elite-tours', 'Page Heroes & CTAs', 'Heroes & CTAs', 'manage_options', 'et-page-heroes', 'etm_page_heroes_page' );
        add_submenu_page( 'elite-tours', 'Funnel Leads',   'Funnel Leads',  'manage_options', 'et-funnel-leads',    'etm_funnel_leads_page' );
        add_submenu_page( 'elite-tours', 'Seed Content',   'Seed Content',  'manage_options', 'et-seed-content',    'etm_seed_content_page' );

        // Remove duplicate top-level item
        remove_submenu_page( 'elite-tours', 'elite-tours' );
    }

    public function dashboard_page(): void {
        ?>
        <div class="wrap etm-wrap">
            <div class="etm-dashboard">
                <div class="etm-dashboard__header">
                    <h1 class="etm-dashboard__title">
                        <span class="etm-logo-et">ET</span>
                        Elite Tours Ireland
                    </h1>
                    <p class="etm-dashboard__sub">Website Content Manager — v<?php echo ETM_VERSION; ?></p>
                </div>
                <div class="etm-dashboard__grid">
                    <?php
                    $sections = [
                        [ 'icon' => 'settings',         'title' => 'Site Settings',   'desc' => 'Logo, phone number, nav CTA text.',                                       'url' => admin_url( 'admin.php?page=et-site-settings' ) ],
                        [ 'icon' => 'home',             'title' => 'Homepage',        'desc' => 'Hero video/image, headline, trust bar.',                                  'url' => admin_url( 'admin.php?page=et-homepage' ) ],
                        [ 'icon' => 'tour',             'title' => 'Experiences',     'desc' => 'Add, edit, reorder experience cards.',                                    'url' => admin_url( 'admin.php?page=et-experiences' ) ],
                        [ 'icon' => 'hotel',            'title' => 'Hotels',          'desc' => 'Manage accommodation listings.',                                          'url' => admin_url( 'admin.php?page=et-hotels' ) ],
                        [ 'icon' => 'map',              'title' => 'Regions',         'desc' => 'The 11 region tiles on the Experiences page.',                            'url' => admin_url( 'admin.php?page=et-regions' ) ],
                        [ 'icon' => 'tour',             'title' => 'Key Experiences', 'desc' => "The 22 named experiences shown below the regions on /experiences/.",      'url' => admin_url( 'admin.php?page=et-key-experiences' ) ],
                        [ 'icon' => 'flag',             'title' => 'Golf Courses',    'desc' => "Manage Ireland's featured golf courses.",                                 'url' => admin_url( 'admin.php?page=et-golf-courses' ) ],
                        [ 'icon' => 'clipboard-list',   'title' => 'Itineraries',     'desc' => 'Sample bespoke and golf itineraries.',                                    'url' => admin_url( 'admin.php?page=et-itineraries' ) ],
                        [ 'icon' => 'file-text',        'title' => 'Page Content',    'desc' => 'Editorial blocks across Bespoke, Golf, Accommodation, Contact pages.',    'url' => admin_url( 'admin.php?page=et-page-content' ) ],
                        [ 'icon' => 'layout-template',  'title' => 'Heroes & CTAs',   'desc' => 'Top hero blocks and bottom CTA sections per page.',                       'url' => admin_url( 'admin.php?page=et-page-heroes' ) ],
                        [ 'icon' => 'inbox',            'title' => 'Funnel Leads',    'desc' => 'Captured leads from experience contact forms.',                           'url' => admin_url( 'admin.php?page=et-funnel-leads' ) ],
                        [ 'icon' => 'sprout',           'title' => 'Seed Content',    'desc' => 'Bulk-populate the experience pages with their full content + images.',    'url' => admin_url( 'admin.php?page=et-seed-content' ) ],
                    ];
                    foreach ( $sections as $s ) : ?>
                        <a href="<?php echo esc_url( $s['url'] ); ?>" class="etm-card">
                            <span class="etm-card__icon"><?php echo etm_lucide( $s['icon'], 26 ); ?></span>
                            <strong class="etm-card__title"><?php echo esc_html( $s['title'] ); ?></strong>
                            <p class="etm-card__desc"><?php echo esc_html( $s['desc'] ); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function hide_wp_footer( string $text ): string {
        $screen = get_current_screen();
        if ( $screen && strpos( $screen->id, 'elite-tours' ) !== false ) {
            return '';
        }
        return $text;
    }

    public function enqueue_assets( string $hook ): void {
        // Only load on our plugin pages
        if ( strpos( $hook, 'elite-tours' ) === false && strpos( $hook, 'et-' ) === false ) {
            return;
        }
        wp_enqueue_style(
            'etm-admin',
            ETM_URL . 'assets/css/admin.css',
            [],
            ETM_VERSION
        );
        wp_enqueue_media(); // for image/video upload
    }
}

new ETM_Admin_Menus();
