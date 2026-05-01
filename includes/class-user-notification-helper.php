<?php
/**
 * Helper class for processing user notification emails:
 * - Placeholder replacement
 * - HTML table generation from form data
 * - Email validation
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_User_Notification_Helper {

    /**
     * Replace placeholders in template with actual values.
     *
     * Available placeholders:
     * - {site_name}: blog name
     * - {site_url}: site URL
     * - {post_type}: CPT slug
     * - {post_title}: post title (usually the consecutive ID)
     * - {post_id}: post ID
     * - {radicado}: value of the 'radicado' field (if present in data)
     * - {field_name}: any field from submitted_data (e.g., {email}, {nombre}, etc.)
     * - {all_fields}: HTML table of all submitted fields
     *
     * @param string $template HTML or plain text with placeholders
     * @param int    $post_id   Post ID
     * @param array  $submitted_data ACF form data [field_name => value]
     * @return string Template with placeholders replaced
     */
    public static function process_placeholders(string $template, int $post_id, array $submitted_data): string {
        if (!$post_id || $template === '') {
            return $template;
        }

        $post = get_post($post_id);
        if (!$post) {
            return $template;
        }

        $replacements = [
            '{site_name}'  => get_bloginfo('name'),
            '{site_url}'   => home_url('/'),
            '{post_type}'  => $post->post_type,
            '{post_title}' => $post->post_title,
            '{post_id}'    => (string) $post_id,
            '{radicado}'   => isset($submitted_data['radicado']) ? (string) $submitted_data['radicado'] : '',
        ];

        // Add field-specific placeholders
        foreach ($submitted_data as $field_name => $value) {
            // Skip array/complex values for now
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $replacements['{' . $field_name . '}'] = (string) $value;
        }

        // Replace all standard placeholders
        $output = strtr($template, $replacements);

        // Replace {all_fields} with HTML table
        if (strpos($output, '{all_fields}') !== false) {
            $table = self::generate_data_table($submitted_data);
            $output = str_replace('{all_fields}', $table, $output);
        }

        return $output;
    }

    /**
     * Generate an HTML table of all submitted form fields.
     *
     * @param array $submitted_data [field_name => value]
     * @return string HTML table
     */
    public static function generate_data_table(array $submitted_data): string {
        if (empty($submitted_data)) {
            return '';
        }

        $html = '<table style="width:100%;border-collapse:collapse;margin:10px 0;">';
        $html .= '<tbody>';

        foreach ($submitted_data as $field_name => $value) {
            // Skip complex types
            if (is_array($value) || is_object($value)) {
                continue;
            }

            // Humanize field name
            $label = ucwords(str_replace('_', ' ', $field_name));
            $val   = esc_html($value);

            $html .= sprintf(
                '<tr style="border-bottom:1px solid #eee;"><td style="padding:6px;font-weight:bold;width:40%%;">%s</td><td style="padding:6px;">%s</td></tr>',
                esc_html($label),
                $val
            );
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Validate and return email value from submitted data.
     * Returns empty string if invalid or not found.
     *
     * @param string $email_field_slug ACF field slug
     * @param array  $submitted_data   Form data
     * @return string Valid email or empty string
     */
    public static function extract_email(string $email_field_slug, array $submitted_data): string {
        if ($email_field_slug === '' || !isset($submitted_data[$email_field_slug])) {
            return '';
        }

        $email = (string) $submitted_data[$email_field_slug];
        $email = trim($email);

        // Validate email
        if (!is_email($email)) {
            return '';
        }

        return $email;
    }

    /**
     * Send notification email to user.
     *
     * @param string $to_email    Recipient email
     * @param string $subject     Email subject
     * @param string $body_html   Email body (already processed with placeholders)
     * @return bool True if mail was accepted for delivery, false otherwise
     */
    public static function send_notification_email(string $to_email, string $subject, string $body_html): bool {
        if (!is_email($to_email)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Formularios Admin: Invalid email address: {$to_email}");
            }
            return false;
        }

        // Prepare headers for HTML email
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $from    = get_option('admin_email');

        if ($from && is_email($from)) {
            $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $from . '>';
        }

        // Log email details before sending
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Formularios Admin: Attempting to send user notification email to {$to_email}");
            error_log("Formularios Admin:   Subject: {$subject}");
            error_log("Formularios Admin:   From: {$from}");
            error_log("Formularios Admin:   Body length: " . strlen($body_html) . " chars");
        }

        // Send email
        $sent = wp_mail($to_email, $subject, $body_html, $headers);

        // Log result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($sent) {
                error_log("Formularios Admin: User notification email sent successfully to {$to_email}");
            } else {
                error_log("Formularios Admin: FAILED to send user notification email to {$to_email}");
                error_log("Formularios Admin: Check SMTP/mail configuration. wp_mail() returned false.");
            }
        }

        return $sent;
    }
}
