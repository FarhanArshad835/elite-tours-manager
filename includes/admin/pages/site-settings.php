<?php
defined( 'ABSPATH' ) || exit;

// ── Save ─────────────────────────────────────────────────────────────────────
add_action( 'admin_post_etm_save_site_settings', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorised' );
    check_admin_referer( 'etm_site_settings' );

    $fields = [ 'logo_id', 'phone_us', 'nav_cta_text', 'contact_email',
                'social_instagram', 'social_facebook', 'social_tripadvisor',
                'founder_image_id' ];
    $data   = [];
    foreach ( $fields as $f ) {
        $data[ $f ] = isset( $_POST[ $f ] ) ? sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) : '';
    }
    update_option( 'et_site_settings', $data );

    wp_redirect( add_query_arg( [ 'page' => 'et-site-settings', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
    exit;
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_site_settings_page(): void {
    $opts    = get_option( 'et_site_settings', [] );
    $logo_id = $opts['logo_id'] ?? '';
    $logo_url = $logo_id ? wp_get_attachment_image_url( (int) $logo_id, 'medium' ) : '';
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">⚙️ Site Settings</h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
            <div class="etm-notice etm-notice--success">Settings saved successfully.</div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'etm_site_settings' ); ?>
            <input type="hidden" name="action" value="etm_save_site_settings">

            <div class="etm-section">
                <h2 class="etm-section__title">Logo</h2>

                <div class="etm-field">
                    <label class="etm-label">Site Logo</label>
                    <div class="etm-media-upload" id="etm-logo-wrap">
                        <?php if ( $logo_url ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>" class="etm-media-preview" id="etm-logo-preview" alt="">
                        <?php else : ?>
                            <img src="" class="etm-media-preview" id="etm-logo-preview" alt="" style="display:none;">
                        <?php endif; ?>
                        <input type="hidden" name="logo_id" id="etm-logo-id" value="<?php echo esc_attr( $logo_id ); ?>">
                        <div class="etm-media-btns">
                            <button type="button" class="etm-btn-upload button" data-target="etm-logo-id" data-preview="etm-logo-preview" data-title="Select Logo">
                                <?php echo $logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                            </button>
                            <?php if ( $logo_url ) : ?>
                                <button type="button" class="etm-btn-remove button-link-delete" data-target="etm-logo-id" data-preview="etm-logo-preview">Remove</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="etm-help">Recommended: SVG or PNG, white version on transparent background. Min width 280px.</p>
                </div>
            </div>

            <div class="etm-section">
                <h2 class="etm-section__title">Navigation</h2>

                <div class="etm-field">
                    <label class="etm-label" for="phone_us">US Phone Number</label>
                    <input type="text" id="phone_us" name="phone_us" class="etm-input"
                           value="<?php echo esc_attr( $opts['phone_us'] ?? '' ); ?>"
                           placeholder="+1 888 000 0000">
                    <p class="etm-help">Shown in the navigation bar header. Include country code.</p>
                </div>

                <div class="etm-field">
                    <label class="etm-label" for="nav_cta_text">Nav CTA Button Text</label>
                    <input type="text" id="nav_cta_text" name="nav_cta_text" class="etm-input"
                           value="<?php echo esc_attr( $opts['nav_cta_text'] ?? 'Plan Your Journey' ); ?>"
                           placeholder="Plan Your Journey">
                    <p class="etm-help">The green button in the top-right of the navigation bar.</p>
                </div>
            </div>

            <div class="etm-section">
                <h2 class="etm-section__title">Contact</h2>
                <div class="etm-field">
                    <label class="etm-label" for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" class="etm-input"
                           value="<?php echo esc_attr( $opts['contact_email'] ?? '' ); ?>"
                           placeholder="info@elitetoursireland.com">
                    <p class="etm-help">Shown in the footer contact column.</p>
                </div>
            </div>

            <div class="etm-section">
                <h2 class="etm-section__title">Social Media</h2>
                <div class="etm-field-row">
                    <div class="etm-field">
                        <label class="etm-label" for="social_instagram">Instagram URL</label>
                        <input type="url" id="social_instagram" name="social_instagram" class="etm-input"
                               value="<?php echo esc_attr( $opts['social_instagram'] ?? '' ); ?>"
                               placeholder="https://instagram.com/elitetoursireland">
                    </div>
                    <div class="etm-field">
                        <label class="etm-label" for="social_facebook">Facebook URL</label>
                        <input type="url" id="social_facebook" name="social_facebook" class="etm-input"
                               value="<?php echo esc_attr( $opts['social_facebook'] ?? '' ); ?>"
                               placeholder="https://facebook.com/elitetoursireland">
                    </div>
                </div>
                <div class="etm-field">
                    <label class="etm-label" for="social_tripadvisor">TripAdvisor URL</label>
                    <input type="url" id="social_tripadvisor" name="social_tripadvisor" class="etm-input"
                           value="<?php echo esc_attr( $opts['social_tripadvisor'] ?? '' ); ?>"
                           placeholder="https://tripadvisor.com/...">
                </div>
            </div>

            <div class="etm-section">
                <h2 class="etm-section__title">Founder Photo</h2>
                <?php
                $founder_id  = $opts['founder_image_id'] ?? '';
                $founder_url = $founder_id ? wp_get_attachment_image_url( (int) $founder_id, 'medium' ) : '';
                ?>
                <div class="etm-field">
                    <label class="etm-label">Raphael Mulally — Photo</label>
                    <div class="etm-media-upload">
                        <img src="<?php echo esc_url( $founder_url ); ?>" id="etm-founder-preview"
                             class="etm-media-preview etm-media-preview--wide" alt=""
                             <?php echo $founder_url ? '' : 'style="display:none;"'; ?>>
                        <input type="hidden" name="founder_image_id" id="etm-founder-id"
                               value="<?php echo esc_attr( $founder_id ); ?>">
                        <div class="etm-media-btns">
                            <button type="button" class="etm-btn-upload button"
                                    data-target="etm-founder-id" data-preview="etm-founder-preview"
                                    data-title="Select Founder Photo">
                                <?php echo $founder_url ? 'Change Photo' : 'Upload Photo'; ?>
                            </button>
                            <?php if ( $founder_url ) : ?>
                                <button type="button" class="etm-btn-remove button-link-delete"
                                        data-target="etm-founder-id" data-preview="etm-founder-preview">Remove</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="etm-help">Photo of Ray Mulally shown in the homepage "Plan Your Journey" section. Portrait orientation preferred, outdoors in Ireland.</p>
                </div>
            </div>

            <div class="etm-actions">
                <button type="submit" class="etm-btn-save button-primary">Save Settings</button>
            </div>

        </form>
    </div>

    <script>
    ( function () {
        document.querySelectorAll( '.etm-btn-upload' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var targetId  = btn.dataset.target;
                var previewId = btn.dataset.preview;
                var title     = btn.dataset.title || 'Select File';
                var frame = wp.media( { title: title, button: { text: 'Use this file' }, multiple: false } );
                frame.on( 'select', function () {
                    var attachment = frame.state().get( 'selection' ).first().toJSON();
                    document.getElementById( targetId ).value = attachment.id;
                    var preview = document.getElementById( previewId );
                    preview.src   = attachment.url;
                    preview.style.display = 'block';
                } );
                frame.open();
            } );
        } );

        document.querySelectorAll( '.etm-btn-remove' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                document.getElementById( btn.dataset.target ).value  = '';
                var preview = document.getElementById( btn.dataset.preview );
                preview.src   = '';
                preview.style.display = 'none';
            } );
        } );
    } )();
    </script>
    <?php
}
