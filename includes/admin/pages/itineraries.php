<?php
defined( 'ABSPATH' ) || exit;

// ── Save Handler (AJAX) ───────────────────────────────────────────────────────
add_action( 'wp_ajax_etm_save_itineraries', function () {
    check_ajax_referer( 'etm_itineraries', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorised', 403 );

    $raw = isset( $_POST['itineraries'] ) ? wp_unslash( $_POST['itineraries'] ) : '[]';
    $items = json_decode( $raw, true );
    if ( ! is_array( $items ) ) wp_send_json_error( 'Invalid data' );

    $clean = [];
    foreach ( $items as $item ) {
        $highlights = [];
        if ( ! empty( $item['highlights'] ) && is_array( $item['highlights'] ) ) {
            foreach ( $item['highlights'] as $h ) {
                $h = sanitize_text_field( $h );
                if ( $h ) $highlights[] = $h;
            }
        }
        $clean[] = [
            'name'       => sanitize_text_field( $item['name'] ?? '' ),
            'meta'       => sanitize_text_field( $item['meta'] ?? '' ),
            'route'      => sanitize_text_field( $item['route'] ?? '' ),
            'highlights' => $highlights,
            'type'       => sanitize_key( $item['type'] ?? 'bespoke' ),
        ];
    }

    update_option( 'et_itineraries', $clean );
    wp_send_json_success( count( $clean ) . ' itineraries saved' );
} );

// ── Render ────────────────────────────────────────────────────────────────────
function etm_itineraries_page(): void {
    $itineraries = get_option( 'et_itineraries', [] );
    if ( ! is_array( $itineraries ) ) $itineraries = [];

    $type_options = [
        'bespoke' => 'Bespoke Tour',
        'golf'    => 'Golf Tour',
    ];
    ?>
    <div class="wrap etm-wrap">
        <h1 class="etm-page-title">Sample Itineraries</h1>
        <p class="etm-page-desc">Add sample itineraries with routes and highlights. Bespoke itineraries appear on the Bespoke Tours page. Golf itineraries appear on the Golf Tours page.</p>

        <div id="etm-itin-feedback" class="etm-notice" style="min-height:1.5em;"></div>

        <form id="etm-itin-form">
            <?php wp_nonce_field( 'etm_itineraries' ); ?>

            <div id="etm-itin-list"></div>

            <button type="button" class="etm-btn-add button" id="etm-itin-add">+ Add Itinerary</button>

            <div class="etm-actions etm-actions--sticky">
                <button type="button" class="etm-btn-save button-primary" id="etm-itin-save">Save Itineraries</button>
                <span class="etm-dirty-dot" id="etm-itin-dirty" style="display:none;" title="Unsaved changes"></span>
                <span class="etm-exp-count" id="etm-itin-count"><?php echo count( $itineraries ); ?> itineraries</span>
            </div>
        </form>
    </div>

    <script>
    (function() {
        var list      = document.getElementById('etm-itin-list');
        var saveBtn   = document.getElementById('etm-itin-save');
        var addBtn    = document.getElementById('etm-itin-add');
        var feedback  = document.getElementById('etm-itin-feedback');
        var dirtyDot  = document.getElementById('etm-itin-dirty');
        var countEl   = document.getElementById('etm-itin-count');
        var form      = document.getElementById('etm-itin-form');
        var isDirty   = false;

        var typeOptions = <?php echo wp_json_encode( $type_options ); ?>;
        var itineraries = <?php echo wp_json_encode( $itineraries ); ?>;

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

            var highlightsHtml = (item.highlights || []).map(function(h) {
                return '<div class="etm-highlight-row"><input type="text" class="etm-highlight-input" value="' + esc(h) + '" placeholder="Highlight"><button type="button" class="etm-highlight-remove">&times;</button></div>';
            }).join('');

            div.innerHTML =
                '<div class="etm-exp-item__header">' +
                    '<span class="etm-exp-item__drag" title="Drag to reorder">&#8942;</span>' +
                    '<div class="etm-exp-item__info">' +
                        '<div class="etm-exp-item__title">' + (item.name || 'Untitled Itinerary') + '</div>' +
                        '<div class="etm-exp-item__meta">' + (typeOptions[item.type] || item.type) + ' &middot; ' + (item.meta || 'No duration') + '</div>' +
                    '</div>' +
                    '<div class="etm-exp-item__actions">' +
                        '<button type="button" class="etm-exp-item__toggle" title="Expand">&#9662;</button>' +
                        '<button type="button" class="etm-exp-item__delete" title="Delete">&times;</button>' +
                    '</div>' +
                '</div>' +
                '<div class="etm-exp-item__body">' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Itinerary Name</label><input type="text" data-field="name" value="' + esc(item.name) + '" placeholder="e.g. The Ancestral Journey"></div>' +
                        '<div class="etm-exp-field"><label>Duration / Meta</label><input type="text" data-field="meta" value="' + esc(item.meta) + '" placeholder="e.g. 8 Days"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row">' +
                        '<div class="etm-exp-field"><label>Type</label>' + makeSelect(typeOptions, item.type, 'type') + '</div>' +
                        '<div class="etm-exp-field"><label>Route</label><input type="text" data-field="route" value="' + esc(item.route) + '" placeholder="Dublin → Galway → Connemara"></div>' +
                    '</div>' +
                    '<div class="etm-exp-row etm-exp-row--full">' +
                        '<div class="etm-exp-field">' +
                            '<label>Highlights</label>' +
                            '<div class="etm-highlights" data-field="highlights">' + highlightsHtml + '</div>' +
                            '<button type="button" class="button etm-highlight-add">+ Add Highlight</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            return div;
        }

        function renderAll() {
            list.innerHTML = '';
            if (itineraries.length === 0) {
                list.innerHTML = '<div class="etm-exp-empty">No itineraries yet. Click "+ Add Itinerary" to create one.</div>';
            } else {
                itineraries.forEach(function(item, i) { list.appendChild(renderItem(item, i)); });
            }
            updateCount();
        }

        function updateCount() {
            countEl.textContent = itineraries.length + ' itinerar' + (itineraries.length !== 1 ? 'ies' : 'y');
        }

        function collectData() {
            var data = [];
            list.querySelectorAll('.etm-exp-item').forEach(function(item) {
                var obj = { highlights: [] };
                item.querySelectorAll('[data-field]').forEach(function(el) {
                    if (el.dataset.field === 'highlights') {
                        el.querySelectorAll('.etm-highlight-input').forEach(function(inp) {
                            if (inp.value.trim()) obj.highlights.push(inp.value);
                        });
                    } else {
                        obj[el.dataset.field] = el.value;
                    }
                });
                data.push(obj);
            });
            itineraries = data;
            return data;
        }

        renderAll();

        addBtn.addEventListener('click', function() {
            itineraries.push({ name: '', meta: '', route: '', highlights: [], type: 'bespoke' });
            var item = renderItem(itineraries[itineraries.length - 1], itineraries.length - 1);
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
                if (confirm('Delete this itinerary?')) {
                    del.closest('.etm-exp-item').remove();
                    collectData();
                    updateCount();
                    markDirty();
                    if (itineraries.length === 0) renderAll();
                }
                return;
            }

            var addH = e.target.closest('.etm-highlight-add');
            if (addH) {
                var container = addH.previousElementSibling;
                var row = document.createElement('div');
                row.className = 'etm-highlight-row';
                row.innerHTML = '<input type="text" class="etm-highlight-input" placeholder="Highlight"><button type="button" class="etm-highlight-remove">&times;</button>';
                container.appendChild(row);
                row.querySelector('input').focus();
                markDirty();
                return;
            }

            var removeH = e.target.closest('.etm-highlight-remove');
            if (removeH) {
                removeH.closest('.etm-highlight-row').remove();
                markDirty();
            }
        });

        list.addEventListener('input', function(e) {
            markDirty();
            if (e.target.dataset.field === 'name') {
                e.target.closest('.etm-exp-item').querySelector('.etm-exp-item__title').textContent = e.target.value || 'Untitled Itinerary';
            }
            if (e.target.dataset.field === 'meta') {
                var item = e.target.closest('.etm-exp-item');
                var t = item.querySelector('[data-field="type"]').value;
                item.querySelector('.etm-exp-item__meta').innerHTML = (typeOptions[t] || t) + ' &middot; ' + (e.target.value || 'No duration');
            }
        });
        list.addEventListener('change', function(e) {
            markDirty();
            if (e.target.dataset.field === 'type') {
                var item = e.target.closest('.etm-exp-item');
                var meta = item.querySelector('[data-field="meta"]').value;
                item.querySelector('.etm-exp-item__meta').innerHTML = (typeOptions[e.target.value] || e.target.value) + ' &middot; ' + (meta || 'No duration');
            }
        });

        saveBtn.addEventListener('click', function() {
            var data = collectData();
            var fd = new FormData(form);
            fd.append('action', 'etm_save_itineraries');
            fd.append('itineraries', JSON.stringify(data));

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
                        setTimeout(function() { saveBtn.textContent = 'Save Itineraries'; saveBtn.disabled = false; }, 2000);
                    } else {
                        saveBtn.textContent = 'Save Itineraries'; saveBtn.disabled = false;
                        feedback.textContent = 'Error: ' + (res.data || 'unknown');
                        feedback.className = 'etm-notice etm-notice--error';
                    }
                })
                .catch(function() {
                    saveBtn.textContent = 'Save Itineraries'; saveBtn.disabled = false;
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

    <style>
    .etm-highlights { display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px; }
    .etm-highlight-row { display: flex; gap: 6px; }
    .etm-highlight-input { flex: 1; padding: 6px 10px; border: 1px solid #ccd0d4; border-radius: 4px; font-size: 13px; }
    .etm-highlight-remove { background: none; border: none; color: #999; cursor: pointer; font-size: 16px; padding: 0 8px; }
    .etm-highlight-remove:hover { color: #c62828; }
    </style>
    <?php
}
