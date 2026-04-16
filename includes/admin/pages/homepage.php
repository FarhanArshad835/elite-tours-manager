<?php
defined( 'ABSPATH' ) || exit;

// ── Save Handler ──────────────────────────────────────────────────────────────
add_action( 'admin_post_etm_save_homepage', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorised' );
    check_admin_referer( 'etm_homepage' );

    $existing = get_option( 'et_homepage_settings', [] );
    $data     = $existing; // preserve any keys not explicitly saved here

    // ── Text fields (sanitize_text_field) ────────────────────────────────────
    $text_fields = [
        // Hero
        'hero_label', 'hero_headline', 'hero_cta_primary', 'hero_cta_secondary', 'hero_video_url',
        // Trust strip
        'trust_ta_sub',
        'trust_failte_sub', 'trust_asta_sub', 'trust_iagto_sub',
        'trust_since_label', 'trust_since_sub',
        // Stats strip
        'stats_1_icon', 'stats_1_label', 'stats_1_desc',
        'stats_2_icon', 'stats_2_label', 'stats_2_desc',
        'stats_3_icon', 'stats_3_label', 'stats_3_desc',
        'stats_4_icon', 'stats_4_label', 'stats_4_desc',
        // Intro
        'intro_label', 'intro_heading', 'intro_cta_text', 'intro_badge_num', 'intro_badge_text',
        // Offers
        'offer_1_label', 'offer_1_heading', 'offer_1_cta_text',
        'offer_2_label', 'offer_2_heading', 'offer_2_cta_text',
        // Process
        'process_label', 'process_heading', 'process_cta_text',
        'step_1_num', 'step_1_title',
        'step_2_num', 'step_2_title',
        'step_3_num', 'step_3_title',
        'step_4_num', 'step_4_title',
        // Experiences
        'exp_label', 'exp_heading',
        'exp_1_label', 'exp_1_title', 'exp_1_desc',
        'exp_2_label', 'exp_2_title', 'exp_2_desc',
        'exp_3_label', 'exp_3_title', 'exp_3_desc',
        'exp_4_label', 'exp_4_title', 'exp_4_desc',
        'exp_5_label', 'exp_5_title', 'exp_5_desc',
        'exp_6_label', 'exp_6_title', 'exp_6_desc',
        // Testimonials
        'testimonials_label', 'testimonials_heading', 'testimonials_sub',
        't_1_name', 't_1_origin',
        't_2_name', 't_2_origin',
        't_3_name', 't_3_origin',
        // Founder
        'founder_label', 'founder_heading', 'founder_cta_text', 'founder_cite',
    ];
    foreach ( $text_fields as $f ) {
        $raw = isset( $_POST[ $f ] ) ? wp_unslash( $_POST[ $f ] ) : '';
        $data[ $f ] = sanitize_text_field( $raw );
    }

    // ── HTML fields (wp_kses_post) ───────────────────────────────────────────
    $html_fields = [
        'hero_subheading',
        'intro_body',
        'offer_1_desc', 'offer_2_desc',
        'step_1_desc', 'step_2_desc', 'step_3_desc', 'step_4_desc',
        't_1_quote', 't_2_quote', 't_3_quote',
        'founder_body', 'founder_quote',
    ];
    foreach ( $html_fields as $f ) {
        $raw = isset( $_POST[ $f ] ) ? wp_unslash( $_POST[ $f ] ) : '';
        $data[ $f ] = wp_kses_post( $raw );
    }

    // ── URL fields (esc_url_raw) ─────────────────────────────────────────────
    $url_fields = [
        'intro_cta_url', 'offer_1_cta_url', 'offer_2_cta_url', 'process_cta_url',
        'founder_cta_url',
        'exp_1_url', 'exp_2_url', 'exp_3_url', 'exp_4_url', 'exp_5_url', 'exp_6_url',
    ];
    foreach ( $url_fields as $f ) {
        $raw = isset( $_POST[ $f ] ) ? wp_unslash( $_POST[ $f ] ) : '';
        $data[ $f ] = esc_url_raw( $raw );
    }

    // ── Image ID fields (absint) ─────────────────────────────────────────────
    $image_fields = [
        'hero_image_id',
        'trust_failte_logo_id', 'trust_asta_logo_id', 'trust_iagto_logo_id',
        'intro_image_id', 'offer_1_image_id', 'offer_2_image_id',
        'exp_1_image_id', 'exp_2_image_id', 'exp_3_image_id',
        'exp_4_image_id', 'exp_5_image_id', 'exp_6_image_id',
        'founder_image_id',
    ];
    foreach ( $image_fields as $f ) {
        $data[ $f ] = absint( $_POST[ $f ] ?? 0 ) ?: '';
    }

    // ── Section visibility toggles ───────────────────────────────────────────
    $slugs = [ 'trust-stats', 'intro', 'offers', 'process', 'experiences', 'testimonials', 'founder-cta' ];
    foreach ( $slugs as $slug ) {
        $key = 'section_' . $slug . '_visible';
        $data[ $key ] = isset( $_POST[ $key ] ) ? '1' : '0';
    }

    // ── Section order (JSON array) ───────────────────────────────────────────
    $raw_order = isset( $_POST['section_order'] ) ? wp_unslash( $_POST['section_order'] ) : '';
    $decoded   = json_decode( $raw_order, true );
    if ( is_array( $decoded ) ) {
        $clean_order = [];
        foreach ( $decoded as $s ) {
            $s = sanitize_key( $s );
            if ( in_array( $s, $slugs, true ) ) {
                $clean_order[] = $s;
            }
        }
        // Ensure all slugs are represented (append missing ones)
        foreach ( $slugs as $slug ) {
            if ( ! in_array( $slug, $clean_order, true ) ) {
                $clean_order[] = $slug;
            }
        }
        $data['section_order'] = wp_json_encode( $clean_order );
    }

    update_option( 'et_homepage_settings', $data );

    wp_redirect( add_query_arg( [ 'page' => 'et-homepage', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
    exit;
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_homepage_page(): void {
    $opts = get_option( 'et_homepage_settings', [] );

    // Helper: get option value with fallback
    $o = function ( string $key, string $fallback = '' ) use ( $opts ): string {
        return isset( $opts[ $key ] ) && $opts[ $key ] !== '' ? $opts[ $key ] : $fallback;
    };
    $oi = function ( string $key, int $fallback = 0 ) use ( $opts ): int {
        return isset( $opts[ $key ] ) && $opts[ $key ] !== '' ? (int) $opts[ $key ] : $fallback;
    };

    // Section order & visibility
    $slugs = [ 'trust-stats', 'intro', 'offers', 'process', 'experiences', 'testimonials', 'founder-cta' ];
    $slug_labels = [
        'trust-stats'  => 'Stats Strip',
        'intro'        => 'Who We Are',
        'offers'       => 'Core Offers',
        'process'      => 'How It Works',
        'experiences'  => 'Experiences Grid',
        'testimonials' => 'Testimonials',
        'founder-cta'  => 'Founder CTA',
    ];
    $stored_order = $o( 'section_order', '' );
    $section_order = $stored_order ? json_decode( $stored_order, true ) : $slugs;
    if ( ! is_array( $section_order ) ) $section_order = $slugs;
    // Ensure all slugs are present
    foreach ( $slugs as $slug ) {
        if ( ! in_array( $slug, $section_order, true ) ) {
            $section_order[] = $slug;
        }
    }

    // Media helpers
    $img_url = function ( string $key, string $size = 'medium' ) use ( $opts ): string {
        $id = absint( $opts[ $key ] ?? 0 );
        return $id ? (string) wp_get_attachment_image_url( $id, $size ) : '';
    };
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">Homepage</h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
            <div class="etm-notice etm-notice--success">Homepage settings saved successfully.</div>
        <?php endif; ?>

        <!-- Page-level tabs -->
        <div class="etm-page-tabs" id="etm-page-tabs">
            <button type="button" class="etm-page-tab etm-page-tab--active" data-panel="etm-panel-sections">Sections</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-hero">Hero</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-trust">Trust Bar</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-stats">Stats Strip</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-intro">Who We Are</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-offers">Core Offers</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-process">How It Works</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-experiences">Experiences</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-testimonials">Testimonials</button>
            <button type="button" class="etm-page-tab" data-panel="etm-panel-founder">Founder CTA</button>
        </div>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'etm_homepage' ); ?>
            <input type="hidden" name="action" value="etm_save_homepage">

            <!-- ── TAB: Sections ─────────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-sections">
                <div class="etm-section">
                    <h2 class="etm-section__title">Section Order &amp; Visibility</h2>
                    <p class="etm-section__desc">Drag to reorder sections. Toggle the switch to show or hide a section. Hero is always first and cannot be moved.</p>

                    <!-- Fixed: Hero (not draggable) -->
                    <div class="etm-section-row etm-section-row--fixed">
                        <span class="etm-drag-handle etm-drag-handle--disabled" aria-hidden="true">&#8942;</span>
                        <span class="etm-section-name">Hero <em>(fixed — always first)</em></span>
                        <span class="etm-section-fixed-badge">Always Visible</span>
                    </div>

                    <!-- Draggable sections -->
                    <div id="etm-sortable-sections">
                        <?php foreach ( $section_order as $slug ) :
                            if ( ! isset( $slug_labels[ $slug ] ) ) continue;
                            $vis_key = 'section_' . $slug . '_visible';
                            $visible = $o( $vis_key, '1' );
                            $chk_id  = 'etm-vis-' . esc_attr( $slug );
                        ?>
                        <div class="etm-section-row" draggable="true" data-slug="<?php echo esc_attr( $slug ); ?>">
                            <span class="etm-drag-handle" title="Drag to reorder" aria-label="Drag handle">&#8942;</span>
                            <span class="etm-section-name"><?php echo esc_html( $slug_labels[ $slug ] ); ?></span>
                            <label class="etm-toggle" for="<?php echo $chk_id; ?>">
                                <input type="checkbox"
                                       id="<?php echo $chk_id; ?>"
                                       name="<?php echo esc_attr( $vis_key ); ?>"
                                       value="1"
                                       <?php checked( $visible, '1' ); ?>>
                                <span class="etm-toggle__track"></span>
                                <span class="etm-toggle__thumb"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="section_order" id="etm-section-order-input"
                           value="<?php echo esc_attr( wp_json_encode( $section_order ) ); ?>">
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Hero ─────────────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-hero" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Hero Section</h2>
                    <p class="etm-section__desc">The full-screen section at the top of the homepage — the first thing visitors see.</p>

                    <!-- Background -->
                    <div class="etm-field">
                        <label class="etm-label">Hero Background</label>
                        <div class="etm-tabs" id="etm-hero-media-tabs">
                            <button type="button" class="etm-tab etm-tab--active" data-tab="hero-image">Image</button>
                            <button type="button" class="etm-tab" data-tab="hero-video">Video (URL)</button>
                        </div>
                        <div class="etm-tab-panel" id="etm-tab-hero-image">
                            <?php
                            $hero_img_url = $img_url( 'hero_image_id' );
                            ?>
                            <div class="etm-media-upload">
                                <img src="<?php echo esc_url( $hero_img_url ); ?>" id="etm-hero-img-preview"
                                     class="etm-media-preview etm-media-preview--wide" alt=""
                                     <?php echo $hero_img_url ? '' : 'style="display:none;"'; ?>>
                                <input type="hidden" name="hero_image_id" id="etm-hero-image-id"
                                       value="<?php echo esc_attr( $oi( 'hero_image_id' ) ?: '' ); ?>">
                                <div class="etm-media-btns">
                                    <button type="button" class="etm-btn-upload button"
                                            data-target="etm-hero-image-id"
                                            data-preview="etm-hero-img-preview"
                                            data-title="Select Hero Image">
                                        <?php echo $hero_img_url ? 'Change Image' : 'Upload Image'; ?>
                                    </button>
                                    <?php if ( $hero_img_url ) : ?>
                                        <button type="button" class="etm-btn-remove button-link-delete"
                                                data-target="etm-hero-image-id"
                                                data-preview="etm-hero-img-preview">Remove</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="etm-help">Recommended: landscape, minimum 1920×1080px. JPG or WebP.</p>
                        </div>
                        <div class="etm-tab-panel" id="etm-tab-hero-video" style="display:none;">
                            <input type="url" name="hero_video_url" class="etm-input etm-input--wide"
                                   value="<?php echo esc_attr( $o( 'hero_video_url' ) ); ?>"
                                   placeholder="https://example.com/hero-video.mp4">
                            <p class="etm-help">Direct URL to an MP4 video file. The image above will be used as a poster/fallback.</p>
                        </div>
                    </div>

                    <!-- Label -->
                    <div class="etm-field">
                        <label class="etm-label" for="hero_label">Label Text</label>
                        <input type="text" id="hero_label" name="hero_label" class="etm-input"
                               value="<?php echo esc_attr( $o( 'hero_label', 'ELITE TOURS IRELAND — SINCE 1973' ) ); ?>">
                        <p class="etm-help">The small gold text above the headline. Uppercase, short.</p>
                    </div>

                    <!-- Headline -->
                    <div class="etm-field">
                        <label class="etm-label" for="hero_headline">Main Headline</label>
                        <input type="text" id="hero_headline" name="hero_headline" class="etm-input etm-input--wide"
                               value="<?php echo esc_attr( $o( 'hero_headline', 'Ireland,<br>Experienced Properly.' ) ); ?>">
                        <p class="etm-help">You can use <code>&lt;br&gt;</code> to force a line break.</p>
                    </div>

                    <!-- Subheading -->
                    <div class="etm-field">
                        <label class="etm-label" for="hero_subheading">Subheading</label>
                        <textarea id="hero_subheading" name="hero_subheading" class="etm-textarea" rows="2"><?php echo esc_textarea( $o( 'hero_subheading', 'Bespoke private journeys — tailored to you, delivered with genuine Irish care.' ) ); ?></textarea>
                        <p class="etm-help">Keep it to 1–2 lines.</p>
                    </div>

                    <!-- CTAs -->
                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="hero_cta_primary">Primary Button Text</label>
                            <input type="text" id="hero_cta_primary" name="hero_cta_primary" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'hero_cta_primary', 'Visit the Emerald Isle' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="hero_cta_secondary">Secondary Button Text</label>
                            <input type="text" id="hero_cta_secondary" name="hero_cta_secondary" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'hero_cta_secondary', 'Explore Our Tours' ) ); ?>">
                        </div>
                    </div>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Trust Bar ─────────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-trust" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Trust Strip</h2>
                    <p class="etm-section__desc">The badge bar at the bottom of the hero — partner logos and credibility signals.</p>

                    <!-- TripAdvisor -->
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">TripAdvisor</h3>
                        <div class="etm-field">
                            <label class="etm-label" for="trust_ta_sub">Sub-label</label>
                            <input type="text" id="trust_ta_sub" name="trust_ta_sub" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'trust_ta_sub', '5-Star Rated' ) ); ?>">
                        </div>
                    </div>

                    <!-- Fáilte Ireland -->
                    <?php
                    $failte_url = $img_url( 'trust_failte_logo_id', 'thumbnail' );
                    ?>
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">Fáilte Ireland</h3>
                        <div class="etm-field">
                            <label class="etm-label">Logo Image</label>
                            <div class="etm-media-upload">
                                <img src="<?php echo esc_url( $failte_url ); ?>" id="etm-failte-preview"
                                     class="etm-media-preview" alt="" <?php echo $failte_url ? '' : 'style="display:none;"'; ?>>
                                <input type="hidden" name="trust_failte_logo_id" id="etm-failte-logo-id"
                                       value="<?php echo esc_attr( $oi( 'trust_failte_logo_id' ) ?: '' ); ?>">
                                <div class="etm-media-btns">
                                    <button type="button" class="etm-btn-upload button"
                                            data-target="etm-failte-logo-id" data-preview="etm-failte-preview"
                                            data-title="Select Fáilte Ireland Logo">
                                        <?php echo $failte_url ? 'Change Logo' : 'Upload Logo'; ?>
                                    </button>
                                    <?php if ( $failte_url ) : ?>
                                        <button type="button" class="etm-btn-remove button-link-delete"
                                                data-target="etm-failte-logo-id" data-preview="etm-failte-preview">Remove</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="etm-help">Falls back to bundled logo. Recommended: PNG with transparency, ~220×80px.</p>
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="trust_failte_sub">Sub-label</label>
                            <input type="text" id="trust_failte_sub" name="trust_failte_sub" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'trust_failte_sub', 'Approved Partner' ) ); ?>">
                        </div>
                    </div>

                    <!-- ASTA -->
                    <?php $asta_url = $img_url( 'trust_asta_logo_id', 'thumbnail' ); ?>
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">ASTA</h3>
                        <div class="etm-field">
                            <label class="etm-label">Logo Image</label>
                            <div class="etm-media-upload">
                                <img src="<?php echo esc_url( $asta_url ); ?>" id="etm-asta-preview"
                                     class="etm-media-preview" alt="" <?php echo $asta_url ? '' : 'style="display:none;"'; ?>>
                                <input type="hidden" name="trust_asta_logo_id" id="etm-asta-logo-id"
                                       value="<?php echo esc_attr( $oi( 'trust_asta_logo_id' ) ?: '' ); ?>">
                                <div class="etm-media-btns">
                                    <button type="button" class="etm-btn-upload button"
                                            data-target="etm-asta-logo-id" data-preview="etm-asta-preview"
                                            data-title="Select ASTA Logo">
                                        <?php echo $asta_url ? 'Change Logo' : 'Upload Logo'; ?>
                                    </button>
                                    <?php if ( $asta_url ) : ?>
                                        <button type="button" class="etm-btn-remove button-link-delete"
                                                data-target="etm-asta-logo-id" data-preview="etm-asta-preview">Remove</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="trust_asta_sub">Sub-label</label>
                            <input type="text" id="trust_asta_sub" name="trust_asta_sub" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'trust_asta_sub', 'Member' ) ); ?>">
                        </div>
                    </div>

                    <!-- IAGTO -->
                    <?php $iagto_url = $img_url( 'trust_iagto_logo_id', 'thumbnail' ); ?>
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">IAGTO</h3>
                        <div class="etm-field">
                            <label class="etm-label">Logo Image</label>
                            <div class="etm-media-upload">
                                <img src="<?php echo esc_url( $iagto_url ); ?>" id="etm-iagto-preview"
                                     class="etm-media-preview" alt="" <?php echo $iagto_url ? '' : 'style="display:none;"'; ?>>
                                <input type="hidden" name="trust_iagto_logo_id" id="etm-iagto-logo-id"
                                       value="<?php echo esc_attr( $oi( 'trust_iagto_logo_id' ) ?: '' ); ?>">
                                <div class="etm-media-btns">
                                    <button type="button" class="etm-btn-upload button"
                                            data-target="etm-iagto-logo-id" data-preview="etm-iagto-preview"
                                            data-title="Select IAGTO Logo">
                                        <?php echo $iagto_url ? 'Change Logo' : 'Upload Logo'; ?>
                                    </button>
                                    <?php if ( $iagto_url ) : ?>
                                        <button type="button" class="etm-btn-remove button-link-delete"
                                                data-target="etm-iagto-logo-id" data-preview="etm-iagto-preview">Remove</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="etm-help">IAGTO logo renders in colour (not white).</p>
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="trust_iagto_sub">Sub-label</label>
                            <input type="text" id="trust_iagto_sub" name="trust_iagto_sub" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'trust_iagto_sub', 'Golf Tourism' ) ); ?>">
                        </div>
                    </div>

                    <!-- Since badge -->
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">Since Badge</h3>
                        <div class="etm-field-row">
                            <div class="etm-field">
                                <label class="etm-label" for="trust_since_label">Badge Text</label>
                                <input type="text" id="trust_since_label" name="trust_since_label" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'trust_since_label', 'Since 1973' ) ); ?>">
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="trust_since_sub">Sub-label</label>
                                <input type="text" id="trust_since_sub" name="trust_since_sub" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'trust_since_sub', '50+ years experience' ) ); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Stats Strip ───────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-stats" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Stats Strip</h2>
                    <p class="etm-section__desc">Four icon + label + description items displayed in a horizontal strip below the hero trust bar.</p>

                    <?php
                    $stat_defaults = [
                        1 => [ 'icon' => 'star',   'label' => 'Since 1973',                  'desc' => 'Over five decades of private touring' ],
                        2 => [ 'icon' => 'pin',    'label' => 'Deep Local Knowledge',        'desc' => 'Ireland brought to life through storytelling' ],
                        3 => [ 'icon' => 'shield', 'label' => 'Trusted by Premium Travellers','desc' => 'Discretion, professionalism, reliability' ],
                        4 => [ 'icon' => 'check',  'label' => 'Every Detail Handled',        'desc' => 'Door-to-door, from first conversation to last day' ],
                    ];
                    $icon_options = [ 'star' => 'Star', 'pin' => 'Pin / Location', 'shield' => 'Shield', 'check' => 'Check Circle' ];
                    foreach ( $stat_defaults as $n => $def ) :
                    ?>
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">Item <?php echo $n; ?></h3>
                        <div class="etm-field">
                            <label class="etm-label" for="stats_<?php echo $n; ?>_icon">Icon</label>
                            <select id="stats_<?php echo $n; ?>_icon" name="stats_<?php echo $n; ?>_icon" class="etm-select">
                                <?php foreach ( $icon_options as $val => $label ) : ?>
                                    <option value="<?php echo esc_attr( $val ); ?>"
                                        <?php selected( $o( 'stats_' . $n . '_icon', $def['icon'] ), $val ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="etm-field-row">
                            <div class="etm-field">
                                <label class="etm-label" for="stats_<?php echo $n; ?>_label">Label</label>
                                <input type="text" id="stats_<?php echo $n; ?>_label"
                                       name="stats_<?php echo $n; ?>_label" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'stats_' . $n . '_label', $def['label'] ) ); ?>">
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="stats_<?php echo $n; ?>_desc">Description</label>
                                <input type="text" id="stats_<?php echo $n; ?>_desc"
                                       name="stats_<?php echo $n; ?>_desc" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'stats_' . $n . '_desc', $def['desc'] ) ); ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Who We Are ───────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-intro" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Who We Are (Intro)</h2>

                    <div class="etm-field">
                        <label class="etm-label" for="intro_label">Section Label</label>
                        <input type="text" id="intro_label" name="intro_label" class="etm-input"
                               value="<?php echo esc_attr( $o( 'intro_label', 'Who We Are' ) ); ?>">
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="intro_heading">Heading</label>
                        <input type="text" id="intro_heading" name="intro_heading" class="etm-input etm-input--wide"
                               value="<?php echo esc_attr( $o( 'intro_heading', 'More Than a Tour.<br>A Deeper Connection to Ireland.' ) ); ?>">
                        <p class="etm-help">You can use <code>&lt;br&gt;</code> for a line break.</p>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="intro_body">Body Text</label>
                        <textarea id="intro_body" name="intro_body" class="etm-textarea" rows="8"><?php
                            $default_intro_body = '<p>For many people, a journey to Ireland is not just a holiday. It is a return to something — ancestry, identity, a sense of belonging. Yet too often, that experience is rushed, impersonal, and built for volume rather than meaning.</p><p>Elite Tours was built to change that.</p><p>Every journey we create is built entirely around you — your interests, your family, your pace. We don\'t move people from place to place. We welcome them into Ireland properly. Every detail is considered. Every experience is shaped to feel effortless, personal, and worth remembering.</p><p>This is not a tour. This is how Ireland should be experienced.</p>';
                            echo esc_textarea( $o( 'intro_body', $default_intro_body ) );
                        ?></textarea>
                        <p class="etm-help">Basic HTML allowed (p, strong, em, br). Each paragraph should be wrapped in &lt;p&gt; tags.</p>
                    </div>
                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="intro_cta_text">CTA Button Text</label>
                            <input type="text" id="intro_cta_text" name="intro_cta_text" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'intro_cta_text', 'Meet Our Story' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="intro_cta_url">CTA URL</label>
                            <input type="url" id="intro_cta_url" name="intro_cta_url" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'intro_cta_url', '/about-us/' ) ); ?>">
                        </div>
                    </div>
                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="intro_badge_num">Badge Number</label>
                            <input type="text" id="intro_badge_num" name="intro_badge_num" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'intro_badge_num', '50+' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="intro_badge_text">Badge Text</label>
                            <input type="text" id="intro_badge_text" name="intro_badge_text" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'intro_badge_text', 'Years of<br>Experience' ) ); ?>">
                            <p class="etm-help">Use <code>&lt;br&gt;</code> for a line break.</p>
                        </div>
                    </div>

                    <!-- Intro image -->
                    <?php $intro_img_url = $img_url( 'intro_image_id' ); ?>
                    <div class="etm-field">
                        <label class="etm-label">Section Image</label>
                        <div class="etm-media-upload">
                            <img src="<?php echo esc_url( $intro_img_url ); ?>" id="etm-intro-img-preview"
                                 class="etm-media-preview" alt=""
                                 <?php echo $intro_img_url ? '' : 'style="display:none;"'; ?>>
                            <input type="hidden" name="intro_image_id" id="etm-intro-image-id"
                                   value="<?php echo esc_attr( $oi( 'intro_image_id' ) ?: '' ); ?>">
                            <div class="etm-media-btns">
                                <button type="button" class="etm-btn-upload button"
                                        data-target="etm-intro-image-id"
                                        data-preview="etm-intro-img-preview"
                                        data-title="Select Intro Image">
                                    <?php echo $intro_img_url ? 'Change Image' : 'Upload Image'; ?>
                                </button>
                                <?php if ( $intro_img_url ) : ?>
                                    <button type="button" class="etm-btn-remove button-link-delete"
                                            data-target="etm-intro-image-id"
                                            data-preview="etm-intro-img-preview">Remove</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="etm-help">Falls back to bundled castle-hillside.jpg if not set.</p>
                    </div>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Core Offers ──────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-offers" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Core Offers</h2>
                    <p class="etm-section__desc">Two full-bleed image cards — Bespoke and Golf.</p>
                    <div class="etm-field-row etm-field-row--halves">

                        <?php
                        $offer_defaults = [
                            1 => [
                                'label' => 'Bespoke Private Tours',
                                'heading' => 'Ireland,<br>Built Around You.',
                                'desc' => 'Deeply personal, privately guided journeys — ancestry, culture, heritage, whiskey, scenic routes. No fixed itineraries. Everything designed from scratch, around the people taking it.',
                                'cta_text' => 'Explore Bespoke Tours',
                                'cta_url'  => '/bespoke-tours/',
                            ],
                            2 => [
                                'label' => 'Golf Tours',
                                'heading' => "Play Ireland's Greatest Courses.",
                                'desc' => "Fully managed golf journeys across Ireland's most iconic links — with priority access, private chauffeur, and Ray's personal hosting standard throughout.",
                                'cta_text' => 'Explore Golf Tours',
                                'cta_url'  => '/golf-tours/',
                            ],
                        ];
                        foreach ( $offer_defaults as $n => $def ) :
                            $offer_img_url = $img_url( 'offer_' . $n . '_image_id' );
                        ?>
                        <div class="etm-field-group">
                            <h3 class="etm-field-group__title">Card <?php echo $n === 1 ? '1 — Bespoke' : '2 — Golf'; ?></h3>
                            <div class="etm-field">
                                <label class="etm-label">Card Image</label>
                                <div class="etm-media-upload">
                                    <img src="<?php echo esc_url( $offer_img_url ); ?>"
                                         id="etm-offer-<?php echo $n; ?>-preview"
                                         class="etm-media-preview" alt=""
                                         <?php echo $offer_img_url ? '' : 'style="display:none;"'; ?>>
                                    <input type="hidden" name="offer_<?php echo $n; ?>_image_id"
                                           id="etm-offer-<?php echo $n; ?>-image-id"
                                           value="<?php echo esc_attr( $oi( 'offer_' . $n . '_image_id' ) ?: '' ); ?>">
                                    <div class="etm-media-btns">
                                        <button type="button" class="etm-btn-upload button"
                                                data-target="etm-offer-<?php echo $n; ?>-image-id"
                                                data-preview="etm-offer-<?php echo $n; ?>-preview"
                                                data-title="Select Card <?php echo $n; ?> Image">
                                            <?php echo $offer_img_url ? 'Change Image' : 'Upload Image'; ?>
                                        </button>
                                        <?php if ( $offer_img_url ) : ?>
                                            <button type="button" class="etm-btn-remove button-link-delete"
                                                    data-target="etm-offer-<?php echo $n; ?>-image-id"
                                                    data-preview="etm-offer-<?php echo $n; ?>-preview">Remove</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="offer_<?php echo $n; ?>_label">Label</label>
                                <input type="text" id="offer_<?php echo $n; ?>_label"
                                       name="offer_<?php echo $n; ?>_label" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'offer_' . $n . '_label', $def['label'] ) ); ?>">
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="offer_<?php echo $n; ?>_heading">Heading</label>
                                <input type="text" id="offer_<?php echo $n; ?>_heading"
                                       name="offer_<?php echo $n; ?>_heading" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'offer_' . $n . '_heading', $def['heading'] ) ); ?>">
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="offer_<?php echo $n; ?>_desc">Description</label>
                                <textarea id="offer_<?php echo $n; ?>_desc"
                                          name="offer_<?php echo $n; ?>_desc"
                                          class="etm-textarea" rows="4"><?php echo esc_textarea( $o( 'offer_' . $n . '_desc', $def['desc'] ) ); ?></textarea>
                            </div>
                            <div class="etm-field-row">
                                <div class="etm-field">
                                    <label class="etm-label" for="offer_<?php echo $n; ?>_cta_text">Button Text</label>
                                    <input type="text" id="offer_<?php echo $n; ?>_cta_text"
                                           name="offer_<?php echo $n; ?>_cta_text" class="etm-input"
                                           value="<?php echo esc_attr( $o( 'offer_' . $n . '_cta_text', $def['cta_text'] ) ); ?>">
                                </div>
                                <div class="etm-field">
                                    <label class="etm-label" for="offer_<?php echo $n; ?>_cta_url">Button URL</label>
                                    <input type="url" id="offer_<?php echo $n; ?>_cta_url"
                                           name="offer_<?php echo $n; ?>_cta_url" class="etm-input"
                                           value="<?php echo esc_attr( $o( 'offer_' . $n . '_cta_url', $def['cta_url'] ) ); ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: How It Works ─────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-process" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">How It Works</h2>

                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="process_label">Section Label</label>
                            <input type="text" id="process_label" name="process_label" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'process_label', 'The Process' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="process_heading">Heading</label>
                            <input type="text" id="process_heading" name="process_heading" class="etm-input etm-input--wide"
                                   value="<?php echo esc_attr( $o( 'process_heading', 'Your Journey, From First Conversation to Final Day.' ) ); ?>">
                        </div>
                    </div>
                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="process_cta_text">CTA Button Text</label>
                            <input type="text" id="process_cta_text" name="process_cta_text" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'process_cta_text', 'Start Planning Your Journey' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="process_cta_url">CTA URL</label>
                            <input type="url" id="process_cta_url" name="process_cta_url" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'process_cta_url', '/contact/' ) ); ?>">
                        </div>
                    </div>

                    <?php
                    $step_defaults = [
                        1 => [ 'num' => '01', 'title' => 'We Listen',                      'desc' => "Tell us who you are, what matters to you, and what you're hoping to feel. No forms. A real conversation." ],
                        2 => [ 'num' => '02', 'title' => 'We Design',                      'desc' => 'We create a bespoke itinerary built entirely around you — your interests, your family, your pace.' ],
                        3 => [ 'num' => '03', 'title' => 'We Handle Everything',           'desc' => "From accommodation to access, transfers to timing — every detail is managed, so you don't have to think about a thing." ],
                        4 => [ 'num' => '04', 'title' => 'You Experience Ireland Properly','desc' => 'Arrive as a visitor. Leave with a deeper connection to Ireland — and often, a lifelong friend.' ],
                    ];
                    foreach ( $step_defaults as $n => $def ) :
                    ?>
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">Step <?php echo $n; ?></h3>
                        <div class="etm-field-row">
                            <div class="etm-field" style="max-width:80px;">
                                <label class="etm-label" for="step_<?php echo $n; ?>_num">Number</label>
                                <input type="text" id="step_<?php echo $n; ?>_num"
                                       name="step_<?php echo $n; ?>_num" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'step_' . $n . '_num', $def['num'] ) ); ?>">
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="step_<?php echo $n; ?>_title">Title</label>
                                <input type="text" id="step_<?php echo $n; ?>_title"
                                       name="step_<?php echo $n; ?>_title" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'step_' . $n . '_title', $def['title'] ) ); ?>">
                            </div>
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="step_<?php echo $n; ?>_desc">Description</label>
                            <textarea id="step_<?php echo $n; ?>_desc"
                                      name="step_<?php echo $n; ?>_desc"
                                      class="etm-textarea" rows="3"><?php echo esc_textarea( $o( 'step_' . $n . '_desc', $def['desc'] ) ); ?></textarea>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Experiences ──────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-experiences" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Experiences Grid</h2>
                    <p class="etm-section__desc">Six experience cards displayed in a grid. Images fall back to bundled photos if not uploaded.</p>

                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="exp_label">Section Label</label>
                            <input type="text" id="exp_label" name="exp_label" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'exp_label', 'Experiences' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="exp_heading">Heading</label>
                            <input type="text" id="exp_heading" name="exp_heading" class="etm-input etm-input--wide"
                                   value="<?php echo esc_attr( $o( 'exp_heading', "Every Journey Is Different. Here's Where Yours Might Begin." ) ); ?>">
                        </div>
                    </div>

                    <?php
                    $exp_defaults = [
                        1 => [ 'label' => 'Ancestry & Roots',        'title' => 'Trace Your Irish Heritage',    'desc' => 'Trace your Irish heritage with depth, dignity, and personal connection.',         'url' => '/bespoke-tours/' ],
                        2 => [ 'label' => 'Whiskey & Culture',       'title' => "Ireland's Craft Distilleries", 'desc' => "Ireland's craft distilleries and rich cultural story, privately curated.",       'url' => '/experiences/' ],
                        3 => [ 'label' => 'Scenic & Coastal Ireland', 'title' => 'The Wild Atlantic',           'desc' => 'The Wild Atlantic, country roads, cliffs and castles — at your pace.',            'url' => '/bespoke-tours/' ],
                        4 => [ 'label' => 'Golf Tours',              'title' => "Ireland's Iconic Links",       'desc' => "Ireland's most iconic links courses, seamlessly handled.",                        'url' => '/golf-tours/' ],
                        5 => [ 'label' => 'Family Private Journey',  'title' => 'For Every Generation',        'desc' => 'A meaningful Irish experience for every generation in your family.',              'url' => '/bespoke-tours/' ],
                        6 => [ 'label' => 'Heritage & History',      'title' => 'Castles & Estate Stays',      'desc' => 'Castles, estates, and the stories of Ireland told through its landscape.',        'url' => '/experiences/' ],
                    ];
                    foreach ( $exp_defaults as $n => $def ) :
                        $exp_img_url = $img_url( 'exp_' . $n . '_image_id' );
                    ?>
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">Card <?php echo $n; ?></h3>
                        <div class="etm-field">
                            <label class="etm-label">Card Image</label>
                            <div class="etm-media-upload">
                                <img src="<?php echo esc_url( $exp_img_url ); ?>"
                                     id="etm-exp-<?php echo $n; ?>-preview"
                                     class="etm-media-preview" alt=""
                                     <?php echo $exp_img_url ? '' : 'style="display:none;"'; ?>>
                                <input type="hidden" name="exp_<?php echo $n; ?>_image_id"
                                       id="etm-exp-<?php echo $n; ?>-image-id"
                                       value="<?php echo esc_attr( $oi( 'exp_' . $n . '_image_id' ) ?: '' ); ?>">
                                <div class="etm-media-btns">
                                    <button type="button" class="etm-btn-upload button"
                                            data-target="etm-exp-<?php echo $n; ?>-image-id"
                                            data-preview="etm-exp-<?php echo $n; ?>-preview"
                                            data-title="Select Experience <?php echo $n; ?> Image">
                                        <?php echo $exp_img_url ? 'Change Image' : 'Upload Image'; ?>
                                    </button>
                                    <?php if ( $exp_img_url ) : ?>
                                        <button type="button" class="etm-btn-remove button-link-delete"
                                                data-target="etm-exp-<?php echo $n; ?>-image-id"
                                                data-preview="etm-exp-<?php echo $n; ?>-preview">Remove</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="etm-field-row">
                            <div class="etm-field">
                                <label class="etm-label" for="exp_<?php echo $n; ?>_label">Label</label>
                                <input type="text" id="exp_<?php echo $n; ?>_label"
                                       name="exp_<?php echo $n; ?>_label" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'exp_' . $n . '_label', $def['label'] ) ); ?>">
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="exp_<?php echo $n; ?>_title">Title</label>
                                <input type="text" id="exp_<?php echo $n; ?>_title"
                                       name="exp_<?php echo $n; ?>_title" class="etm-input"
                                       value="<?php echo esc_attr( $o( 'exp_' . $n . '_title', $def['title'] ) ); ?>">
                            </div>
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="exp_<?php echo $n; ?>_desc">Description</label>
                            <input type="text" id="exp_<?php echo $n; ?>_desc"
                                   name="exp_<?php echo $n; ?>_desc" class="etm-input etm-input--wide"
                                   value="<?php echo esc_attr( $o( 'exp_' . $n . '_desc', $def['desc'] ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="exp_<?php echo $n; ?>_url">Link URL</label>
                            <input type="url" id="exp_<?php echo $n; ?>_url"
                                   name="exp_<?php echo $n; ?>_url" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'exp_' . $n . '_url', $def['url'] ) ); ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Testimonials ─────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-testimonials" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Testimonials</h2>

                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="testimonials_label">Section Label</label>
                            <input type="text" id="testimonials_label" name="testimonials_label" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'testimonials_label', 'Client Stories' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="testimonials_heading">Heading</label>
                            <input type="text" id="testimonials_heading" name="testimonials_heading" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'testimonials_heading', 'What Our Clients Say' ) ); ?>">
                        </div>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="testimonials_sub">Sub-line</label>
                        <input type="text" id="testimonials_sub" name="testimonials_sub" class="etm-input etm-input--wide"
                               value="<?php echo esc_attr( $o( 'testimonials_sub', 'These are not reviews. These are stories.' ) ); ?>">
                    </div>

                    <?php
                    $test_defaults = [
                        1 => [ 'quote' => "We arrived not knowing what to expect. We left feeling like Ireland was part of us. Ray thought of everything — things we didn't even know we needed. It was the most personal trip we've ever taken.", 'name' => 'Patricia & Tom M.', 'origin' => 'Boston' ],
                        2 => [ 'quote' => "The golf was extraordinary. Old Head was a moment I'll never forget. But it was the way everything was handled — every tee time, every detail — that made it truly special.",                           'name' => 'James K.',           'origin' => 'New York' ],
                        3 => [ 'quote' => "We came to find our family's roots in County Cork. What we found was far more than we expected. This wasn't tourism — it was a homecoming.",                                                         'name' => 'The McCarthy Family', 'origin' => 'Chicago' ],
                    ];
                    foreach ( $test_defaults as $n => $def ) :
                    ?>
                    <div class="etm-field-group">
                        <h3 class="etm-field-group__title">Testimonial <?php echo $n; ?></h3>
                        <div class="etm-field">
                            <label class="etm-label" for="t_<?php echo $n; ?>_quote">Quote</label>
                            <textarea id="t_<?php echo $n; ?>_quote"
                                      name="t_<?php echo $n; ?>_quote"
                                      class="etm-textarea" rows="4"><?php echo esc_textarea( $o( 't_' . $n . '_quote', $def['quote'] ) ); ?></textarea>
                        </div>
                        <div class="etm-field-row">
                            <div class="etm-field">
                                <label class="etm-label" for="t_<?php echo $n; ?>_name">Name</label>
                                <input type="text" id="t_<?php echo $n; ?>_name"
                                       name="t_<?php echo $n; ?>_name" class="etm-input"
                                       value="<?php echo esc_attr( $o( 't_' . $n . '_name', $def['name'] ) ); ?>">
                            </div>
                            <div class="etm-field">
                                <label class="etm-label" for="t_<?php echo $n; ?>_origin">Origin / Location</label>
                                <input type="text" id="t_<?php echo $n; ?>_origin"
                                       name="t_<?php echo $n; ?>_origin" class="etm-input"
                                       value="<?php echo esc_attr( $o( 't_' . $n . '_origin', $def['origin'] ) ); ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

            <!-- ── TAB: Founder CTA ──────────────────────────────────────── -->
            <div class="etm-panel" id="etm-panel-founder" style="display:none;">
                <div class="etm-section">
                    <h2 class="etm-section__title">Founder CTA</h2>

                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="founder_label">Section Label</label>
                            <input type="text" id="founder_label" name="founder_label" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'founder_label', 'Plan Your Journey' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="founder_heading">Heading</label>
                            <input type="text" id="founder_heading" name="founder_heading" class="etm-input etm-input--wide"
                                   value="<?php echo esc_attr( $o( 'founder_heading', 'Start Planning<br>Your Journey.' ) ); ?>">
                            <p class="etm-help">Use <code>&lt;br&gt;</code> for a line break.</p>
                        </div>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="founder_body">Body Text</label>
                        <textarea id="founder_body" name="founder_body" class="etm-textarea" rows="4"><?php echo esc_textarea( $o( 'founder_body', "Every journey is tailored to you — designed with care, local insight, and a deep understanding of Ireland. There are no fixed packages here. Just a real conversation about who you are and what you'd love to experience." ) ); ?></textarea>
                    </div>
                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="founder_cta_text">Button Text</label>
                            <input type="text" id="founder_cta_text" name="founder_cta_text" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'founder_cta_text', 'Plan Your Journey' ) ); ?>">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="founder_cta_url">Button URL</label>
                            <input type="url" id="founder_cta_url" name="founder_cta_url" class="etm-input"
                                   value="<?php echo esc_attr( $o( 'founder_cta_url', '/contact/' ) ); ?>">
                        </div>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="founder_quote">Pull Quote <em>(italic)</em></label>
                        <textarea id="founder_quote" name="founder_quote" class="etm-textarea" rows="3"><?php echo esc_textarea( $o( 'founder_quote', "I've spent decades helping people experience Ireland in a truly personal way." ) ); ?></textarea>
                        <p class="etm-help">Displayed in italic below the CTA button. Do not include quotation marks — they are added automatically.</p>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="founder_cite">Citation</label>
                        <input type="text" id="founder_cite" name="founder_cite" class="etm-input etm-input--wide"
                               value="<?php echo esc_attr( $o( 'founder_cite', 'Raphael Mulally, Founder, Elite Tours Ireland' ) ); ?>">
                        <p class="etm-help">Displayed as "— [citation]" below the quote.</p>
                    </div>

                    <!-- Founder image -->
                    <?php $founder_img_url = $img_url( 'founder_image_id' ); ?>
                    <div class="etm-field">
                        <label class="etm-label">Founder Photo</label>
                        <div class="etm-media-upload">
                            <img src="<?php echo esc_url( $founder_img_url ); ?>" id="etm-founder-img-preview"
                                 class="etm-media-preview" alt=""
                                 <?php echo $founder_img_url ? '' : 'style="display:none;"'; ?>>
                            <input type="hidden" name="founder_image_id" id="etm-founder-image-id"
                                   value="<?php echo esc_attr( $oi( 'founder_image_id' ) ?: '' ); ?>">
                            <div class="etm-media-btns">
                                <button type="button" class="etm-btn-upload button"
                                        data-target="etm-founder-image-id"
                                        data-preview="etm-founder-img-preview"
                                        data-title="Select Founder Photo">
                                    <?php echo $founder_img_url ? 'Change Photo' : 'Upload Photo'; ?>
                                </button>
                                <?php if ( $founder_img_url ) : ?>
                                    <button type="button" class="etm-btn-remove button-link-delete"
                                            data-target="etm-founder-image-id"
                                            data-preview="etm-founder-img-preview">Remove</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="etm-help">Falls back to bundled castle-hillside.jpg if not set. Portrait orientation recommended.</p>
                    </div>
                </div>

                <div class="etm-actions">
                    <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
                </div>
            </div>

        </form>
    </div><!-- .wrap -->

    <style>
    /* ── Page tabs ─────────────────────────────────────────────────────────── */
    .etm-page-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin: 16px 0 0;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 0;
    }
    .etm-page-tab {
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 500;
        color: #555;
        cursor: pointer;
        margin-bottom: -2px;
        border-radius: 0;
        transition: color .15s, border-color .15s;
    }
    .etm-page-tab:hover {
        color: #1A4F31;
    }
    .etm-page-tab--active {
        color: #1A4F31;
        border-bottom-color: #1A4F31;
        font-weight: 600;
    }

    /* ── Section rows (drag-and-drop) ──────────────────────────────────────── */
    .etm-section-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        margin-bottom: 6px;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        cursor: default;
        user-select: none;
        transition: border-color .15s, background .15s;
    }
    .etm-section-row:hover {
        border-color: #b0b0b0;
        background: #fafafa;
    }
    .etm-section-row.is-dragging {
        opacity: 0.4;
        border-style: dashed;
    }
    .etm-section-row.drag-over {
        border-color: #1A4F31;
        background: #f0f7f3;
    }
    .etm-section-row--fixed {
        background: #f5f5f5;
        border-style: dashed;
        cursor: default;
    }
    .etm-drag-handle {
        font-size: 20px;
        color: #aaa;
        cursor: grab;
        line-height: 1;
        padding: 0 2px;
        flex-shrink: 0;
    }
    .etm-drag-handle:active {
        cursor: grabbing;
    }
    .etm-drag-handle--disabled {
        cursor: default;
        opacity: 0.3;
    }
    .etm-section-name {
        flex: 1;
        font-weight: 500;
        font-size: 14px;
    }
    .etm-section-name em {
        font-weight: 400;
        color: #888;
        font-size: 12px;
    }
    .etm-section-fixed-badge {
        font-size: 11px;
        background: #e8f5e9;
        color: #2e7d32;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 500;
    }

    /* ── Toggle switch ──────────────────────────────────────────────────────── */
    .etm-toggle {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        flex-shrink: 0;
        position: relative;
    }
    .etm-toggle input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .etm-toggle__track {
        display: block;
        width: 40px;
        height: 22px;
        background: #ccc;
        border-radius: 11px;
        transition: background .2s;
        flex-shrink: 0;
    }
    .etm-toggle__thumb {
        position: absolute;
        left: 2px;
        top: 2px;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,.3);
        transition: transform .2s;
        pointer-events: none;
    }
    .etm-toggle input:checked + .etm-toggle__track {
        background: #1A4F31;
    }
    .etm-toggle input:checked ~ .etm-toggle__thumb {
        transform: translateX(18px);
    }

    /* ── Select field ───────────────────────────────────────────────────────── */
    .etm-select {
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 6px 10px;
        font-size: 13px;
        min-width: 160px;
    }

    /* ── Offer cards side by side ───────────────────────────────────────────── */
    .etm-field-row--halves {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .etm-field-row--halves { grid-template-columns: 1fr; }
    }
    </style>

    <script>
    ( function () {

        // ── Page-level tab switching ────────────────────────────────────────
        var pageTabs   = document.querySelectorAll( '.etm-page-tab' );
        var pagePanels = document.querySelectorAll( '.etm-panel' );

        pageTabs.forEach( function ( tab ) {
            tab.addEventListener( 'click', function () {
                pageTabs.forEach( function ( t ) { t.classList.remove( 'etm-page-tab--active' ); } );
                pagePanels.forEach( function ( p ) { p.style.display = 'none'; } );
                tab.classList.add( 'etm-page-tab--active' );
                var panel = document.getElementById( tab.dataset.panel );
                if ( panel ) panel.style.display = '';
            } );
        } );

        // ── Hero image/video inner tabs ─────────────────────────────────────
        document.querySelectorAll( '.etm-tab' ).forEach( function ( tab ) {
            tab.addEventListener( 'click', function () {
                var field = tab.closest( '.etm-field' );
                field.querySelectorAll( '.etm-tab' ).forEach( function ( t ) {
                    t.classList.remove( 'etm-tab--active' );
                } );
                tab.classList.add( 'etm-tab--active' );
                field.querySelectorAll( '.etm-tab-panel' ).forEach( function ( p ) {
                    p.style.display = 'none';
                } );
                var target = document.getElementById( 'etm-tab-' + tab.dataset.tab );
                if ( target ) target.style.display = '';
            } );
        } );

        // ── Media upload (wp.media) ─────────────────────────────────────────
        document.querySelectorAll( '.etm-btn-upload' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var frame = wp.media( {
                    title  : btn.dataset.title || 'Select File',
                    button : { text: 'Use this file' },
                    multiple: false,
                } );
                frame.on( 'select', function () {
                    var att     = frame.state().get( 'selection' ).first().toJSON();
                    var input   = document.getElementById( btn.dataset.target );
                    var preview = document.getElementById( btn.dataset.preview );
                    if ( input )   input.value          = att.id;
                    if ( preview ) {
                        preview.src          = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                        preview.style.display = '';
                    }
                    btn.textContent = btn.textContent.indexOf( 'Upload' ) >= 0
                        ? btn.textContent.replace( 'Upload', 'Change' )
                        : btn.textContent;
                } );
                frame.open();
            } );
        } );

        document.querySelectorAll( '.etm-btn-remove' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var input   = document.getElementById( btn.dataset.target );
                var preview = document.getElementById( btn.dataset.preview );
                if ( input )   input.value           = '';
                if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
            } );
        } );

        // ── Drag-and-drop section reordering ────────────────────────────────
        var sortable     = document.getElementById( 'etm-sortable-sections' );
        var orderInput   = document.getElementById( 'etm-section-order-input' );
        var dragSrc      = null;

        function getRows() {
            return Array.from( sortable.querySelectorAll( '.etm-section-row[draggable="true"]' ) );
        }

        function updateOrderInput() {
            var slugs = getRows().map( function ( row ) { return row.dataset.slug; } );
            orderInput.value = JSON.stringify( slugs );
        }

        sortable.addEventListener( 'dragstart', function ( e ) {
            var row = e.target.closest( '[draggable="true"]' );
            if ( ! row ) return;
            dragSrc = row;
            row.classList.add( 'is-dragging' );
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData( 'text/plain', row.dataset.slug );
        } );

        sortable.addEventListener( 'dragend', function () {
            getRows().forEach( function ( row ) {
                row.classList.remove( 'is-dragging', 'drag-over' );
            } );
            dragSrc = null;
        } );

        sortable.addEventListener( 'dragover', function ( e ) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var row = e.target.closest( '[draggable="true"]' );
            if ( ! row || row === dragSrc ) return;
            getRows().forEach( function ( r ) { r.classList.remove( 'drag-over' ); } );
            row.classList.add( 'drag-over' );
        } );

        sortable.addEventListener( 'dragleave', function ( e ) {
            var row = e.target.closest( '[draggable="true"]' );
            if ( row ) row.classList.remove( 'drag-over' );
        } );

        sortable.addEventListener( 'drop', function ( e ) {
            e.preventDefault();
            var target = e.target.closest( '[draggable="true"]' );
            if ( ! target || ! dragSrc || target === dragSrc ) return;

            // Determine insert position
            var rows     = getRows();
            var srcIdx   = rows.indexOf( dragSrc );
            var tgtIdx   = rows.indexOf( target );
            var rect     = target.getBoundingClientRect();
            var midY     = rect.top + rect.height / 2;

            if ( e.clientY < midY ) {
                // Insert before target
                sortable.insertBefore( dragSrc, target );
            } else {
                // Insert after target
                var next = target.nextElementSibling;
                if ( next ) {
                    sortable.insertBefore( dragSrc, next );
                } else {
                    sortable.appendChild( dragSrc );
                }
            }

            target.classList.remove( 'drag-over' );
            updateOrderInput();
        } );

    } )();
    </script>
    <?php
}
