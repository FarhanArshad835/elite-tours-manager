<?php
/**
 * Page Content — bundles editorial arrays for individual page templates that
 * aren't covered by the dedicated admin pages (Hotels, Golf Courses, Itineraries,
 * Experiences). Each section in this admin page maps to a single wp_option:
 *
 *   et_bespoke_journey_types          6 cards on /bespoke-tours/  (label/title/desc/image/url)
 *   et_bespoke_durations              3 cards on /bespoke-tours/  (num/title/desc)
 *   et_bespoke_includes               4 cards on /bespoke-tours/  (num/title/desc)
 *   et_golf_pillars                   5 cards on /golf-tours/     (num/title/desc)
 *   et_accommodation_category_intros  3 cards on /accommodation/  (label/title/desc/image, fixed keys)
 *   et_contact_interests              List of strings on /contact/ (interest checkboxes)
 *   et_about_dna                      5 DNA pillars on /about-us/ (title/desc, em allowed)
 *   et_about_signature_moments        6 Signature Moments on /about-us/ (title/desc, em allowed)
 *   et_about_compare                  6 differentiator rows on /about-us/ (left/right)
 *   et_page_strings                   Single assoc array of named editorial strings
 *
 * One AJAX endpoint saves everything in one shot. Reads use safe defaults so
 * pages still render even if the option is empty.
 */

defined( 'ABSPATH' ) || exit;

