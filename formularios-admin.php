<?php
/**
 * Plugin Name: Formularios Admin (Dinámico)
 * Description: Crea el menú "Formularios" y organiza automáticamente los CPT personalizados dentro de él.
 * Version: 1.1.0
 * Author: @pedrozopayares - Impactos
 */

if (!defined('ABSPATH')) exit;

// Variable global para guardar CPT detectados
$FORMULARIOS_CPTS = [];

// ── Import Feature ──────────────────────────────────────
require_once __DIR__ . '/includes/class-import-handler.php';
require_once __DIR__ . '/includes/class-import-admin-page.php';

new Formularios_Import_Admin_Page();

// Función para dibujar la vista principal del Menú Formularios
function formularios_admin_dashboard_page() {

    // Obtener CPT asociados a la taxonomía "formularios"
    $cpts = get_post_types([
        'public'   => true,
        '_builtin' => false,
    ], 'objects');

    // Filtrar solo los que tengan la taxonomía formularios
    $filtered = [];
    foreach ($cpts as $cpt) {
        if (in_array('formularios', (array) $cpt->taxonomies)) {
            $filtered[] = $cpt;
        }
    }

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom:20px;">Formularios</h1>';
    echo '<p>Seleccione el tipo de formulario que desea administrar:</p>';

    // Estilos para las cards
    echo '
        <style>
            .form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
                margin-top: 25px;
            }
            .form-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            }
            .form-card h2 {
                margin-top: 0;
                font-size: 18px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .form-meta { 
                font-size: 13px; 
                color: #555; 
                margin-bottom: 12px;
            }
            .form-buttons a {
                display: inline-block;
                margin-right: 10px;
                padding: 6px 12px;
                background: #0073aa;
                color: #fff !important;
                border-radius: 4px;
                text-decoration: none;
            }
            .form-buttons a:hover {
                background: #005f8d;
            }
        </style>
    ';

    echo '<div class="form-grid">';

    foreach ($filtered as $cpt) {

        $count = wp_count_posts($cpt->name)->publish ?? 0;
        $icon = $cpt->menu_icon ?: 'dashicons-feedback';

        echo '<div class="form-card">';

        echo '<h2><span class="dashicons ' . esc_attr($icon) . '"></span> ' . esc_html($cpt->labels->name) . '</h2>';

        echo '<div class="form-meta">';
        echo '<strong>Slug:</strong> ' . esc_html($cpt->name) . '<br>';
        echo '<strong>Rewrite:</strong> ' . esc_html($cpt->rewrite['slug'] ?? $cpt->name) . '<br>';
        echo '<strong>Singular:</strong> ' . esc_html($cpt->labels->singular_name) . '<br>';
        echo '<strong>Total publicados:</strong> ' . intval($count);
        echo '</div>';

        // Contar verificados
        $verified_count = new WP_Query([
            'post_type'      => $cpt->name,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [['key' => '_formulario_verificado', 'value' => '1']],
            'fields'         => 'ids',
            'no_found_rows'  => false,
        ]);
        $total_verificados = $verified_count->found_posts;
        wp_reset_postdata();

        echo '<div class="form-meta">';
        echo '<strong>' . esc_html__('Verificados:', 'formularios-admin') . '</strong> ' . intval($total_verificados);
        echo '</div>';

        echo '<div class="form-buttons">';
        echo '<a href="edit.php?post_type=' . $cpt->name . '">Ver todos</a>';
        echo '<a href="post-new.php?post_type=' . $cpt->name . '">Añadir nuevo</a>';

        // Link al listado público de verificados
        $archive_link = get_post_type_archive_link($cpt->name);
        if ($archive_link) {
            echo '<a href="' . esc_url($archive_link) . '" target="_blank" style="background:#00a32a">Ver verificados</a>';
        }

        // Link de descarga Excel
        $excel_url = add_query_arg([
            'formularios_export' => 'excel',
            'post_type'          => $cpt->name,
            'nonce'              => wp_create_nonce('formularios_export_' . $cpt->name),
        ], admin_url('admin-ajax.php'));
        echo '<a href="' . esc_url($excel_url) . '" style="background:#135e96">⬇ Descargar Excel</a>';

        // Link de importar JSON
        if (current_user_can('manage_options')) {
            $import_url = admin_url('admin.php?page=formularios-import&cpt=' . $cpt->name);
            echo '<a href="' . esc_url($import_url) . '" style="background:#8c5e00">⬆ Importar JSON</a>';
        }

        echo '</div>';

        echo '</div>';
    }

    echo '</div>'; // .form-grid
    echo '</div>'; // .wrap
}


