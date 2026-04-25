<?php
defined( 'ABSPATH' ) || exit;

// ── Save Handler (AJAX) ───────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_hotels', function () {
    check_ajax_referer( 'etm_hotels', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $raw = isset( $_POST['hotels'] ) ? wp_unslash( $_POST['hotels'] ) : '[]';
    $items = json_decode( $raw, true );
    if ( ! is_array( $items ) ) wp_send_json_error( 'Invalid data' );

    $clean = [];
    foreach ( $items as $item ) {
        $clean[] = [
            'name'     => sanitize_text_field( $item['name'] ?? '' ),
            'location' => sanitize_text_field( $item['location'] ?? '' ),
            'desc'     => sanitize_text_field( $item['desc'] ?? '' ),
            'category' => sanitize_key( $item['category'] ?? 'castle' ),
            'url'      => esc_url_raw( $item['url'] ?? '' ),
            'image_id' => absint( $item['image_id'] ?? 0 ),
        ];
    }

    update_option( 'et_hotels', $clean );
    wp_send_json_success( count( $clean ) . ' hotels saved' );
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_hotels_page(): void {
    $hotels = get_option( 'et_hotels', [] );
    if ( ! is_array( $hotels ) ) $hotels = [];

    $category_options = [
        'castle'   => 'Castle & Estate',
        'boutique' => 'Boutique & Country House',
        'coastal'  => 'Luxury Coastal & Scenic',
    ];
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">Hotels &amp; Accommodation</h1>
        <p class="etm-page-desc">Add, edit, and reorder hotels. These appear on the Accommodation page and can be referenced across the site.</p>

        <div id="etm-hotel-feedback" class="etm-notice" style="min-height:1.5em;"></div>

        <form id="etm-hotel-form">
            <?php wp_nonce_field( 'etm_hotels' ); ?>

            <div id="etm-hotel-list"></div>

            <button type="button" class="etm-btn-add button" id="etm-hotel-add">+ Add Hotel</button>

            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary" id="etm-hotel-save">Save Hotels</button>
                <span class="etm-dirty-dot" id="etm-hotel-dirty" style="display:none;" title="Unsaved changes"></span>
                <span class="etm-exp-count" id="etm-hotel-count"><?php echo count( $hotels ); ?> hotels</span>
            </div>
        </form>
    </div>

    <script>
    (function() {
        var list      = document.getElementById('etm-hotel-list');
        var saveBtn   = document.getElementById('etm-hotel-save');
        var addBtn    = document.getElementById('etm-hotel-add');
        var feedback  = document.getElementById('etm-hotel-feedback');
        var dirtyDot  = document.getElementById('etm-hotel-dirty');
        var countEl   = document.getElementById('etm-hotel-count');
        var form      = document.getElementById('etm-hotel-form');
        var isDirty   = false;

        var categoryOptions = <?php echo wp_json_encode( $category_options ); ?>;
        var hotels = <?php echo wp_json_encode( $hotels ); ?>;

        function markDirty() { if (!isDirty) { isDirty = true; dirtyDot.style.display = ''; saveBtn.classList.add('etm-btn-save--dirty'); } }
        function markClean() { isDirty = false; dirtyDot.style.display = 'none'; saveBtn.classList.remove('etm-btn-save--dirty'); }
        window.addEventListener('beforeunload', function(e) { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });

        function esc(str) { var d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML.replace(/"/g, '&quot;'); }

        function makeSelect(options, value, name) {
            var html = '<select data-field="' + name + '">';
            for (var k in options) html += '<option value="' + k + '"' + (k === value ? ' selected' : '') + '>' + options[k] + '</option>';
            return html + '</select>';
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
                        '<div class="etm-exp-item__title">' + (item.name || 'Untitled Hotel') + '</div>' +
                        '<div class="etm-exp-item__meta">' + (item.location || '') + ' &middot; ' + (categoryOptions[item.category] || item.category) + '</div>' +
                    '</div>' +
                    '<div class="etm-exp-item__actions">' +
                        '<button type="button" class="etm-exp-item__toggle" title="Expand">&#9662;</button>' +
                        '<button type="button" class="etm-exp-item__delete" title="Delete">&times;</button>' +
                    '</div>' +
                '</div>' +
                '<div class="etm-exp-item__body">' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Hotel Name</label><input type="text" data-field="name" value="' + esc(item.name) + '" placeholder="e.g. Dromoland Castle"></div>' +
                        '<div class="etm-exp-field"><label>Location</label><input type="text" data-field="location" value="' + esc(item.location) + '" placeholder="e.g. Co. Clare"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Description</label><textarea data-field="desc" rows="2" placeholder="Short description of the hotel">' + esc(item.desc) + '</textarea></div>' +
                    '</div>' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Category</label>' + makeSelect(categoryOptions, item.category, 'category') + '</div>' +
                        '<div class="etm-exp-field"><label>Link URL (optional)</label><input type="url" data-field="url" value="' + esc(item.url) + '" placeholder="https://..."></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Image</label>' +
                            '<div class="etm-exp-img-row">' +
                                '<img class="etm-exp-img-preview" src="" data-img-preview="1" alt="" style="' + (item.image_id ? '' : 'display:none') + '">' +
                                '<input type="hidden" data-field="image_id" value="' + (item.image_id || '') + '">' +
                                '<button type="button" class="button etm-exp-upload">' + (item.image_id ? 'Change' : 'Upload') + '</button>' +
                                '<button type="button" class="button-link-delete etm-exp-remove-img" style="' + (item.image_id ? '' : 'display:none') + '">Remove</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            return div;
        }

        function renderAll() {
            list.innerHTML = '';
            if (hotels.length === 0) {
                list.innerHTML = '<div class="etm-exp-empty">No hotels yet. Click "+ Add Hotel" to create one.</div>';
            } else {
                hotels.forEach(function(item, i) { list.appendChild(renderItem(item, i)); });
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
            countEl.textContent = hotels.length + ' hotel' + (hotels.length !== 1 ? 's' : '');
        }

        function collectData() {
            var data = [];
            list.querySelectorAll('.etm-exp-item').forEach(function(item) {
                var obj = {};
                item.querySelectorAll('[data-field]').forEach(function(el) { obj[el.dataset.field] = el.value; });
                data.push(obj);
            });
            hotels = data;
            return data;
        }

        renderAll();

        addBtn.addEventListener('click', function() {
            hotels.push({ name: '', location: '', desc: '', category: 'castle', url: '', image_id: 0 });
            var item = renderItem(hotels[hotels.length - 1], hotels.length - 1);
            var empty = list.querySelector('.etm-exp-empty');
            if (empty) empty.remove();
            list.appendChild(item);
            item.classList.add('is-open');
            item.querySelector('[data-field="name"]').focus();
            updateCount();
            markDirty();
        });

        list.addEventListener('click', function(e) {
            var toggle = e.target.closest('.etm-exp-item__toggle');
            if (toggle) { toggle.closest('.etm-exp-item').classList.toggle('is-open'); return; }

            var del = e.target.closest('.etm-exp-item__delete');
            if (del) {
                if (confirm('Delete this hotel?')) {
                    del.closest('.etm-exp-item').remove();
                    collectData();
                    updateCount();
                    markDirty();
                    if (hotels.length === 0) renderAll();
                }
                return;
            }

            var upload = e.target.closest('.etm-exp-upload');
            if (upload) {
                var body = upload.closest('.etm-exp-item__body');
                var frame = wp.media({ title: 'Select Hotel Image', button: { text: 'Use this image' }, multiple: false });
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
                if (e.target.dataset.field === 'name') {
                    e.target.closest('.etm-exp-item').querySelector('.etm-exp-item__title').textContent = e.target.value || 'Untitled Hotel';
                }
            }
        });
        list.addEventListener('change', function(e) {
            if (e.target.dataset.field) {
                markDirty();
                if (e.target.dataset.field === 'category' || e.target.dataset.field === 'location') {
                    var item = e.target.closest('.etm-exp-item');
                    var loc = item.querySelector('[data-field="location"]').value;
                    var cat = item.querySelector('[data-field="category"]').value;
                    item.querySelector('.etm-exp-item__meta').innerHTML = (loc || '') + ' &middot; ' + (categoryOptions[cat] || cat);
                }
            }
        });

        saveBtn.addEventListener('click', function() {
            var data = collectData();
            var fd = new FormData(form);
            fd.append('action', 'etm_save_hotels');
            fd.append('hotels', JSON.stringify(data));

            saveBtn.textContent = 'Saving\u2026';
            saveBtn.disabled = true;

            fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        markClean();
                        saveBtn.textContent = 'Saved \u2714';
                        feedback.textContent = res.data;
                        feedback.className = 'etm-notice etm-notice--success';
                        setTimeout(function() { saveBtn.textContent = 'Save Hotels'; saveBtn.disabled = false; }, 2000);
                    } else {
                        saveBtn.textContent = 'Save Hotels'; saveBtn.disabled = false;
                        feedback.textContent = 'Error: ' + (res.data || 'unknown');
                        feedback.className = 'etm-notice etm-notice--error';
                    }
                })
                .catch(function() {
                    saveBtn.textContent = 'Save Hotels'; saveBtn.disabled = false;
                    feedback.textContent = 'Network error';
                    feedback.className = 'etm-notice etm-notice--error';
                });
        });

        // Drag reorder (pointer events)
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