// ── Save Handler (AJAX) ──────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_page_content', function () {
    check_ajax_referer( 'etm_page_content', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '{}';
    $data    = json_decode( $payload, true );
    if ( ! is_array( $data ) ) wp_send_json_error( 'Invalid payload' );

    // Bespoke Journey Types — variable length, fields: label, title, desc, image_id, url
    $clean = [];
    foreach ( (array) ( $data['bespoke_journey_types'] ?? [] ) as $row ) {
        $clean[] = [
            'label'    => sanitize_text_field( $row['label']    ?? '' ),
            'title'    => sanitize_text_field( $row['title']    ?? '' ),
            'desc'     => sanitize_textarea_field( $row['desc'] ?? '' ),
            'image_id' => absint( $row['image_id'] ?? 0 ),
            'url'      => esc_url_raw( $row['url']  ?? '' ),
        ];
    }
    update_option( 'et_bespoke_journey_types', $clean );

    // Bespoke Durations — variable length, fields: num, title, desc
    $clean = [];
    foreach ( (array) ( $data['bespoke_durations'] ?? [] ) as $row ) {
        $clean[] = [
            'num'   => sanitize_text_field( $row['num']   ?? '' ),
            'title' => sanitize_text_field( $row['title'] ?? '' ),
            'desc'  => sanitize_textarea_field( $row['desc'] ?? '' ),
        ];
    }
    update_option( 'et_bespoke_durations', $clean );

    // Bespoke Includes — variable length, fields: num, title, desc
    $clean = [];
    foreach ( (array) ( $data['bespoke_includes'] ?? [] ) as $row ) {
        $clean[] = [
            'num'   => sanitize_text_field( $row['num']   ?? '' ),
            'title' => sanitize_text_field( $row['title'] ?? '' ),
            'desc'  => sanitize_textarea_field( $row['desc'] ?? '' ),
        ];
    }
    update_option( 'et_bespoke_includes', $clean );

    // Golf Pillars — variable length, fields: num, title, desc
    $clean = [];
    foreach ( (array) ( $data['golf_pillars'] ?? [] ) as $row ) {
        $clean[] = [
            'num'   => sanitize_text_field( $row['num']   ?? '' ),
            'title' => sanitize_text_field( $row['title'] ?? '' ),
            'desc'  => sanitize_textarea_field( $row['desc'] ?? '' ),
        ];
    }
    update_option( 'et_golf_pillars', $clean );

    // Accommodation Category Intros — fixed keys (castle/boutique/coastal), fields: label, title, desc, image_id
    $clean = [];
    foreach ( (array) ( $data['accommodation_intros'] ?? [] ) as $row ) {
        $key = sanitize_key( $row['key'] ?? '' );
        if ( ! in_array( $key, [ 'castle', 'boutique', 'coastal' ], true ) ) continue;
        $clean[] = [
            'key'      => $key,
            'label'    => sanitize_text_field( $row['label']    ?? '' ),
            'title'    => sanitize_text_field( $row['title']    ?? '' ),
            'desc'     => sanitize_textarea_field( $row['desc'] ?? '' ),
            'image_id' => absint( $row['image_id'] ?? 0 ),
        ];
    }
    update_option( 'et_accommodation_category_intros', $clean );

    // Contact Interests — list of strings
    $clean = [];
    foreach ( (array) ( $data['contact_interests'] ?? [] ) as $row ) {
        $val = sanitize_text_field( is_array( $row ) ? ( $row['value'] ?? '' ) : $row );
        if ( $val !== '' ) $clean[] = $val;
    }
    update_option( 'et_contact_interests', $clean );

    // About — DNA pillars (title may contain <em>)
    $clean = [];
    $allowed_em = [ 'em' => [], 'strong' => [] ];
    foreach ( (array) ( $data['about_dna'] ?? [] ) as $row ) {
        $clean[] = [
            'title' => wp_kses( $row['title'] ?? '', $allowed_em ),
            'desc'  => sanitize_textarea_field( $row['desc'] ?? '' ),
        ];
    }
    update_option( 'et_about_dna', $clean );

    // About — Signature Moments (desc may contain <em>)
    $clean = [];
    foreach ( (array) ( $data['about_moments'] ?? [] ) as $row ) {
        $clean[] = [
            'title' => sanitize_text_field( $row['title'] ?? '' ),
            'desc'  => wp_kses( $row['desc'] ?? '', $allowed_em ),
        ];
    }
    update_option( 'et_about_signature_moments', $clean );

    // About — Differentiator table (left / right)
    $clean = [];
    foreach ( (array) ( $data['about_compare'] ?? [] ) as $row ) {
        $clean[] = [
            'left'  => sanitize_text_field( $row['left']  ?? '' ),
            'right' => sanitize_text_field( $row['right'] ?? '' ),
        ];
    }
    update_option( 'et_about_compare', $clean );

    // Page Strings — single assoc array of named editorial strings.
    // Stored as a flat dictionary; render code reads with `$strings['key'] ?? ''`.
    $string_keys = [
        'bespoke_itinerary_disclaimer',
        'bespoke_philosophy_title',
        'bespoke_philosophy_body',
        'golf_itinerary_disclaimer',
        'golf_availability_note',
        'golf_philosophy_title',
        'golf_philosophy_body',
        'golf_philosophy_blockquote',
        'accommodation_trust_quote',
        'about_origin_story',
        'about_founder_title',
        'about_founder_subtitle',
        'about_founder_body',
        'about_founder_quote',
        'about_founder_quote_attribution',
    ];
    $strings = [];
    $payload_strings = (array) ( $data['page_strings'] ?? [] );
    foreach ( $string_keys as $k ) {
        $val = $payload_strings[ $k ] ?? '';
        // Allow paragraph breaks (newlines) — sanitize_textarea_field preserves them.
        $strings[ $k ] = sanitize_textarea_field( (string) $val );
    }
    update_option( 'et_page_strings', $strings );

    wp_send_json_success( 'Saved' );
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_page_content_page(): void {
    $journey_types = get_option( 'et_bespoke_journey_types', [] );
    $durations     = get_option( 'et_bespoke_durations', [] );
    $includes      = get_option( 'et_bespoke_includes', [] );
    $pillars       = get_option( 'et_golf_pillars', [] );
    $intros        = get_option( 'et_accommodation_category_intros', [] );
    $interests     = get_option( 'et_contact_interests', [] );
    $about_dna     = get_option( 'et_about_dna', [] );
    $about_moments = get_option( 'et_about_signature_moments', [] );
    $about_compare = get_option( 'et_about_compare', [] );
    $page_strings  = get_option( 'et_page_strings', [] );
    if ( ! is_array( $page_strings ) ) $page_strings = [];

    // Normalise interests to the {value:string} shape the JS expects
    $interests_objs = array_map( function ( $v ) { return [ 'value' => (string) $v ]; }, (array) $interests );
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title"><?php echo etm_lucide( 'file-text', 22 ); ?> Page Content</h1>
        <p class="etm-page-desc">Editorial blocks across Bespoke Tours, Golf Tours, Accommodation, and Contact pages. Edits here update the live site immediately.</p>

        <div id="etm-pc-feedback" class="etm-notice" style="min-height:1.5em;margin-bottom:14px;"></div>

        <form id="etm-pc-form">
            <?php wp_nonce_field( 'etm_page_content' ); ?>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Bespoke Tours — Journey Types Grid</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "Where Would You Like to Begin?" tile grid on /bespoke-tours/. Drag to reorder.</p>
                <div class="etm-pc-rows" data-section="bespoke_journey_types"
                     data-fields='[{"k":"label","l":"Label (eyebrow)","placeholder":"Ancestry & Roots"},{"k":"title","l":"Title","placeholder":"Find Where You Came From"},{"k":"desc","l":"Description","type":"textarea","placeholder":"Trace your Irish heritage..."},{"k":"url","l":"Link URL (optional)","type":"url","placeholder":"https://..."},{"k":"image_id","l":"Image","type":"image"}]'></div>
                <button type="button" class="button etm-pc-add" data-section="bespoke_journey_types">+ Add Journey Type</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Bespoke Tours — Duration Cards</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "How Long Would You Like?" 3-card row on /bespoke-tours/.</p>
                <div class="etm-pc-rows" data-section="bespoke_durations"
                     data-fields='[{"k":"num","l":"Number / Symbol","placeholder":"6-10"},{"k":"title","l":"Title","placeholder":"Days"},{"k":"desc","l":"Description","type":"textarea","placeholder":"A focused, deeply personal..."}]'></div>
                <button type="button" class="button etm-pc-add" data-section="bespoke_durations">+ Add Duration</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Bespoke Tours — What's Included</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "What Every Journey Includes" cards on /bespoke-tours/.</p>
                <div class="etm-pc-rows" data-section="bespoke_includes"
                     data-fields='[{"k":"num","l":"Number","placeholder":"01"},{"k":"title","l":"Title","placeholder":"Private Chauffeur"},{"k":"desc","l":"Description","type":"textarea","placeholder":"Door-to-door throughout..."}]'></div>
                <button type="button" class="button etm-pc-add" data-section="bespoke_includes">+ Add Inclusion</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Golf Tours — 5 Pillars</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "What Every Elite Golf Journey Includes" cards on /golf-tours/.</p>
                <div class="etm-pc-rows" data-section="golf_pillars"
                     data-fields='[{"k":"num","l":"Number","placeholder":"01"},{"k":"title","l":"Title","placeholder":"Golf-Led Personalisation"},{"k":"desc","l":"Description","type":"textarea","placeholder":"Built around you..."}]'></div>
                <button type="button" class="button etm-pc-add" data-section="golf_pillars">+ Add Pillar</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Accommodation — Category Intro Cards</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The 3 category overview tiles at the top of /accommodation/. Use one row per category — keys must be <code>castle</code>, <code>boutique</code>, or <code>coastal</code> (matches hotel category dropdown).</p>
                <div class="etm-pc-rows" data-section="accommodation_intros"
                     data-fields='[{"k":"key","l":"Category Key","type":"select","options":{"castle":"Castle & Estate","boutique":"Boutique & Country House","coastal":"Luxury Coastal & Scenic"}},{"k":"label","l":"Label (eyebrow)","placeholder":"Castle & Estate Hotels"},{"k":"title","l":"Title","placeholder":"Sleep inside history."},{"k":"desc","l":"Description","type":"textarea","placeholder":"Ashford, Dromoland..."},{"k":"image_id","l":"Image","type":"image"}]'></div>
                <button type="button" class="button etm-pc-add" data-section="accommodation_intros">+ Add Category Intro</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Contact — Interest Checkboxes</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "Interests (select all that apply)" checkboxes on the /contact/ enquiry form.</p>
                <div class="etm-pc-rows" data-section="contact_interests"
                     data-fields='[{"k":"value","l":"Interest","placeholder":"Ancestry"}]'></div>
                <button type="button" class="button etm-pc-add" data-section="contact_interests">+ Add Interest</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">About Us — The DNA</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The 5 DNA pillars on /about-us/. Title accepts <code>&lt;em&gt;</code> and <code>&lt;strong&gt;</code> for emphasis.</p>
                <div class="etm-pc-rows" data-section="about_dna"
                     data-fields='[{"k":"title","l":"Title (HTML allowed: em, strong)","placeholder":"Hosted Ireland, not guided"},{"k":"desc","l":"Description","type":"textarea","placeholder":"You are not guided through Ireland..."}]'></div>
                <button type="button" class="button etm-pc-add" data-section="about_dna">+ Add Pillar</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">About Us — Signature Moments</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The 6 Signature Moments on /about-us/. Description accepts <code>&lt;em&gt;</code> and <code>&lt;strong&gt;</code> for emphasis.</p>
                <div class="etm-pc-rows" data-section="about_moments"
                     data-fields='[{"k":"title","l":"Title","placeholder":"The First Conversation"},{"k":"desc","l":"Description (HTML allowed: em, strong)","type":"textarea","placeholder":"Sitting down with Ray..."}]'></div>
                <button type="button" class="button etm-pc-add" data-section="about_moments">+ Add Moment</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">About Us — Differentiator Table</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "Instead of this / We offer this" table on /about-us/.</p>
                <div class="etm-pc-rows" data-section="about_compare"
                     data-fields='[{"k":"left","l":"Instead of this","placeholder":"Group tours that move people around in coaches"},{"k":"right","l":"We offer this","placeholder":"A privately hosted journey, end-to-end..."}]'></div>
                <button type="button" class="button etm-pc-add" data-section="about_compare">+ Add Row</button>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Editorial Strings</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">Single-string copy across multiple pages. Edit and save — the strings update everywhere they appear.</p>

                <div class="etm-pc-string-grid" style="display:grid;gap:14px;">
                    <div class="etm-pc-string">
                        <label style="display:block;font-size:13px;font-weight:600;color:#222;margin-bottom:4px;">Bespoke Tours — itinerary disclaimer</label>
                        <p class="etm-help" style="margin:0 0 6px 0;">Italic line below the sample itineraries on /bespoke-tours/.</p>
                        <input type="text" data-string-key="bespoke_itinerary_disclaimer" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['bespoke_itinerary_disclaimer'] ?? '' ); ?>" placeholder="These are starting points. Your journey will be designed around you.">
                    </div>

                    <div class="etm-pc-string">
                        <label style="display:block;font-size:13px;font-weight:600;color:#222;margin-bottom:4px;">Golf Tours — itinerary disclaimer</label>
                        <p class="etm-help" style="margin:0 0 6px 0;">Italic line below the sample golf journeys on /golf-tours/.</p>
                        <input type="text" data-string-key="golf_itinerary_disclaimer" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['golf_itinerary_disclaimer'] ?? '' ); ?>" placeholder="All itineraries designed around the group. These are starting points only.">
                    </div>

                    <div class="etm-pc-string">
                        <label style="display:block;font-size:13px;font-weight:600;color:#222;margin-bottom:4px;">Golf Tours — availability note</label>
                        <p class="etm-help" style="margin:0 0 6px 0;">Italic note below the Featured Courses grid on /golf-tours/.</p>
                        <textarea data-string-key="golf_availability_note" rows="2" class="etm-input" style="width:100%;" placeholder="Availability at Ireland's top courses is limited..."><?php echo esc_textarea( $page_strings['golf_availability_note'] ?? '' ); ?></textarea>
                    </div>

                    <div class="etm-pc-string">
                        <label style="display:block;font-size:13px;font-weight:600;color:#222;margin-bottom:4px;">Accommodation — trust quote</label>
                        <p class="etm-help" style="margin:0 0 6px 0;">The blockquote between hotel categories and the CTA on /accommodation/.</p>
                        <textarea data-string-key="accommodation_trust_quote" rows="3" class="etm-input" style="width:100%;" placeholder="We have built relationships with Ireland's finest hotels..."><?php echo esc_textarea( $page_strings['accommodation_trust_quote'] ?? '' ); ?></textarea>
                    </div>

                    <div class="etm-pc-string">
                        <label style="display:block;font-size:13px;font-weight:600;color:#222;margin-bottom:4px;">About — origin story</label>
                        <p class="etm-help" style="margin:0 0 6px 0;">The 4-paragraph origin story on /about-us/. Use blank lines to separate paragraphs. Wrap text in <code>*asterisks*</code> for italics.</p>
                        <textarea data-string-key="about_origin_story" rows="10" class="etm-input" style="width:100%;font-family:inherit;" placeholder="For many visitors, a trip to Ireland..."><?php echo esc_textarea( $page_strings['about_origin_story'] ?? '' ); ?></textarea>
                    </div>
                </div>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Bespoke Tours — Philosophy Block</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "This Is Not a Tour Package" two-column block on /bespoke-tours/.</p>

                <div class="etm-field">
                    <label class="etm-label">Section Title</label>
                    <input type="text" data-string-key="bespoke_philosophy_title" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['bespoke_philosophy_title'] ?? '' ); ?>" placeholder="This Is Not a Tour Package">
                </div>
                <div class="etm-field">
                    <label class="etm-label">Body (paragraphs separated by blank lines)</label>
                    <textarea data-string-key="bespoke_philosophy_body" rows="10" class="etm-input" style="width:100%;font-family:inherit;" placeholder="Most companies offer you a list of itineraries..."><?php echo esc_textarea( $page_strings['bespoke_philosophy_body'] ?? '' ); ?></textarea>
                </div>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">Golf Tours — Philosophy Block</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "This Is Not a Golf Package" two-column block on /golf-tours/.</p>

                <div class="etm-field">
                    <label class="etm-label">Section Title</label>
                    <input type="text" data-string-key="golf_philosophy_title" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['golf_philosophy_title'] ?? '' ); ?>" placeholder="This Is Not a Golf Package">
                </div>
                <div class="etm-field">
                    <label class="etm-label">Body (paragraphs separated by blank lines)</label>
                    <textarea data-string-key="golf_philosophy_body" rows="8" class="etm-input" style="width:100%;font-family:inherit;" placeholder="We don't hand you a list of courses..."><?php echo esc_textarea( $page_strings['golf_philosophy_body'] ?? '' ); ?></textarea>
                </div>
                <div class="etm-field">
                    <label class="etm-label">Blockquote (callout line)</label>
                    <input type="text" data-string-key="golf_philosophy_blockquote" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['golf_philosophy_blockquote'] ?? '' ); ?>" placeholder="The best golf trip of your life, without having to think about anything.">
                </div>
                            </div>
            </div>

            <div class="etm-exp-item is-open">
                <div class="etm-exp-item__header">
                    <div class="etm-exp-item__thumb etm-exp-item__thumb--empty"><?php echo etm_lucide( 'file-text', 18 ); ?></div>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title">About Us — Founder Feature</div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Collapse"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">
                
                <p class="etm-help">The "Raphael Mulally" founder block on /about-us/.</p>

                <div class="etm-field-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="etm-field">
                        <label class="etm-label">Heading</label>
                        <input type="text" data-string-key="about_founder_title" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['about_founder_title'] ?? '' ); ?>" placeholder="Raphael Mulally">
                    </div>
                    <div class="etm-field">
                        <label class="etm-label">Subheading</label>
                        <input type="text" data-string-key="about_founder_subtitle" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['about_founder_subtitle'] ?? '' ); ?>" placeholder="Founder, host & the Irish connection">
                    </div>
                </div>
                <div class="etm-field">
                    <label class="etm-label">Body (paragraphs separated by blank lines; wrap text in <code>**double asterisks**</code> for bold)</label>
                    <textarea data-string-key="about_founder_body" rows="10" class="etm-input" style="width:100%;font-family:inherit;" placeholder="The product is not the route..."><?php echo esc_textarea( $page_strings['about_founder_body'] ?? '' ); ?></textarea>
                </div>
                <div class="etm-field">
                    <label class="etm-label">Pull Quote</label>
                    <textarea data-string-key="about_founder_quote" rows="3" class="etm-input" style="width:100%;font-family:inherit;" placeholder="I've spent decades..."><?php echo esc_textarea( $page_strings['about_founder_quote'] ?? '' ); ?></textarea>
                </div>
                <div class="etm-field">
                    <label class="etm-label">Quote Attribution</label>
                    <input type="text" data-string-key="about_founder_quote_attribution" class="etm-input" style="width:100%;" value="<?php echo esc_attr( $page_strings['about_founder_quote_attribution'] ?? '' ); ?>" placeholder="Raphael Mulally · Founder, Elite Tours Ireland">
                </div>
                            </div>
            </div>

            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary" id="etm-pc-save">Save All Page Content</button>
                <span class="etm-dirty-dot" id="etm-pc-dirty" style="display:none;" title="Unsaved changes"></span>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var DATA = {
            bespoke_journey_types: <?php echo wp_json_encode( $journey_types ); ?>,
            bespoke_durations:     <?php echo wp_json_encode( $durations ); ?>,
            bespoke_includes:      <?php echo wp_json_encode( $includes ); ?>,
            golf_pillars:          <?php echo wp_json_encode( $pillars ); ?>,
            accommodation_intros:  <?php echo wp_json_encode( $intros ); ?>,
            contact_interests:     <?php echo wp_json_encode( $interests_objs ); ?>,
            about_dna:             <?php echo wp_json_encode( $about_dna ); ?>,
            about_moments:         <?php echo wp_json_encode( $about_moments ); ?>,
            about_compare:         <?php echo wp_json_encode( $about_compare ); ?>,
        };

        var saveBtn  = document.getElementById('etm-pc-save');
        var feedback = document.getElementById('etm-pc-feedback');
        var dirtyDot = document.getElementById('etm-pc-dirty');
        var form     = document.getElementById('etm-pc-form');
        var isDirty  = false;

        function markDirty() { if (!isDirty) { isDirty = true; dirtyDot.style.display = ''; saveBtn.classList.add('etm-btn-save--dirty'); } }
        function markClean() { isDirty = false; dirtyDot.style.display = 'none'; saveBtn.classList.remove('etm-btn-save--dirty'); }
        window.addEventListener('beforeunload', function (e) { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });

        function esc(str) { var d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML.replace(/"/g, '&quot;'); }

        function buildField(field, value) {
            var name = field.k;
            var label = '<label style="display:block;font-size:12px;font-weight:500;color:#444;margin-bottom:4px;">' + esc(field.l) + '</label>';

            if (field.type === 'textarea') {
                return '<div class="etm-pc-field">' + label +
                    '<textarea data-field="' + name + '" rows="2" placeholder="' + esc(field.placeholder || '') + '">' + esc(value) + '</textarea></div>';
            }
            if (field.type === 'select') {
                var opts = field.options || {};
                var html = '<select data-field="' + name + '">';
                for (var k in opts) html += '<option value="' + esc(k) + '"' + (k === value ? ' selected' : '') + '>' + esc(opts[k]) + '</option>';
                html += '</select>';
                return '<div class="etm-pc-field">' + label + html + '</div>';
            }
            if (field.type === 'image') {
                var hasImg = value && value !== '0';
                return '<div class="etm-pc-field">' + label +
                    '<div class="etm-pc-img-row" style="display:flex;align-items:center;gap:10px;">' +
                        '<img data-img-preview="1" src="" style="width:60px;height:60px;object-fit:cover;border-radius:4px;' + (hasImg ? '' : 'display:none;') + '">' +
                        '<input type="hidden" data-field="' + name + '" value="' + esc(value || '') + '">' +
                        '<button type="button" class="button etm-pc-upload">' + (hasImg ? 'Change' : 'Upload') + '</button>' +
                        '<button type="button" class="button-link-delete etm-pc-remove-img" style="' + (hasImg ? '' : 'display:none;') + '">Remove</button>' +
                    '</div></div>';
            }
            return '<div class="etm-pc-field">' + label +
                '<input type="' + (field.type || 'text') + '" data-field="' + name + '" value="' + esc(value) + '" placeholder="' + esc(field.placeholder || '') + '"></div>';
        }

        function buildRow(fields, item, idx) {
            var row = document.createElement('div');
            row.className = 'etm-pc-row';
            row.style.cssText = 'display:flex;flex-direction:column;gap:8px;padding:14px;background:#fff;border:1px solid #d6dade;border-radius:6px;margin-bottom:10px;position:relative;';

            var inner = '';
            fields.forEach(function (f) { inner += buildField(f, item[f.k] || ''); });

            row.innerHTML =
                '<div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#999;">' +
                    '<span class="etm-pc-drag" style="cursor:grab;font-size:18px;line-height:1;color:#aaa;" title="Drag to reorder">⋮⋮</span>' +
                    '<span>Row ' + (idx + 1) + '</span>' +
                    '<button type="button" class="etm-pc-delete" style="margin-left:auto;background:none;border:none;color:#a00;cursor:pointer;font-size:18px;padding:0 4px;" title="Delete">×</button>' +
                '</div>' + inner;
            return row;
        }

        function renderSection(container) {
            var section = container.dataset.section;
            var fields  = JSON.parse(container.dataset.fields);
            var items   = DATA[section] || [];
            container.innerHTML = '';
            items.forEach(function (item, i) { container.appendChild(buildRow(fields, item, i)); });
            resolveImages(container);
        }

        function resolveImages(scope) {
            (scope || document).querySelectorAll('[data-img-preview]').forEach(function (img) {
                var input = img.parentNode.querySelector('[data-field][type="hidden"]');
                if (!input || !input.value) return;
                if (window.wp && wp.media && wp.media.attachment) {
                    var att = wp.media.attachment(parseInt(input.value));
                    att.fetch().then(function () {
                        var url = att.get('sizes') && att.get('sizes').thumbnail ? att.get('sizes').thumbnail.url : att.get('url');
                        img.src = url;
                        img.style.display = '';
                    });
                }
            });
        }

        function collectAll() {
            var out = {};
            document.querySelectorAll('.etm-pc-rows').forEach(function (container) {
                var section = container.dataset.section;
                var rows = [];
                container.querySelectorAll('.etm-pc-row').forEach(function (row) {
                    var obj = {};
                    row.querySelectorAll('[data-field]').forEach(function (el) { obj[el.dataset.field] = el.value; });
                    rows.push(obj);
                });
                out[section] = rows;
            });
            // Single-string editorial inputs
            var strings = {};
            document.querySelectorAll('[data-string-key]').forEach(function (el) {
                strings[el.dataset.stringKey] = el.value;
            });
            out.page_strings = strings;
            return out;
        }

        // Initial render
        document.querySelectorAll('.etm-pc-rows').forEach(renderSection);

        // Single-string inputs — dirty tracking
        document.querySelectorAll('[data-string-key]').forEach(function (el) {
            el.addEventListener('input',  function () { markDirty(); });
            el.addEventListener('change', function () { markDirty(); });
        });

        // Add row
        document.querySelectorAll('.etm-pc-add').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var section = btn.dataset.section;
                var container = document.querySelector('.etm-pc-rows[data-section="' + section + '"]');
                var fields = JSON.parse(container.dataset.fields);
                var blank = {};
                fields.forEach(function (f) { blank[f.k] = ''; });
                DATA[section] = collectAll()[section] || [];
                DATA[section].push(blank);
                container.appendChild(buildRow(fields, blank, DATA[section].length - 1));
                markDirty();
            });
        });

        // Delegated: delete, image upload/remove, dirty tracking
        document.querySelectorAll('.etm-pc-rows').forEach(function (container) {
            container.addEventListener('click', function (e) {
                var del = e.target.closest('.etm-pc-delete');
                if (del) {
                    if (confirm('Delete this row?')) {
                        del.closest('.etm-pc-row').remove();
                        // Re-number row labels
                        container.querySelectorAll('.etm-pc-row').forEach(function (r, i) {
                            r.querySelector('span:nth-of-type(2), span + span').textContent = 'Row ' + (i + 1);
                        });
                        markDirty();
                    }
                    return;
                }

                var upload = e.target.closest('.etm-pc-upload');
                if (upload) {
                    var imgRow = upload.closest('.etm-pc-img-row');
                    var frame = wp.media({ title: 'Select Image', button: { text: 'Use this image' }, multiple: false });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        imgRow.querySelector('[data-field]').value = att.id;
                        var preview = imgRow.querySelector('[data-img-preview]');
                        preview.src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                        preview.style.display = '';
                        imgRow.querySelector('.etm-pc-remove-img').style.display = '';
                        upload.textContent = 'Change';
                        markDirty();
                    });
                    frame.open();
                    return;
                }

                var rm = e.target.closest('.etm-pc-remove-img');
                if (rm) {
                    var imgRow2 = rm.closest('.etm-pc-img-row');
                    imgRow2.querySelector('[data-field]').value = '';
                    imgRow2.querySelector('[data-img-preview]').style.display = 'none';
                    rm.style.display = 'none';
                    imgRow2.querySelector('.etm-pc-upload').textContent = 'Upload';
                    markDirty();
                }
            });

            container.addEventListener('input', function (e) { if (e.target.dataset.field) markDirty(); });
            container.addEventListener('change', function (e) { if (e.target.dataset.field) markDirty(); });

            // Drag reorder
            var draggedEl = null;
            var placeholder = document.createElement('div');
            placeholder.style.cssText = 'border:2px dashed #2271b1;border-radius:6px;height:80px;margin-bottom:10px;background:rgba(34,113,177,0.05);';

            container.addEventListener('pointerdown', function (e) {
                var handle = e.target.closest('.etm-pc-drag');
                if (!handle) return;
                var row = handle.closest('.etm-pc-row');
                if (!row) return;
                e.preventDefault();
                draggedEl = row;
                var rect = row.getBoundingClientRect();
                var shiftY = e.clientY - rect.top;

                var ghost = row.cloneNode(true);
                ghost.style.cssText = 'position:fixed;z-index:10000;opacity:0.85;pointer-events:none;width:' + rect.width + 'px;left:' + rect.left + 'px;top:' + (e.clientY - shiftY) + 'px;box-shadow:0 8px 24px rgba(0,0,0,0.18);';
                document.body.appendChild(ghost);
                placeholder.style.height = rect.height + 'px';
                row.parentNode.insertBefore(placeholder, row);
                row.style.display = 'none';

                function onMove(ev) {
                    ghost.style.top = (ev.clientY - shiftY) + 'px';
                    var rows = Array.from(container.querySelectorAll('.etm-pc-row')).filter(function (r) { return r !== draggedEl; });
                    for (var i = 0; i < rows.length; i++) {
                        var rr = rows[i].getBoundingClientRect();
                        if (ev.clientY < rr.top + rr.height / 2) { container.insertBefore(placeholder, rows[i]); return; }
                    }
                    var btnAdd = container.parentNode.querySelector('.etm-pc-add');
                    container.appendChild(placeholder);
                }
                function onUp() {
                    document.removeEventListener('pointermove', onMove);
                    document.removeEventListener('pointerup', onUp);
                    container.insertBefore(draggedEl, placeholder);
                    draggedEl.style.display = '';
                    if (placeholder.parentNode) placeholder.parentNode.removeChild(placeholder);
                    if (ghost.parentNode) ghost.parentNode.removeChild(ghost);
                    draggedEl = null;
                    // Re-number
                    container.querySelectorAll('.etm-pc-row').forEach(function (r, i) {
                        var label = r.querySelector('span + span');
                        if (label) label.textContent = 'Row ' + (i + 1);
                    });
                    markDirty();
                }
                document.addEventListener('pointermove', onMove);
                document.addEventListener('pointerup', onUp);
            });
        });

        // Save
        saveBtn.addEventListener('click', function () {
            var fd = new FormData(form);
            fd.append('action', 'etm_save_page_content');
            fd.append('payload', JSON.stringify(collectAll()));

            saveBtn.textContent = 'Saving…';
            saveBtn.disabled = true;
            feedback.textContent = '';
            feedback.className = 'etm-notice';

            fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        markClean();
                        saveBtn.textContent = 'Saved';
                        feedback.textContent = 'All page content saved.';
                        feedback.className = 'etm-notice etm-notice--success';
                        setTimeout(function () { saveBtn.textContent = 'Save All Page Content'; saveBtn.disabled = false; }, 2200);
                    } else {
                        saveBtn.textContent = 'Save All Page Content';
                        saveBtn.disabled = false;
                        feedback.textContent = 'Save failed: ' + (res.data || 'unknown');
                        feedback.className = 'etm-notice etm-notice--error';
                    }
                })
                .catch(function (err) {
                    saveBtn.textContent = 'Save All Page Content';
                    saveBtn.disabled = false;
                    feedback.textContent = 'Network error: ' + err;
                    feedback.className = 'etm-notice etm-notice--error';
                });
        });
    
        // Collapse/expand each section by clicking its header.
        form.querySelectorAll('.etm-exp-item__header').forEach(function (header) {
            header.addEventListener('click', function (e) {
                if (e.target.closest('input, textarea, select, button')) {
                    if (!e.target.closest('.etm-exp-item__toggle')) return;
                }
                header.closest('.etm-exp-item').classList.toggle('is-open');
            });
        });

        })();
    </script>
    <?php
}
