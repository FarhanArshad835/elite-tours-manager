<?php
/**
 * Page Heroes & Bottom CTAs — single admin page for the per-page top hero
 * blocks (h1 / subtitle / hero CTA / background image) and the bottom CTA
 * sections that sit above the footer on every page template.
 *
 * Storage:
 *   et_page_heroes  — assoc array keyed by page slug. Each value:
 *                       eyebrow, title (br/em allowed), subtitle, cta_text,
 *                       cta_url, image_id, image_filename (fallback)
 *   et_page_ctas    — assoc array keyed by page slug. Each value:
 *                       title (br/em allowed), subtitle, cta_text, cta_url
 *
 * Pages covered: bespoke-tours, golf-tours, experiences, accommodation,
 * about-us, contact (hero only — page is the form).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canonical list of pages this admin manages, in render order.
 * Keep slugs in sync with the page templates' template-name comments.
 */
function etm_page_hero_definitions(): array {
    return [
        'bespoke-tours'  => [ 'label' => 'Bespoke Tours',  'has_hero' => true, 'has_cta' => true,  'has_hero_cta' => true ],
        'golf-tours'     => [ 'label' => 'Golf Tours',     'has_hero' => true, 'has_cta' => true,  'has_hero_cta' => true ],
        'experiences'    => [ 'label' => 'Experiences',    'has_hero' => true, 'has_cta' => true,  'has_hero_cta' => false ],
        'accommodation'  => [ 'label' => 'Accommodation',  'has_hero' => true, 'has_cta' => true,  'has_hero_cta' => false ],
        'about-us'       => [ 'label' => 'About Us',       'has_hero' => true, 'has_cta' => true,  'has_hero_cta' => false ],
        'contact'        => [ 'label' => 'Contact',        'has_hero' => true, 'has_cta' => false, 'has_hero_cta' => false ],
    ];
}

