/**
 * Formularios Import – 4-step wizard with batched AJAX.
 */
(function ($) {
    'use strict';

    var cfg  = window.formulariosImport || {};
    var i18n = cfg.i18n || {};

    /* ── State ────────────────────────────────────────────── */
    var state = {
        sessionId:   '',
        postType:    '',
        jsonFields:  [],   // [{key, samples}]
        acfFields:   [],   // [{group_key, group_title, fields:[{key,name,label,type}]}]
        mapping:     {},
        total:       0,
        running:     false,
        paused:      false,
        batchSize:   10,
    };

    /* ── Init ────────────────────────────────────────────── */
    $(function () {
        populateCptDropdown();
        bindStep1();
        bindStep2();
        bindStep3();
        bindStep4();
    });

    /* ── CPT Dropdown ────────────────────────────────────── */
    function populateCptDropdown() {
        var $sel = $('#fi-cpt-select');
        (cfg.cpts || []).forEach(function (c) {
            $sel.append('<option value="' + esc(c.slug) + '">' + esc(c.label) + '</option>');
        });
        // Pre-select CPT from URL parameter
        if (cfg.preselect_cpt) {
            $sel.val(cfg.preselect_cpt).trigger('change');
        }
    }

    /* ══════════════════════════════════════════════════════
       STEP 1 – Upload
       ══════════════════════════════════════════════════════ */
    function bindStep1() {
        var $btn = $('#fi-btn-upload');

        // Enable upload button only when both CPT and file selected
        $('#fi-cpt-select, #fi-json-file').on('change', function () {
            $btn.prop('disabled', !$('#fi-cpt-select').val() || !$('#fi-json-file').val());
        });

        $btn.on('click', function () {
            var postType = $('#fi-cpt-select').val();
            var fileInput = $('#fi-json-file')[0];
            if (!postType || !fileInput.files.length) return;

            var fd = new FormData();
            fd.append('action', 'formularios_upload_json');
            fd.append('nonce', cfg.nonce);
            fd.append('post_type', postType);
            fd.append('json_file', fileInput.files[0]);

            $btn.prop('disabled', true).text(i18n.uploading || 'Subiendo...');
            $('#fi-spinner-upload').addClass('is-active');
            hideNotice('#fi-upload-notice');

            $.ajax({
                url: cfg.ajax_url,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        state.sessionId  = res.data.session_id;
                        state.postType   = postType;
                        state.total      = res.data.total;
                        state.jsonFields = res.data.json_fields;
                        state.acfFields  = res.data.acf_fields;
                        buildMappingUI();
                        goToStep(2);
                    } else {
                        showNotice('#fi-upload-notice', res.data || 'Error', 'error');
                    }
                },
                error: function () {
                    showNotice('#fi-upload-notice', 'Error de conexión.', 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false).text(i18n.step1 ? 'Subir y continuar' : 'Subir y continuar');
                    $('#fi-spinner-upload').removeClass('is-active');
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       STEP 2 – Mapping
       ══════════════════════════════════════════════════════ */
    function buildMappingUI() {
        var $body = $('#fi-mapping-body').empty();
        var $verif = $('#fi-verification-field').empty();
        var $dup   = $('#fi-duplicate-key').empty().append('<option value="">' + esc(i18n.select_field || '— Sin duplicados —') + '</option>');

        // Build ACF field <optgroup> markup once
        var acfOptions = '<option value="__ignore__">' + esc(i18n.select_field || '— No importar —') + '</option>';
        (state.acfFields || []).forEach(function (group) {
            acfOptions += '<optgroup label="' + esc(group.group_title) + '">';
            (group.fields || []).forEach(function (f) {
                var badge = f.type ? ' [' + f.type + ']' : '';
                acfOptions += '<option value="' + esc(f.name) + '" data-type="' + esc(f.type) + '">' + esc(f.label) + badge + '</option>';
            });
            acfOptions += '</optgroup>';
        });

        // Build a lookup of ACF field names (lowercase) for auto-match
        var acfNameMap = {};
        (state.acfFields || []).forEach(function (group) {
            (group.fields || []).forEach(function (f) {
                acfNameMap[f.name.toLowerCase()] = f.name;
                // Also index by stripping underscores/dashes
                acfNameMap[f.name.toLowerCase().replace(/[_-]/g, '')] = f.name;
            });
        });

        (state.jsonFields || []).forEach(function (jf) {
            var sample = (jf.samples || []).join(' | ');
            if (sample.length > 100) sample = sample.substring(0, 100) + '…';

            var $row = $('<tr>');
            $row.append('<td class="fi-json-key"><code>' + esc(jf.key) + '</code></td>');
            $row.append('<td class="fi-sample">' + esc(sample) + '</td>');

            var $select = $('<select class="fi-acf-select" data-json-key="' + esc(jf.key) + '">' + acfOptions + '</select>');

            // Auto-match: try exact name match (case-insensitive)
            var jkLower = jf.key.toLowerCase().replace(/[_-]/g, '');
            if (acfNameMap[jf.key.toLowerCase()]) {
                $select.val(acfNameMap[jf.key.toLowerCase()]);
            } else if (acfNameMap[jkLower]) {
                $select.val(acfNameMap[jkLower]);
            }

            $row.append($('<td>').append($select));
            $body.append($row);

            // Populate verification/duplicate dropdowns with JSON keys
            $verif.append('<option value="' + esc(jf.key) + '">' + esc(jf.key) + '</option>');
            $dup.append('<option value="' + esc(jf.key) + '">' + esc(jf.key) + '</option>');
        });
    }

    function bindStep2() {
        $('#fi-btn-back-1').on('click', function () { goToStep(1); });
        $('#fi-btn-to-options').on('click', function () {
            // Collect mapping
            state.mapping = {};
            var count = 0;
            $('.fi-acf-select').each(function () {
                var val = $(this).val();
                if (val && val !== '__ignore__') {
                    state.mapping[$(this).data('json-key')] = val;
                    count++;
                }
            });
            if (count === 0) {
                alert(i18n.no_mapping || 'Debe mapear al menos un campo.');
                return;
            }
            goToStep(3);
        });
    }

    /* ══════════════════════════════════════════════════════
       STEP 3 – Options
       ══════════════════════════════════════════════════════ */
    function bindStep3() {
        // Enable/disable verification field dropdown
        $('input[name="fi_verification"]').on('change', function () {
            $('#fi-verification-field').prop('disabled', $(this).val() !== 'map');
        });

        $('#fi-btn-back-2').on('click', function () { goToStep(2); });

        $('#fi-btn-start-import').on('click', function () {
            if (!confirm(i18n.confirm_start || '¿Iniciar la importación?')) return;

            var options = {
                verification:        $('input[name="fi_verification"]:checked').val(),
                verification_field:  $('#fi-verification-field').val(),
                duplicate_key:       $('#fi-duplicate-key').val(),
                post_status:         $('input[name="fi_post_status"]:checked').val(),
                title_template:      $('#fi-title-template').val(),
                file_base_url:       $('#fi-file-base-url').val(),
                attach_extra_files:  $('#fi-attach-extra-files').is(':checked'),
            };

            state.batchSize = parseInt($('#fi-batch-size').val(), 10) || 10;

            // Send mapping + options to server
            $.post(cfg.ajax_url, {
                action:     'formularios_start_import',
                nonce:      cfg.nonce,
                session_id: state.sessionId,
                mapping:    JSON.stringify(state.mapping),
                options:    JSON.stringify(options),
            }, function (res) {
                if (res.success) {
                    goToStep(4);
                    startProcessing();
                } else {
                    alert(res.data || 'Error al iniciar importación.');
                }
            }, 'json');
        });
    }

    /* ══════════════════════════════════════════════════════
       STEP 4 – Import Progress
       ══════════════════════════════════════════════════════ */
    function bindStep4() {
        $('#fi-btn-pause').on('click', function () {
            state.paused = true;
            $(this).hide();
            $('#fi-btn-resume').show();
            $.post(cfg.ajax_url, {
                action: 'formularios_pause_import',
                nonce: cfg.nonce,
                session_id: state.sessionId
            });
        });

        $('#fi-btn-resume').on('click', function () {
            state.paused = false;
            $(this).hide();
            $('#fi-btn-pause').show();
            startProcessing();
        });

        $('#fi-btn-new-import').on('click', function () {
            location.reload();
        });
    }

    function startProcessing() {
        state.running = true;
        state.paused  = false;
        $('#fi-btn-pause').show();
        $('#fi-btn-resume').hide();
        $('#fi-complete-message').hide();
        processNextBatch();
    }

    function processNextBatch() {
        if (state.paused || !state.running) return;

        $.post(cfg.ajax_url, {
            action:     'formularios_process_batch',
            nonce:      cfg.nonce,
            session_id: state.sessionId,
            batch_size: state.batchSize,
        }, function (res) {
            if (!res.success) {
                logEntry('error', res.data || 'Error desconocido');
                state.running = false;
                return;
            }

            var d = res.data;

            // Update stats
            updateProgress(d.offset, d.total, d.created, d.skipped, d.errors);

            // Append batch log entries
            (d.batch_log || []).forEach(function (entry) {
                if (entry.status === 'created') {
                    logEntry('created', '#' + entry.index + ' → Post #' + entry.post_id);
                } else if (entry.status === 'skipped') {
                    logEntry('skipped', '#' + entry.index + ' ' + (entry.msg || 'Duplicado'));
                } else {
                    logEntry('error', '#' + entry.index + ' ' + (entry.msg || 'Error'));
                }
            });

            if (d.status === 'completed') {
                onComplete(d);
            } else if (!state.paused) {
                // Chain next batch
                processNextBatch();
            }
        }, 'json').fail(function () {
            logEntry('error', 'Error de conexión. Puede reanudar la importación.');
            state.running = false;
            state.paused  = true;
            $('#fi-btn-pause').hide();
            $('#fi-btn-resume').show();
        });
    }

    function updateProgress(offset, total, created, skipped, errors) {
        var pct = total ? Math.round((offset / total) * 100) : 0;
        $('#fi-progress-fill').css('width', pct + '%');
        $('#fi-progress-text').text(offset + ' ' + (i18n.of || 'de') + ' ' + total + ' ' + (i18n.records || 'registros'));
        $('#fi-stat-created').text(created);
        $('#fi-stat-skipped').text(skipped);
        $('#fi-stat-errors').text(errors);
    }

    function onComplete(data) {
        state.running = false;
        $('#fi-import-actions').hide();
        $('#fi-complete-message').show();
        $('#fi-complete-summary').text(
            (data.created || 0) + ' ' + (i18n.created || 'creados') + ', ' +
            (data.skipped || 0) + ' ' + (i18n.skipped || 'omitidos') + ', ' +
            (data.errors || 0) + ' ' + (i18n.errors || 'errores') + '.'
        );
        $('#fi-view-posts').attr('href', 'edit.php?post_type=' + esc(state.postType));
        updateProgress(data.total, data.total, data.created, data.skipped, data.errors);
    }

    function logEntry(type, msg) {
        var cls = 'fi-log-' + type;
        var $el = $('<div class="fi-log-entry ' + cls + '">').text(msg);
        $('#fi-log').prepend($el);
    }

    /* ── Navigation ──────────────────────────────────────── */
    function goToStep(n) {
        $('.fi-panel').hide();
        $('#fi-step-' + n).show();
        $('.fi-step').removeClass('fi-step-active fi-step-done');
        $('.fi-step').each(function () {
            var s = parseInt($(this).data('step'), 10);
            if (s < n) $(this).addClass('fi-step-done');
            if (s === n) $(this).addClass('fi-step-active');
        });
    }

    /* ── Helpers ──────────────────────────────────────────── */
    function esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function showNotice(sel, msg, type) {
        $(sel).html('<div class="notice notice-' + type + ' inline"><p>' + esc(msg) + '</p></div>').show();
    }

    function hideNotice(sel) {
        $(sel).hide().empty();
    }

})(jQuery);
