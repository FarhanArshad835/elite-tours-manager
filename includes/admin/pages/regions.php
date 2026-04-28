<?php
/**
 * Regions admin — manages et_regions, the 11 region tiles rendered as
 * "The country, in eleven movements." on /experiences/. Same UX pattern
 * as Hotels: drag-reorder, image picker, AJAX save, dirty tracking.
 *
 * Highlights are stored as a nested array of strings; the admin UI
 * exposes them as one bullet per line in a single textarea.
 */

defined( 'ABSPATH' ) || exit;

// ── Save Handler (AJAX) ───────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_regions', function () {
    check_ajax_referer( 'etm_regions', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $raw   = isset( $_POST['regions'] ) ? wp_unslash( $_POST['regions'] ) : '[]';
    $items = json_decode( $raw, true );
    if ( ! is_array( $items ) ) wp_send_json_error( 'Invalid data' );

    $clean = [];
    foreach ( $items as $item ) {
        $title = sanitize_text_field( $item['title'] ?? '' );
        $slug  = sanitize_title( $item['slug'] ?? '' );
        if ( $slug === '' ) $slug = sanitize_title( $title );

        // Highlights: textarea with one per line → array of strings
        $highlights_raw = (string) ( $item['highlights'] ?? '' );
        $highlight_lines = array_filter( array_map( 'trim', preg_split( '/\r?\n/', $highlights_raw ) ) );
        $highlights = array_values( array_map( 'sanitize_text_field', $highlight_lines ) );

        $clean[] = [
            'slug'           => $slug,
            'title'          => $title,
            'eyebrow'        => sanitize_text_field( $item['eyebrow'] ?? '' ),
            'blurb'          => sanitize_textarea_field( $item['blurb'] ?? '' ),
            'highlights'     => $highlights,
            'image_id'       => absint( $item['image_id'] ?? 0 ),
            'image_filename' => sanitize_text_field( $item['image_filename'] ?? '' ),
            'tour_link_text' => sanitize_text_field( $item['tour_link_text'] ?? '' ),
            'tour_link_url'  => esc_url_raw( $item['tour_link_url'] ?? '' ),
        ];
    }

    update_option( 'et_regions', $clean );
    wp_send_json_success( count( $clean ) . ' regions saved' );
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_regions_page(): void {
    $regions = get_option( 'et_regions', [] );
    if ( ! is_array( $regions ) ) $regions = [];
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title"><?php echo etm_lucide( 'map', 22 ); ?> Regions of Ireland</h1>
        <p class="etm-page-desc">The 11 region tiles shown on <code>/experiences/</code> under "The country, in eleven movements." Drag to reorder. Each region card shows the eyebrow, title, blurb, up to 3 highlights, and a CTA link.</p>

        <div id="etm-rg-feedback" class="etm-notice" style="min-height:1.5em;"></div>

        <form id="etm-rg-form">
            <?php wp_nonce_field( 'etm_regions' ); ?>

            <div id="etm-rg-list"></div>

            <button type="button" class="etm-btn-add button" id="etm-rg-add">+ Add Region</button>

            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary" id="etm-rg-save">Save Regions</button>
                <span class="etm-dirty-dot" id="etm-rg-dirty" style="display:none;" title="Unsaved changes"></span>
                <span class="etm-exp-count" id="etm-rg-count"><?php echo count( $regions ); ?> regions</span>
            </div>
        </form>
    </div>

    <script>
    (function() {
        var list      = document.getElementById('etm-rg-list');
        var saveBtn   = document.getElementById('etm-rg-save');
        var addBtn    = document.getElementById('etm-rg-add');
        var feedback  = document.getElementById('etm-rg-feedback');
        var dirtyDot  = document.getElementById('etm-rg-dirty');
        var countEl   = document.getElementById('etm-rg-count');
        var form      = document.getElementById('etm-rg-form');
        var isDirty   = false;

        var regions = <?php echo wp_json_encode( $regions ); ?>;

        function markDirty() { if (!isDirty) { isDirty = true; dirtyDot.style.display = ''; saveBtn.classList.add('etm-btn-save--dirty'); } }
        function markClean() { isDirty = false; dirtyDot.style.display = 'none'; saveBtn.classList.remove('etm-btn-save--dirty'); }
        window.addEventListener('beforeunload', function(e) { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });

        function esc(str) { var d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML.replace(/"/g, '&quot;'); }

        function highlightsAsText(arr) {
            if (!Array.isArray(arr)) return '';
            return arr.join('\n');
        }

        function renderItem(item, idx) {
            var div = document.createElement('div');
            div.className = 'etm-exp-item';
            div.dataset.idx = idx;

            var thumb = item.image_id
                ? '<img class="etm-exp-item__thumb" src="" data-resolve-id="' + item.image_id + '" alt="">'
                : '<div class="etm-exp-item__thumb etm-exp-item__thumb--empty">IMG</div>';

            div.innerHTML =
                '<div class="etm-exp-item__header">' +
                    '<span class="etm-exp-item__drag" title="Drag to reorder">&#8942;</span>' +
                    thumb +
                    '<div class="etm-exp-item__info">' +
                        '<div class="etm-exp-item__title">' + (item.title || 'Untitled Region') + '</div>' +
                        '<div class="etm-exp-item__meta">' + (item.eyebrow || '') + ' &middot; /' + (item.slug || '') + '/</div>' +
                    '</div>' +
                    '<div class="etm-exp-item__actions">' +
                        '<button type="button" class="etm-exp-item__toggle" title="Expand">&#9662;</button>' +
                        '<button type="button" class="etm-exp-item__delete" title="Delete">&times;</button>' +
                    '</div>' +
                '</div>' +
                '<div class="etm-exp-item__body">' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Title</label><input type="text" data-field="title" value="' + esc(item.title) + '" placeholder="Dublin & Ancient Ireland"></div>' +
                        '<div class="etm-exp-field"><label>Slug (auto from title if blank)</label><input type="text" data-field="slug" value="' + esc(item.slug) + '" placeholder="dublin-and-ancient-ireland"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Eyebrow (small label above title)</label><input type="text" data-field="eyebrow" value="' + esc(item.eyebrow) + '" placeholder="The Foundations"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Blurb (description paragraph)</label><textarea data-field="blurb" rows="3">' + esc(item.blurb) + '</textarea></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Highlights (one bullet per line — first 3 shown on the card)</label><textarea data-field="highlights" rows="4" placeholder="EPIC Museum & the real Irish emigration story\nTrinity College, Christ Church & Dublinia\nViking walking tour with Brendan">' + esc(highlightsAsText(item.highlights)) + '</textarea></div>' +
                    '</div>' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>CTA Link Text</label><input type="text" data-field="tour_link_text" value="' + esc(item.tour_link_text) + '" placeholder="Featured in: Signature & Bespoke"></div>' +
                        '<div class="etm-exp-field"><label>CTA Link URL</label><input type="text" data-field="tour_link_url" value="' + esc(item.tour_link_url) + '" placeholder="/experiences/signature-ireland-journey/"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Image</label>' +
                            '<div class="etm-exp-img-row">' +
                                '<img class="etm-exp-img-preview" src="" data-img-preview="1" alt="" style="' + (item.image_id ? '' : 'display:none') + '">' +
                                '<input type="hidden" data-field="image_id" value="' + (item.image_id || '') + '">' +
                                '<input type="hidden" data-field="image_filename" value="' + esc(item.image_filename) + '">' +
                                '<button type="button" class="button etm-exp-upload">' + (item.image_id ? 'Change' : 'Upload') + '</button>' +
                                '<button type="button" class="button-link-delete etm-exp-remove-img" style="' + (item.image_id ? '' : 'display:none') + '">Remove</button>' +
                            '</div>' +
                            '<p class="etm-help">Falls back to bundled theme image: <code>' + esc(item.image_filename || '(none)') + '</code></p>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            return div;
        }

        function renderAll() {
            list.innerHTML = '';
            if (regions.length === 0) {
                list.innerHTML = '<div class="etm-exp-empty">No regions yet. Click "+ Add Region" to create one.</div>';
            } else {
                regions.forEach(function(item, i) { list.appendChild(renderItem(item, i)); });
                resolveImages();
            }
            updateCount();
        }

        function resolveImages() {
            list.querySelectorAll('[data-resolve-id], [data-img-preview]').forEach(function(img) {
                var id = img.dataset.resolveId;
                if (!id) {
                    var input = img.parentNode.querySelector('[data-field="image_id"]');
                    if (input) id = input.value;
                }
                if (id && window.wp && wp.media && wp.media.attachment) {
                    var att = wp.media.attachment(parseInt(id));
                    att.fetch().then(function() {
                        var url = att.get('sizes') && att.get('sizes').thumbnail ? att.get('sizes').thumbnail.url : att.get('url');
                        img.src = url;
                    });
                }
            });
        }

        function updateCount() {
            countEl.textContent = regions.length + ' region' + (regions.length !== 1 ? 's' : '');
        }

        function collectData() {
            var data = [];
            list.querySelectorAll('.etm-exp-item').forEach(function(item) {
                var obj = {};
                item.querySelectorAll('[data-field]').forEach(function(el) { obj[el.dataset.field] = el.value; });
                data.push(obj);
            });
            regions = data;
            return data;
        }

        renderAll();

        addBtn.addEventListener('click', function() {
            regions.push({ slug: '', title: '', eyebrow: '', blurb: '', highlights: [], image_id: 0, image_filename: '', tour_link_text: '', tour_link_url: '' });
            var item = renderItem(regions[regions.length - 1], regions.length - 1);
            var empty = list.querySelector('.etm-exp-empty');
            if (empty) empty.remove();
            list.appendChild(item);
            item.classList.add('is-open');
            item.querySelector('[data-field="title"]').focus();
            updateCount();
            markDirty();
        });

        list.addEventListener('click', function(e) {
            var toggle = e.target.closest('.etm-exp-item__toggle');
            if (toggle) { toggle.closest('.etm-exp-item').classList.toggle('is-open'); return; }

            var del = e.target.closest('.etm-exp-item__delete');
            if (del) {
                if (confirm('Delete this region?')) {
                    del.closest('.etm-exp-item').remove();
                    collectData();
                    updateCount();
                    markDirty();
                    if (regions.length === 0) renderAll();
                }
                return;
            }

            var upload = e.target.closest('.etm-exp-upload');
            if (upload) {
                var body = upload.closest('.etm-exp-item__body');
                var frame = wp.media({ title: 'Select Region Image', button: { text: 'Use this image' }, multiple: false });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    body.querySelector('[data-field="image_id"]').value = att.id;
                    var preview = body.querySelector('[data-img-preview]');
                    preview.src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                    preview.style.display = '';
                    body.querySelector('.etm-exp-remove-img').style.display = '';
                    upload.textContent = 'Change';
                    var header = body.closest('.etm-exp-item').querySelector('.etm-exp-item__header');
                    var headerThumb = header.querySelector('.etm-exp-item__thumb, .etm-exp-item__thumb--empty');
                    if (headerThumb) {
                        var img = document.createElement('img');
                        img.className = 'etm-exp-item__thumb';
                        img.src = preview.src;
                        headerThumb.replaceWith(img);
                    }
                    markDirty();
                });
                frame.open();
                return;
            }

            var removeImg = e.target.closest('.etm-exp-remove-img');
            if (removeImg) {
                var body2 = removeImg.closest('.etm-exp-item__body');
                body2.querySelector('[data-field="image_id"]').value = '';
                body2.querySelector('[data-img-preview]').style.display = 'none';
                removeImg.style.display = 'none';
                body2.querySelector('.etm-exp-upload').textContent = 'Upload';
                markDirty();
            }
        });

        list.addEventListener('input', function(e) {
            if (e.target.dataset.field) {
                markDirty();
                if (e.target.dataset.field === 'title') {
                    e.target.closest('.etm-exp-item').querySelector('.etm-exp-item__title').textContent = e.target.value || 'Untitled Region';
                }
                if (e.target.dataset.field === 'eyebrow' || e.target.dataset.field === 'slug') {
                    var item = e.target.closest('.etm-exp-item');
                    var eye = item.querySelector('[data-field="eyebrow"]').value;
                    var sl = item.querySelector('[data-field="slug"]').value;
                    item.querySelector('.etm-exp-item__meta').innerHTML = (eye || '') + ' &middot; /' + (sl || '') + '/';
                }
            }
        });

        saveBtn.addEventListener('click', function() {
            var data = collectData();
            var fd = new FormData(form);
            fd.append('action', 'etm_save_regions');
            fd.append('regions', JSON.stringify(data));

            saveBtn.textContent = 'Saving…';
            saveBtn.disabled = true;

            fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        markClean();
                        saveBtn.textContent = 'Saved';
                        feedback.textContent = res.data;
                        feedback.className = 'etm-notice etm-notice--success';
                        setTimeout(function() { saveBtn.textContent = 'Save Regions'; saveBtn.disabled = false; }, 2000);
                    } else {
                        saveBtn.textContent = 'Save Regions'; saveBtn.disabled = false;
                        feedback.textContent = 'Error: ' + (res.data || 'unknown');
                        feedback.className = 'etm-notice etm-notice--error';
                    }
                })
                .catch(function() {
                    saveBtn.textContent = 'Save Regions'; saveBtn.disabled = false;
                    feedback.textContent = 'Network error';
                    feedback.className = 'etm-notice etm-notice--error';
                });
        });

        // Drag reorder
        var draggedEl = null;
        var placeholder = document.createElement('div');
        placeholder.className = 'etm-drop-placeholder-exp';

        list.addEventListener('pointerdown', function(e) {
            var handle = e.target.closest('.etm-exp-item__drag');
            if (!handle) return;
            var item = handle.closest('.etm-exp-item');
            if (!item) return;

            e.preventDefault();
            draggedEl = item;
            var rect = item.getBoundingClientRect();
            var shiftY = e.clientY - rect.top;

            var ghost = item.cloneNode(true);
            ghost.style.cssText = 'position:fixed;z-index:10000;opacity:0.85;pointer-events:none;width:' + rect.width + 'px;left:' + rect.left + 'px;top:' + (e.clientY - shiftY) + 'px;box-shadow:0 8px 24px rgba(0,0,0,0.18);border-radius:8px;';
            document.body.appendChild(ghost);

            placeholder.style.height = rect.height + 'px';
            item.parentNode.insertBefore(placeholder, item);
            item.style.display = 'none';

            function onMove(ev) {
                ghost.style.top = (ev.clientY - shiftY) + 'px';
                var items = Array.from(list.querySelectorAll('.etm-exp-item')).filter(function(r) { return r !== draggedEl; });
                for (var i = 0; i < items.length; i++) {
                    var rr = items[i].getBoundingClientRect();
                    if (ev.clientY < rr.top + rr.height / 2) { list.insertBefore(placeholder, items[i]); return; }
                }
                list.appendChild(placeholder);
            }
            function onUp() {
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                list.insertBefore(draggedEl, placeholder);
                draggedEl.style.display = '';
                if (placeholder.parentNode) placeholder.parentNode.removeChild(placeholder);
                if (ghost.parentNode) ghost.parentNode.removeChild(ghost);
                draggedEl = null;
                collectData();
                markDirty();
            }
            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp);
        });
    })();
    </script>
    <?php
}
