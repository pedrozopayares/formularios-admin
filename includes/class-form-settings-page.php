<?php
/**
 * Admin page: per-CPT export + list column settings.
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_Form_Settings_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_page'], 31);
        add_action('admin_post_formularios_save_form_settings', [$this, 'handle_save']);
    }

    public function register_page(): void {
        add_submenu_page(
            'formularios-menu',
            __('Configuración de formularios', 'formularios-admin'),
            __('Configuración', 'formularios-admin'),
            'manage_options',
            'formularios-settings',
            [$this, 'render_page']
        );
    }

    /**
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
        $cpts = $this->get_form_cpts();
        $notice = !empty($_GET['saved']) ? __('Configuración guardada correctamente.', 'formularios-admin') : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Configuración de formularios', 'formularios-admin'); ?></h1>
            <p class="description">
                <?php esc_html_e('Configura, por cada formulario, la exportación pública a Excel (visible en el frontend), los campos incluidos en el Excel y los campos disponibles como columnas en el listado del panel de administración.', 'formularios-admin'); ?>
            </p>

            <?php if ($notice): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <?php if (empty($cpts)): ?>
                <div class="notice notice-warning"><p><?php esc_html_e('No hay formularios registrados todavía.', 'formularios-admin'); ?></p></div>
                </div>
                <?php return;
            endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('formularios_save_form_settings'); ?>
                <input type="hidden" name="action" value="formularios_save_form_settings">

                <style>
                    .ff-cpt-block { background:#fff; border:1px solid #ccd0d4; border-radius:6px; padding:18px 22px; margin:18px 0; }
                    .ff-cpt-block h2 { margin-top:0; display:flex; align-items:center; gap:8px; }
                    .ff-cpt-block h2 .dashicons { color:#0073aa; }
                    .ff-fields-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:6px 16px; margin:8px 0 4px; }
                    .ff-fields-grid label { font-size:13px; display:flex; align-items:flex-start; gap:6px; }
                    .ff-fields-grid label code { font-size:11px; color:#666; }
                    .ff-section { margin-top:14px; padding-top:12px; border-top:1px dashed #e0e0e0; }
                    .ff-section > strong { display:block; margin-bottom:4px; }
                    .ff-toolbar { font-size:12px; margin-bottom:6px; }
                    .ff-toolbar a { margin-right:10px; }
                </style>

                <?php foreach ($cpts as $cpt):
                    $slug     = $cpt->name;
                    $settings = Formularios_Form_Settings::get_for($slug);
                    $fields   = Formularios_Form_Settings::get_available_fields($slug);
                    $export_url_public = add_query_arg([
                        'formularios_export' => 'excel',
                        'post_type'          => $slug,
                        'nonce'              => wp_create_nonce('formularios_export_' . $slug),
                    ], home_url('/'));
                ?>
                <div class="ff-cpt-block" data-cpt="<?php echo esc_attr($slug); ?>">
                    <h2>
                        <span class="dashicons <?php echo esc_attr($cpt->menu_icon ?: 'dashicons-feedback'); ?>"></span>
                        <?php echo esc_html($cpt->labels->name); ?>
                        <code style="font-size:12px;color:#666;font-weight:normal"><?php echo esc_html($slug); ?></code>
                    </h2>

                    <!-- Public export toggle -->
                    <div class="ff-section" style="border-top:0;padding-top:0;margin-top:6px">
                        <label>
                            <input type="checkbox"
                                   name="settings[<?php echo esc_attr($slug); ?>][public_export]"
                                   value="1"
                                   <?php checked($settings['public_export']); ?>>
                            <strong><?php esc_html_e('Habilitar enlace público de descarga Excel (frontend).', 'formularios-admin'); ?></strong>
                        </label>
                        <p class="description" style="margin:4px 0 0 24px">
                            <?php
                            printf(
                                /* translators: %s: public export URL */
                                esc_html__('Los usuarios del panel admin siempre pueden exportar, independientemente de esta opción. Enlace público: %s', 'formularios-admin'),
                                '<code style="user-select:all">' . esc_html($export_url_public) . '</code>'
                            );
                            ?>
                        </p>
                    </div>

                    <?php if (empty($fields)): ?>
                        <p style="color:#b32d2e"><em><?php esc_html_e('No se detectaron campos ACF para este formulario.', 'formularios-admin'); ?></em></p>
                    <?php else: ?>

                        <!-- Export fields -->
                        <div class="ff-section">
                            <strong><?php esc_html_e('Campos a incluir en el Excel exportado', 'formularios-admin'); ?></strong>
                            <p class="description" style="margin:0 0 6px">
                                <?php esc_html_e('Si no seleccionas ninguno, se exportarán todos los campos del formulario.', 'formularios-admin'); ?>
                            </p>
                            <div class="ff-toolbar">
                                <a href="#" class="ff-check-all" data-group="export_fields"><?php esc_html_e('Seleccionar todos', 'formularios-admin'); ?></a>
                                <a href="#" class="ff-uncheck-all" data-group="export_fields"><?php esc_html_e('Limpiar selección', 'formularios-admin'); ?></a>
                            </div>
                            <div class="ff-fields-grid">
                                <?php foreach ($fields as $f): ?>
                                    <label>
                                        <input type="checkbox"
                                               data-group="export_fields"
                                               name="settings[<?php echo esc_attr($slug); ?>][export_fields][]"
                                               value="<?php echo esc_attr($f['name']); ?>"
                                               <?php checked(in_array($f['name'], $settings['export_fields'], true)); ?>>
                                        <span>
                                            <?php echo esc_html($f['label']); ?>
                                            <code><?php echo esc_html($f['name']); ?></code>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- List columns -->
                        <div class="ff-section">
                            <strong><?php esc_html_e('Campos disponibles como columna en el listado de wp-admin', 'formularios-admin'); ?></strong>
                            <p class="description" style="margin:0 0 6px">
                                <?php esc_html_e('Los campos marcados aparecerán en "Opciones de pantalla → Columnas" del listado, donde cada usuario podrá mostrarlos u ocultarlos individualmente.', 'formularios-admin'); ?>
                            </p>
                            <div class="ff-toolbar">
                                <a href="#" class="ff-check-all" data-group="list_columns"><?php esc_html_e('Seleccionar todos', 'formularios-admin'); ?></a>
                                <a href="#" class="ff-uncheck-all" data-group="list_columns"><?php esc_html_e('Limpiar selección', 'formularios-admin'); ?></a>
                            </div>
                            <div class="ff-fields-grid">
                                <?php foreach ($fields as $f): ?>
                                    <label>
                                        <input type="checkbox"
                                               data-group="list_columns"
                                               name="settings[<?php echo esc_attr($slug); ?>][list_columns][]"
                                               value="<?php echo esc_attr($f['name']); ?>"
                                               <?php checked(in_array($f['name'], $settings['list_columns'], true)); ?>>
                                        <span>
                                            <?php echo esc_html($f['label']); ?>
                                            <code><?php echo esc_html($f['name']); ?></code>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Guardar cambios', 'formularios-admin'); ?></button>
                </p>
            </form>

            <script>
            (function(){
                document.querySelectorAll('.ff-check-all, .ff-uncheck-all').forEach(function(a){
                    a.addEventListener('click', function(e){
                        e.preventDefault();
                        var block = a.closest('.ff-cpt-block');
                        if (!block) return;
                        var group = a.getAttribute('data-group');
                        var check = a.classList.contains('ff-check-all');
                        block.querySelectorAll('input[type=checkbox][data-group="' + group + '"]').forEach(function(cb){
                            cb.checked = check;
                        });
                    });
                });
            })();
            </script>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'formularios-admin'));
        }
        check_admin_referer('formularios_save_form_settings');

        $submitted = $_POST['settings'] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }

        Formularios_Form_Settings::save_all($submitted);

        wp_safe_redirect(add_query_arg('saved', '1', admin_url('admin.php?page=formularios-settings')));
        exit;
    }
}
