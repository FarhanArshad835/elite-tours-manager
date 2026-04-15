<?php
defined( 'ABSPATH' ) || exit;

// ── Save ─────────────────────────────────────────────────────────────────────
add_action( 'admin_post_etm_save_homepage', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorised' );
    check_admin_referer( 'etm_homepage' );

    $fields = [
        'hero_label', 'hero_headline', 'hero_subheading',
        'hero_cta_primary', 'hero_cta_secondary',
        'hero_video_url', 'hero_image_id',
        // Trust strip
        'trust_ta_sub',
        'trust_failte_sub', 'trust_failte_logo_id',
        'trust_asta_sub',   'trust_asta_logo_id',
        'trust_iagto_sub',  'trust_iagto_logo_id',
        'trust_since_label', 'trust_since_sub',
    ];
    $data = [];
    foreach ( $fields as $f ) {
        $raw = isset( $_POST[ $f ] ) ? wp_unslash( $_POST[ $f ] ) : '';
        // Allow <br> only in headline
        $data[ $f ] = ( $f === 'hero_headline' )
            ? wp_kses( $raw, [ 'br' => [] ] )
            : sanitize_text_field( $raw );
    }
    update_option( 'et_homepage_settings', $data );

    wp_redirect( add_query_arg( [ 'page' => 'et-homepage', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
    exit;
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_homepage_page(): void {
    $opts     = get_option( 'et_homepage_settings', [] );
    $image_id = $opts['hero_image_id'] ?? '';
    $image_url = $image_id ? wp_get_attachment_image_url( (int) $image_id, 'medium' ) : '';
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">🏠 Homepage</h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
            <div class="etm-notice etm-notice--success">Homepage settings saved successfully.</div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'etm_homepage' ); ?>
            <input type="hidden" name="action" value="etm_save_homepage">

            <!-- ── HERO SECTION ──────────────────────────────────── -->
            <div class="etm-section">
                <h2 class="etm-section__title">Hero Section</h2>
                <p class="etm-section__desc">The full-screen section at the top of the homepage — the first thing visitors see.</p>

                <!-- Background Media -->
                <div class="etm-field">
                    <label class="etm-label">Hero Background</label>
                    <div class="etm-tabs" id="etm-hero-media-tabs">
                        <button type="button" class="etm-tab etm-tab--active" data-tab="image">Image</button>
                        <button type="button" class="etm-tab" data-tab="video">Video (URL)</button>
                    </div>

                    <!-- Image upload -->
                    <div class="etm-tab-panel" id="etm-tab-image">
                        <div class="etm-media-upload">
                            <?php if ( $image_url ) : ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" class="etm-media-preview etm-media-preview--wide" id="etm-hero-img-preview" alt="">
                            <?php else : ?>
                                <img src="" class="etm-media-preview etm-media-preview--wide" id="etm-hero-img-preview" alt="" style="display:none;">
                            <?php endif; ?>
                            <input type="hidden" name="hero_image_id" id="etm-hero-image-id" value="<?php echo esc_attr( $image_id ); ?>">
                            <div class="etm-media-btns">
                                <button type="button" class="etm-btn-upload button"
                                        data-target="etm-hero-image-id"
                                        data-preview="etm-hero-img-preview"
                                        data-title="Select Hero Image">
                                    <?php echo $image_url ? 'Change Image' : 'Upload Image'; ?>
                                </button>
                                <?php if ( $image_url ) : ?>
                                    <button type="button" class="etm-btn-remove button-link-delete"
                                            data-target="etm-hero-image-id"
                                            data-preview="etm-hero-img-preview">Remove</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="etm-help">Recommended: landscape, minimum 1920×1080px. JPG or WebP.</p>
                    </div>

                    <!-- Video URL -->
                    <div class="etm-tab-panel" id="etm-tab-video" style="display:none;">
                        <input type="url" name="hero_video_url" id="hero_video_url" class="etm-input etm-input--wide"
                               value="<?php echo esc_attr( $opts['hero_video_url'] ?? '' ); ?>"
                               placeholder="https://example.com/hero-video.mp4">
                        <p class="etm-help">Direct URL to an MP4 video file. The image above will be used as a poster/fallback. Keep the video under 15MB for fast loading.</p>
                    </div>
                </div>

                <!-- Label -->
                <div class="etm-field">
                    <label class="etm-label" for="hero_label">Label Text</label>
                    <input type="text" id="hero_label" name="hero_label" class="etm-input"
                           value="<?php echo esc_attr( $opts['hero_label'] ?? 'ELITE TOURS IRELAND — SINCE 1973' ); ?>"
                           placeholder="ELITE TOURS IRELAND — SINCE 1973">
                    <p class="etm-help">The small gold text above the headline. Uppercase, short.</p>
                </div>

                <!-- Headline -->
                <div class="etm-field">
                    <label class="etm-label" for="hero_headline">Main Headline</label>
                    <input type="text" id="hero_headline" name="hero_headline" class="etm-input etm-input--wide"
                           value="<?php echo esc_attr( $opts['hero_headline'] ?? 'Ireland,<br>Experienced Properly.' ); ?>"
                           placeholder="Ireland,&lt;br&gt;Experienced Properly.">
                    <p class="etm-help">The large white heading. You can use <code>&lt;br&gt;</code> to force a line break.</p>
                </div>

                <!-- Subheading -->
                <div class="etm-field">
                    <label class="etm-label" for="hero_subheading">Subheading</label>
                    <textarea id="hero_subheading" name="hero_subheading" class="etm-textarea" rows="2"
                              placeholder="Bespoke private journeys — tailored to you..."><?php echo esc_textarea( $opts['hero_subheading'] ?? 'Bespoke private journeys — tailored to you, delivered with genuine Irish care.' ); ?></textarea>
                    <p class="etm-help">The smaller descriptive text below the headline. Keep it to 1–2 lines.</p>
                </div>

                <!-- CTAs -->
                <div class="etm-field-row">
                    <div class="etm-field">
                        <label class="etm-label" for="hero_cta_primary">Primary Button Text</label>
                        <input type="text" id="hero_cta_primary" name="hero_cta_primary" class="etm-input"
                               value="<?php echo esc_attr( $opts['hero_cta_primary'] ?? 'Visit the Emerald Isle' ); ?>"
                               placeholder="Plan Your Journey">
                        <p class="etm-help">The main green button. Links to the Contact page.</p>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="hero_cta_secondary">Secondary Button Text</label>
                        <input type="text" id="hero_cta_secondary" name="hero_cta_secondary" class="etm-input"
                               value="<?php echo esc_attr( $opts['hero_cta_secondary'] ?? 'Explore Our Tours' ); ?>"
                               placeholder="Explore Our Tours">
                        <p class="etm-help">The outline button. Scrolls down to the tours section.</p>
                    </div>
                </div>

            </div>
            <!-- ── END HERO ──────────────────────────────────────── -->

            <!-- ── TRUST STRIP ───────────────────────────────────── -->
            <div class="etm-section">
                <h2 class="etm-section__title">Trust Strip</h2>
                <p class="etm-section__desc">The badge bar at the bottom of the hero — shows partner logos and credibility signals. Logos fall back to the bundled images if no upload is provided.</p>

                <!-- TripAdvisor -->
                <div class="etm-field-group">
                    <h3 class="etm-field-group__title">TripAdvisor</h3>
                    <div class="etm-field">
                        <label class="etm-label" for="trust_ta_sub">Sub-label</label>
                        <input type="text" id="trust_ta_sub" name="trust_ta_sub" class="etm-input"
                               value="<?php echo esc_attr( $opts['trust_ta_sub'] ?? '5-Star Rated' ); ?>"
                               placeholder="5-Star Rated">
                    </div>
                </div>

                <!-- Fáilte Ireland -->
                <div class="etm-field-group">
                    <h3 class="etm-field-group__title">Fáilte Ireland</h3>
                    <?php
                    $failte_logo_id  = $opts['trust_failte_logo_id'] ?? '';
                    $failte_logo_url = $failte_logo_id ? wp_get_attachment_image_url( (int) $failte_logo_id, 'thumbnail' ) : '';
                    ?>
                    <div class="etm-field">
                        <label class="etm-label">Logo Image</label>
                        <div class="etm-media-upload">
                            <img src="<?php echo esc_url( $failte_logo_url ); ?>" id="etm-failte-preview"
                                 class="etm-media-preview" alt="" <?php echo $failte_logo_url ? '' : 'style="display:none;"'; ?>>
                            <input type="hidden" name="trust_failte_logo_id" id="etm-failte-logo-id"
                                   value="<?php echo esc_attr( $failte_logo_id ); ?>">
                            <div class="etm-media-btns">
                                <button type="button" class="etm-btn-upload button"
                                        data-target="etm-failte-logo-id" data-preview="etm-failte-preview"
                                        data-title="Select Fáilte Ireland Logo">
                                    <?php echo $failte_logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                                </button>
                                <?php if ( $failte_logo_url ) : ?>
                                    <button type="button" class="etm-btn-remove button-link-delete"
                                            data-target="etm-failte-logo-id" data-preview="etm-failte-preview">Remove</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="etm-help">If blank, uses the bundled Fáilte Ireland logo. Recommended: PNG with transparency, ~220×80px.</p>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="trust_failte_sub">Sub-label</label>
                        <input type="text" id="trust_failte_sub" name="trust_failte_sub" class="etm-input"
                               value="<?php echo esc_attr( $opts['trust_failte_sub'] ?? 'Approved Partner' ); ?>"
                               placeholder="Approved Partner">
                    </div>
                </div>

                <!-- ASTA -->
                <div class="etm-field-group">
                    <h3 class="etm-field-group__title">ASTA</h3>
                    <?php
                    $asta_logo_id  = $opts['trust_asta_logo_id'] ?? '';
                    $asta_logo_url = $asta_logo_id ? wp_get_attachment_image_url( (int) $asta_logo_id, 'thumbnail' ) : '';
                    ?>
                    <div class="etm-field">
                        <label class="etm-label">Logo Image</label>
                        <div class="etm-media-upload">
                            <img src="<?php echo esc_url( $asta_logo_url ); ?>" id="etm-asta-preview"
                                 class="etm-media-preview" alt="" <?php echo $asta_logo_url ? '' : 'style="display:none;"'; ?>>
                            <input type="hidden" name="trust_asta_logo_id" id="etm-asta-logo-id"
                                   value="<?php echo esc_attr( $asta_logo_id ); ?>">
                            <div class="etm-media-btns">
                                <button type="button" class="etm-btn-upload button"
                                        data-target="etm-asta-logo-id" data-preview="etm-asta-preview"
                                        data-title="Select ASTA Logo">
                                    <?php echo $asta_logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                                </button>
                                <?php if ( $asta_logo_url ) : ?>
                                    <button type="button" class="etm-btn-remove button-link-delete"
                                            data-target="etm-asta-logo-id" data-preview="etm-asta-preview">Remove</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="etm-help">If blank, uses the bundled ASTA logo.</p>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="trust_asta_sub">Sub-label</label>
                        <input type="text" id="trust_asta_sub" name="trust_asta_sub" class="etm-input"
                               value="<?php echo esc_attr( $opts['trust_asta_sub'] ?? 'Member' ); ?>"
                               placeholder="Member">
                    </div>
                </div>

                <!-- IAGTO -->
                <div class="etm-field-group">
                    <h3 class="etm-field-group__title">IAGTO</h3>
                    <?php
                    $iagto_logo_id  = $opts['trust_iagto_logo_id'] ?? '';
                    $iagto_logo_url = $iagto_logo_id ? wp_get_attachment_image_url( (int) $iagto_logo_id, 'thumbnail' ) : '';
                    ?>
                    <div class="etm-field">
                        <label class="etm-label">Logo Image</label>
                        <div class="etm-media-upload">
                            <img src="<?php echo esc_url( $iagto_logo_url ); ?>" id="etm-iagto-preview"
                                 class="etm-media-preview" alt="" <?php echo $iagto_logo_url ? '' : 'style="display:none;"'; ?>>
                            <input type="hidden" name="trust_iagto_logo_id" id="etm-iagto-logo-id"
                                   value="<?php echo esc_attr( $iagto_logo_id ); ?>">
                            <div class="etm-media-btns">
                                <button type="button" class="etm-btn-upload button"
                                        data-target="etm-iagto-logo-id" data-preview="etm-iagto-preview"
                                        data-title="Select IAGTO Logo">
                                    <?php echo $iagto_logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                                </button>
                                <?php if ( $iagto_logo_url ) : ?>
                                    <button type="button" class="etm-btn-remove button-link-delete"
                                            data-target="etm-iagto-logo-id" data-preview="etm-iagto-preview">Remove</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="etm-help">If blank, uses the bundled IAGTO logo. IAGTO logo renders in colour (not white).</p>
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="trust_iagto_sub">Sub-label</label>
                        <input type="text" id="trust_iagto_sub" name="trust_iagto_sub" class="etm-input"
                               value="<?php echo esc_attr( $opts['trust_iagto_sub'] ?? 'Golf Tourism' ); ?>"
                               placeholder="Golf Tourism">
                    </div>
                </div>

                <!-- Since 1973 -->
                <div class="etm-field-group">
                    <h3 class="etm-field-group__title">Since Badge</h3>
                    <div class="etm-field-row">
                        <div class="etm-field">
                            <label class="etm-label" for="trust_since_label">Badge Text</label>
                            <input type="text" id="trust_since_label" name="trust_since_label" class="etm-input"
                                   value="<?php echo esc_attr( $opts['trust_since_label'] ?? 'Since 1973' ); ?>"
                                   placeholder="Since 1973">
                            <p class="etm-help">Shown in gold. e.g. "Since 1973"</p>
                        </div>
                        <div class="etm-field">
                            <label class="etm-label" for="trust_since_sub">Sub-label</label>
                            <input type="text" id="trust_since_sub" name="trust_since_sub" class="etm-input"
                                   value="<?php echo esc_attr( $opts['trust_since_sub'] ?? '50+ years experience' ); ?>"
                                   placeholder="50+ years experience">
                        </div>
                    </div>
                </div>

            </div>
            <!-- ── END TRUST STRIP ───────────────────────────────── -->

            <div class="etm-actions">
                <button type="submit" class="etm-btn-save button-primary">Save Homepage Settings</button>
            </div>

        </form>
    </div>

    <script>
    ( function () {
        // Media upload
        document.querySelectorAll( '.etm-btn-upload' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var frame = wp.media( {
                    title:    btn.dataset.title || 'Select File',
                    button:   { text: 'Use this file' },
                    multiple: false,
                } );
                frame.on( 'select', function () {
                    var att = frame.state().get( 'selection' ).first().toJSON();
                    document.getElementById( btn.dataset.target ).value = att.id;
                    var preview = document.getElementById( btn.dataset.preview );
                    preview.src          = att.url;
                    preview.style.display = 'block';
                    btn.textContent = 'Change Image';
                } );
                frame.open();
            } );
        } );

        document.querySelectorAll( '.etm-btn-remove' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                document.getElementById( btn.dataset.target ).value = '';
                var preview = document.getElementById( btn.dataset.preview );
                preview.src          = '';
                preview.style.display = 'none';
            } );
        } );

        // Tabs
        document.querySelectorAll( '.etm-tab' ).forEach( function ( tab ) {
            tab.addEventListener( 'click', function () {
                var parent = tab.closest( '.etm-field' );
                parent.querySelectorAll( '.etm-tab' ).forEach( function ( t ) {
                    t.classList.remove( 'etm-tab--active' );
                } );
                tab.classList.add( 'etm-tab--active' );
                parent.querySelectorAll( '.etm-tab-panel' ).forEach( function ( p ) {
                    p.style.display = 'none';
                } );
                var target = parent.querySelector( '#etm-tab-' + tab.dataset.tab );
                if ( target ) target.style.display = 'block';
            } );
        } );
    } )();
    </script>
    <?php
}
