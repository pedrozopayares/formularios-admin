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
}