// ── Save Handler (AJAX) ──────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_page_heroes', function () {
    check_ajax_referer( 'etm_page_heroes', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '{}';
    $data    = json_decode( $payload, true );
    if ( ! is_array( $data ) ) wp_send_json_error( 'Invalid payload' );

    $allowed_inline = [ 'br' => [], 'em' => [], 'strong' => [] ];
    $defs = etm_page_hero_definitions();

    $heroes_clean = [];
    $ctas_clean   = [];
    foreach ( $defs as $slug => $def ) {
        if ( $def['has_hero'] ) {
            $h = $data['heroes'][ $slug ] ?? [];
            $heroes_clean[ $slug ] = [
                'eyebrow'        => sanitize_text_field( $h['eyebrow']  ?? '' ),
                'title'          => wp_kses( $h['title']    ?? '', $allowed_inline ),
                'subtitle'       => sanitize_textarea_field( $h['subtitle'] ?? '' ),
                'cta_text'       => sanitize_text_field( $h['cta_text'] ?? '' ),
                'cta_url'        => esc_url_raw( $h['cta_url']  ?? '' ),
                'image_id'       => absint( $h['image_id'] ?? 0 ),
                'image_filename' => sanitize_text_field( $h['image_filename'] ?? '' ),
            ];
        }
        if ( $def['has_cta'] ) {
            $c = $data['ctas'][ $slug ] ?? [];
            $ctas_clean[ $slug ] = [
                'title'    => wp_kses( $c['title']    ?? '', $allowed_inline ),
                'subtitle' => sanitize_textarea_field( $c['subtitle'] ?? '' ),
                'cta_text' => sanitize_text_field( $c['cta_text'] ?? '' ),
                'cta_url'  => esc_url_raw( $c['cta_url']  ?? '' ),
            ];
        }
    }

    update_option( 'et_page_heroes', $heroes_clean );
    update_option( 'et_page_ctas',   $ctas_clean );
    wp_send_json_success( 'Saved' );
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_page_heroes_page(): void {
    $defs   = etm_page_hero_definitions();
    $heroes = get_option( 'et_page_heroes', [] );
    $ctas   = get_option( 'et_page_ctas',   [] );
    if ( ! is_array( $heroes ) ) $heroes = [];
    if ( ! is_array( $ctas ) )   $ctas   = [];
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title"><?php echo etm_lucide( 'layout-template', 22 ); ?> Page Heroes &amp; CTAs</h1>
        <p class="etm-page-desc">Hero block (top) and bottom CTA for each page template. Edit once — reflected immediately on the live site.</p>

        <div id="etm-ph-feedback" class="etm-notice" style="min-height:1.5em;margin-bottom:14px;"></div>

        <form id="etm-ph-form">
            <?php wp_nonce_field( 'etm_page_heroes' ); ?>

            <?php foreach ( $defs as $slug => $def ) :
                $h = $heroes[ $slug ] ?? [];
                $c = $ctas[ $slug ]   ?? [];
                $img_id  = absint( $h['image_id'] ?? 0 );
                $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
                $section_meta = $def['has_hero'] && $def['has_cta']
                    ? 'Hero + bottom CTA'
                    : ( $def['has_hero'] ? 'Hero only' : 'CTA only' );
                $thumb_html = $img_url
                    ? '<img class="etm-exp-item__thumb" src="' . esc_url( $img_url ) . '" alt="">'
                    : '<div class="etm-exp-item__thumb etm-exp-item__thumb--empty">' . etm_lucide( 'layout-template', 18 ) . '</div>';
            ?>
            <div class="etm-exp-item" data-page-slug="<?php echo esc_attr( $slug ); ?>">
                <div class="etm-exp-item__header">
                    <?php echo $thumb_html; ?>
                    <div class="etm-exp-item__info">
                        <div class="etm-exp-item__title"><?php echo esc_html( $def['label'] ); ?></div>
                        <div class="etm-exp-item__meta">/<?php echo esc_html( $slug ); ?>/ &middot; <?php echo esc_html( $section_meta ); ?></div>
                    </div>
                    <div class="etm-exp-item__actions">
                        <button type="button" class="etm-exp-item__toggle" title="Expand"><?php echo etm_lucide( 'chevron-down', 16 ); ?></button>
                    </div>
                </div>
                <div class="etm-exp-item__body">

                <?php if ( $def['has_hero'] ) : ?>
                <div style="margin-bottom:18px;padding:14px 16px;background:#f6f7f7;border-radius:6px;">
                    <h3 style="margin:0 0 12px;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;color:#555;">Top Hero</h3>

                    <div class="etm-field">
                        <label class="etm-label">Eyebrow (small label above title — optional)</label>
                        <input type="text" data-hero-field="eyebrow" class="etm-input" value="<?php echo esc_attr( $h['eyebrow'] ?? '' ); ?>" placeholder="">
                    </div>

                    <div class="etm-field">
                        <label class="etm-label">Title (HTML allowed: &lt;br&gt;, &lt;em&gt;)</label>
                        <textarea data-hero-field="title" rows="2" class="etm-input" style="width:100%;font-family:inherit;"><?php echo esc_textarea( $h['title'] ?? '' ); ?></textarea>
                        <p class="etm-help">Use <code>&lt;br&gt;</code> for line breaks, <code>&lt;em&gt;</code> for italics.</p>
                    </div>

                    <div class="etm-field">
                        <label class="etm-label">Subtitle</label>
                        <textarea data-hero-field="subtitle" rows="3" class="etm-input" style="width:100%;font-family:inherit;"><?php echo esc_textarea( $h['subtitle'] ?? '' ); ?></textarea>
                    </div>

                    <?php if ( $def['has_hero_cta'] ) : ?>
                    <div class="etm-field-row" style="display:grid;grid-template-columns:1fr 2fr;gap:12px;">
                        <div class="etm-field">
                            <label class="etm-label">Hero CTA — Button Text</label>
                            <input type="text" data-hero-field="cta_text" class="etm-input" value="<?php echo esc_attr( $h['cta_text'] ?? '' ); ?>" placeholder="Begin Your First Conversation">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label">Hero CTA — Link URL</label>
                            <input type="text" data-hero-field="cta_url" class="etm-input" value="<?php echo esc_attr( $h['cta_url'] ?? '' ); ?>" placeholder="/contact/">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="etm-field">
                        <label class="etm-label">Background Image</label>
                        <div class="etm-pc-img-row" style="display:flex;align-items:center;gap:10px;">
                            <img class="etm-ph-img-preview" src="<?php echo esc_url( $img_url ); ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:4px;<?php echo $img_url ? '' : 'display:none;'; ?>">
                            <input type="hidden" data-hero-field="image_id" value="<?php echo esc_attr( $img_id ); ?>">
                            <button type="button" class="button etm-ph-upload"><?php echo $img_url ? 'Change' : 'Upload'; ?></button>
                            <button type="button" class="button-link-delete etm-ph-remove-img" style="<?php echo $img_url ? '' : 'display:none;'; ?>">Remove</button>
                        </div>
                        <p class="etm-help">Hero background. Falls back to the bundled theme image if not set: <code><?php echo esc_html( $h['image_filename'] ?? '(none)' ); ?></code></p>
                        <input type="hidden" data-hero-field="image_filename" value="<?php echo esc_attr( $h['image_filename'] ?? '' ); ?>">
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $def['has_cta'] ) : ?>
                <div style="padding:14px 16px;background:#f6f7f7;border-radius:6px;">
                    <h3 style="margin:0 0 12px;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;color:#555;">Bottom CTA Section</h3>

                    <div class="etm-field">
                        <label class="etm-label">Title (HTML allowed: &lt;br&gt;, &lt;em&gt;)</label>
                        <textarea data-cta-field="title" rows="2" class="etm-input" style="width:100%;font-family:inherit;"><?php echo esc_textarea( $c['title'] ?? '' ); ?></textarea>
                    </div>

                    <div class="etm-field">
                        <label class="etm-label">Subtitle</label>
                        <textarea data-cta-field="subtitle" rows="3" class="etm-input" style="width:100%;font-family:inherit;"><?php echo esc_textarea( $c['subtitle'] ?? '' ); ?></textarea>
                    </div>

                    <div class="etm-field-row" style="display:grid;grid-template-columns:1fr 2fr;gap:12px;">
                        <div class="etm-field">
                            <label class="etm-label">Button Text</label>
                            <input type="text" data-cta-field="cta_text" class="etm-input" value="<?php echo esc_attr( $c['cta_text'] ?? '' ); ?>" placeholder="Begin Your First Conversation">
                        </div>
                        <div class="etm-field">
                            <label class="etm-label">Button URL</label>
                            <input type="text" data-cta-field="cta_url" class="etm-input" value="<?php echo esc_attr( $c['cta_url'] ?? '' ); ?>" placeholder="/contact/">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                </div><!-- /.etm-exp-item__body -->
            </div>
            <?php endforeach; ?>

            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary" id="etm-ph-save">Save All Heroes &amp; CTAs</button>
                <span class="etm-dirty-dot" id="etm-ph-dirty" style="display:none;" title="Unsaved changes"></span>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var saveBtn  = document.getElementById('etm-ph-save');
        var feedback = document.getElementById('etm-ph-feedback');
        var dirtyDot = document.getElementById('etm-ph-dirty');
        var form     = document.getElementById('etm-ph-form');
        var isDirty  = false;

        function markDirty() { if (!isDirty) { isDirty = true; dirtyDot.style.display = ''; saveBtn.classList.add('etm-btn-save--dirty'); } }
        function markClean() { isDirty = false; dirtyDot.style.display = 'none'; saveBtn.classList.remove('etm-btn-save--dirty'); }
        window.addEventListener('beforeunload', function (e) { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });

        // Dirty tracking on every input
        form.querySelectorAll('[data-hero-field], [data-cta-field]').forEach(function (el) {
            el.addEventListener('input',  markDirty);
            el.addEventListener('change', markDirty);
        });

        // Collapse/expand each page section by clicking its header. Skip clicks
        // landing on the inputs/buttons inside the body (header doesn't contain them).
        form.querySelectorAll('.etm-exp-item__header').forEach(function (header) {
            header.addEventListener('click', function (e) {
                if (e.target.closest('input, textarea, select, button')) {
                    if (!e.target.closest('.etm-exp-item__toggle')) return;
                }
                header.closest('.etm-exp-item').classList.toggle('is-open');
            });
        });

        // Image picker / remove (per page section)
        form.querySelectorAll('.etm-exp-item[data-page-slug]').forEach(function (section) {
            var upload = section.querySelector('.etm-ph-upload');
            var remove = section.querySelector('.etm-ph-remove-img');
            var imgId  = section.querySelector('[data-hero-field="image_id"]');
            var preview = section.querySelector('.etm-ph-img-preview');
            if (upload) {
                upload.addEventListener('click', function () {
                    var frame = wp.media({ title: 'Select Hero Background', button: { text: 'Use this image' }, multiple: false });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        imgId.value = att.id;
                        preview.src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                        preview.style.display = '';
                        if (remove) remove.style.display = '';
                        upload.textContent = 'Change';
                        markDirty();
                    });
                    frame.open();
                });
            }
            if (remove) {
                remove.addEventListener('click', function () {
                    imgId.value = '';
                    preview.src = '';
                    preview.style.display = 'none';
                    remove.style.display = 'none';
                    upload.textContent = 'Upload';
                    markDirty();
                });
            }
        });

        function collect() {
            var heroes = {};
            var ctas   = {};
            form.querySelectorAll('.etm-exp-item[data-page-slug]').forEach(function (section) {
                var slug = section.dataset.pageSlug;
                var h = {}, c = {};
                section.querySelectorAll('[data-hero-field]').forEach(function (el) { h[el.dataset.heroField] = el.value; });
                section.querySelectorAll('[data-cta-field]').forEach(function (el)  { c[el.dataset.ctaField]  = el.value; });
                if (Object.keys(h).length) heroes[slug] = h;
                if (Object.keys(c).length) ctas[slug]   = c;
            });
            return { heroes: heroes, ctas: ctas };
        }

        saveBtn.addEventListener('click', function () {
            var fd = new FormData(form);
            fd.append('action', 'etm_save_page_heroes');
            fd.append('payload', JSON.stringify(collect()));

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
                        feedback.textContent = 'Heroes & CTAs saved.';
                        feedback.className = 'etm-notice etm-notice--success';
                        setTimeout(function () { saveBtn.textContent = 'Save All Heroes & CTAs'; saveBtn.disabled = false; }, 2200);
                    } else {
                        saveBtn.textContent = 'Save All Heroes & CTAs';
                        saveBtn.disabled = false;
                        feedback.textContent = 'Save failed: ' + (res.data || 'unknown');
                        feedback.className = 'etm-notice etm-notice--error';
                    }
                })
                .catch(function (err) {
                    saveBtn.textContent = 'Save All Heroes & CTAs';
                    saveBtn.disabled = false;
                    feedback.textContent = 'Network error: ' + err;
                    feedback.className = 'etm-notice etm-notice--error';
                });
        });
    })();
    </script>
    <?php
}

