<?php
/**
 * Admin: Key Experiences
 *
 * The 22 named "Key experiences" the client called out in
 * Full list of experiences.txt — Midleton Distillery, Old Head of Kinsale,
 * Ring of Kerry, Slea Head Drive, Foxy John's, Cliffs of Moher (via Doolin),
 * Galway, Connemara, Ashford Castle, Giant's Causeway, Black Taxi Tour,
 * Titanic Quarter, etc. Stored in the et_key_experiences wp_options array
 * and rendered as a featured grid on /experiences/ below the 11 regions.
 *
 * Same UX as Hotels / Regions / Golf Courses: collapsible card-list with
 * drag-reorder, image picker, sticky save bar.
 */
defined( 'ABSPATH' ) || exit;

// ── Save Handler (AJAX) ──────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_key_experiences', function () {
    check_ajax_referer( 'etm_key_experiences', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $raw   = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '[]';
    $items = json_decode( $raw, true );
    if ( ! is_array( $items ) ) wp_send_json_error( 'Invalid data' );

    $clean = [];
    foreach ( $items as $item ) {
        $clean[] = [
            'name'           => sanitize_text_field( $item['name']   ?? '' ),
            'region'         => sanitize_text_field( $item['region'] ?? '' ),
            'desc'           => sanitize_textarea_field( $item['desc'] ?? '' ),
            'url'            => esc_url_raw( $item['url'] ?? '' ),
            'image_id'       => absint( $item['image_id'] ?? 0 ),
            'image_filename' => sanitize_text_field( $item['image_filename'] ?? '' ),
        ];
    }
    update_option( 'et_key_experiences', $clean );
    wp_send_json_success( count( $clean ) . ' key experiences saved' );
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_key_experiences_page(): void {
    $items = get_option( 'et_key_experiences', [] );
    if ( ! is_array( $items ) ) $items = [];
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title"><?php echo etm_lucide( 'tour', 22 ); ?> Key Experiences</h1>
        <p class="etm-page-desc">The named experiences shown as a featured grid on <code>/experiences/</code>, below the 11 region tiles. Each card shows an image, the experience name, the county tag, and a short blurb. Drag to reorder.</p>

        <div id="etm-ke-feedback" class="etm-notice" style="min-height:1.5em;"></div>

        <form id="etm-ke-form">
            <?php wp_nonce_field( 'etm_key_experiences' ); ?>

            <div id="etm-ke-list"></div>

            <button type="button" class="etm-btn-add button" id="etm-ke-add">+ Add Key Experience</button>

            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary" id="etm-ke-save">Save Key Experiences</button>
                <span class="etm-dirty-dot" id="etm-ke-dirty" style="display:none;" title="Unsaved changes"></span>
                <span class="etm-exp-count" id="etm-ke-count"><?php echo count( $items ); ?> items</span>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var list     = document.getElementById('etm-ke-list');
        var saveBtn  = document.getElementById('etm-ke-save');
        var addBtn   = document.getElementById('etm-ke-add');
        var feedback = document.getElementById('etm-ke-feedback');
        var dirtyDot = document.getElementById('etm-ke-dirty');
        var countEl  = document.getElementById('etm-ke-count');
        var form     = document.getElementById('etm-ke-form');
        var isDirty  = false;

        var items = <?php echo wp_json_encode( $items ); ?>;

        function markDirty() { if (!isDirty) { isDirty = true; dirtyDot.style.display = ''; saveBtn.classList.add('etm-btn-save--dirty'); } }
        function markClean() { isDirty = false; dirtyDot.style.display = 'none'; saveBtn.classList.remove('etm-btn-save--dirty'); }
        window.addEventListener('beforeunload', function (e) { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });

        function esc(str) { var d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML.replace(/"/g, '&quot;'); }

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
                        '<div class="etm-exp-item__title">' + (item.name || 'Untitled') + '</div>' +
                        '<div class="etm-exp-item__meta">' + (item.region || '—') + '</div>' +
                    '</div>' +
                    '<div class="etm-exp-item__actions">' +
                        '<button type="button" class="etm-exp-item__toggle" title="Expand">&#9662;</button>' +
                        '<button type="button" class="etm-exp-item__delete" title="Delete">&times;</button>' +
                    '</div>' +
                '</div>' +
                '<div class="etm-exp-item__body">' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Name</label><input type="text" data-field="name" value="' + esc(item.name) + '" placeholder="Old Head of Kinsale"></div>' +
                        '<div class="etm-exp-field"><label>Region / County</label><input type="text" data-field="region" value="' + esc(item.region) + '" placeholder="Co. Cork"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Description</label><textarea data-field="desc" rows="2" placeholder="Short, evocative blurb (one or two sentences)">' + esc(item.desc) + '</textarea></div>' +
                    '</div>' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Link URL (optional)</label><input type="text" data-field="url" value="' + esc(item.url) + '" placeholder="/experiences/ or full URL"></div>' +
                        '<div class="etm-exp-field"><label>Bundled image filename (fallback)</label><input type="text" data-field="image_filename" value="' + esc(item.image_filename) + '" placeholder="cliffs-of-moher.jpg"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Image (Media Library)</label>' +
                            '<div class="etm-exp-img-row">' +
                                '<img class="etm-exp-img-preview" src="" data-resolve-id="' + (item.image_id || '') + '" alt=""' + (item.image_id ? '' : ' style="display:none"') + '>' +
                                '<input type="hidden" data-field="image_id" value="' + (item.image_id || '') + '">' +
                                '<button type="button" class="button etm-exp-upload">' + (item.image_id ? 'Change' : 'Upload') + '</button>' +
                                '<button type="button" class="button-link-delete etm-exp-remove-img"' + (item.image_id ? '' : ' style="display:none"') + '>Remove</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            return div;
        }

        function renderAll() {
            list.innerHTML = '';
            items.forEach(function (item, i) { list.appendChild(renderItem(item, i)); });
            countEl.textContent = items.length + ' items';
            // Resolve any pending image_id thumbs/previews
            list.querySelectorAll('[data-resolve-id]').forEach(function (img) {
                var id = parseInt(img.dataset.resolveId, 10);
                if (!id) return;
                wp.media.attachment(id).fetch().then(function (att) {
                    var url = att.attributes && att.attributes.sizes && att.attributes.sizes.medium
                        ? att.attributes.sizes.medium.url
                        : (att.attributes ? att.attributes.url : '');
                    if (url) { img.src = url; img.style.display = ''; }
                });
            });
        }

        // Track edits from inputs (delegated so dynamic rows work)
        list.addEventListener('input', function (e) {
            var t = e.target;
            if (!t.dataset.field) return;
            var row = t.closest('.etm-exp-item');
            if (!row) return;
            items[ parseInt(row.dataset.idx, 10) ][ t.dataset.field ] = t.value;
            markDirty();
        });

        // Toggle expand / delete
        list.addEventListener('click', function (e) {
            var t = e.target;
            if (t.classList.contains('etm-exp-item__toggle')) {
                t.closest('.etm-exp-item').classList.toggle('is-open');
                return;
            }
            if (t.classList.contains('etm-exp-item__delete')) {
                if (!confirm('Delete this key experience?')) return;
                var row = t.closest('.etm-exp-item');
                items.splice( parseInt(row.dataset.idx, 10), 1 );
                renderAll();
                markDirty();
                return;
            }
            // Image picker
            if (t.classList.contains('etm-exp-upload')) {
                var row = t.closest('.etm-exp-item');
                var idx = parseInt(row.dataset.idx, 10);
                var frame = wp.media({ title: 'Select image for this experience', button: { text: 'Use this image' }, multiple: false });
                frame.on('select', function () {
                    var att = frame.state().get('selection').first().toJSON();
                    items[idx].image_id = att.id;
                    var preview = row.querySelector('.etm-exp-img-preview');
                    if (preview) {
                        preview.src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                        preview.style.display = '';
                    }
                    row.querySelector('[data-field="image_id"]').value = att.id;
                    var thumb = row.querySelector('.etm-exp-item__thumb');
                    if (thumb) {
                        thumb.outerHTML = '<img class="etm-exp-item__thumb" src="' + (att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url) + '" alt="">';
                    }
                    var rm = row.querySelector('.etm-exp-remove-img');
                    if (rm) rm.style.display = '';
                    t.textContent = 'Change';
                    markDirty();
                });
                frame.open();
                return;
            }
            if (t.classList.contains('etm-exp-remove-img')) {
                var row = t.closest('.etm-exp-item');
                var idx = parseInt(row.dataset.idx, 10);
                items[idx].image_id = 0;
                row.querySelector('[data-field="image_id"]').value = '';
                var preview = row.querySelector('.etm-exp-img-preview');
                if (preview) preview.style.display = 'none';
                t.style.display = 'none';
                var upload = row.querySelector('.etm-exp-upload');
                if (upload) upload.textContent = 'Upload';
                markDirty();
                return;
            }
        });

        // Add new
        addBtn.addEventListener('click', function () {
            items.push({ name: '', region: '', desc: '', url: '', image_id: 0, image_filename: '' });
            renderAll();
            // open the freshly added row
            var rows = list.querySelectorAll('.etm-exp-item');
            if (rows.length) rows[ rows.length - 1 ].classList.add('is-open');
            markDirty();
        });

        // Save
        saveBtn.addEventListener('click', function () {
            var fd = new FormData(form);
            fd.append('action', 'etm_save_key_experiences');
            fd.append('items', JSON.stringify(items));

            saveBtn.textContent = 'Saving…'; saveBtn.disabled = true;
            feedback.textContent = ''; feedback.className = 'etm-notice';

            fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        markClean();
                        saveBtn.textContent = 'Saved';
                        feedback.textContent = res.data;
                        feedback.className = 'etm-notice etm-notice--success';
                        setTimeout(function () { saveBtn.textContent = 'Save Key Experiences'; saveBtn.disabled = false; }, 2000);
                    } else {
                        saveBtn.textContent = 'Save Key Experiences'; saveBtn.disabled = false;
                        feedback.textContent = 'Error: ' + (res.data || 'unknown');
                        feedback.className = 'etm-notice etm-notice--error';
                    }
                })
                .catch(function (err) {
                    saveBtn.textContent = 'Save Key Experiences'; saveBtn.disabled = false;
                    feedback.textContent = 'Network error: ' + err;
                    feedback.className = 'etm-notice etm-notice--error';
                });
        });

        renderAll();
    })();
    </script>
    <?php
}
