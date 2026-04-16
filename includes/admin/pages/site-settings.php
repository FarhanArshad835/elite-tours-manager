<?php
defined( 'ABSPATH' ) || exit;

// ── Save (AJAX) ──────────────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_site_settings', function () {
    check_ajax_referer( 'etm_site_settings', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $fields = [ 'logo_id', 'phone_us', 'nav_cta_text', 'contact_email', 'address',
                'social_instagram', 'social_facebook', 'social_tripadvisor',
                'founder_image_id' ];
    $data   = [];
    foreach ( $fields as $f ) {
        $data[ $f ] = isset( $_POST[ $f ] ) ? sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) : '';
    }
    update_option( 'et_site_settings', $data );
    wp_send_json_success( 'Saved' );
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

        <form method="post" id="etm-settings-form">
            <?php wp_nonce_field( 'etm_site_settings' ); ?>

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
                    <label class="etm-label" for="phone_us">Phone Number</label>
                    <input type="text" id="phone_us" name="phone_us" class="etm-input"
                           value="<?php echo esc_attr( $opts['phone_us'] ?? '' ); ?>"
                           placeholder="+353 86 050 0500">
                    <p class="etm-help">Shown in the navigation bar header and footer. Include country code.</p>
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
                           placeholder="elitetoursireland@gmail.com">
                    <p class="etm-help">Shown in the footer contact column.</p>
                </div>
                <div class="etm-field">
                    <label class="etm-label" for="address">Business Address</label>
                    <input type="text" id="address" name="address" class="etm-input etm-input--wide"
                           value="<?php echo esc_attr( $opts['address'] ?? '' ); ?>"
                           placeholder="26 Mallow St, Limerick, V94 V049, Ireland">
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
                <button type="button" class="etm-btn-save button-primary">Save Settings</button>
            </div>
            <div id="etm-save-feedback" class="etm-notice" style="margin-top:12px;"></div>

        </form>
    </div>

    <script>
    ( function () {
        // ── AJAX Save ───────────────────────────────────────────────────────
        var form     = document.getElementById( 'etm-settings-form' );
        var saveBtn  = document.querySelector( '.etm-btn-save' );
        var feedback = document.getElementById( 'etm-save-feedback' );

        if ( saveBtn ) {
            saveBtn.addEventListener( 'click', function () {
                if ( ! form ) { alert( 'Form not found.' ); return; }
                var data = new FormData( form );
                data.append( 'action', 'etm_save_site_settings' );

                saveBtn.textContent = 'Saving\u2026';
                saveBtn.disabled    = true;
                if ( feedback ) { feedback.textContent = ''; feedback.className = 'etm-notice'; }

                fetch( ajaxurl, { method: 'POST', body: data, credentials: 'same-origin' } )
                    .then( function ( r ) { return r.json(); } )
                    .then( function ( res ) {
                        if ( res.success ) {
                            saveBtn.textContent = 'Saved \u2714';
                            if ( feedback ) { feedback.textContent = 'Settings saved.'; feedback.className = 'etm-notice etm-notice--success'; }
                            setTimeout( function () { saveBtn.textContent = 'Save Settings'; saveBtn.disabled = false; }, 2500 );
                        } else {
                            saveBtn.textContent = 'Save Settings';
                            saveBtn.disabled    = false;
                            if ( feedback ) { feedback.textContent = 'Save failed \u2014 ' + ( res.data || 'unknown error' ); feedback.className = 'etm-notice etm-notice--error'; }
                        }
                    } )
                    .catch( function ( err ) {
                        saveBtn.textContent = 'Save Settings';
                        saveBtn.disabled    = false;
                        if ( feedback ) { feedback.textContent = 'Network error \u2014 ' + err; feedback.className = 'etm-notice etm-notice--error'; }
                    } );
            } );
        }

        // ── Media upload ────────────────────────────────────────────────────
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
