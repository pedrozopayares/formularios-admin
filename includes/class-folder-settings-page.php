<?php
/**
 * Settings page for the CPT → folder map.
 * Renders a table with one row per registered CPT and persists the map via POST.
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_Folder_Settings_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_page'], 30);
        add_action('admin_post_formularios_save_folder_map', [$this, 'handle_save']);
        add_action('admin_post_formularios_create_folder',   [$this, 'handle_create_folder']);
    }

    public function register_page(): void {
        add_submenu_page(
            'formularios-menu',
            __('Carpetas de archivos', 'formularios-admin'),
            __('Carpetas de archivos', 'formularios-admin'),
            'manage_options',
            'formularios-folders',
            [$this, 'render_page']
        );
    }

    /**
     * Collect CPTs that belong to the "formularios" taxonomy (same logic as dashboard).
     *
     * @return array<int, WP_Post_Type>
     */
    private function get_form_cpts(): array {
        $cpts = get_post_types(['public' => true, '_builtin' => false], 'objects');
        $out  = [];
        foreach ($cpts as $cpt) {
            if (in_array('formularios', (array) $cpt->taxonomies, true)) {
                $out[] = $cpt;
            }
        }
        return $out;
    }

    public function render_page(): void {
        $cpts     = $this->get_form_cpts();
        $stored   = Formularios_Folder_Resolver::get_map();
        $defaults = Formularios_Folder_Resolver::default_map();

        $notice = '';
        if (!empty($_GET['saved'])) {
            $notice = __('Configuración guardada correctamente.', 'formularios-admin');
        } elseif (!empty($_GET['created'])) {
            $notice = sprintf(__('Carpeta "%s" creada correctamente.', 'formularios-admin'), esc_html($_GET['created']));
        } elseif (!empty($_GET['create_failed'])) {
            $notice = sprintf(__('No se pudo crear la carpeta "%s". Revisa permisos de escritura.', 'formularios-admin'), esc_html($_GET['create_failed']));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Carpetas de archivos por formulario', 'formularios-admin'); ?></h1>

            <p class="description">
                <?php
                echo esc_html(sprintf(
                    __('Cada formulario almacena sus archivos bajo %s. Configura aquí el nombre de carpeta para cada CPT. Los archivos importados desde JSON legacy se enlazarán a esta carpeta sin duplicarse, y los envíos nuevos del frontend usarán la misma ubicación.', 'formularios-admin'),
                    '<code>' . Formularios_Folder_Resolver::BASE_SUBDIR . '/&lt;carpeta&gt;/</code> ' . esc_html__('(en la raíz del sitio, junto a wp-admin/ y wp-content/)', 'formularios-admin')
                ));
                ?>
            </p>

            <?php if ($notice): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <?php if (empty($cpts)): ?>
                <div class="notice notice-warning"><p><?php esc_html_e('No hay formularios (CPT con taxonomía "formularios") registrados todavía.', 'formularios-admin'); ?></p></div>
            <?php else: ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('formularios_save_folder_map'); ?>
                    <input type="hidden" name="action" value="formularios_save_folder_map">

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width:30%"><?php esc_html_e('Formulario', 'formularios-admin'); ?></th>
                                <th style="width:20%"><?php esc_html_e('Slug (CPT)', 'formularios-admin'); ?></th>
                                <th style="width:30%"><?php esc_html_e('Carpeta (bajo documentos/ en la raíz)', 'formularios-admin'); ?></th>
                                <th style="width:20%"><?php esc_html_e('Estado', 'formularios-admin'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cpts as $cpt):
                            $slug   = $cpt->name;
                            $value  = $stored[$slug] ?? ($defaults[$slug] ?? $slug);
                            $abs    = Formularios_Folder_Resolver::get_absolute_path($slug);
                            $exists = is_dir($abs);
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($cpt->labels->name); ?></strong></td>
                                <td><code><?php echo esc_html($slug); ?></code></td>
                                <td>
                                    <input type="text"
                                           class="regular-text"
                                           name="folder_map[<?php echo esc_attr($slug); ?>]"
                                           value="<?php echo esc_attr($value); ?>"
                                           pattern="[A-Za-z0-9_\-]+"
                                           title="<?php esc_attr_e('Solo letras, números, guion y guion bajo.', 'formularios-admin'); ?>">
                                </td>
                                <td>
                                    <?php if ($exists): ?>
                                        <span style="color:#00a32a">✔ <?php esc_html_e('Existe', 'formularios-admin'); ?></span>
                                    <?php else: ?>
                                        <span style="color:#b32d2e">⚠ <?php esc_html_e('No existe', 'formularios-admin'); ?></span>
                                        <a href="<?php echo esc_url(wp_nonce_url(
                                            admin_url('admin-post.php?action=formularios_create_folder&cpt=' . rawurlencode($slug)),
                                            'formularios_create_folder_' . $slug
                                        )); ?>" class="button button-small" style="margin-left:8px">
                                            <?php esc_html_e('Crear', 'formularios-admin'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Guardar cambios', 'formularios-admin'); ?></button>
                    </p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'formularios-admin'));
        }
        check_admin_referer('formularios_save_folder_map');

        $submitted = $_POST['folder_map'] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }

        Formularios_Folder_Resolver::save_map($submitted);

        // Ensure folders exist for each configured CPT
        foreach (array_keys($submitted) as $cpt) {
            Formularios_Folder_Resolver::ensure_folder_exists($cpt);
        }

        wp_safe_redirect(add_query_arg('saved', '1', admin_url('admin.php?page=formularios-folders')));
        exit;
    }

    public function handle_create_folder(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'formularios-admin'));
        }

        $cpt = sanitize_key($_GET['cpt'] ?? '');
        check_admin_referer('formularios_create_folder_' . $cpt);

        $ok     = Formularios_Folder_Resolver::ensure_folder_exists($cpt);
        $folder = Formularios_Folder_Resolver::get_folder_for_cpt($cpt);

        $arg = $ok ? ['created' => $folder] : ['create_failed' => $folder];
        wp_safe_redirect(add_query_arg($arg, admin_url('admin.php?page=formularios-folders')));
        exit;
    }
}
