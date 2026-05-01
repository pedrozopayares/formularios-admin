<?php
/**
 * Bridge between ACF Forms Frontend Creator and Formularios Admin.
 *
 * Listens to the agnostic hooks exposed by ACF Forms
 * (`eff_pre_file_uploads`, `eff_post_file_uploads`,
 * `eff_after_attachment_created`, `eff_before_finalize_attachments`) and
 * applies the per-CPT web-root folder logic via Formularios_Folder_Resolver.
 *
 * Keeping this logic here (and NOT in ACF Forms) ensures ACF Forms remains
 * storage-agnostic — its only dependency is ACF itself.
 */
defined('ABSPATH') || exit;

class Formularios_ACF_Forms_Bridge {

    /** @var \Closure|null */
    private static $upload_filter = null;

    /** @var \Closure|null */
    private static $prefilter = null;

    /** @var bool  True while this bridge is handling the current submission. */
    private static $active = false;

    public static function register_hooks(): void {
        add_action('eff_pre_file_uploads',             [__CLASS__, 'on_pre_uploads'],          10, 2);
        add_action('eff_post_file_uploads',            [__CLASS__, 'on_post_uploads'],         10, 3);
        add_action('eff_after_attachment_created',     [__CLASS__, 'on_attachment_created'],   10, 4);
        add_action('eff_before_finalize_attachments',  [__CLASS__, 'on_before_finalize'],      10, 3);
        add_action('eff_after_submission',             [__CLASS__, 'on_after_submission'],     10, 3);
    }

    /**
     * Redirect uploads to ABSPATH/documentos/<folder>/ for the configured CPT
     * and give files a temporary name (renamed in `on_before_finalize`).
     */
    public static function on_pre_uploads(string $post_type, array $fields): void {
        if ($post_type === '' || !class_exists('Formularios_Folder_Resolver')) {
            return;
        }

        $map = Formularios_Folder_Resolver::get_map();
        if (empty($map[$post_type])) {
            return;
        }

        Formularios_Folder_Resolver::ensure_folder_exists($post_type);

        $abs_dir  = untrailingslashit(Formularios_Folder_Resolver::get_absolute_path($post_type));
        $url_dir  = untrailingslashit(Formularios_Folder_Resolver::get_url($post_type));
        $abs_base = untrailingslashit(Formularios_Folder_Resolver::get_base_absolute_path());
        $url_base = untrailingslashit(Formularios_Folder_Resolver::get_base_url());
        $folder   = Formularios_Folder_Resolver::get_folder_for_cpt($post_type);

        self::$upload_filter = function (array $uploads) use ($abs_dir, $url_dir, $abs_base, $url_base, $folder): array {
            $uploads['basedir'] = $abs_base;
            $uploads['baseurl'] = $url_base;
            $uploads['subdir']  = '/' . $folder;
            $uploads['path']    = $abs_dir;
            $uploads['url']     = $url_dir;
            $uploads['error']   = false;

            if (!file_exists($uploads['path'])) {
                wp_mkdir_p($uploads['path']);
            }

            return $uploads;
        };
        add_filter('upload_dir', self::$upload_filter);

        self::$prefilter = function (array $file): array {
            $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
            $ext  = $ext ? '.' . strtolower($ext) : '';
            $file['name'] = 'tmp-' . uniqid('', true) . $ext;
            return $file;
        };
        add_filter('wp_handle_upload_prefilter', self::$prefilter);

        self::$active = true;
    }

    /**
     * Remove the filters added in `on_pre_uploads`.
     *
     * @param array $results  [field_name => attachment_id]
     */
    public static function on_post_uploads(string $post_type, array $fields, array $results): void {
        if (self::$upload_filter !== null) {
            remove_filter('upload_dir', self::$upload_filter);
            self::$upload_filter = null;
        }
        if (self::$prefilter !== null) {
            remove_filter('wp_handle_upload_prefilter', self::$prefilter);
            self::$prefilter = null;
        }
        self::$active = false;
    }

    /**
     * Stamp docroot meta markers on attachments created during a bridged
     * submission so `Formularios_Folder_Resolver` can later filter URLs/paths.
     */
    public static function on_attachment_created(int $attachment_id, array $upload, array $field, string $post_type): void {
        if (!self::$active || !class_exists('Formularios_Folder_Resolver')) {
            return;
        }

        // Store the absolute path (outside wp-content/uploads) and canonical URL.
        update_post_meta($attachment_id, '_wp_attached_file', $upload['file']);
        update_post_meta($attachment_id, Formularios_Folder_Resolver::META_MARKER, 1);
        update_post_meta($attachment_id, Formularios_Folder_Resolver::META_MANAGED, 1);
        update_post_meta($attachment_id, Formularios_Folder_Resolver::META_PUBLIC_URL, $upload['url']);
    }

