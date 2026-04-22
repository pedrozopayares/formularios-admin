<?php
/**
 * Per-CPT form settings: public export toggle, exportable fields, and
 * admin list columns.
 *
 * Storage format (option `formularios_form_settings`):
 *
 *   [
 *     'organizacion-esal' => [
 *         'public_export' => true,              // show public download link
 *         'export_fields' => ['nit','municipio', ...], // empty = all visible
 *         'list_columns'  => ['nit','municipio'],      // empty = none
 *     ],
 *     ...
 *   ]
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_Form_Settings {

    const OPTION_KEY = 'formularios_form_settings';

    /**
     * @return array<string, array{public_export:bool,export_fields:array,list_columns:array}>
     */
    public static function get_all(): array {
        $raw = get_option(self::OPTION_KEY, []);
        return is_array($raw) ? $raw : [];
    }

    public static function get_for(string $post_type): array {
        $all = self::get_all();
        $row = $all[$post_type] ?? [];
        return [
            'public_export' => !empty($row['public_export']),
            'export_fields' => isset($row['export_fields']) && is_array($row['export_fields']) ? array_values($row['export_fields']) : [],
            'list_columns'  => isset($row['list_columns'])  && is_array($row['list_columns'])  ? array_values($row['list_columns'])  : [],
        ];
    }

    public static function save_all(array $data): void {
        $clean = [];
        foreach ($data as $pt => $row) {
            $pt = sanitize_key($pt);
            if ($pt === '') continue;
            $clean[$pt] = [
                'public_export' => !empty($row['public_export']),
                'export_fields' => array_values(array_filter(array_map('sanitize_key', (array) ($row['export_fields'] ?? [])))),
                'list_columns'  => array_values(array_filter(array_map('sanitize_key', (array) ($row['list_columns']  ?? [])))),
            ];
        }
        update_option(self::OPTION_KEY, $clean, false);
    }

    /**
     * Is the public export link enabled for this CPT?
     */
    public static function is_public_export_enabled(string $post_type): bool {
        $s = self::get_for($post_type);
        return $s['public_export'];
    }

    /**
     * List of field definitions available for a CPT, pulled from ACF field groups.
     * Structural fields (tab, message, accordion, group) are filtered out.
     *
     * @return array<int, array{name:string,label:string,type:string,key:string}>
     */
    public static function get_available_fields(string $post_type): array {
        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return [];
        }

        $groups = acf_get_field_groups(['post_type' => $post_type]);
        $out    = [];
        $seen   = [];

        foreach ($groups as $group) {
            $fields = acf_get_fields($group['key']) ?: [];
            foreach ($fields as $f) {
                self::collect_field($f, $out, $seen);
            }
        }
        return $out;
    }

    /**
     * Recursively flatten ACF fields (group children are included).
     */
    private static function collect_field(array $field, array &$out, array &$seen): void {
        $type = $field['type'] ?? '';

        if (in_array($type, ['tab', 'message', 'accordion'], true)) {
            return;
        }

        if ($type === 'group') {
            foreach ((array) ($field['sub_fields'] ?? []) as $sub) {
                self::collect_field($sub, $out, $seen);
            }
            return;
        }

        $name = $field['name'] ?? '';
        if ($name === '' || isset($seen[$name])) {
            return;
        }
        $seen[$name] = true;
        $out[] = [
            'name'  => $name,
            'label' => $field['label'] ?? $name,
            'type'  => $type,
            'key'   => $field['key'] ?? '',
        ];
    }

    /**
     * Return the fields that should appear in an Excel export for this CPT.
     * If the user has selected none, fall back to all available fields.
     *
     * @return array<int, array{name:string,label:string,type:string,key:string}>
     */
    public static function get_export_fields(string $post_type): array {
        $all      = self::get_available_fields($post_type);
        $settings = self::get_for($post_type);
        $picked   = $settings['export_fields'];

        if (empty($picked)) {
            return $all;
        }

        $by_name = [];
        foreach ($all as $f) {
            $by_name[$f['name']] = $f;
        }
        $out = [];
        foreach ($picked as $name) {
            if (isset($by_name[$name])) {
                $out[] = $by_name[$name];
            }
        }
        return $out;
    }

    /**
     * Return the fields that should be registered as admin list columns.
     *
     * @return array<int, array{name:string,label:string,type:string,key:string}>
     */
    public static function get_list_column_fields(string $post_type): array {
        $settings = self::get_for($post_type);
        $picked   = $settings['list_columns'];
        if (empty($picked)) {
            return [];
        }
        $all     = self::get_available_fields($post_type);
        $by_name = [];
        foreach ($all as $f) {
            $by_name[$f['name']] = $f;
        }
        $out = [];
        foreach ($picked as $name) {
            if (isset($by_name[$name])) {
                $out[] = $by_name[$name];
            }
        }
        return $out;
    }

    /**
     * Register `manage_{cpt}_posts_columns` + render hooks so the configured
     * ACF fields appear as columns (and thus in Screen Options → Columns).
     */
    public static function register_list_columns(): void {
        // We need the CPTs to already be registered; hook runs on admin_init.
        $cpts = get_post_types(['public' => true, '_builtin' => false], 'objects');
        foreach ($cpts as $cpt) {
            if (!in_array('formularios', (array) $cpt->taxonomies, true)) {
                continue;
            }
            $slug = $cpt->name;
            $fields = self::get_list_column_fields($slug);
            if (empty($fields)) {
                continue;
            }

            add_filter("manage_{$slug}_posts_columns", function (array $cols) use ($fields) {
                // Insert ACF columns before 'date' if present.
                $tail = [];
                if (isset($cols['date'])) {
                    $tail['date'] = $cols['date'];
                    unset($cols['date']);
                }
                foreach ($fields as $f) {
                    $cols['fa_' . $f['name']] = esc_html($f['label']);
                }
                return array_merge($cols, $tail);
            });

            add_action("manage_{$slug}_posts_custom_column", function ($column, $post_id) use ($fields) {
                if (strpos($column, 'fa_') !== 0) {
                    return;
                }
                $name = substr($column, 3);
                foreach ($fields as $f) {
                    if ($f['name'] !== $name) continue;
                    $value = function_exists('get_field') ? get_field($name, $post_id) : get_post_meta($post_id, $name, true);
                    echo self::render_column_value($value, $f);
                    return;
                }
            }, 10, 2);
        }
    }

    /**
     * Minimal escaped renderer for column values.
     */
    private static function render_column_value($value, array $field): string {
        if ($value === '' || $value === null || $value === false) {
            return '<span style="color:#999">—</span>';
        }
        if (is_array($value)) {
            if (isset($value['label'])) {
                return esc_html((string) $value['label']);
            }
            if (isset($value['url'])) {
                return '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html__('Ver archivo', 'formularios-admin') . '</a>';
            }
            $labels = array_map(function ($v) {
                if (is_array($v) && isset($v['label'])) return $v['label'];
                if (is_scalar($v)) return (string) $v;
                return '';
            }, $value);
            return esc_html(implode(', ', array_filter($labels)));
        }
        if ($field['type'] === 'true_false') {
            return $value ? '✅' : '—';
        }
        if ($field['type'] === 'image' || $field['type'] === 'file') {
            $url = is_numeric($value) ? wp_get_attachment_url((int) $value) : (string) $value;
            if ($url) {
                return '<a href="' . esc_url($url) . '" target="_blank">' . esc_html__('Ver archivo', 'formularios-admin') . '</a>';
            }
        }
        $str = (string) $value;
        if (mb_strlen($str) > 60) {
            return esc_html(mb_substr($str, 0, 60)) . '…';
        }
        return esc_html($str);
    }
}