/**
 * Front-end render helper — renders a standard hero section for a given page slug.
 * Reads from et_page_heroes; falls back to defaults the caller supplies.
 *
 * @param string $slug      Page slug (e.g. 'bespoke-tours').
 * @param array  $defaults  Fallback values: title, subtitle, eyebrow, cta_text,
 *                          cta_url, image_filename. Used when the wp_option for
 *                          this slug is empty.
 * @param string $base_url  Theme images URL (for filename → URL resolution).
 * @param string $extra_class  Optional class on the outer <section>.
 */
function etm_render_page_hero( string $slug, array $defaults = [], string $base_url = '', string $extra_class = '' ): void {
    $heroes = get_option( 'et_page_heroes', [] );
    $h      = is_array( $heroes ) && isset( $heroes[ $slug ] ) && is_array( $heroes[ $slug ] ) ? $heroes[ $slug ] : [];

    $eyebrow  = $h['eyebrow']  ?? ( $defaults['eyebrow']  ?? '' );
    $title    = $h['title']    ?? ( $defaults['title']    ?? '' );
    $subtitle = $h['subtitle'] ?? ( $defaults['subtitle'] ?? '' );
    $cta_text = $h['cta_text'] ?? ( $defaults['cta_text'] ?? '' );
    $cta_url  = $h['cta_url']  ?? ( $defaults['cta_url']  ?? '' );
    $img_id   = absint( $h['image_id'] ?? 0 );
    $img_filename = $h['image_filename'] ?? ( $defaults['image_filename'] ?? '' );
    $img_url  = $img_id
        ? wp_get_attachment_image_url( $img_id, 'large' )
        : ( $img_filename && $base_url ? trailingslashit( $base_url ) . $img_filename : '' );

    $allowed = [ 'br' => [], 'em' => [], 'strong' => [] ];
    ?>
    <section class="et-page-hero <?php echo esc_attr( $extra_class ); ?>">
        <?php if ( $img_url ) : ?>
        <div class="et-page-hero__bg" style="background-image:url('<?php echo esc_url( $img_url ); ?>')"></div>
        <?php endif; ?>
        <div class="et-page-hero__overlay"></div>
        <div class="et-container">
            <div class="et-page-hero__content et-reveal">
                <?php if ( $eyebrow ) : ?>
                <p class="et-page-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <?php endif; ?>
                <?php if ( $title ) : ?>
                <h1 class="et-page-hero__title"><?php echo wp_kses( $title, $allowed ); ?></h1>
                <?php endif; ?>
                <?php if ( $subtitle ) : ?>
                <p class="et-page-hero__sub"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>
                <?php if ( $cta_text && $cta_url ) :
                    $href = ( strpos( $cta_url, 'http' ) === 0 ) ? $cta_url : home_url( $cta_url );
                ?>
                <a href="<?php echo esc_url( $href ); ?>" class="et-btn et-btn--primary et-btn--lg et-page-hero__cta"><?php echo esc_html( $cta_text ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render a multi-paragraph editorial body. Paragraphs split by blank lines.
 * Inline emphasis tokens supported:
 *   *italic*           → <em>italic</em>
 *   **bold**           → <strong>bold</strong>
 *
 * @param string $body  The raw body text.
 */
function etm_render_paragraphs( string $body ): void {
    $body = trim( $body );
    if ( $body === '' ) return;
    $paragraphs = preg_split( '/\n\s*\n/', $body );
    foreach ( $paragraphs as $p ) {
        $p = trim( $p );
        if ( $p === '' ) continue;
        // **bold** — capture before *italic* (greedy match prevention)
        $parts = preg_split( '/\*\*([^*]+)\*\*|\*([^*]+)\*/', $p, -1, PREG_SPLIT_DELIM_CAPTURE );
        $out = '';
        for ( $i = 0; $i < count( $parts ); $i++ ) {
            $chunk = $parts[ $i ];
            if ( $i % 3 === 0 ) {
                $out .= esc_html( $chunk );
            } elseif ( $i % 3 === 1 && $chunk !== '' ) {
                // Bold capture group
                $out .= '<strong>' . esc_html( $chunk ) . '</strong>';
            } elseif ( $i % 3 === 2 && $chunk !== '' ) {
                // Italic capture group
                $out .= '<em>' . esc_html( $chunk ) . '</em>';
            }
        }
        echo '<p>' . $out . '</p>';
    }
}

/**
 * Front-end render helper — renders the bottom green CTA section.
 *
 * @param string $slug      Page slug.
 * @param array  $defaults  Fallback values: title, subtitle, cta_text, cta_url.
 * @param string $section_class  Optional override for the section CSS class
 *                                (defaults to 'et-section et-section--green').
 */
function etm_render_page_cta( string $slug, array $defaults = [], string $section_class = 'et-section et-section--green' ): void {
    $ctas = get_option( 'et_page_ctas', [] );
    $c    = is_array( $ctas ) && isset( $ctas[ $slug ] ) && is_array( $ctas[ $slug ] ) ? $ctas[ $slug ] : [];

    $title    = $c['title']    ?? ( $defaults['title']    ?? '' );
    $subtitle = $c['subtitle'] ?? ( $defaults['subtitle'] ?? '' );
    $cta_text = $c['cta_text'] ?? ( $defaults['cta_text'] ?? '' );
    $cta_url  = $c['cta_url']  ?? ( $defaults['cta_url']  ?? '' );

    if ( ! $title && ! $subtitle && ! $cta_text ) return;

    $allowed = [ 'br' => [], 'em' => [], 'strong' => [] ];
    $href    = $cta_url ? ( strpos( $cta_url, 'http' ) === 0 ? $cta_url : home_url( $cta_url ) ) : '';
    ?>
    <section class="<?php echo esc_attr( $section_class ); ?>">
        <div class="et-container">
            <div class="et-section__header et-section__header--center et-reveal">
                <?php if ( $title ) : ?>
                <h2 class="et-section__title"><?php echo wp_kses( $title, $allowed ); ?></h2>
                <?php endif; ?>
                <?php if ( $subtitle ) : ?>
                <p class="et-section__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>
            </div>
            <?php if ( $cta_text && $href ) : ?>
            <div style="text-align:center;" class="et-reveal">
                <a href="<?php echo esc_url( $href ); ?>" class="et-btn et-btn--pill et-btn--pill-light et-btn--lg"><?php echo esc_html( $cta_text ); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