/**
 * --------------------------------------------------------
 * 1. Detectar CPT personalizados que tengan la taxonomía "formularios"
 * --------------------------------------------------------
 */
add_action('init', function () {
    global $FORMULARIOS_CPTS;

    // Obtener todos los CPT personalizados (de CPT UI o cualquier plugin/tema)
    $all_cpts = get_post_types([
        'public'   => true,
        '_builtin' => false
    ], 'objects');

    $FORMULARIOS_CPTS = [];

    // Filtrar solo los CPT que usen la taxonomía "formularios"
    foreach ($all_cpts as $cpt_name => $cpt_object) {

        if (isset($cpt_object->taxonomies) && in_array('formularios', $cpt_object->taxonomies, true)) {
            $FORMULARIOS_CPTS[] = $cpt_name;
        }
    }
});

/**
 * 2. Crear el menú principal "Formularios"
 *    → SIEMPRE con slug fijo "formularios-menu"
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Formularios',
        'Formularios',
        'edit_posts',
        'formularios-menu',     // SLUG FIJO = NO SE ROMPE
        'formularios_admin_dashboard_page',
        'dashicons-feedback',
        26
    );
}, 9);

/**
 * 3. Crear submenús para cada CPT
 */
add_action('admin_menu', function () {
    global $FORMULARIOS_CPTS;

    foreach ($FORMULARIOS_CPTS as $cpt) {

        $label = ucwords(str_replace(['-', '_'], ' ', $cpt));

        add_submenu_page(
            'formularios-menu',               // SIEMPRE apunta al menú fijo
            $label,
            $label,
            'edit_posts',
            'edit.php?post_type=' . $cpt
        );
    }
}, 20);

/**
 * 4. Ocultar los CPT originales del menú para evitar duplicados
 */
add_action('admin_menu', function () {
    global $menu, $FORMULARIOS_CPTS;

    foreach ($menu as $index => $item) {
        if (isset($item[2])) {
            foreach ($FORMULARIOS_CPTS as $cpt) {
                if ($item[2] === 'edit.php?post_type=' . $cpt) {
                    unset($menu[$index]);
                }
            }
        }
    }
}, 999);

/**
 * --------------------------------------------------------
 * 5. Campos de auditoría: verificado, verificado por, observaciones
 * --------------------------------------------------------
 */

/**
 * Determina si un post_type pertenece a los formularios gestionados.
 */
function formularios_es_cpt_formulario(string $post_type): bool {
    global $FORMULARIOS_CPTS;
    return is_array($FORMULARIOS_CPTS) && in_array($post_type, $FORMULARIOS_CPTS, true);
}

/**
 * 5a. Registrar meta box de auditoría en los CPT de formularios.
 */
add_action('add_meta_boxes', function () {
    $screen = get_current_screen();
    if (!$screen || 'post' !== $screen->base) {
        return;
    }

    if (!formularios_es_cpt_formulario($screen->post_type)) {
        return;
    }

    add_meta_box(
        'formularios_auditoria',
        __('Auditoría', 'formularios-admin'),
        'formularios_render_auditoria_meta_box',
        $screen->post_type,
        'side',
        'high'
    );
});

/**
 * Renderiza el meta box de auditoría.
 */
