<?php
defined( 'ABSPATH' ) || exit;

// ── Save Handler (AJAX) ───────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_experiences', function () {
    check_ajax_referer( 'etm_experiences', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $raw = isset( $_POST['experiences'] ) ? wp_unslash( $_POST['experiences'] ) : '[]';
    $items = json_decode( $raw, true );
    if ( ! is_array( $items ) ) wp_send_json_error( 'Invalid data' );

    $clean = [];
    foreach ( $items as $item ) {
        $clean[] = [
            'label'    => sanitize_text_field( $item['label'] ?? '' ),
            'title'    => sanitize_text_field( $item['title'] ?? '' ),
            'desc'     => sanitize_text_field( $item['desc'] ?? '' ),
            'type'     => sanitize_key( $item['type'] ?? 'bespoke' ),
            'duration' => sanitize_key( $item['duration'] ?? 'bespoke' ),
            'url'      => esc_url_raw( $item['url'] ?? '' ),
            'image_id' => absint( $item['image_id'] ?? 0 ),
        ];
    }

    // Also save taxonomies (types + durations) from the current experiences
    $types = [];
    $durations = [];
    foreach ( $clean as $item ) {
        if ( $item['type'] && ! isset( $types[ $item['type'] ] ) ) {
            $types[ $item['type'] ] = ucfirst( str_replace( '-', ' ', $item['type'] ) );
        }
        if ( $item['duration'] && ! isset( $durations[ $item['duration'] ] ) ) {
            $durations[ $item['duration'] ] = $item['duration'];
        }
    }

    // Merge with any custom types/durations sent
    $custom_types = isset( $_POST['custom_types'] ) ? json_decode( wp_unslash( $_POST['custom_types'] ), true ) : [];
    $custom_durations = isset( $_POST['custom_durations'] ) ? json_decode( wp_unslash( $_POST['custom_durations'] ), true ) : [];
    if ( is_array( $custom_types ) ) {
        foreach ( $custom_types as $k => $v ) { $types[ sanitize_key( $k ) ] = sanitize_text_field( $v ); }
    }
    if ( is_array( $custom_durations ) ) {
        foreach ( $custom_durations as $k => $v ) { $durations[ sanitize_key( $k ) ] = sanitize_text_field( $v ); }
    }

    update_option( 'et_experience_taxonomies', [ 'types' => $types, 'durations' => $durations ] );
    update_option( 'et_experiences', $clean );
    wp_send_json_success( count( $clean ) . ' experiences saved' );
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_experiences_page(): void {
    $experiences = get_option( 'et_experiences', [] );
    if ( ! is_array( $experiences ) ) $experiences = [];

    $taxonomies = get_option( 'et_experience_taxonomies', [] );
    $type_options = ! empty( $taxonomies['types'] )
        ? $taxonomies['types']
        : [ 'bespoke' => 'Bespoke', 'golf' => 'Golf', 'culinary' => 'Culinary', 'adventure' => 'Adventure', 'family' => 'Family' ];
    $duration_options = ! empty( $taxonomies['durations'] )
        ? $taxonomies['durations']
        : [ '6-10' => '6-10 Days', '11-15' => '11-15 Days', 'bespoke' => 'Bespoke' ];
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">Experiences</h1>
        <p class="etm-page-desc">Add, edit, and reorder experiences. These appear on the homepage and the experiences page.</p>

        <div id="etm-exp-feedback" class="etm-notice" style="min-height:1.5em;"></div>

        <!-- Manage Types & Durations -->
        <div class="etm-tax-manager">
            <div class="etm-tax-group">
                <h3 class="etm-tax-group__title">Experience Types</h3>
                <div class="etm-tax-tags" id="etm-type-tags">
                    <?php foreach ( $type_options as $k => $v ) : ?>
                    <span class="etm-tax-tag" data-key="<?php echo esc_attr( $k ); ?>">
                        <?php echo esc_html( $v ); ?>
                        <button type="button" class="etm-tax-tag__remove" title="Remove">&times;</button>
                    </span>
                    <?php endforeach; ?>
                </div>
                <div class="etm-tax-add">
                    <input type="text" id="etm-add-type" class="etm-tax-input" placeholder="New type name">
                    <button type="button" class="button etm-tax-add-btn" id="etm-add-type-btn">Add</button>
                </div>
            </div>
            <div class="etm-tax-group">
                <h3 class="etm-tax-group__title">Duration Options</h3>
                <div class="etm-tax-tags" id="etm-duration-tags">
                    <?php foreach ( $duration_options as $k => $v ) : ?>
                    <span class="etm-tax-tag" data-key="<?php echo esc_attr( $k ); ?>">
                        <?php echo esc_html( $v ); ?>
                        <button type="button" class="etm-tax-tag__remove" title="Remove">&times;</button>
                    </span>
                    <?php endforeach; ?>
                </div>
                <div class="etm-tax-add">
                    <input type="text" id="etm-add-duration" class="etm-tax-input" placeholder="e.g. 16-20 Days">
                    <button type="button" class="button etm-tax-add-btn" id="etm-add-duration-btn">Add</button>
                </div>
            </div>
        </div>

        <form id="etm-exp-form">
            <?php wp_nonce_field( 'etm_experiences' ); ?>

            <div id="etm-exp-list">
                <!-- JS renders items here -->
            </div>

            <button type="button" class="etm-btn-add button" id="etm-exp-add">+ Add Experience</button>

            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary" id="etm-exp-save">Save Experiences</button>
                <span class="etm-dirty-dot" id="etm-exp-dirty" style="display:none;" title="Unsaved changes"></span>
                <span class="etm-exp-count" id="etm-exp-count"><?php echo count( $experiences ); ?> experiences</span>
            </div>
        </form>
    </div>

    <style>
    .etm-page-desc { color: #666; margin: 0 0 20px; font-size: 14px; }

    /* Taxonomy manager */
    .etm-tax-manager {
        display: flex;
        gap: 24px;
        margin-bottom: 24px;
        padding: 20px;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
    }
    .etm-tax-group { flex: 1; }
    .etm-tax-group__title { font-size: 13px; font-weight: 600; color: #1A4F31; margin: 0 0 10px; }
    .etm-tax-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
    .etm-tax-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: #f0f5f0;
        border: 1px solid #d0ddd0;
        border-radius: 14px;
        font-size: 12px;
        color: #1A4F31;
    }
    .etm-tax-tag__remove {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        color: #999;
        padding: 0;
        line-height: 1;
    }
    .etm-tax-tag__remove:hover { color: #c62828; }
    .etm-tax-add { display: flex; gap: 6px; }
    .etm-tax-input {
        padding: 4px 10px;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        font-size: 13px;
        flex: 1;
        max-width: 180px;
    }
    @media (max-width: 600px) { .etm-tax-manager { flex-direction: column; } }
    .etm-exp-item {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .etm-exp-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .etm-exp-item__header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: #fafafa;
        cursor: default;
    }
    .etm-exp-item__drag {
        font-size: 18px;
        color: #aaa;
        cursor: grab;
        padding: 4px;
        touch-action: none;
        user-select: none;
    }
    .etm-exp-item__drag:active { cursor: grabbing; }
    .etm-exp-item__thumb {
        width: 48px;
        height: 36px;
        border-radius: 4px;
        object-fit: cover;
        background: #e8e8e8;
        flex-shrink: 0;
    }
    .etm-exp-item__thumb--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #aaa;
    }
    .etm-exp-item__info {
        flex: 1;
        min-width: 0;
    }
    .etm-exp-item__title {
        font-size: 14px;
        font-weight: 600;
        color: #1A4F31;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .etm-exp-item__meta {
        font-size: 11px;
        color: #888;
    }
    .etm-exp-item__actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }
    .etm-exp-item__toggle,
    .etm-exp-item__delete {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        padding: 4px;
        color: #888;
        transition: color 0.2s;
    }
    .etm-exp-item__toggle:hover { color: #1A4F31; }
    .etm-exp-item__delete:hover { color: #c62828; }
    .etm-exp-item__body {
        display: none;
        padding: 16px;
        border-top: 1px solid #eee;
    }
    .etm-exp-item.is-open .etm-exp-item__body { display: block; }
    .etm-exp-item.is-open .etm-exp-item__toggle { transform: rotate(180deg); }
    .etm-exp-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }
    .etm-exp-row--full { grid-template-columns: 1fr; }
    .etm-exp-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #444;
        margin-bottom: 4px;
    }
    .etm-exp-field input,
    .etm-exp-field textarea,
    .etm-exp-field select {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        font-size: 13px;
        font-family: inherit;
    }
    .etm-exp-field textarea { resize: vertical; }
    .etm-exp-img-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .etm-exp-img-preview {
        width: 80px;
        height: 60px;
        border-radius: 4px;
        object-fit: cover;
        background: #f0f0f0;
    }
    .etm-btn-add {
        margin: 16px 0;
        font-size: 14px;
    }
    .etm-exp-count {
        font-size: 13px;
        color: #666;
        margin-left: auto;
    }
    .etm-exp-empty {
        text-align: center;
        padding: 48px 20px;
        color: #888;
        font-size: 15px;
    }
    .etm-drop-placeholder-exp {
        border: 2px dashed #1A4F31;
        border-radius: 8px;
        background: #f0f7f3;
        margin-bottom: 10px;
        transition: height 0.15s;
    }
    @media (max-width: 600px) {
        .etm-exp-row { grid-template-columns: 1fr; }
    }
    </style>

    <script>
    (function() {
        var list      = document.getElementById('etm-exp-list');
        var saveBtn   = document.getElementById('etm-exp-save');
        var addBtn    = document.getElementById('etm-exp-add');
        var feedback  = document.getElementById('etm-exp-feedback');
        var dirtyDot  = document.getElementById('etm-exp-dirty');
        var countEl   = document.getElementById('etm-exp-count');
        var form      = document.getElementById('etm-exp-form');
        var isDirty   = false;

        var typeOptions     = <?php echo wp_json_encode( $type_options ); ?>;
        var durationOptions = <?php echo wp_json_encode( $duration_options ); ?>;
        var experiences     = <?php echo wp_json_encode( $experiences ); ?>;

        // ── Taxonomy management ─────────────────────────────────
        function slugify(str) { return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); }

        function addTaxTag(container, key, label) {
            var tag = document.createElement('span');
            tag.className = 'etm-tax-tag';
            tag.dataset.key = key;
            tag.innerHTML = label + ' <button type="button" class="etm-tax-tag__remove" title="Remove">&times;</button>';
            container.appendChild(tag);
        }

        function refreshSelectsInList() {
            list.querySelectorAll('[data-field="type"]').forEach(function(sel) {
                var val = sel.value;
                sel.innerHTML = '';
                for (var k in typeOptions) {
                    sel.innerHTML += '<option value="' + k + '"' + (k === val ? ' selected' : '') + '>' + typeOptions[k] + '</option>';
                }
            });
            list.querySelectorAll('[data-field="duration"]').forEach(function(sel) {
                var val = sel.value;
                sel.innerHTML = '';
                for (var k in durationOptions) {
                    sel.innerHTML += '<option value="' + k + '"' + (k === val ? ' selected' : '') + '>' + durationOptions[k] + '</option>';
                }
            });
        }

        // Add type
        document.getElementById('etm-add-type-btn').addEventListener('click', function() {
            var input = document.getElementById('etm-add-type');
            var label = input.value.trim();
            if (!label) return;
            var key = slugify(label);
            if (typeOptions[key]) { input.value = ''; return; }
            typeOptions[key] = label;
            addTaxTag(document.getElementById('etm-type-tags'), key, label);
            input.value = '';
            refreshSelectsInList();
            markDirty();
        });

        // Add duration
        document.getElementById('etm-add-duration-btn').addEventListener('click', function() {
            var input = document.getElementById('etm-add-duration');
            var label = input.value.trim();
            if (!label) return;
            var key = slugify(label);
            if (durationOptions[key]) { input.value = ''; return; }
            durationOptions[key] = label;
            addTaxTag(document.getElementById('etm-duration-tags'), key, label);
            input.value = '';
            refreshSelectsInList();
            markDirty();
        });

        // Enter key on inputs
        document.getElementById('etm-add-type').addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('etm-add-type-btn').click(); } });
        document.getElementById('etm-add-duration').addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('etm-add-duration-btn').click(); } });

        // Remove tags (delegated)
        document.querySelector('.etm-tax-manager').addEventListener('click', function(e) {
            var removeBtn = e.target.closest('.etm-tax-tag__remove');
            if (!removeBtn) return;
            var tag = removeBtn.closest('.etm-tax-tag');
            var key = tag.dataset.key;
            var isType = tag.closest('#etm-type-tags');
            if (isType) { delete typeOptions[key]; } else { delete durationOptions[key]; }
            tag.remove();
            refreshSelectsInList();
            markDirty();
        });

        function markDirty() {
            if (!isDirty) { isDirty = true; dirtyDot.style.display = ''; saveBtn.classList.add('etm-btn-save--dirty'); }
        }
        function markClean() {
            isDirty = false; dirtyDot.style.display = 'none'; saveBtn.classList.remove('etm-btn-save--dirty');
        }
        window.addEventListener('beforeunload', function(e) { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });

        function makeSelect(options, value, name) {
            var html = '<select data-field="' + name + '">';
            for (var k in options) {
                html += '<option value="' + k + '"' + (k === value ? ' selected' : '') + '>' + options[k] + '</option>';
            }
            return html + '</select>';
        }

        function getThumbHtml(imgId) {
            if (imgId) {
                return '<img class="etm-exp-img-preview" src="" data-img-id="' + imgId + '" alt="">';
            }
            return '<div class="etm-exp-item__thumb etm-exp-item__thumb--empty">No img</div>';
        }

        function renderItem(exp, idx) {
            var div = document.createElement('div');
            div.className = 'etm-exp-item';
            div.dataset.idx = idx;

            var thumbSrc = exp.image_id ? '' : '';
            var thumbEl = exp.image_id
                ? '<img class="etm-exp-item__thumb" src="" data-resolve-id="' + exp.image_id + '" alt="">'
                : '<div class="etm-exp-item__thumb etm-exp-item__thumb--empty">IMG</div>';

            div.innerHTML =
                '<div class="etm-exp-item__header">' +
                    '<span class="etm-exp-item__drag" title="Drag to reorder">&#8942;</span>' +
                    thumbEl +
                    '<div class="etm-exp-item__info">' +
                        '<div class="etm-exp-item__title">' + (exp.title || 'Untitled Experience') + '</div>' +
                        '<div class="etm-exp-item__meta">' + (typeOptions[exp.type] || exp.type) + ' &middot; ' + (durationOptions[exp.duration] || exp.duration) + '</div>' +
                    '</div>' +
                    '<div class="etm-exp-item__actions">' +
                        '<button type="button" class="etm-exp-item__toggle" title="Expand">&#9662;</button>' +
                        '<button type="button" class="etm-exp-item__delete" title="Delete">&times;</button>' +
                    '</div>' +
                '</div>' +
                '<div class="etm-exp-item__body">' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Label</label><input type="text" data-field="label" value="' + esc(exp.label) + '" placeholder="e.g. Ancestry & Roots"></div>' +
                        '<div class="etm-exp-field"><label>Title</label><input type="text" data-field="title" value="' + esc(exp.title) + '" placeholder="e.g. Trace Your Irish Heritage"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field"><label>Description</label><textarea data-field="desc" rows="2" placeholder="One-line description">' + esc(exp.desc) + '</textarea></div>' +
                    '</div>' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Type</label>' + makeSelect(typeOptions, exp.type, 'type') + '</div>' +
                        '<div class="etm-exp-field"><label>Duration</label>' + makeSelect(durationOptions, exp.duration, 'duration') + '</div>' +
                    '</div>' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Link URL</label><input type="url" data-field="url" value="' + esc(exp.url) + '" placeholder="/bespoke-tours/"></div>' +
                        '<div class="etm-exp-field"><label>Image</label>' +
                            '<div class="etm-exp-img-row">' +
                                '<img class="etm-exp-img-preview" src="" data-img-preview="1" alt="" style="' + (exp.image_id ? '' : 'display:none') + '">' +
                                '<input type="hidden" data-field="image_id" value="' + (exp.image_id || '') + '">' +
                                '<button type="button" class="button etm-exp-upload">' + (exp.image_id ? 'Change' : 'Upload') + '</button>' +
                                '<button type="button" class="button-link-delete etm-exp-remove-img" style="' + (exp.image_id ? '' : 'display:none') + '">Remove</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            return div;
        }

        function esc(str) {
            var d = document.createElement('div');
            d.textContent = str || '';
            return d.innerHTML.replace(/"/g, '&quot;');
        }

        function renderAll() {
            list.innerHTML = '';
            if (experiences.length === 0) {
                list.innerHTML = '<div class="etm-exp-empty">No experiences yet. Click "+ Add Experience" to create one.</div>';
            } else {
                experiences.forEach(function(exp, i) {
                    list.appendChild(renderItem(exp, i));
                });
                resolveImages();
            }
            updateCount();
        }

        function resolveImages() {
            list.querySelectorAll('[data-resolve-id]').forEach(function(img) {
                var id = img.dataset.resolveId;
                if (id && window.wp && wp.media && wp.media.attachment) {
                    var att = wp.media.attachment(parseInt(id));
                    att.fetch().then(function() {
                        var url = att.get('sizes') && att.get('sizes').thumbnail ? att.get('sizes').thumbnail.url : att.get('url');
                        img.src = url;
                    });
                }
            });
            list.querySelectorAll('[data-img-preview]').forEach(function(img) {
                var input = img.parentNode.querySelector('[data-field="image_id"]');
                if (input && input.value && window.wp && wp.media && wp.media.attachment) {
                    var att = wp.media.attachment(parseInt(input.value));
                    att.fetch().then(function() {
                        var url = att.get('sizes') && att.get('sizes').thumbnail ? att.get('sizes').thumbnail.url : att.get('url');
                        img.src = url;
                    });
                }
            });
        }

        function updateCount() {
            countEl.textContent = experiences.length + ' experience' + (experiences.length !== 1 ? 's' : '');
        }

        function collectData() {
            var items = list.querySelectorAll('.etm-exp-item');
            var data = [];
            items.forEach(function(item) {
                var obj = {};
                item.querySelectorAll('[data-field]').forEach(function(el) {
                    obj[el.dataset.field] = el.value;
                });
                data.push(obj);
            });
            experiences = data;
            return data;
        }

        // Render initial state
        renderAll();

        // Add new experience
        addBtn.addEventListener('click', function() {
            experiences.push({ label: '', title: '', desc: '', type: 'bespoke', duration: 'bespoke', url: '', image_id: 0 });
            var item = renderItem(experiences[experiences.length - 1], experiences.length - 1);
            var empty = list.querySelector('.etm-exp-empty');
            if (empty) empty.remove();
            list.appendChild(item);
            item.classList.add('is-open');
            item.querySelector('[data-field="label"]').focus();
            updateCount();
            markDirty();
        });

        // Delegate: toggle, delete, upload, remove, field changes
        list.addEventListener('click', function(e) {
            var toggle = e.target.closest('.etm-exp-item__toggle');
            if (toggle) {
                toggle.closest('.etm-exp-item').classList.toggle('is-open');
                return;
            }
            var del = e.target.closest('.etm-exp-item__delete');
            if (del) {
                var item = del.closest('.etm-exp-item');
                if (confirm('Delete this experience?')) {
                    item.remove();
                    collectData();
                    updateCount();
                    markDirty();
                    if (experiences.length === 0) renderAll();
                }
                return;
            }
            var upload = e.target.closest('.etm-exp-upload');
            if (upload) {
                var body = upload.closest('.etm-exp-item__body');
                var frame = wp.media({ title: 'Select Experience Image', button: { text: 'Use this image' }, multiple: false });
                frame.on('select', function() {
                    var att = frame.state().get('selection').first().toJSON();
                    var input = body.querySelector('[data-field="image_id"]');
                    var preview = body.querySelector('[data-img-preview]');
                    var removeBtn = body.querySelector('.etm-exp-remove-img');
                    input.value = att.id;
                    preview.src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                    preview.style.display = '';
                    removeBtn.style.display = '';
                    upload.textContent = 'Change';
                    // Update header thumb
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
                // Live-update header title
                if (e.target.dataset.field === 'title') {
                    var item = e.target.closest('.etm-exp-item');
                    item.querySelector('.etm-exp-item__title').textContent = e.target.value || 'Untitled Experience';
                }
            }
        });
        list.addEventListener('change', function(e) {
            if (e.target.dataset.field) {
                markDirty();
                if (e.target.dataset.field === 'type' || e.target.dataset.field === 'duration') {
                    var item = e.target.closest('.etm-exp-item');
                    var t = item.querySelector('[data-field="type"]').value;
                    var d = item.querySelector('[data-field="duration"]').value;
                    item.querySelector('.etm-exp-item__meta').innerHTML = (typeOptions[t] || t) + ' &middot; ' + (durationOptions[d] || d);
                }
            }
        });

        // Save
        saveBtn.addEventListener('click', function() {
            var data = collectData();
            var fd = new FormData(form);
            fd.append('action', 'etm_save_experiences');
            fd.append('experiences', JSON.stringify(data));
            fd.append('custom_types', JSON.stringify(typeOptions));
            fd.append('custom_durations', JSON.stringify(durationOptions));

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
                        setTimeout(function() { saveBtn.textContent = 'Save Experiences'; saveBtn.disabled = false; }, 2000);
                    } else {
                        saveBtn.textContent = 'Save Experiences'; saveBtn.disabled = false;
                        feedback.textContent = 'Error: ' + (res.data || 'unknown');
                        feedback.className = 'etm-notice etm-notice--error';
                    }
                })
                .catch(function(err) {
                    saveBtn.textContent = 'Save Experiences'; saveBtn.disabled = false;
                    feedback.textContent = 'Network error';
                    feedback.className = 'etm-notice etm-notice--error';
                });
        });

        // Drag reorder (pointer events, same approach as homepage)
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
                    if (ev.clientY < rr.top + rr.height / 2) {
                        list.insertBefore(placeholder, items[i]);
                        return;
                    }
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
