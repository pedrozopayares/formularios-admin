<?php
/**
 * Per-CPT user notification email settings.
 *
 * Storage format (option `formularios_user_notification_settings`):
 *
 *   [
 *     'organizacion-esal' => [
 *         'enabled'       => true,                     // toggle send/no-send
 *         'email_field'   => 'email',                  // ACF field slug = email destination
 *         'subject'       => 'Confirmación de registro', // email subject
 *         'body_html'     => '<p>Gracias...</p>',      // email body (HTML)
 *     ],
 *     ...
 *   ]
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_User_Notification_Settings {

    const OPTION_KEY = 'formularios_user_notification_settings';

    /**
     * @return array<string, array{enabled:bool,email_field:string,subject:string,body_html:string}>
     */
    public static function get_all(): array {
        $raw = get_option(self::OPTION_KEY, []);
        return is_array($raw) ? $raw : [];
    }

    /**
     * @return array{enabled:bool,email_field:string,subject:string,body_html:string}
     */
    public static function get_for(string $post_type): array {
        $all = self::get_all();
        $row = $all[$post_type] ?? [];
        return [
            'enabled'     => !empty($row['enabled']),
            'email_field' => isset($row['email_field']) ? (string) $row['email_field'] : '',
            'subject'     => isset($row['subject']) ? (string) $row['subject'] : '',
            'body_html'   => isset($row['body_html']) ? (string) $row['body_html'] : '',
        ];
    }

    public static function save_all(array $data): void {
        $clean = [];
        foreach ($data as $pt => $row) {
            $pt = sanitize_key($pt);
            if ($pt === '') continue;
            $clean[$pt] = [
                'enabled'     => !empty($row['enabled']),
                'email_field' => isset($row['email_field']) ? sanitize_key($row['email_field']) : '',
                'subject'     => isset($row['subject']) ? wp_kses_post($row['subject']) : '',
                'body_html'   => isset($row['body_html']) ? wp_kses_post($row['body_html']) : '',
            ];
        }
        update_option(self::OPTION_KEY, $clean, false);
    }

    /**
     * Is user notification email enabled for this CPT?
     */
    public static function is_enabled(string $post_type): bool {
        $s = self::get_for($post_type);
        return $s['enabled'];
    }

    /**
     * List of email field definitions available for a CPT (only type='email').
     *
     * @return array<int, array{name:string,label:string,type:string,key:string}>
     */
    public static function get_available_email_fields(string $post_type): array {
        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Formularios Admin: ACF functions missing when scanning email fields for post_type={$post_type}");
            }
            return [];
        }

        $groups = acf_get_field_groups(['post_type' => $post_type]);
        $out    = [];
        $seen   = [];

        if (empty($groups)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Formularios Admin: No ACF field groups found for post_type={$post_type}");
            }
        }

        foreach ($groups as $group) {
            $fields = acf_get_fields($group['key']) ?: [];
            foreach ($fields as $f) {
                self::collect_email_field($f, $out, $seen);
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf("Formularios Admin: Found %d email field(s) for post_type=%s", count($out), $post_type));
        }

        return $out;
    }

    /**
     * Recursively collect only email fields (ignoring group nesting).
     */
    private static function collect_email_field(array $field, array &$out, array &$seen): void {
        // If this field contains nested fields (group, repeater, flexible, etc.), recurse first.
        if (!empty($field['sub_fields']) && is_array($field['sub_fields'])) {
            foreach ($field['sub_fields'] as $sub) {
                self::collect_email_field($sub, $out, $seen);
            }
        }

        // Flexible content: iterate layouts' sub_fields
        if (!empty($field['layouts']) && is_array($field['layouts'])) {
            foreach ($field['layouts'] as $layout) {
                foreach ((array) ($layout['sub_fields'] ?? []) as $sub) {
                    self::collect_email_field($sub, $out, $seen);
                }
            }
        }

        // Clone fields may reference other fields — try to expand them when possible.
        if (!empty($field['clone']) && is_array($field['clone']) && function_exists('acf_get_field')) {
            foreach ($field['clone'] as $clone_key) {
                $cloned = acf_get_field($clone_key);
                if ($cloned) {
                    self::collect_email_field($cloned, $out, $seen);
                }
            }
        }

        $type = $field['type'] ?? '';

        if ($type !== 'email') {
            return;
        }

        $key  = $field['key']  ?? '';
        $name = $field['name'] ?? '';
        $id   = $name !== '' ? $name : $key;

        if ($id === '' || isset($seen[$id])) {
            return;
        }
        $seen[$id] = true;

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
}