function formularios_render_auditoria_meta_box(WP_Post $post): void {
    wp_nonce_field('formularios_auditoria_' . $post->ID, 'formularios_auditoria_nonce');

    $verificado    = (bool) get_post_meta($post->ID, '_formulario_verificado', true);
    $verificado_por = get_post_meta($post->ID, '_formulario_verificado_por', true);
    $verificado_at = get_post_meta($post->ID, '_formulario_verificado_at', true);
    $observaciones = get_post_meta($post->ID, '_formulario_observaciones', true);

    // Checkbox verificado
    echo '<p>';
    echo '<label>';
    echo '<input type="checkbox" name="formulario_verificado" value="1" ' . checked($verificado, true, false) . '> ';
    echo '<strong>' . esc_html__('Verificado', 'formularios-admin') . '</strong>';
    echo '</label>';
    echo '</p>';

    // Info de verificación (solo lectura)
    if ($verificado && $verificado_por) {
        $user = get_userdata((int) $verificado_por);
        $nombre = $user ? $user->display_name : __('Desconocido', 'formularios-admin');
        echo '<p style="margin-top:4px;color:#00a32a">';
        echo '✅ <strong>' . esc_html__('Verificado por:', 'formularios-admin') . '</strong> ' . esc_html($nombre);
        if ($verificado_at) {
            echo '<br><small>' . esc_html($verificado_at) . '</small>';
        }
        echo '</p>';
    } elseif (!$verificado) {
        echo '<p style="color:#b32d2e"><small>⏳ ' . esc_html__('Este registro NO es visible en el sitio público hasta ser verificado.', 'formularios-admin') . '</small></p>';
    }

    // Observaciones
    echo '<hr style="margin:12px 0">';
    echo '<p><label for="formulario_observaciones"><strong>' . esc_html__('Observaciones:', 'formularios-admin') . '</strong></label></p>';
    echo '<textarea id="formulario_observaciones" name="formulario_observaciones" rows="4" style="width:100%">' . esc_textarea($observaciones) . '</textarea>';
    echo '<p class="description">' . esc_html__('Solo visible para administradores.', 'formularios-admin') . '</p>';
}

/**
 * 5b. Guardar los campos de auditoría.
 */
add_action('save_post', function (int $post_id, WP_Post $post) {
    // Verificar nonce
    if (!isset($_POST['formularios_auditoria_nonce']) ||
        !wp_verify_nonce($_POST['formularios_auditoria_nonce'], 'formularios_auditoria_' . $post_id)) {
        return;
    }

    if (!formularios_es_cpt_formulario($post->post_type)) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $verificado_anterior = (bool) get_post_meta($post_id, '_formulario_verificado', true);
    $verificado_nuevo    = !empty($_POST['formulario_verificado']);

    update_post_meta($post_id, '_formulario_verificado', $verificado_nuevo ? '1' : '');

    // Registrar quién verificó y cuándo (solo la primera vez que se marca)
    if ($verificado_nuevo && !$verificado_anterior) {
        update_post_meta($post_id, '_formulario_verificado_por', get_current_user_id());
        update_post_meta($post_id, '_formulario_verificado_at', current_time('mysql'));
    }

    // Si se desmarca, limpiar datos de verificación
    if (!$verificado_nuevo && $verificado_anterior) {
        delete_post_meta($post_id, '_formulario_verificado_por');
        delete_post_meta($post_id, '_formulario_verificado_at');
    }

    // Observaciones
    $observaciones = sanitize_textarea_field(wp_unslash($_POST['formulario_observaciones'] ?? ''));
    update_post_meta($post_id, '_formulario_observaciones', $observaciones);
}, 10, 2);

/**
 * 5c. Solo mostrar registros verificados en consultas públicas (frontend).
 *     Los registros NO verificados permanecen ocultos para los visitantes.
 */
add_action('pre_get_posts', function (WP_Query $query) {
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');

    // Si es un string simple, verificar directamente
    if (is_string($post_type) && formularios_es_cpt_formulario($post_type)) {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key'   => '_formulario_verificado',
            'value' => '1',
        ];
        $query->set('meta_query', $meta_query);
        return;
    }

    // Si es un array de post types, verificar si alguno es de formularios
    if (is_array($post_type)) {
        foreach ($post_type as $pt) {
            if (formularios_es_cpt_formulario($pt)) {
                $meta_query = $query->get('meta_query') ?: [];
                $meta_query[] = [
                    'key'   => '_formulario_verificado',
                    'value' => '1',
                ];
                $query->set('meta_query', $meta_query);
                return;
            }
        }
    }
});

/**
 * 5d. Filtrar también consultas de single posts (publicaciones individuales).
 */
add_filter('template_redirect', function () {
    if (is_admin() || !is_singular()) {
        return;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return;
    }

    if (!formularios_es_cpt_formulario($post->post_type)) {
        return;
    }

    $verificado = (bool) get_post_meta($post->ID, '_formulario_verificado', true);
    if (!$verificado) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
});

/**
 * 5e. Añadir columna "Verificado" en la tabla de listado del admin.
 */