    /**
     * Rename `tmp-*` files to `{post_id}-{timestamp}-{seq}{ext}` and update
     * the attachment's physical file, meta, guid and post_parent.
     *
     * Skips attachments that are not docroot-managed or not tmp.
     *
     * @param array<string,int> $file_values  [field_name => attachment_id].
     */
    public static function on_before_finalize(array $file_values, int $post_id, string $post_type): void {
        if (!class_exists('Formularios_Folder_Resolver')) {
            return;
        }

        $map = Formularios_Folder_Resolver::get_map();
        if (empty($map[$post_type])) {
            return;
        }

        $abs_dir    = Formularios_Folder_Resolver::get_absolute_path($post_type);
        $url_prefix = Formularios_Folder_Resolver::get_url($post_type);
        $timestamp  = current_time('timestamp');
        $seq        = 0;

        foreach ($file_values as $attachment_id) {
            $attachment_id = (int) $attachment_id;
            $seq++;

            $current_stored = get_post_meta($attachment_id, '_wp_attached_file', true);
            if (!is_string($current_stored) || $current_stored === '') {
                continue;
            }

            $is_docroot       = (bool) get_post_meta($attachment_id, Formularios_Folder_Resolver::META_MARKER, true);
            $current_basename = basename($current_stored);
            $is_tmp           = (strpos($current_basename, 'tmp-') === 0);

            if (!$is_docroot || !$is_tmp) {
                continue;
            }

            // For docroot attachments, _wp_attached_file is an absolute path.
            $current_abs = $current_stored;
            if (!file_exists($current_abs)) {
                continue;
            }

            $ext          = pathinfo($current_basename, PATHINFO_EXTENSION);
            $ext          = $ext ? '.' . strtolower($ext) : '';
            $new_basename = $post_id . '-' . $timestamp . '-' . $seq . $ext;
            $new_abs      = $abs_dir . $new_basename;

            // Avoid collisions.
            $collision = 0;
            while (file_exists($new_abs)) {
                $collision++;
                $new_basename = $post_id . '-' . $timestamp . '-' . $seq . '-' . $collision . $ext;
                $new_abs      = $abs_dir . $new_basename;
                if ($collision > 20) {
                    break;
                }
            }

            if (!@rename($current_abs, $new_abs)) {
                continue;
            }

            $new_url = $url_prefix . $new_basename;
            update_post_meta($attachment_id, '_wp_attached_file', $new_abs);
            update_post_meta($attachment_id, Formularios_Folder_Resolver::META_PUBLIC_URL, $new_url);

            wp_update_post([
                'ID'          => $attachment_id,
                'post_parent' => $post_id,
                'guid'        => $new_url,
                'post_title'  => pathinfo($new_basename, PATHINFO_FILENAME),
                'post_name'   => sanitize_title(pathinfo($new_basename, PATHINFO_FILENAME)),
            ]);

            // Regenerate metadata (especially for images) now that the file path changed.
            $mime = get_post_mime_type($attachment_id);
            if (is_string($mime) && strpos($mime, 'image/') === 0) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $metadata = wp_generate_attachment_metadata($attachment_id, $new_abs);
                if (!empty($metadata)) {
                    wp_update_attachment_metadata($attachment_id, $metadata);
                }
            }
        }
    }

    /**
     * Send user notification email after form submission.
     *
     * Listens to `eff_after_submission` hook from ACF Forms Frontend Creator.
     * Checks if user notifications are enabled for the CPT, extracts the email
     * from the submitted data, processes placeholders, and sends the email.
     *
     * @param int    $post_id        Post ID of the newly created submission
     * @param string $post_type      CPT slug
     * @param array  $submitted_data Sanitized form data [field_name => value]
     */
    public static function on_after_submission(int $post_id, string $post_type, array $submitted_data): void {
        if (!class_exists('Formularios_User_Notification_Settings') || !class_exists('Formularios_User_Notification_Helper')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Formularios Admin: Notification classes not available; skipping user notification for post_type={$post_type}");
            }
            return;
        }

        // Check if notifications are enabled for this CPT
        if (!Formularios_User_Notification_Settings::is_enabled($post_type)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Formularios Admin: Notifications disabled for post_type={$post_type}");
            }
            return;
        }

        $settings = Formularios_User_Notification_Settings::get_for($post_type);

        // Provide sensible fallbacks for subject/body when admin left them empty
        $subject_fallback = __('Confirmación de registro en {site_name}', 'formularios-admin');
        $body_fallback    = '<p>' . __('Gracias por tu envío en {site_name}.', 'formularios-admin') . '</p>';

        $subject  = trim((string) ($settings['subject'] ?? '')) ?: $subject_fallback;
        $body_html = trim((string) ($settings['body_html'] ?? '')) ?: $body_fallback;

        if (empty($settings['email_field'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Formularios Admin: No email_field configured for post_type={$post_type}; skipping user notification");
            }
            return;
        }

        // Extract email from submitted data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Formularios Admin: Extracting email from field '{$settings['email_field']}' (submitted keys: " . implode(', ', array_keys($submitted_data)) . ")");
        }

        $to_email = Formularios_User_Notification_Helper::extract_email($settings['email_field'], $submitted_data);
        if ($to_email === '') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf("Formularios Admin: Could not extract valid email from field '%s' for post_type=%s", $settings['email_field'], $post_type));
            }
            return;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Formularios Admin: Extracted email: {$to_email}");
        }

        // Process placeholders in subject and body
        $subject  = Formularios_User_Notification_Helper::process_placeholders($subject, $post_id, $submitted_data);
        $body_html = Formularios_User_Notification_Helper::process_placeholders($body_html, $post_id, $submitted_data);

        // Send email and log result
        $sent = Formularios_User_Notification_Helper::send_notification_email($to_email, $subject, $body_html);
    }
}
