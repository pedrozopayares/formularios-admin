<?php
/**
 * Plugin Name: Formularios Admin (Dinámico)
 * Description: Crea el menú "Formularios" y organiza automáticamente los CPT personalizados dentro de él.
 * Version: 1.0.0
 * Author: @pedrozopayares - Impactos
 */

if (!defined('ABSPATH')) exit;

// Variable global para guardar CPT detectados
$FORMULARIOS_CPTS = [];

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

        echo '<div class="form-buttons">';
        echo '<a href="edit.php?post_type=' . $cpt->name . '">Ver todos</a>';
        echo '<a href="post-new.php?post_type=' . $cpt->name . '">Añadir nuevo</a>';
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