add_filter('manage_posts_columns', function (array $columns, string $post_type): array {
    if (!formularios_es_cpt_formulario($post_type)) {
        return $columns;
    }

    // Insertar después de 'title'
    $new_columns = [];
    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ('title' === $key) {
            $new_columns['formulario_verificado']    = __('Verificado', 'formularios-admin');
            $new_columns['formulario_observaciones'] = __('Observaciones', 'formularios-admin');
        }
    }
    return $new_columns;
}, 10, 2);

add_action('manage_posts_custom_column', function (string $column, int $post_id) {
    if ('formulario_verificado' === $column) {
        $verificado = (bool) get_post_meta($post_id, '_formulario_verificado', true);
        if ($verificado) {
            $user_id = get_post_meta($post_id, '_formulario_verificado_por', true);
            $user = get_userdata((int) $user_id);
            $nombre = $user ? $user->display_name : '';
            echo '<span style="color:#00a32a" title="' . esc_attr($nombre) . '">✅ Sí</span>';
        } else {
            echo '<span style="color:#b32d2e">❌ No</span>';
        }
    }

    if ('formulario_observaciones' === $column) {
        $obs = get_post_meta($post_id, '_formulario_observaciones', true);
        if ($obs) {
            echo '<span title="' . esc_attr($obs) . '">' . esc_html(wp_trim_words($obs, 10, '…')) . '</span>';
        } else {
            echo '<span style="color:#999">—</span>';
        }
    }
}, 10, 2);

/**
 * 5f. Hacer la columna "Verificado" sortable.
 */
add_filter('manage_edit-post_sortable_columns', function (array $columns): array {
    $columns['formulario_verificado'] = 'formulario_verificado';
    return $columns;
});

add_action('pre_get_posts', function (WP_Query $query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ('formulario_verificado' === $query->get('orderby')) {
        $query->set('meta_key', '_formulario_verificado');
        $query->set('orderby', 'meta_value');
    }
});

/**
 * --------------------------------------------------------
 * 6. Shortcode [formularios_verificados] — listado público de verificados
 *    Uso: [formularios_verificados post_type="organizacion-esal"]
 * --------------------------------------------------------
 */
