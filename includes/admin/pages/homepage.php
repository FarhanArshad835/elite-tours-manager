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
