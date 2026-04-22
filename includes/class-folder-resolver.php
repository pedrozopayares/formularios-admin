<?php
/**
 * Resolves the physical folder where files for each CPT should live.
 *
 * Files live at the WEB ROOT under /documentos/<folder>/ (alongside
 * wp-admin, wp-content, etc.), NOT inside wp-content/uploads.
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_Folder_Resolver {

    /** WP option name where the CPT → folder map is persisted. */
    const OPTION_KEY = 'formularios_folder_map';

    /** Base subdirectory (at the web root) that contains every per-CPT folder. */
    const BASE_SUBDIR = 'documentos';

    /** Attachment meta key marking this attachment as document-root managed. */
    const META_MARKER = '_formularios_docroot';

    /** Attachment meta key holding the canonical public URL for files outside uploads. */
    const META_PUBLIC_URL = '_formularios_docroot_url';

    /**
     * Default CPT → folder map inferred from the legacy JSON prefixes.
     * Used only the first time the settings page is opened (pre-populated).
     */
    public static function default_map(): array {
        return [
            'esal'                    => 'inscritosesal',
            'transformadoras'         => 'transformadoras',
            'vertimientosliquidos'    => 'vertimientosliquidos',
            'gestoras-acu'            => 'inscritosgestoresacu',
            'generadoras-acu'         => 'inscritosgeneradoresacu',
            'gestores-llantas'        => 'inscritosgestoresllantas',
            'gestores-rcd'            => 'inscritosgestoresrcd',
            'autodeclaracionliquidos' => 'autodeclaracionliquidos',
        ];
    }

    /**
     * Absolute filesystem path to the web-root documentos/ base. Ends with '/'.
     */
    public static function get_base_absolute_path(): string {
        return trailingslashit(ABSPATH) . self::BASE_SUBDIR . '/';
    }

    /**
     * Public URL to the web-root documentos/ base. Ends with '/'.
     */
    public static function get_base_url(): string {
        return trailingslashit(home_url('/' . self::BASE_SUBDIR));
    }

    /**
     * Return the persisted map.
     */
    public static function get_map(): array {
        $stored = get_option(self::OPTION_KEY, []);
        if (!is_array($stored)) {
            $stored = [];
        }
        return $stored;
    }

    /**
     * Persist the map. Sanitizes each folder name.
     */
    public static function save_map(array $map): void {
        $clean = [];
        foreach ($map as $cpt => $folder) {
            $cpt    = sanitize_key($cpt);
            $folder = self::sanitize_folder_name($folder);
            if ($cpt && $folder) {
                $clean[$cpt] = $folder;
            }
        }
        update_option(self::OPTION_KEY, $clean, false);
    }

    /**
     * Resolve the folder for a given CPT.
     * Priority: saved map → default map → sanitized CPT slug.
     */
    public static function get_folder_for_cpt(string $post_type): string {
        $post_type = sanitize_key($post_type);
        $map       = self::get_map();

        if (!empty($map[$post_type])) {
            return $map[$post_type];
        }

        $defaults = self::default_map();
        if (!empty($defaults[$post_type])) {
            return $defaults[$post_type];
        }

        return $post_type;
    }

    /**
     * Absolute filesystem path to the CPT folder. Always ends with '/'.
     * Example: /var/www/html/documentos/inscritosesal/
     */
    public static function get_absolute_path(string $post_type): string {
        return self::get_base_absolute_path() . self::get_folder_for_cpt($post_type) . '/';
    }

    /**
     * Public URL to the CPT folder. Always ends with '/'.
     * Example: https://site.com/documentos/inscritosesal/
     */
    public static function get_url(string $post_type): string {
        return self::get_base_url() . self::get_folder_for_cpt($post_type) . '/';
    }

    /**
     * Path relative to the web root, for display / logging only.
     * NOTE: not used for `_wp_attached_file` (we store the absolute path there,
     * since the file is outside wp-content/uploads).
     */
    public static function get_relative_path(string $post_type): string {
        return self::BASE_SUBDIR . '/' . self::get_folder_for_cpt($post_type) . '/';
    }

    /**
     * Create the CPT folder if missing. Returns true on success (or already exists).
     */
    public static function ensure_folder_exists(string $post_type): bool {
        $path = self::get_absolute_path($post_type);
        if (is_dir($path)) {
            return true;
        }
        return wp_mkdir_p($path);
    }

    /**
     * Sanitize a folder name: strip slashes, traversal and invalid chars.
     * Allowed: letters, numbers, dash, underscore.
     */
    public static function sanitize_folder_name(string $folder): string {
        $folder = str_replace(['..', '\\'], '', $folder);
        $folder = trim($folder, "/ \t\n\r\0\x0B");
        $folder = preg_replace('/[^A-Za-z0-9_\-]/', '', $folder);
        return (string) $folder;
    }

    /* ===========================================================
     * Hooks that make attachments living outside uploads/ resolve
     * correctly via wp_get_attachment_url() / get_attached_file().
     * =========================================================== */

    /**
     * Register the URL / path filters. Call once at plugin load.
     */
    public static function register_hooks(): void {
        add_filter('wp_get_attachment_url', [__CLASS__, 'filter_attachment_url'], 10, 2);
        add_filter('get_attached_file',     [__CLASS__, 'filter_attached_file'], 10, 2);

        // Hide docroot-managed attachments from the WordPress Media Library.
        // They still exist as attachment posts (so ACF file/image fields keep
        // working), but they are excluded from every listing surface:
        //   - Media Library modal (grid view)        → ajax_query_attachments_args
        //   - Media Library list page (upload.php)   → pre_get_posts
        //   - Gutenberg / REST media picker          → rest_attachment_query
        add_filter('ajax_query_attachments_args', [__CLASS__, 'filter_ajax_query_attachments'], 10, 1);
        add_action('pre_get_posts',               [__CLASS__, 'filter_pre_get_posts'],          10, 1);
        add_filter('rest_attachment_query',       [__CLASS__, 'filter_rest_attachment_query'],  10, 2);
    }

    /**
     * If the attachment has a stored canonical URL (web-root file), return it.
     */
    public static function filter_attachment_url($url, $post_id) {
        $stored = get_post_meta($post_id, self::META_PUBLIC_URL, true);
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }
        return $url;
    }

    /**
     * For document-root-managed attachments, return the absolute path stored
     * in `_wp_attached_file` verbatim (core would otherwise prepend uploads basedir).
     */
    public static function filter_attached_file($file, $post_id) {
        if (!get_post_meta($post_id, self::META_MARKER, true)) {
            return $file;
        }
        $raw = get_post_meta($post_id, '_wp_attached_file', true);
        if (is_string($raw) && $raw !== '' && file_exists($raw)) {
            return $raw;
        }
        return $file;
    }

    /**
     * Build a meta_query clause that excludes docroot-managed attachments.
     * Merged into existing meta_query via AND (WP will wrap in an AND group).
     */
    private static function exclude_docroot_meta_query(array $existing = []): array {
        $clause = [
            'key'     => self::META_MARKER,
            'compare' => 'NOT EXISTS',
        ];

        if (empty($existing)) {
            return [$clause];
        }

        // If there is an existing meta_query, AND our clause to it.
        if (!isset($existing['relation'])) {
            $existing = array_merge(['relation' => 'AND'], $existing);
        }
        $existing[] = $clause;
        return $existing;
    }

    /**
     * Exclude docroot attachments from the Media Library modal (grid view).
     */
    public static function filter_ajax_query_attachments(array $args): array {
        // Bypass the filter when the user explicitly asks for docroot files
        // (e.g. a future admin tool built on top of this plugin).
        if (!empty($args['formularios_include_docroot'])) {
            unset($args['formularios_include_docroot']);
            return $args;
        }

        $args['meta_query'] = self::exclude_docroot_meta_query($args['meta_query'] ?? []);
        return $args;
    }

    /**
     * Exclude docroot attachments from admin list queries on attachments
     * (upload.php list view, and any WP_Query targeting attachments in admin).
     *
     * Does not touch frontend queries nor queries for a specific post id
     * (so ACF file/image fields keep resolving attachments by ID).
     */
    public static function filter_pre_get_posts(WP_Query $query): void {
        if ($query->get('formularios_include_docroot')) {
            return;
        }

        // Only filter listing-type queries in admin context.
        if (!is_admin()) {
            return;
        }

        // Don't touch queries looking up a specific attachment by id / name.
        if ($query->get('p') || $query->get('post__in') || $query->get('name') || $query->get('attachment_id')) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== 'attachment' && $post_type !== ['attachment']) {
            return;
        }

        $existing = $query->get('meta_query');
        if (!is_array($existing)) {
            $existing = [];
        }
        $query->set('meta_query', self::exclude_docroot_meta_query($existing));
    }

    /**
     * Exclude docroot attachments from REST queries (Gutenberg / block editor
     * media picker, external clients).
     *
     * @param array            $args    Query args passed to WP_Query.
     * @param WP_REST_Request  $request REST request.
     */
    public static function filter_rest_attachment_query(array $args, $request): array {
        $args['meta_query'] = self::exclude_docroot_meta_query($args['meta_query'] ?? []);
        return $args;
    }
}