add_shortcode('formularios_verificados', function (array $atts = []): string {
    $atts = shortcode_atts([
        'post_type' => '',
    ], $atts, 'formularios_verificados');

    $post_type = sanitize_key($atts['post_type']);
    if (empty($post_type) || !post_type_exists($post_type)) {
        return '<p>' . esc_html__('Tipo de formulario no válido.', 'formularios-admin') . '</p>';
    }

    if (!formularios_es_cpt_formulario($post_type)) {
        return '<p>' . esc_html__('Este tipo de contenido no es un formulario gestionado.', 'formularios-admin') . '</p>';
    }

    $paged = max(1, get_query_var('paged', 1));
    $query = new WP_Query([
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'paged'          => $paged,
        'meta_query'     => [['key' => '_formulario_verificado', 'value' => '1']],
    ]);

    if (!$query->have_posts()) {
        return '<p>' . esc_html__('No hay registros verificados aún.', 'formularios-admin') . '</p>';
    }

    $cpt_obj  = get_post_type_object($post_type);
    $cpt_name = $cpt_obj ? $cpt_obj->labels->name : $post_type;

    // Obtener campos ACF del primer post para construir encabezados
    $first_post = $query->posts[0];
    $acf_fields = function_exists('get_field_objects') ? get_field_objects($first_post->ID) : [];
    if (!is_array($acf_fields)) {
        $acf_fields = [];
    }

    // Filtrar solo campos visibles (excluir tabs, messages, etc.)
    $visible_fields = [];
    foreach ($acf_fields as $field) {
        if (in_array($field['type'], ['tab', 'message', 'accordion', 'group'], true)) {
            continue;
        }
        $visible_fields[] = $field;
    }

    // Limitar a 6 columnas para que sea legible
    $display_fields = array_slice($visible_fields, 0, 6);

    ob_start();

    // Excel download link
    $excel_url = add_query_arg([
        'formularios_export' => 'excel',
        'post_type'          => $post_type,
        'nonce'              => wp_create_nonce('formularios_export_' . $post_type),
    ], home_url('/'));

    echo '<div class="formularios-verificados-wrap">';
    echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px">';
    echo '<h3 style="margin:0">' . esc_html($cpt_name) . ' — ' . esc_html__('Registros Verificados', 'formularios-admin') . ' (' . intval($query->found_posts) . ')</h3>';
    echo '<a href="' . esc_url($excel_url) . '" style="display:inline-flex;align-items:center;gap:6px;background:#135e96;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;font-size:14px">⬇ ' . esc_html__('Descargar Excel', 'formularios-admin') . '</a>';
    echo '</div>';

    echo '<div style="overflow-x:auto">';
    echo '<table class="formularios-verificados-table" style="width:100%;border-collapse:collapse;font-size:14px">';
    echo '<thead><tr style="background:#f0f0f1">';
    echo '<th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ccd0d4">' . esc_html__('Nombre', 'formularios-admin') . '</th>';
    foreach ($display_fields as $field) {
        echo '<th style="padding:10px 12px;text-align:left;border-bottom:2px solid #ccd0d4">' . esc_html($field['label']) . '</th>';
    }
    echo '</tr></thead><tbody>';

    while ($query->have_posts()) {
        $query->the_post();
        $pid = get_the_ID();
        echo '<tr style="border-bottom:1px solid #e0e0e0">';
        echo '<td style="padding:8px 12px"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></td>';
        foreach ($display_fields as $field) {
            $value = get_field($field['name'], $pid);
            $display = formularios_format_field_value($value, $field);
            echo '<td style="padding:8px 12px">' . $display . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table></div>';

    // Paginación
    $total_pages = $query->max_num_pages;
    if ($total_pages > 1) {
        echo '<div style="margin-top:16px;text-align:center">';
        echo paginate_links([
            'total'   => $total_pages,
            'current' => $paged,
        ]);
        echo '</div>';
    }

    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
});

/**
 * Formatea un valor de campo ACF para visualización en tabla.
 */
function formularios_format_field_value($value, array $field): string {
    if (is_null($value) || $value === '' || $value === false) {
        return '<span style="color:#999">—</span>';
    }

    if (is_array($value)) {
        // Choices (select, checkbox, etc.)
        if (isset($value['label'])) {
            return esc_html($value['label']);
        }
        // Multiple values
        $labels = array_map(function ($v) {
            return is_array($v) && isset($v['label']) ? $v['label'] : (is_scalar($v) ? $v : '');
        }, $value);
        return esc_html(implode(', ', array_filter($labels)));
    }

    // Boolean
    if ($field['type'] === 'true_false') {
        return $value ? '✅ Sí' : '❌ No';
    }

    // Truncate long text
    $str = (string) $value;
    if (mb_strlen($str) > 80) {
        return esc_html(mb_substr($str, 0, 80)) . '…';
    }

    return esc_html($str);
}

/**
 * --------------------------------------------------------
 * 7. Exportación a Excel (CSV UTF-8 con BOM) de registros verificados
 * --------------------------------------------------------
 */
add_action('init', function () {
    if (empty($_GET['formularios_export']) || 'excel' !== $_GET['formularios_export']) {
        return;
    }

    $post_type = sanitize_key($_GET['post_type'] ?? '');
    $nonce     = sanitize_text_field($_GET['nonce'] ?? '');

    if (empty($post_type) || !wp_verify_nonce($nonce, 'formularios_export_' . $post_type)) {
        wp_die(__('Enlace de descarga inválido o expirado.', 'formularios-admin'), 403);
    }

    if (!post_type_exists($post_type)) {
        wp_die(__('Tipo de contenido no existe.', 'formularios-admin'), 404);
    }

    // En admin requiere permisos; en frontend es público (solo verificados)
    $is_admin_request = is_admin() || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') !== false);
    if ($is_admin_request && !current_user_can('edit_posts')) {
        wp_die(__('No tienes permisos para descargar este archivo.', 'formularios-admin'), 403);
    }

    $posts = get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [['key' => '_formulario_verificado', 'value' => '1']],
    ]);

    if (empty($posts)) {
        wp_die(__('No hay registros verificados para exportar.', 'formularios-admin'), 404);
    }

    // Obtener campos ACF del primer post
    $acf_fields = function_exists('get_field_objects') ? get_field_objects($posts[0]->ID) : [];
    if (!is_array($acf_fields)) {
        $acf_fields = [];
    }

    $visible_fields = [];
    foreach ($acf_fields as $field) {
        if (in_array($field['type'], ['tab', 'message', 'accordion', 'group'], true)) {
            continue;
        }
        $visible_fields[] = $field;
    }

    $cpt_obj  = get_post_type_object($post_type);
    $cpt_label = $cpt_obj ? sanitize_file_name($cpt_obj->labels->name) : $post_type;
    $filename = $cpt_label . '-verificados-' . gmdate('Y-m-d') . '.csv';

    // Headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM para que Excel reconozca los caracteres
    fwrite($output, "\xEF\xBB\xBF");

    // Encabezados
    $headers = [__('Nombre', 'formularios-admin')];
    foreach ($visible_fields as $field) {
        $headers[] = $field['label'];
    }
    $headers[] = __('Fecha de publicación', 'formularios-admin');
    fputcsv($output, $headers, ';');

    // Filas
    foreach ($posts as $p) {
        $row = [get_the_title($p->ID)];
        foreach ($visible_fields as $field) {
            $value = get_field($field['name'], $p->ID);
            $row[] = formularios_flatten_field_value($value, $field);
        }
        $row[] = get_the_date('d/m/Y', $p->ID);
        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;
}, 1);

/**
 * Aplana un valor de campo ACF para exportación CSV.
 */
function formularios_flatten_field_value($value, array $field): string {
    if (is_null($value) || $value === '' || $value === false) {
        return '';
    }

    if ($field['type'] === 'true_false') {
        return $value ? 'Sí' : 'No';
    }

    if (is_array($value)) {
        if (isset($value['label'])) {
            return $value['label'];
        }
        $labels = array_map(function ($v) {
            return is_array($v) && isset($v['label']) ? $v['label'] : (is_scalar($v) ? (string) $v : '');
        }, $value);
        return implode(', ', array_filter($labels));
    }

    return (string) $value;
}


/* =========================================================================
 * AJAX Handlers – JSON Import
 * ========================================================================= */

/**
 * AJAX: Upload JSON file and create an import session.
 */
add_action('wp_ajax_formularios_upload_json', function () {
    check_ajax_referer('formularios_import', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes.', 'formularios-admin'));
    }

    $post_type = sanitize_key($_POST['post_type'] ?? '');
    if (empty($post_type)) {
        wp_send_json_error(__('Tipo de formulario no especificado.', 'formularios-admin'));
    }

    if (empty($_FILES['json_file'])) {
        wp_send_json_error(__('No se recibió archivo.', 'formularios-admin'));
    }

    $handler = new Formularios_Import_Handler();

    $session = $handler->upload_json($_FILES['json_file'], $post_type);
    if (is_wp_error($session)) {
        wp_send_json_error($session->get_error_message());
    }

    // Also return field introspection data so step 2 can render immediately
    $json_fields = $handler->get_json_fields($session['session_id']);
    $acf_fields  = $handler->get_acf_fields($post_type);

    wp_send_json_success([
        'session_id'  => $session['session_id'],
        'total'       => $session['total'],
        'json_fields' => is_wp_error($json_fields) ? [] : $json_fields,
        'acf_fields'  => $acf_fields,
    ]);
});

/**
 * AJAX: Save mapping and options, prepare session for processing.
 */
add_action('wp_ajax_formularios_start_import', function () {
    check_ajax_referer('formularios_import', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes.', 'formularios-admin'));
    }

    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $mapping    = json_decode(wp_unslash($_POST['mapping'] ?? '{}'), true);
    $options    = json_decode(wp_unslash($_POST['options'] ?? '{}'), true);

    if (empty($session_id) || !is_array($mapping)) {
        wp_send_json_error(__('Datos inválidos.', 'formularios-admin'));
    }

    $handler = new Formularios_Import_Handler();
    $result  = $handler->save_mapping($session_id, $mapping, $options);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success(['status' => 'ready']);
});

/**
 * AJAX: Process next batch of records.
 */
add_action('wp_ajax_formularios_process_batch', function () {
    check_ajax_referer('formularios_import', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes.', 'formularios-admin'));
    }

    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $batch_size = intval($_POST['batch_size'] ?? 10);
    $batch_size = max(1, min(50, $batch_size));

    $handler = new Formularios_Import_Handler();
    $result  = $handler->process_batch($session_id, $batch_size);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success($result);
});

/**
 * AJAX: Pause an import session.
 */
add_action('wp_ajax_formularios_pause_import', function () {
    check_ajax_referer('formularios_import', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes.', 'formularios-admin'));
    }

    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $handler    = new Formularios_Import_Handler();
    $result     = $handler->pause_session($session_id);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success(['status' => 'paused']);
});

/**
 * AJAX: Get current session status (for resume detection).
 */
add_action('wp_ajax_formularios_get_import_status', function () {
    check_ajax_referer('formularios_import', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Permisos insuficientes.', 'formularios-admin'));
    }

    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $handler    = new Formularios_Import_Handler();
    $session    = $handler->get_session($session_id);

    if (is_wp_error($session)) {
        wp_send_json_error($session->get_error_message());
    }

    wp_send_json_success([
        'session_id' => $session['session_id'],
        'status'     => $session['status'],
        'offset'     => $session['offset'],
        'total'      => $session['total'],
        'created'    => $session['created'],
        'skipped'    => $session['skipped'],
        'errors'     => $session['errors'],
    ]);
});

/**
 * --------------------------------------------------------
 * 9. Grupo ACF "Datos Internos de Proceso"
 *    Campos administrativos generados automáticamente.
 *    Se asigna dinámicamente a todos los CPT de formularios.
 *    NO se muestra en el formulario público (grupo separado).
 *    Prioridad 20 para ejecutarse después de que $FORMULARIOS_CPTS
 *    se llene en init:10.
 * --------------------------------------------------------
 */
add_action('init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    global $FORMULARIOS_CPTS;
    $cpts = !empty($FORMULARIOS_CPTS) ? $FORMULARIOS_CPTS : [];

    // Construir reglas de ubicación: un OR por cada CPT
    $location = [];
    foreach ($cpts as $cpt) {
        $location[] = [
            [
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => $cpt,
            ],
        ];
    }

    // Si no hay CPTs registrados aún, no registrar el grupo
    if (empty($location)) {
        return;
    }

    acf_add_local_field_group([
        'key'      => 'group_datos_internos_proceso',
        'title'    => 'Datos Internos de Proceso',
        'fields'   => [
            [
                'key'          => 'field_codigo_legacy',
                'label'        => 'Código Legacy',
                'name'         => 'codigo_legacy',
                'type'         => 'number',
                'instructions' => 'ID secuencial del sistema anterior. Se genera automáticamente.',
                'required'     => 0,
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_radicado',
                'label'        => 'Radicado',
                'name'         => 'radicado',
                'type'         => 'text',
                'instructions' => 'Número de radicación (PW-YYYY-MM-DD-nnn). Solo para autodeclaraciones.',
                'required'     => 0,
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_fecha_registro_original',
                'label'        => 'Fecha de Registro Original',
                'name'         => 'fecha_registro_original',
                'type'         => 'text',
                'instructions' => 'Fecha/hora del registro original.',
                'required'     => 0,
                'readonly'     => 1,
            ],
        ],
        'location'           => $location,
        'menu_order'         => 100,  // Aparece después del grupo principal
        'position'           => 'normal',
        'style'              => 'default',
        'label_placement'    => 'top',
        'instruction_placement' => 'label',
        'active'             => true,
    ]);
}, 20);

/**
 * --------------------------------------------------------
 * 10. Auto-generar campos internos al enviar formulario
 *     Escucha el hook de acf-forms-frontend-creator.
 *     CPTs con radicado generan PW-{fecha}-{consecutivo}.
 *     Todos reciben codigo_legacy y fecha_registro_original.
 * --------------------------------------------------------
 */
add_action('eff_after_submission', function (int $post_id, string $post_type, array $sanitized) {
    // Solo procesar CPTs gestionados por este plugin
    if (!formularios_es_cpt_formulario($post_type)) {
        return;
    }

    // Reutilizar el consecutivo ya generado por acf-forms-frontend-creator
    $consecutive = (int) get_option('eff_consecutive_' . $post_type, 1);

    update_field('codigo_legacy', $consecutive, $post_id);
    update_field('fecha_registro_original', current_time('Y-m-d H:i:s'), $post_id);

    // CPTs que generan número de radicado
    $cpts_con_radicado = ['autodecl-vertim', 'autodecl-aguas'];

    if (in_array($post_type, $cpts_con_radicado, true)) {
        $radicado = 'PW-' . gmdate('Y-m-d') . '-' . $consecutive;
        update_field('radicado', $radicado, $post_id);
    }
}, 10, 3);
