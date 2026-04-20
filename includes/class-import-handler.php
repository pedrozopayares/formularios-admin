<?php
/**
 * Handles JSON import logic: parsing, batch processing, file downloading,
 * duplicate detection, and ACF field mapping for the Formularios Admin plugin.
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_Import_Handler {

    /** @var string Base directory for temporary import files */
    private string $upload_base;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->upload_base = trailingslashit($upload_dir['basedir']) . 'formularios-import';
    }

    /* =========================================================================
     * 1. JSON Upload & Validation
     * ========================================================================= */

    /**
     * Handle uploaded JSON file: validate, store temporarily, create import session.
     *
     * @param array  $file      $_FILES entry for the uploaded JSON.
     * @param string $post_type Target CPT slug.
     * @return array|WP_Error   Session data on success.
     */
    public function upload_json(array $file, string $post_type) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('Error al subir el archivo.', 'formularios-admin'));
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            return new WP_Error('invalid_type', __('Solo se permiten archivos .json.', 'formularios-admin'));
        }

        $raw = file_get_contents($file['tmp_name']);
        if (false === $raw || empty($raw)) {
            return new WP_Error('empty_file', __('El archivo está vacío.', 'formularios-admin'));
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', sprintf(
                __('JSON inválido: %s', 'formularios-admin'),
                json_last_error_msg()
            ));
        }

        if (!is_array($data) || empty($data)) {
            return new WP_Error('empty_data', __('El archivo JSON no contiene registros.', 'formularios-admin'));
        }

        // Normalize: if data is associative (single record), wrap in array
        if (!isset($data[0])) {
            $data = [$data];
        }

        // Create temp directory
        if (!file_exists($this->upload_base)) {
            wp_mkdir_p($this->upload_base);
            // Protect directory
            file_put_contents($this->upload_base . '/.htaccess', 'Deny from all');
            file_put_contents($this->upload_base . '/index.php', '<?php // Silence is golden.');
        }

        $session_id = wp_generate_uuid4();
        $file_path  = $this->upload_base . '/' . $session_id . '.json';

        // Store the parsed, validated data
        file_put_contents($file_path, wp_json_encode($data, JSON_UNESCAPED_UNICODE));

        // Create session record
        $session = [
            'session_id'   => $session_id,
            'post_type'    => sanitize_key($post_type),
            'file_path'    => $file_path,
            'total'        => count($data),
            'offset'       => 0,
            'created'      => 0,
            'skipped'      => 0,
            'errors'       => 0,
            'error_log'    => [],
            'mapping'      => [],
            'options'      => [],
            'status'       => 'pending', // pending | running | paused | completed | failed
            'created_at'   => current_time('mysql'),
            'created_by'   => get_current_user_id(),
        ];

        update_option('_formularios_import_' . $session_id, $session, false);

        return $session;
    }

    /* =========================================================================
     * 2. Field Introspection
     * ========================================================================= */

    /**
     * Extract all unique JSON field keys with sample values.
     *
     * @param string $session_id Import session ID.
     * @return array|WP_Error    Array of ['key' => string, 'samples' => string[]].
     */
    public function get_json_fields(string $session_id) {
        $data = $this->load_json_data($session_id);
        if (is_wp_error($data)) return $data;

        $keys = [];
        $sample_count = min(3, count($data));

        foreach ($data as $i => $record) {
            foreach ($record as $key => $value) {
                if (!isset($keys[$key])) {
                    $keys[$key] = ['key' => $key, 'samples' => []];
                }
                if (count($keys[$key]['samples']) < $sample_count) {
                    $display = is_scalar($value) ? mb_substr((string) $value, 0, 120) : wp_json_encode($value);
                    $keys[$key]['samples'][] = $display;
                }
            }
        }

        return array_values($keys);
    }

    /**
     * Get available ACF fields for a given CPT, organized by group.
     *
     * @param string $post_type CPT slug.
     * @return array            Grouped field list.
     */
    public function get_acf_fields(string $post_type): array {
        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return [];
        }

        $groups = acf_get_field_groups(['post_type' => $post_type]);
        $result = [];

        foreach ($groups as $group) {
            $fields = acf_get_fields($group['key']);
            if (empty($fields)) continue;

            $group_fields = [];
            $this->flatten_acf_fields($fields, $group_fields);

            $result[] = [
                'group_key'   => $group['key'],
                'group_title' => $group['title'],
                'fields'      => $group_fields,
            ];
        }

        return $result;
    }

    /**
     * Flatten ACF fields recursively (handles groups, tabs, etc.).
     */
    private function flatten_acf_fields(array $fields, array &$out, string $prefix = ''): void {
        foreach ($fields as $field) {
            $type = $field['type'] ?? '';

            // Skip layout-only types
            if (in_array($type, ['message', 'accordion', 'tab', 'clone'], true)) {
                continue;
            }

            $field_info = [
                'key'   => $field['key'],
                'name'  => $prefix . $field['name'],
                'label' => $field['label'],
                'type'  => $type,
            ];

            $out[] = $field_info;

            // Recurse into group sub_fields
            if ($type === 'group' && !empty($field['sub_fields'])) {
                $this->flatten_acf_fields($field['sub_fields'], $out, $field['name'] . '_');
            }

            // Repeater: show sub_fields info for mapping reference
            if ($type === 'repeater' && !empty($field['sub_fields'])) {
                foreach ($field['sub_fields'] as $sub) {
                    $sub_type = $sub['type'] ?? '';
                    if (in_array($sub_type, ['message', 'accordion', 'tab', 'clone'], true)) continue;
                    $out[] = [
                        'key'   => $sub['key'],
                        'name'  => $field['name'] . '_0_' . $sub['name'],
                        'label' => $field['label'] . ' → ' . $sub['label'],
                        'type'  => $sub_type,
                        'parent_repeater' => $field['name'],
                    ];
                }
            }
        }
    }

    /* =========================================================================
     * 3. Mapping & Options Storage
     * ========================================================================= */

    /**
     * Save field mapping and import options to the session.
     *
     * @param string $session_id Session UUID.
     * @param array  $mapping    ['json_key' => 'acf_field_name', ...]
     * @param array  $options    Import options (verification, post_status, etc.).
     * @return true|WP_Error
     */
    public function save_mapping(string $session_id, array $mapping, array $options) {
        $session = $this->get_session($session_id);
        if (is_wp_error($session)) return $session;

        $session['mapping'] = $mapping;
        $session['options'] = $options;
        $session['status']  = 'ready';

        update_option('_formularios_import_' . $session_id, $session, false);
        return true;
    }

    /* =========================================================================
     * 4. Batch Processing
     * ========================================================================= */

    /**
     * Process the next batch of records.
     *
     * @param string $session_id Session UUID.
     * @param int    $batch_size Number of records per batch.
     * @return array|WP_Error    Progress info.
     */
    public function process_batch(string $session_id, int $batch_size = 10) {
        $session = $this->get_session($session_id);
        if (is_wp_error($session)) return $session;

        if (!in_array($session['status'], ['ready', 'running', 'paused'], true)) {
            return new WP_Error('invalid_status', __('La sesión de importación no está en un estado válido para procesar.', 'formularios-admin'));
        }

        $data = $this->load_json_data($session_id);
        if (is_wp_error($data)) return $data;

        // Set lock
        $lock_key = '_formularios_import_lock_' . $session_id;
        if (get_transient($lock_key)) {
            return new WP_Error('locked', __('Ya hay un proceso de importación en ejecución para esta sesión.', 'formularios-admin'));
        }
        set_transient($lock_key, 1, 300); // 5 min timeout

        $session['status'] = 'running';
        $offset   = (int) $session['offset'];
        $mapping  = $session['mapping'];
        $options  = $session['options'];
        $post_type = $session['post_type'];

        $batch_log = [];
        $slice = array_slice($data, $offset, $batch_size);

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Pre-import filter: parse filter_field and filter_values from options.
        $filter_field  = trim($options['filter_field'] ?? '');
        $filter_values = [];
        if ($filter_field !== '' && !empty($options['filter_values'])) {
            $filter_values = array_map('trim', explode(',', $options['filter_values']));
            $filter_values = array_filter($filter_values, function ($v) { return $v !== ''; });
        }

        // Required fields: skip records where ALL listed fields are empty (placeholders).
        $required_fields = [];
        if (!empty($options['required_fields'])) {
            $required_fields = array_map('trim', explode(',', $options['required_fields']));
            $required_fields = array_filter($required_fields, function ($v) { return $v !== ''; });
        }

        foreach ($slice as $i => $record) {
            $record_index = $offset + $i;

            // Check 1: Skip empty placeholder records (missing required field data).
            if ($this->is_empty_placeholder($record, $required_fields)) {
                $session['skipped']++;
                $batch_log[] = [
                    'index'  => $record_index,
                    'status' => 'skipped',
                    'msg'    => 'Registro vacío (sin datos en campos obligatorios)',
                ];
                continue;
            }

            // Check 2: Apply value-based filter (if configured).
            if ($this->should_skip_record($record, $filter_field, $filter_values)) {
                $session['skipped']++;
                $batch_log[] = [
                    'index'  => $record_index,
                    'status' => 'skipped',
                    'msg'    => sprintf('Filtrado (%s=%s)', $filter_field, $record[$filter_field] ?? ''),
                ];
                continue;
            }

            try {
                $result = $this->import_single_record($record, $record_index, $mapping, $options, $post_type, $session_id);

                if ($result === 'skipped') {
                    $session['skipped']++;
                    $batch_log[] = ['index' => $record_index, 'status' => 'skipped', 'msg' => 'Duplicado'];
                } elseif (is_wp_error($result)) {
                    $session['errors']++;
                    $msg = $result->get_error_message();
                    $session['error_log'][] = ['index' => $record_index, 'error' => $msg];
                    $batch_log[] = ['index' => $record_index, 'status' => 'error', 'msg' => $msg];
                } else {
                    $session['created']++;
                    $batch_log[] = ['index' => $record_index, 'status' => 'created', 'post_id' => $result];
                }
            } catch (\Throwable $e) {
                $session['errors']++;
                $msg = $e->getMessage();
                $session['error_log'][] = ['index' => $record_index, 'error' => $msg];
                $batch_log[] = ['index' => $record_index, 'status' => 'error', 'msg' => $msg];
            }
        }

        $session['offset'] = $offset + count($slice);

        // Check if done
        if ($session['offset'] >= $session['total']) {
            $session['status'] = 'completed';
        } else {
            $session['status'] = 'running';
        }

        update_option('_formularios_import_' . $session_id, $session, false);
        delete_transient($lock_key);

        return [
            'session_id' => $session_id,
            'offset'     => $session['offset'],
            'total'      => $session['total'],
            'created'    => $session['created'],
            'skipped'    => $session['skipped'],
            'errors'     => $session['errors'],
            'status'     => $session['status'],
            'batch_log'  => $batch_log,
        ];
    }

    /**
     * Import a single record: duplicate check, post creation, ACF field mapping.
     *
     * @return int|string|WP_Error  Post ID on success, 'skipped' if duplicate, WP_Error on failure.
     */
    private function import_single_record(array $record, int $index, array $mapping, array $options, string $post_type, string $session_id) {
        // --- Duplicate detection ---
        $duplicate_key = $options['duplicate_key'] ?? '';
        if (!empty($duplicate_key) && isset($mapping[$duplicate_key])) {
            $acf_field_name = $mapping[$duplicate_key];
            $source_value   = $record[$duplicate_key] ?? '';

            if (!empty($source_value)) {
                $existing = $this->find_existing_post($post_type, $acf_field_name, $source_value);
                if ($existing) {
                    return 'skipped';
                }

                // Also check records imported in this session (by import source meta)
                $existing_by_meta = get_posts([
                    'post_type'   => $post_type,
                    'post_status' => 'any',
                    'meta_query'  => [
                        ['key' => '_formularios_import_source_key', 'value' => $duplicate_key . ':' . $source_value],
                    ],
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                ]);
                if (!empty($existing_by_meta)) {
                    return 'skipped';
                }
            }
        }

        // --- Determine verification outcome for this record (must run before post creation) ---
        $verification = $options['verification'] ?? 'unverified';
        $is_verified  = false;
        if ($verification === 'verified') {
            $is_verified = true;
        } elseif ($verification === 'map' && !empty($options['verification_field'])) {
            $is_verified = $this->is_truthy($record[$options['verification_field']] ?? '');
        }

        // --- Determine post status ---
        // Unverified records cannot be published; downgrade to pending review.
        $intended_status = ($options['post_status'] ?? 'publish') === 'pending' ? 'pending' : 'publish';
        $post_status     = (!$is_verified && $intended_status === 'publish') ? 'pending' : $intended_status;

        // --- Build post title ---
        $title = $this->build_post_title($record, $mapping, $options, $post_type);

        // --- Create the post ---
        $post_id = wp_insert_post([
            'post_type'   => $post_type,
            'post_title'  => $title,
            'post_status' => $post_status,
            'post_author' => get_current_user_id() ?: 1,
        ], true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // --- Map and save ACF fields ---
        $base_url     = $options['file_base_url'] ?? '';
        $attach_extra = !empty($options['attach_extra_files']);

        foreach ($mapping as $json_key => $acf_field_name) {
            if (empty($acf_field_name) || $acf_field_name === '__ignore__') continue;

            $raw_value = $record[$json_key] ?? '';

            // Clean empty date placeholders
            if ($raw_value === '0000-00-00' || $raw_value === '0000-00-00 00:00:00') {
                $raw_value = '';
            }

            // Determine ACF field type
            $acf_field = $this->get_acf_field_object($acf_field_name);
            $field_type = $acf_field ? ($acf_field['type'] ?? 'text') : 'text';

            if (in_array($field_type, ['file', 'image'], true)) {
                $this->handle_file_field($raw_value, $acf_field_name, $post_id, $base_url, $attach_extra);
            } else {
                // Sanitize based on type
                $clean_value = $this->sanitize_import_value($raw_value, $field_type);
                update_field($acf_field_name, $clean_value, $post_id);
            }
        }

        // --- Set verification meta (uses $is_verified computed before post creation) ---
        if ($is_verified) {
            update_post_meta($post_id, '_formulario_verificado', '1');
            update_post_meta($post_id, '_formulario_verificado_por', get_current_user_id());
            update_post_meta($post_id, '_formulario_verificado_at', current_time('mysql'));
        }
        // unverified → no verification meta set (default)

        // --- Save observaciones meta ---
        $observaciones_field = trim($options['observaciones_field'] ?? '');
        if ($observaciones_field !== '' && isset($record[$observaciones_field])) {
            $obs_value = sanitize_textarea_field((string) $record[$observaciones_field]);
            if ($obs_value !== '') {
                update_post_meta($post_id, '_formulario_observaciones', $obs_value);
            }
        }

        // --- Import tracking meta ---
        update_post_meta($post_id, '_eff_submitted_from', 'import');
        update_post_meta($post_id, '_formularios_import_session', $session_id);

        // Store duplicate detection key value for future checks
        if (!empty($duplicate_key) && isset($record[$duplicate_key])) {
            update_post_meta($post_id, '_formularios_import_source_key', $duplicate_key . ':' . $record[$duplicate_key]);
        }

        return $post_id;
    }

    /* =========================================================================
     * 5. File Handling
     * ========================================================================= */

    /**
     * Extract file URLs from a value that may contain HTML <a> tags, plain URLs, or relative paths.
     *
     * @param string $value    Raw field value.
     * @param string $base_url Base URL to prepend to relative paths.
     * @return array           Array of ['url' => string, 'filename' => string].
     */
    public function extract_file_urls(string $value, string $base_url = ''): array {
        $value = trim($value);
        if (empty($value)) return [];

        $results = [];

        // Case 1: Contains HTML <a> tags
        if (preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>([^<]*)<\/a>/i', $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $url      = trim($match[1]);
                $filename = trim($match[2]);
                if (empty($filename)) {
                    $filename = basename(wp_parse_url($url, PHP_URL_PATH) ?: $url);
                }
                $results[] = ['url' => $url, 'filename' => $filename];
            }
            return $results;
        }

        // Case 2: Multiple URLs separated by newlines or commas
        $parts = preg_split('/[\r\n,]+/', $value);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            if (filter_var($part, FILTER_VALIDATE_URL)) {
                // Full URL
                $results[] = [
                    'url'      => $part,
                    'filename' => basename(wp_parse_url($part, PHP_URL_PATH) ?: $part),
                ];
            } elseif (!empty($base_url) && preg_match('/\.[a-zA-Z0-9]{2,5}$/', $part)) {
                // Relative path with file extension
                $url = rtrim($base_url, '/') . '/' . ltrim($part, '/');
                $results[] = [
                    'url'      => $url,
                    'filename' => basename($part),
                ];
            }
        }

        return $results;
    }

    /**
     * Download a remote file and create a WordPress attachment.
     *
     * @param string $url      Remote file URL.
     * @param string $filename Desired filename.
     * @param int    $post_id  Parent post ID.
     * @return int|WP_Error    Attachment ID on success.
     */
    public function download_and_attach(string $url, string $filename, int $post_id) {
        $response = wp_remote_get($url, [
            'timeout'     => 60,
            'sslverify'   => false, // Old servers may have invalid certs
            'redirection' => 5,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('download_failed', sprintf(
                __('No se pudo descargar "%s": %s', 'formularios-admin'),
                $filename,
                $response->get_error_message()
            ));
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('download_http_error', sprintf(
                __('Error HTTP %d al descargar "%s".', 'formularios-admin'),
                $code,
                $filename
            ));
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('download_empty', sprintf(
                __('El archivo descargado "%s" está vacío.', 'formularios-admin'),
                $filename
            ));
        }

        // Sanitize filename
        $filename = sanitize_file_name($filename);

        // Save to uploads
        $upload = wp_upload_bits($filename, null, $body);
        if (!empty($upload['error'])) {
            return new WP_Error('upload_save_failed', $upload['error']);
        }

        // Determine MIME type
        $mime = wp_check_filetype($filename);
        $mime_type = $mime['type'] ?: (wp_remote_retrieve_header($response, 'content-type') ?: 'application/octet-stream');

        $attachment_id = wp_insert_attachment([
            'post_mime_type' => $mime_type,
            'post_title'     => pathinfo($filename, PATHINFO_FILENAME),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_parent'    => $post_id,
        ], $upload['file'], $post_id);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);

        return $attachment_id;
    }

    /**
     * Handle a file-type ACF field: extract URLs, download files, attach to post.
     *
     * First file is saved to the ACF field. When $attach_extra is true,
     * additional files are downloaded and attached as native WP attachments
     * (post_parent = $post_id) so they appear in the Media Library for
     * this post. This works without ACF Pro (no repeater/gallery needed).
     *
     * @param string $raw_value       Raw field value (HTML, URLs, etc.).
     * @param string $acf_field_name  ACF field name for the first file.
     * @param int    $post_id         Target post ID.
     * @param string $base_url        Base URL for relative paths.
     * @param bool   $attach_extra    Whether to download extra files as post attachments.
     */
    private function handle_file_field(string $raw_value, string $acf_field_name, int $post_id, string $base_url, bool $attach_extra = false): void {
        $files = $this->extract_file_urls($raw_value, $base_url);
        if (empty($files)) return;

        // First file → save to the ACF field
        $first = $files[0];
        $attachment_id = $this->download_and_attach($first['url'], $first['filename'], $post_id);

        if (!is_wp_error($attachment_id)) {
            update_field($acf_field_name, $attachment_id, $post_id);
        }

        // Remaining files → attach as native WP media linked to the post
        if ($attach_extra && count($files) > 1) {
            for ($i = 1, $n = count($files); $i < $n; $i++) {
                $this->download_and_attach($files[$i]['url'], $files[$i]['filename'], $post_id);
                // download_and_attach already sets post_parent = $post_id,
                // so additional files are automatically linked in the Media Library.
            }
        }
    }

    /* =========================================================================
     * 6. Helpers
     * ========================================================================= */

    /**
     * Get session data from WP options.
     */
    public function get_session(string $session_id) {
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $session_id)) {
            return new WP_Error('invalid_session', __('ID de sesión inválido.', 'formularios-admin'));
        }

        $session = get_option('_formularios_import_' . $session_id);
        if (empty($session) || !is_array($session)) {
            return new WP_Error('session_not_found', __('Sesión de importación no encontrada.', 'formularios-admin'));
        }

        return $session;
    }

    /**
     * Load JSON data from session file.
     */
    private function load_json_data(string $session_id) {
        $session = $this->get_session($session_id);
        if (is_wp_error($session)) return $session;

        $path = $session['file_path'];
        if (!file_exists($path)) {
            return new WP_Error('file_missing', __('El archivo JSON de la sesión ya no existe.', 'formularios-admin'));
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            return new WP_Error('corrupt_data', __('Los datos JSON de la sesión están corruptos.', 'formularios-admin'));
        }

        return $data;
    }

    /**
     * Find an existing post by ACF field value.
     */
    private function find_existing_post(string $post_type, string $acf_field_name, string $value): ?int {
        $posts = get_posts([
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'meta_query'     => [
                ['key' => $acf_field_name, 'value' => $value, 'compare' => '='],
            ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);

        return !empty($posts) ? (int) $posts[0] : null;
    }

    /**
     * Get ACF field object by name (searches all field groups).
     */
    private function get_acf_field_object(string $field_name): ?array {
        if (!function_exists('acf_get_field')) return null;

        $field = acf_get_field($field_name);
        return is_array($field) ? $field : null;
    }

    /**
     * Build post title from options template or consecutive counter.
     */
    private function build_post_title(array $record, array $mapping, array $options, string $post_type): string {
        $template = $options['title_template'] ?? '';

        if (!empty($template)) {
            // Replace {field_name} placeholders with JSON values
            $title = preg_replace_callback('/\{([^}]+)\}/', function ($m) use ($record) {
                $key = $m[1];
                return isset($record[$key]) ? sanitize_text_field((string) $record[$key]) : '';
            }, $template);
            return trim($title) ?: $this->consecutive_title($post_type);
        }

        return $this->consecutive_title($post_type);
    }

    /**
     * Generate a consecutive title: TYPE 0001, TYPE 0002, etc.
     */
    private function consecutive_title(string $post_type): string {
        $counter_key = 'eff_consecutive_' . $post_type;
        $consecutive = (int) get_option($counter_key, 0) + 1;
        update_option($counter_key, $consecutive, false);

        $cpt_obj    = get_post_type_object($post_type);
        $type_label = $cpt_obj ? $cpt_obj->labels->singular_name : $post_type;

        return sprintf('%s %04d', strtoupper($type_label), $consecutive);
    }

    /**
     * Sanitize a value for import based on ACF field type.
     */
    private function sanitize_import_value($value, string $field_type) {
        if (is_null($value) || $value === '') return '';

        switch ($field_type) {
            case 'number':
                $num = filter_var($value, FILTER_VALIDATE_FLOAT);
                return (false === $num) ? '' : $num;

            case 'email':
                return sanitize_email($value);

            case 'url':
                return esc_url_raw($value);

            case 'true_false':
                return $this->is_truthy($value) ? 1 : 0;

            case 'textarea':
            case 'wysiwyg':
                return sanitize_textarea_field(wp_unslash((string) $value));

            case 'date_picker':
                $clean = sanitize_text_field($value);
                if (!empty($clean) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $clean)) {
                    return $clean;
                }
                return '';

            case 'date_time_picker':
                $clean = sanitize_text_field($value);
                if (!empty($clean) && preg_match('/^\d{4}-\d{2}-\d{2}/', $clean)) {
                    return $clean;
                }
                return '';

            case 'select':
            case 'radio':
            case 'checkbox':
                // For imports, store as-is — the value should match choices
                return sanitize_text_field(wp_unslash((string) $value));

            default:
                return sanitize_text_field(wp_unslash((string) $value));
        }
    }

    /**
     * Determine if a record should be skipped based on the pre-import value filter.
     *
     * @param array  $record        The JSON record.
     * @param string $filter_field   JSON field name to filter on (empty = no filter).
     * @param array  $filter_values  Allowed values for the field.
     * @return bool True if the record should be skipped.
     */
    private function should_skip_record(array $record, string $filter_field, array $filter_values): bool {
        if ($filter_field === '' || empty($filter_values)) {
            return false; // No filter configured — import all.
        }

        $value = trim((string) ($record[$filter_field] ?? ''));
        return !in_array($value, $filter_values, true);
    }

    /**
     * Determine if a record is an empty placeholder (missing data in required fields).
     *
     * @param array $record          The JSON record.
     * @param array $required_fields  JSON field names that must have data.
     * @return bool True if ALL required fields are empty (placeholder record).
     */
    private function is_empty_placeholder(array $record, array $required_fields): bool {
        if (empty($required_fields)) {
            return false; // No required fields configured — accept all.
        }

        foreach ($required_fields as $field) {
            $value = trim((string) ($record[$field] ?? ''));
            if ($value !== '' && $value !== '0') {
                return false; // At least one required field has data.
            }
        }

        return true; // ALL required fields are empty — placeholder.
    }

    /**
     * Determine if a value is truthy (handles various legacy formats).
     */
    private function is_truthy($value): bool {
        if (is_bool($value)) return $value;
        $str = strtolower(trim((string) $value));
        return in_array($str, ['1', 'true', 'yes', 'si', 'sí', 'verificado', 'aprobado'], true);
    }

    /**
     * Pause an import session.
     */
    public function pause_session(string $session_id) {
        $session = $this->get_session($session_id);
        if (is_wp_error($session)) return $session;

        $session['status'] = 'paused';
        update_option('_formularios_import_' . $session_id, $session, false);
        delete_transient('_formularios_import_lock_' . $session_id);

        return true;
    }

    /**
     * Cancel an import session (keeps already-created posts).
     */
    public function cancel_session(string $session_id) {
        $session = $this->get_session($session_id);
        if (is_wp_error($session)) return $session;

        $session['status'] = 'cancelled';
        update_option('_formularios_import_' . $session_id, $session, false);
        delete_transient('_formularios_import_lock_' . $session_id);

        // Clean up temp file
        if (!empty($session['file_path']) && file_exists($session['file_path'])) {
            wp_delete_file($session['file_path']);
        }

        return true;
    }

    /**
     * Find incomplete import sessions.
     *
     * @return array Sessions that are in progress (running/paused/ready).
     */
    public function get_active_sessions(): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options}
             WHERE option_name LIKE '_formularios_import_%'
             AND option_name NOT LIKE '_formularios_import_lock_%'
             ORDER BY option_id DESC
             LIMIT 20",
            ARRAY_A
        );

        $active = [];
        foreach ($rows as $row) {
            $session = maybe_unserialize($row['option_value']);
            if (!is_array($session)) continue;
            if (in_array($session['status'] ?? '', ['ready', 'running', 'paused'], true)) {
                $active[] = $session;
            }
        }

        return $active;
    }

    /**
     * Clean up old completed/cancelled sessions and their temp files (for wp-cron).
     */
    public function cleanup_old_sessions(): void {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options}
             WHERE option_name LIKE '_formularios_import_%'
             AND option_name NOT LIKE '_formularios_import_lock_%'",
            ARRAY_A
        );

        $cutoff = strtotime('-24 hours');

        foreach ($rows as $row) {
            $session = maybe_unserialize($row['option_value']);
            if (!is_array($session)) continue;

            if (!in_array($session['status'] ?? '', ['completed', 'cancelled', 'failed'], true)) {
                continue;
            }

            $created = strtotime($session['created_at'] ?? '');
            if ($created && $created < $cutoff) {
                if (!empty($session['file_path']) && file_exists($session['file_path'])) {
                    wp_delete_file($session['file_path']);
                }
                delete_option($row['option_name']);
            }
        }
    }
}
