<?php
defined( 'ABSPATH' ) || exit;

// ── Save Handler (AJAX) ───────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_hp_save', function () {
    check_ajax_referer( 'etm_homepage', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $existing = get_option( 'et_homepage_settings', [] );
    $data     = $existing; // preserve any keys not explicitly saved here

    // ── Text fields (sanitize_text_field) ────────────────────────────────────
    $text_fields = [
        // Hero
        'hero_label', 'hero_headline', 'hero_cta_primary', 'hero_cta_secondary', 'hero_proof_text', 'hero_video_url',
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
    $slugs = [ 'intro', 'offers', 'process', 'experiences', 'testimonials', 'founder-cta' ];
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
    wp_send_json_success( 'Saved' );
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
    $slugs = [ 'intro', 'offers', 'process', 'experiences', 'testimonials', 'founder-cta' ];
    $slug_labels = [

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

        <div id="etm-save-feedback" class="etm-notice" style="min-height:1.5em;"></div>

        <form method="post" id="etm-hp-form">
            <?php wp_nonce_field( 'etm_homepage' ); ?>

            <!-- ── Hero ─────────────────────────────────────────────── -->
            <div class="etm-accordion" id="etm-panel-hero">
                <button type="button" class="etm-accordion__toggle">Hero Section <span class="etm-accordion__arrow">&#9662;</span></button>
                <div class="etm-accordion__body" style="display:none;">
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
                               value="<?php echo esc_attr( $o( 'hero_label', 'ELITE TOURS IRELAND · SINCE 1973' ) ); ?>">
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
                        <textarea id="hero_subheading" name="hero_subheading" class="etm-textarea" rows="2"><?php echo esc_textarea( $o( 'hero_subheading', 'Bespoke private journeys, tailored to you, delivered with genuine Irish care.' ) ); ?></textarea>
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

                    <!-- TripAdvisor proof badge — appears above the buttons -->
                    <div class="etm-field">
                        <label class="etm-label" for="hero_proof_text">TripAdvisor Proof Line <small>(above buttons)</small></label>
                        <input type="text" id="hero_proof_text" name="hero_proof_text" class="etm-input"
                               value="<?php echo esc_attr( $o( 'hero_proof_text', "Ireland's Highest-Rated Tour Provider on TripAdvisor" ) ); ?>">
                        <p class="etm-help">Short social-proof line shown with 5 green stars, just above the hero buttons. Leave blank to hide.</p>
                    </div>
                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            <!-- ── Trust Bar ─────────────────────────────────────────── -->
            <div class="etm-accordion" id="etm-panel-trust">
                <button type="button" class="etm-accordion__toggle">Trust Strip <span class="etm-accordion__arrow">&#9662;</span></button>
                <div class="etm-accordion__body" style="display:none;">
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
                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            <!-- ── Sortable homepage sections ─────────────────────────── -->
            <p class="etm-sortable-hint">Drag sections to reorder. Toggle visibility with the switch.</p>
            <input type="hidden" name="section_order" id="etm-section-order-input"
                   value="<?php echo esc_attr( wp_json_encode( $section_order ) ); ?>">
            <div id="etm-sortable-sections">

            <?php /* ── Who We Are ── */ ?>
            <div class="etm-accordion etm-accordion--sortable" id="etm-panel-intro" data-slug="intro">
                <div class="etm-accordion__header">
                    <span class="etm-drag-handle" title="Drag to reorder">&#8942;</span>
                    <span class="etm-accordion__title" role="button">Who We Are</span>
                    <label class="etm-toggle" onclick="event.stopPropagation()">
                        <input type="checkbox" name="section_intro_visible" value="1"
                               <?php checked( $o( 'section_intro_visible', '1' ), '1' ); ?>>
                        <span class="etm-toggle__track"></span>
                        <span class="etm-toggle__thumb"></span>
                    </label>
                    <span class="etm-accordion__arrow">&#9662;</span>
                </div>
                <div class="etm-accordion__body" style="display:none;">

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
                            $default_intro_body = '<p>For many people, a journey to Ireland is not just a holiday. It is a return to something. Ancestry, identity, a sense of belonging. Yet too often, that experience is rushed, impersonal, and built for volume rather than meaning.</p><p>Elite Tours was built to change that.</p><p>Every journey we create is built entirely around you. Your interests, your family, your pace. We don\'t move people from place to place. We welcome them into Ireland properly. Every detail is considered. Every experience is shaped to feel effortless, personal, and worth remembering.</p><p>This is not a tour. This is how Ireland should be experienced.</p>';
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
                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            <?php /* ── Core Offers ── */ ?>
            <div class="etm-accordion etm-accordion--sortable" id="etm-panel-offers" data-slug="offers">
                <div class="etm-accordion__header">
                    <span class="etm-drag-handle" title="Drag to reorder">&#8942;</span>
                    <span class="etm-accordion__title" role="button">Core Offers</span>
                    <label class="etm-toggle" onclick="event.stopPropagation()">
                        <input type="checkbox" name="section_offers_visible" value="1"
                               <?php checked( $o( 'section_offers_visible', '1' ), '1' ); ?>>
                        <span class="etm-toggle__track"></span>
                        <span class="etm-toggle__thumb"></span>
                    </label>
                    <span class="etm-accordion__arrow">&#9662;</span>
                </div>
                <div class="etm-accordion__body" style="display:none;">
                    <p class="etm-section__desc">Two full-bleed image cards — Bespoke and Golf.</p>
                    <div class="etm-field-row etm-field-row--halves">

                        <?php
                        $offer_defaults = [
                            1 => [
                                'label' => 'Bespoke Private Tours',
                                'heading' => 'Ireland,<br>Built Around You.',
                                'desc' => 'Deeply personal, privately guided journeys. Ancestry, culture, heritage, whiskey, scenic routes. No fixed itineraries. Everything designed from scratch, around the people taking it.',
                                'cta_text' => 'Explore Bespoke Tours',
                                'cta_url'  => '/bespoke-tours/',
                            ],
                            2 => [
                                'label' => 'Golf Tours',
                                'heading' => "Play Ireland's Greatest Courses.",
                                'desc' => "Fully managed golf journeys across Ireland's most iconic links, with priority access, private chauffeur, and Ray's personal hosting standard throughout.",
                                'cta_text' => 'Explore Golf Tours',
                                'cta_url'  => '/golf-tours/',
                            ],
                        ];
                        foreach ( $offer_defaults as $n => $def ) :
                            $offer_img_url = $img_url( 'offer_' . $n . '_image_id' );
                        ?>
                        <div class="etm-field-group">
                            <h3 class="etm-field-group__title">Card <?php echo $n === 1 ? '1 - Bespoke' : '2 - Golf'; ?></h3>
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

                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            <?php /* ── How It Works ── */ ?>
            <div class="etm-accordion etm-accordion--sortable" id="etm-panel-process" data-slug="process">
                <div class="etm-accordion__header">
                    <span class="etm-drag-handle" title="Drag to reorder">&#8942;</span>
                    <span class="etm-accordion__title" role="button">How It Works</span>
                    <label class="etm-toggle" onclick="event.stopPropagation()">
                        <input type="checkbox" name="section_process_visible" value="1"
                               <?php checked( $o( 'section_process_visible', '1' ), '1' ); ?>>
                        <span class="etm-toggle__track"></span>
                        <span class="etm-toggle__thumb"></span>
                    </label>
                    <span class="etm-accordion__arrow">&#9662;</span>
                </div>
                <div class="etm-accordion__body" style="display:none;">

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
                        2 => [ 'num' => '02', 'title' => 'We Design',                      'desc' => 'We create a bespoke itinerary built entirely around you. Your interests, your family, your pace.' ],
                        3 => [ 'num' => '03', 'title' => 'We Handle Everything',           'desc' => "From accommodation to access, transfers to timing, every detail is managed, so you don't have to think about a thing." ],
                        4 => [ 'num' => '04', 'title' => 'You Experience Ireland Properly','desc' => 'Arrive as a visitor. Leave with a deeper connection to Ireland, and often, a lifelong friend.' ],
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
                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            <?php /* ── Experiences ── */ ?>
            <div class="etm-accordion etm-accordion--sortable" id="etm-panel-experiences" data-slug="experiences">
                <div class="etm-accordion__header">
                    <span class="etm-drag-handle" title="Drag to reorder">&#8942;</span>
                    <span class="etm-accordion__title" role="button">Experiences Grid</span>
                    <label class="etm-toggle" onclick="event.stopPropagation()">
                        <input type="checkbox" name="section_experiences_visible" value="1"
                               <?php checked( $o( 'section_experiences_visible', '1' ), '1' ); ?>>
                        <span class="etm-toggle__track"></span>
                        <span class="etm-toggle__thumb"></span>
                    </label>
                    <span class="etm-accordion__arrow">&#9662;</span>
                </div>
                <div class="etm-accordion__body" style="display:none;">
                    <div class="etm-field">
                        <label class="etm-label" for="exp_heading">Section Heading</label>
                        <input type="text" id="exp_heading" name="exp_heading" class="etm-input etm-input--wide"
                               value="<?php echo esc_attr( $o( 'exp_heading', "Every Journey Is Different. Here's Where Yours Might Begin." ) ); ?>">
                    </div>
                    <p class="etm-help" style="margin-top:16px;">
                        Experience cards are managed in the
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=et-experiences' ) ); ?>" style="color:#1A4F31;font-weight:600;">Experiences</a>
                        tab. Add, edit, reorder, and delete experiences there.
                    </p>
                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            <?php /* ── Testimonials ── */ ?>
            <div class="etm-accordion etm-accordion--sortable" id="etm-panel-testimonials" data-slug="testimonials">
                <div class="etm-accordion__header">
                    <span class="etm-drag-handle" title="Drag to reorder">&#8942;</span>
                    <span class="etm-accordion__title" role="button">Testimonials</span>
                    <label class="etm-toggle" onclick="event.stopPropagation()">
                        <input type="checkbox" name="section_testimonials_visible" value="1"
                               <?php checked( $o( 'section_testimonials_visible', '1' ), '1' ); ?>>
                        <span class="etm-toggle__track"></span>
                        <span class="etm-toggle__thumb"></span>
                    </label>
                    <span class="etm-accordion__arrow">&#9662;</span>
                </div>
                <div class="etm-accordion__body" style="display:none;">

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
                        1 => [ 'quote' => "Ray went above and beyond and completely transformed our trip from good to simply amazing. He took time to know us and customize a really special tour that was perfectly suited to our family. I cannot imagine trying to explore Ireland without him.", 'name' => 'Beth G.', 'origin' => 'TripAdvisor' ],
                        2 => [ 'quote' => "Ray is more than a driver. He's a storyteller, a guide, and now, a dear friend. Whether we were at the Cliffs of Moher, winding through the Gap of Dunloe, or soaking in the charm of Cobh, Ray brought each place to life in a way only someone deeply connected to Ireland could.", 'name' => 'Margaret B.', 'origin' => 'TripAdvisor' ],
                        3 => [ 'quote' => "By the end of the trip, it felt like we were saying goodbye to a friend rather than a driver. Ray's insider tips led us away from the typical tourist crowds and gave us a more authentic experience. He is truly a gem, and we can't recommend him highly enough.", 'name' => 'Ellie M.', 'origin' => 'Boston' ],
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
                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            <?php /* ── Founder CTA ── */ ?>
            <div class="etm-accordion etm-accordion--sortable" id="etm-panel-founder" data-slug="founder-cta">
                <div class="etm-accordion__header">
                    <span class="etm-drag-handle" title="Drag to reorder">&#8942;</span>
                    <span class="etm-accordion__title" role="button">Founder CTA</span>
                    <label class="etm-toggle" onclick="event.stopPropagation()">
                        <input type="checkbox" name="section_founder-cta_visible" value="1"
                               <?php checked( $o( 'section_founder-cta_visible', '1' ), '1' ); ?>>
                        <span class="etm-toggle__track"></span>
                        <span class="etm-toggle__thumb"></span>
                    </label>
                    <span class="etm-accordion__arrow">&#9662;</span>
                </div>
                <div class="etm-accordion__body" style="display:none;">

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
                        <textarea id="founder_body" name="founder_body" class="etm-textarea" rows="4"><?php echo esc_textarea( $o( 'founder_body', "Every journey is tailored to you, designed with care, local insight, and a deep understanding of Ireland." ) ); ?></textarea>
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
                </div><!-- /accordion body -->
            </div><!-- /accordion -->

            </div><!-- /#etm-sortable-sections -->

            <!-- ── Sticky Save ──────────────────────────────────────── -->
            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary">Save Homepage Settings</button>
                <span class="etm-dirty-dot" id="etm-dirty-dot" style="display:none;" title="Unsaved changes"></span>
            </div>

        </form>
    </div><!-- .wrap -->

    <style>
    /* ── Accordion ─────────────────────────────────────────────────────────── */
    .etm-accordion {
        margin-bottom: 8px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }
    .etm-accordion__toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 14px 20px;
        background: #fafafa;
        border: none;
        border-bottom: 1px solid transparent;
        font-size: 14px;
        font-weight: 600;
        color: #1A4F31;
        cursor: pointer;
        transition: background .15s;
    }
    .etm-accordion__toggle:hover { background: #f0f5f0; }
    .etm-accordion.is-open > .etm-accordion__toggle,
    .etm-accordion.is-open > .etm-accordion__header {
        border-bottom-color: #e0e0e0;
        background: #f0f5f0;
    }
    .etm-accordion__arrow {
        font-size: 16px;
        transition: transform .2s;
        cursor: pointer;
    }
    .etm-accordion.is-open > .etm-accordion__toggle .etm-accordion__arrow,
    .etm-accordion.is-open > .etm-accordion__header .etm-accordion__arrow {
        transform: rotate(180deg);
    }

    /* ── Sortable accordion header ─────────────────────────────────────── */
    .etm-accordion__header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        background: #fafafa;
        border-bottom: 1px solid transparent;
        transition: background .15s;
        cursor: default;
    }
    .etm-accordion__header:hover { background: #f0f5f0; }
    .etm-accordion__title {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        color: #1A4F31;
        cursor: pointer;
    }
    .etm-accordion--sortable {
        cursor: grab;
        transition: border-color .15s, box-shadow .15s, opacity .15s;
    }
    .etm-accordion--sortable.is-dragging {
        opacity: 0;
    }
    .etm-drop-placeholder {
        border: 2px dashed #1A4F31;
        border-radius: 8px;
        background: #f0f7f3;
        margin-bottom: 8px;
        transition: height .15s;
    }
    .etm-sortable-hint {
        font-size: 12px;
        color: #888;
        margin: 16px 0 8px;
        font-style: italic;
    }
    .etm-accordion__body {
        padding: 20px;
    }

    /* ── Sticky save bar ──────────────────────────────────────────────────── */
    .etm-actions--sticky {
        position: sticky;
        bottom: 0;
        z-index: 50;
        background: #fff;
        border-top: 2px solid #e0e0e0;
        padding: 14px 20px;
        margin-top: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 -2px 8px rgba(0,0,0,0.06);
    }
    .etm-btn-save--dirty {
        background: #e65100 !important;
        border-color: #bf360c !important;
        animation: etm-pulse 1.5s infinite;
    }
    .etm-dirty-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #e65100;
        display: inline-block;
        animation: etm-pulse 1.5s infinite;
    }
    @keyframes etm-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    /* ── Drag handle ───────────────────────────────────────────────────────── */
    .etm-drag-handle {
        font-size: 20px;
        color: #aaa;
        cursor: grab;
        line-height: 1;
        padding: 8px 6px;
        flex-shrink: 0;
        touch-action: none;
        user-select: none;
    }
    .etm-drag-handle:hover {
        color: #1A4F31;
    }
    .etm-drag-handle:active {
        cursor: grabbing;
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

        // ── Accordion toggle ────────────────────────────────────────────────
        function toggleAccordion( acc ) {
            var body = acc.querySelector( '.etm-accordion__body' );
            var open = acc.classList.toggle( 'is-open' );
            body.style.display = open ? '' : 'none';
        }
        // Plain button toggles (Hero, Trust)
        document.querySelectorAll( '.etm-accordion__toggle' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                toggleAccordion( btn.closest( '.etm-accordion' ) );
            } );
        } );
        // Sortable accordion toggles (click title or arrow)
        document.querySelectorAll( '.etm-accordion__title, .etm-accordion__header > .etm-accordion__arrow' ).forEach( function ( el ) {
            el.addEventListener( 'click', function () {
                toggleAccordion( el.closest( '.etm-accordion' ) );
            } );
        } );

        // ── Dirty state (unsaved changes indicator) ─────────────────────────
        var form     = document.getElementById( 'etm-hp-form' );
        var allBtns  = document.querySelectorAll( '.etm-btn-save' );
        var feedback = document.getElementById( 'etm-save-feedback' );
        var dirtyDot = document.getElementById( 'etm-dirty-dot' );
        var isDirty  = false;

        function markDirty() {
            if ( isDirty ) return;
            isDirty = true;
            if ( dirtyDot ) dirtyDot.style.display = '';
            allBtns.forEach( function ( b ) {
                b.classList.add( 'etm-btn-save--dirty' );
            } );
        }
        function markClean() {
            isDirty = false;
            if ( dirtyDot ) dirtyDot.style.display = 'none';
            allBtns.forEach( function ( b ) {
                b.classList.remove( 'etm-btn-save--dirty' );
            } );
        }

        // Track changes on all inputs/selects/textareas
        if ( form ) {
            form.addEventListener( 'input', markDirty );
            form.addEventListener( 'change', markDirty );
        }

        // Warn before leaving with unsaved changes
        window.addEventListener( 'beforeunload', function ( e ) {
            if ( isDirty ) { e.preventDefault(); e.returnValue = ''; }
        } );

        // ── AJAX Save ───────────────────────────────────────────────────────
        function setBtns( text, disabled ) {
            allBtns.forEach( function ( b ) {
                b.textContent = text;
                b.disabled    = disabled;
            } );
        }

        allBtns.forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                if ( ! form ) { alert( 'Form not found — please refresh the page.' ); return; }

                updateOrderInput();

                var data = new FormData( form );
                data.append( 'action', 'etm_hp_save' );

                setBtns( 'Saving\u2026', true );
                if ( feedback ) { feedback.textContent = ''; feedback.className = 'etm-notice'; }

                fetch( ajaxurl, {
                    method      : 'POST',
                    body        : data,
                    credentials : 'same-origin',
                } )
                .then( function ( r ) { return r.json(); } )
                .then( function ( res ) {
                    if ( res.success ) {
                        markClean();
                        setBtns( 'Saved \u2714', false );
                        if ( feedback ) {
                            feedback.textContent = 'Settings saved successfully.';
                            feedback.className   = 'etm-notice etm-notice--success';
                        }
                        setTimeout( function () { setBtns( 'Save Homepage Settings', false ); }, 2500 );
                    } else {
                        setBtns( 'Save Homepage Settings', false );
                        if ( feedback ) {
                            feedback.textContent = 'Save failed \u2014 ' + ( res.data || 'unknown error' );
                            feedback.className   = 'etm-notice etm-notice--error';
                        }
                    }
                } )
                .catch( function ( err ) {
                    setBtns( 'Save Homepage Settings', false );
                    if ( feedback ) {
                        feedback.textContent = 'Network error \u2014 ' + err;
                        feedback.className   = 'etm-notice etm-notice--error';
                    }
                } );
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

        // ── Drag-and-drop section reordering (handle-based) ────────────────
        var sortable     = document.getElementById( 'etm-sortable-sections' );
        var orderInput   = document.getElementById( 'etm-section-order-input' );
        var dragSrc      = null;
        var draggedEl    = null;
        var placeholder  = document.createElement( 'div' );
        placeholder.className = 'etm-drop-placeholder';

        function getRows() {
            return Array.from( sortable.querySelectorAll( '.etm-accordion--sortable' ) );
        }

        function updateOrderInput() {
            var slugs = getRows().map( function ( row ) { return row.dataset.slug; } );
            orderInput.value = JSON.stringify( slugs );
            markDirty();
        }

        // Use pointer events on the drag handle for reliable cross-browser drag
        sortable.addEventListener( 'pointerdown', function ( e ) {
            var handle = e.target.closest( '.etm-drag-handle' );
            if ( ! handle ) return;
            {
                var acc = handle.closest( '.etm-accordion--sortable' );
                if ( ! acc ) return;

                e.preventDefault();
                draggedEl = acc;
                var rect  = acc.getBoundingClientRect();
                var shiftY = e.clientY - rect.top;

                // Create a visual clone for dragging
                var ghost = acc.cloneNode( true );
                ghost.className = 'etm-drag-ghost';
                ghost.style.width = rect.width + 'px';
                ghost.style.position = 'fixed';
                ghost.style.zIndex = '10000';
                ghost.style.opacity = '0.85';
                ghost.style.pointerEvents = 'none';
                ghost.style.left = rect.left + 'px';
                ghost.style.top = e.clientY - shiftY + 'px';
                ghost.style.boxShadow = '0 8px 24px rgba(0,0,0,0.18)';
                ghost.style.borderRadius = '8px';
                ghost.style.transform = 'scale(1.02)';
                document.body.appendChild( ghost );

                // Collapse the source visually
                acc.classList.add( 'is-dragging' );

                // Insert placeholder
                placeholder.style.height = rect.height + 'px';
                acc.parentNode.insertBefore( placeholder, acc );
                acc.style.display = 'none';

                function onMove( ev ) {
                    ghost.style.top = ev.clientY - shiftY + 'px';

                    // Find which row we're over
                    var rows = getRows().filter( function ( r ) { return r !== draggedEl; } );
                    for ( var i = 0; i < rows.length; i++ ) {
                        var r    = rows[ i ];
                        var rr   = r.getBoundingClientRect();
                        var midY = rr.top + rr.height / 2;
                        if ( ev.clientY < midY ) {
                            sortable.insertBefore( placeholder, r );
                            return;
                        }
                    }
                    // Past all rows — put at end
                    sortable.appendChild( placeholder );
                }

                function onUp() {
                    document.removeEventListener( 'pointermove', onMove );
                    document.removeEventListener( 'pointerup', onUp );

                    // Place the real element where the placeholder is
                    sortable.insertBefore( draggedEl, placeholder );
                    draggedEl.style.display = '';
                    draggedEl.classList.remove( 'is-dragging' );
                    if ( placeholder.parentNode ) placeholder.parentNode.removeChild( placeholder );
                    if ( ghost.parentNode ) ghost.parentNode.removeChild( ghost );
                    draggedEl = null;

                    updateOrderInput();
                }

                document.addEventListener( 'pointermove', onMove );
                document.addEventListener( 'pointerup', onUp );
            }
        } );

    } )();
    </script>
    <?php
}
