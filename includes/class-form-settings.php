<?php
/**
 * Per-CPT form settings: public export toggle, exportable fields,
 * admin list columns, and radicado generation.
 *
 * Storage format (option `formularios_form_settings`):
 *
 *   [
 *     'organizacion-esal' => [
 *         'public_export' => true,
 *         'export_fields' => ['nit','municipio', ...],
 *         'list_columns'  => ['nit','municipio'],
 *         'radicado'      => [
 *             'enabled'     => true,
 *             'prefix'      => 'PW',
 *             'date_format' => 'Y-m-d',
 *             'digits'      => 3,
 *         ],
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
        $rad = isset($row['radicado']) && is_array($row['radicado']) ? $row['radicado'] : [];
        return [
            'public_export' => !empty($row['public_export']),
            'export_fields' => isset($row['export_fields']) && is_array($row['export_fields']) ? array_values($row['export_fields']) : [],
            'list_columns'  => isset($row['list_columns'])  && is_array($row['list_columns'])  ? array_values($row['list_columns'])  : [],
            'radicado'      => [
                'enabled'     => !array_key_exists('enabled', $rad) || !empty($rad['enabled']),
                'prefix'      => isset($rad['prefix'])      ? (string) $rad['prefix']      : 'PW',
                'date_format' => isset($rad['date_format']) ? (string) $rad['date_format'] : 'Y-m-d',
                'digits'      => isset($rad['digits'])      ? (int)    $rad['digits']      : 3,
            ],
        ];
    }

    public static function save_all(array $data): void {
        $allowed_date_formats = ['Y-m-d', 'Ymd', 'Y/m/d', 'd-m-Y', 'd/m/Y', 'Y'];
        $clean = [];
        foreach ($data as $pt => $row) {
            $pt = sanitize_key($pt);
            if ($pt === '') continue;
            $rad = isset($row['radicado']) && is_array($row['radicado']) ? $row['radicado'] : [];
            $digits = max(1, min(8, (int) ($rad['digits'] ?? 3)));
            $date_fmt = in_array($rad['date_format'] ?? '', $allowed_date_formats, true)
                ? $rad['date_format']
                : 'Y-m-d';
            $clean[$pt] = [
                'public_export' => !empty($row['public_export']),
                'export_fields' => array_values(array_filter(array_map('sanitize_key', (array) ($row['export_fields'] ?? [])))),
                'list_columns'  => array_values(array_filter(array_map('sanitize_key', (array) ($row['list_columns']  ?? [])))),
                'radicado'      => [
                    'enabled'     => !empty($rad['enabled']),
                    'prefix'      => strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', (string) ($rad['prefix'] ?? 'PW'))),
                    'date_format' => $date_fmt,
                    'digits'      => $digits,
                ],
            ];
        }
        update_option(self::OPTION_KEY, $clean, false);
    }

    /**
     * Return radicado generation settings for a CPT.
     *
     * @return array{enabled:bool,prefix:string,date_format:string,digits:int}
     */
    public static function get_radicado_settings(string $post_type): array {
        return self::get_for($post_type)['radicado'];
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
     *
     * Some legacy/imported ACF field groups have an empty `post_excerpt` in the
     * database, which causes ACF to return fields with an empty `name`. In that
     * case we fall back to the field `key` as the identifier, which `get_field()`
     * also accepts for value retrieval.
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

        $key  = $field['key']  ?? '';
        $name = $field['name'] ?? '';

        // Stable identifier: prefer `name` (human-readable meta key), fall back
        // to `key` when the DB row has a broken post_excerpt.
        $id = $name !== '' ? $name : $key;
        if ($id === '' || isset($seen[$id])) {
            return;
        }
        $seen[$id] = true;

        // Label: use declared label, else the name, else derive from the key
        // by stripping a leading "field_" and the next underscore-separated token
        // (which is typically a group prefix like "gacu_").
        $label = $field['label'] ?? '';
        if ($label === '') {
            if ($name !== '') {
                $label = $name;
            } else {
                $stripped = preg_replace('/^field_[^_]+_/', '', $key);
                $label    = str_replace('_', ' ', $stripped ?: $key);
            }
        }

        $out[] = [
            'name'  => $id,
            'label' => $label,
            'type'  => $type,
            'key'   => $key,
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
