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
                    .ff-cpt-block { background:#fff; border:1px solid #ccd0d4; border-radius:6px; margin:10px 0; }
                    .ff-cpt-block > summary {
                        list-style:none; cursor:pointer; padding:10px 14px;
                        display:flex; align-items:center; gap:10px; flex-wrap:wrap;
                        font-size:14px; font-weight:600; user-select:none;
                    }
                    .ff-cpt-block > summary::-webkit-details-marker { display:none; }
                    .ff-cpt-block > summary::before {
                        content:"\25B8"; color:#0073aa; font-size:12px; width:12px; display:inline-block;
                        transition:transform .15s ease;
                    }
                    .ff-cpt-block[open] > summary::before { transform:rotate(90deg); }
                    .ff-cpt-block > summary .dashicons { color:#0073aa; }
                    .ff-cpt-block > summary code { font-size:11px; color:#666; font-weight:normal; }
                    .ff-cpt-block > summary .ff-badge {
                        background:#f0f0f1; border-radius:10px; padding:1px 8px;
                        font-size:11px; font-weight:normal; color:#50575e; margin-left:auto;
                    }
                    .ff-cpt-block > summary .ff-badge.ff-on { background:#d1e7dd; color:#0f5132; }
                    .ff-body { padding:0 14px 14px; }
                    .ff-public-toggle { margin:0 0 10px; padding:8px 10px; background:#f6f7f7; border-radius:4px; font-size:13px; }
                    .ff-public-toggle code { font-size:11px; }
                    .ff-cols { display:grid; grid-template-columns:1fr; gap:14px; }
                    @media (min-width:1200px) { .ff-cols { grid-template-columns:1fr 1fr; } }
                    .ff-section { border:1px solid #e0e0e0; border-radius:4px; padding:10px 12px; background:#fbfbfc; }
                    .ff-section > header { display:flex; align-items:center; gap:8px; margin-bottom:6px; flex-wrap:wrap; }
                    .ff-section > header strong { font-size:13px; }
                    .ff-section .description { margin:0 0 6px; font-size:12px; color:#646970; }
                    .ff-fields-grid {
                        display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
                        gap:2px 12px; max-height:340px; overflow:auto;
                        padding:6px 8px; background:#fff; border:1px solid #eaeaea; border-radius:3px;
                    }
                    .ff-fields-grid label {
                        font-size:12px; display:flex; align-items:flex-start; gap:6px;
                        padding:3px 4px; line-height:1.35; min-width:0;
                        border-radius:3px;
                    }
                    .ff-fields-grid label:hover { background:#f6f7f7; }
                    .ff-fields-grid label > input { flex:0 0 auto; margin-top:2px; }
                    .ff-fields-grid label > span {
                        min-width:0; flex:1 1 auto;
                        display:block; overflow:hidden;
                    }
                    .ff-fields-grid label .ff-label-text {
                        display:block;
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
                    }
                    .ff-fields-grid label code {
                        display:block; font-size:10px; color:#999;
                        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
                        direction:ltr;
                    }
                    /* On hover show full text (wrap) */
                    .ff-fields-grid label:hover .ff-label-text,
                    .ff-fields-grid label:hover code {
                        white-space:normal; word-break:break-word;
                    }
                    .ff-toolbar { font-size:11px; margin-left:auto; }
                    .ff-toolbar a { margin-left:8px; text-decoration:none; }
                    .ff-expand-all { margin:4px 0 10px; font-size:12px; }
                    .ff-expand-all a { text-decoration:none; margin-right:12px; }
                </style>
                <p class="ff-expand-all">
                    <a href="#" id="ff-expand-all"><?php esc_html_e('Expandir todo', 'formularios-admin'); ?></a>
                    <a href="#" id="ff-collapse-all"><?php esc_html_e('Colapsar todo', 'formularios-admin'); ?></a>
                </p>

                <?php foreach ($cpts as $cpt):
                    $slug     = $cpt->name;
                    $settings = Formularios_Form_Settings::get_for($slug);
                    $fields   = Formularios_Form_Settings::get_available_fields($slug);
                    $export_url_public = add_query_arg([
                        'formularios_export' => 'excel',
                        'post_type'          => $slug,
                        'nonce'              => wp_create_nonce('formularios_export_' . $slug),
                    ], home_url('/'));
                    $n_fields  = count($fields);
                    $n_export  = count($settings['export_fields']);
                    $n_columns = count($settings['list_columns']);
                    $rad       = $settings['radicado'];
                ?>
                <details class="ff-cpt-block" data-cpt="<?php echo esc_attr($slug); ?>">
                    <summary>
                        <span class="dashicons <?php echo esc_attr($cpt->menu_icon ?: 'dashicons-feedback'); ?>"></span>
                        <?php echo esc_html($cpt->labels->name); ?>
                        <code><?php echo esc_html($slug); ?></code>
                        <span class="ff-badge"><?php
                            printf(
                                /* translators: %d: number of ACF fields */
                                esc_html(_n('%d campo', '%d campos', $n_fields, 'formularios-admin')),
                                (int) $n_fields
                            );
                        ?></span>
                        <?php if ($settings['public_export']): ?>
                            <span class="ff-badge ff-on"><?php esc_html_e('Público', 'formularios-admin'); ?></span>
                        <?php endif; ?>
                        <?php if ($rad['enabled']): ?>
                            <span class="ff-badge ff-on"><?php esc_html_e('Radicado', 'formularios-admin'); ?></span>
                        <?php endif; ?>
                        <span class="ff-badge"><?php
                            printf(esc_html__('Excel: %d', 'formularios-admin'), (int) $n_export);
                        ?></span>
                        <span class="ff-badge"><?php
                            printf(esc_html__('Col: %d', 'formularios-admin'), (int) $n_columns);
                        ?></span>
                    </summary>
                    <div class="ff-body">
                        <!-- Public export toggle -->
                        <div class="ff-public-toggle">
                            <label>
                                <input type="checkbox"
                                       name="settings[<?php echo esc_attr($slug); ?>][public_export]"
                                       value="1"
                                       <?php checked($settings['public_export']); ?>>
                                <strong><?php esc_html_e('Habilitar enlace público de descarga Excel (frontend).', 'formularios-admin'); ?></strong>
                            </label>
                            <div style="margin:4px 0 0 24px; font-size:12px; color:#646970">
                                <?php esc_html_e('Los usuarios del panel admin siempre pueden exportar, independientemente de esta opción.', 'formularios-admin'); ?>
                                <br>
                                <?php esc_html_e('Enlace público:', 'formularios-admin'); ?>
                                <code style="user-select:all"><?php echo esc_html($export_url_public); ?></code>
                            </div>
                        </div>

                        <?php if (empty($fields)): ?>
                            <p style="color:#b32d2e"><em><?php esc_html_e('No se detectaron campos ACF para este formulario.', 'formularios-admin'); ?></em></p>
                        <?php else: ?>
                            <div class="ff-cols">
                                <!-- Export fields -->
                                <div class="ff-section">
                                    <header>
                                        <strong><?php esc_html_e('Campos del Excel exportado', 'formularios-admin'); ?></strong>
                                        <span class="ff-toolbar">
                                            <a href="#" class="ff-check-all" data-group="export_fields"><?php esc_html_e('Todos', 'formularios-admin'); ?></a>
                                            <a href="#" class="ff-uncheck-all" data-group="export_fields"><?php esc_html_e('Ninguno', 'formularios-admin'); ?></a>
                                        </span>
                                    </header>
                                    <p class="description">
                                        <?php esc_html_e('Si no seleccionas ninguno, se exportarán todos.', 'formularios-admin'); ?>
                                    </p>
                                    <div class="ff-fields-grid">
                                        <?php foreach ($fields as $f): ?>
                                            <label title="<?php echo esc_attr($f['label'] . ' — ' . $f['name']); ?>">
                                                <input type="checkbox"
                                                       data-group="export_fields"
                                                       name="settings[<?php echo esc_attr($slug); ?>][export_fields][]"
                                                       value="<?php echo esc_attr($f['name']); ?>"
                                                       <?php checked(in_array($f['name'], $settings['export_fields'], true)); ?>>
                                                <span>
                                                    <span class="ff-label-text"><?php echo esc_html($f['label']); ?></span>
                                                    <code><?php echo esc_html($f['name']); ?></code>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- List columns -->
                                <div class="ff-section">
                                    <header>
                                        <strong><?php esc_html_e('Columnas disponibles en el listado', 'formularios-admin'); ?></strong>
                                        <span class="ff-toolbar">
                                            <a href="#" class="ff-check-all" data-group="list_columns"><?php esc_html_e('Todos', 'formularios-admin'); ?></a>
                                            <a href="#" class="ff-uncheck-all" data-group="list_columns"><?php esc_html_e('Ninguno', 'formularios-admin'); ?></a>
                                        </span>
                                    </header>
                                    <p class="description">
                                        <?php esc_html_e('Aparecerán en "Opciones de pantalla → Columnas" del listado.', 'formularios-admin'); ?>
                                    </p>
                                    <div class="ff-fields-grid">
                                        <?php foreach ($fields as $f): ?>
                                            <label title="<?php echo esc_attr($f['label'] . ' — ' . $f['name']); ?>">
                                                <input type="checkbox"
                                                       data-group="list_columns"
                                                       name="settings[<?php echo esc_attr($slug); ?>][list_columns][]"
                                                       value="<?php echo esc_attr($f['name']); ?>"
                                                       <?php checked(in_array($f['name'], $settings['list_columns'], true)); ?>>
                                                <span>
                                                    <span class="ff-label-text"><?php echo esc_html($f['label']); ?></span>
                                                    <code><?php echo esc_html($f['name']); ?></code>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Radicado settings -->
                        <div class="ff-section" style="margin-top:10px;">
                            <header>
                                <strong><?php esc_html_e('Radicado automático', 'formularios-admin'); ?></strong>
                            </header>
                            <p class="description">
                                <?php esc_html_e('Configura la generación automática del número de radicado al enviar el formulario.', 'formularios-admin'); ?>
                            </p>
                            <table class="form-table" style="margin:0;">
                                <tr>
                                    <th style="padding:4px 10px 4px 0;font-size:12px;width:160px;"><?php esc_html_e('Habilitado', 'formularios-admin'); ?></th>
                                    <td style="padding:4px 0;">
                                        <label>
                                            <input type="checkbox"
                                                   name="settings[<?php echo esc_attr($slug); ?>][radicado][enabled]"
                                                   value="1"
                                                   <?php checked($rad['enabled']); ?>>
                                            <?php esc_html_e('Generar radicado automáticamente', 'formularios-admin'); ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="padding:4px 10px 4px 0;font-size:12px;"><?php esc_html_e('Prefijo', 'formularios-admin'); ?></th>
                                    <td style="padding:4px 0;">
                                        <input type="text"
                                               name="settings[<?php echo esc_attr($slug); ?>][radicado][prefix]"
                                               value="<?php echo esc_attr($rad['prefix']); ?>"
                                               placeholder="PW"
                                               style="width:100px;font-family:monospace;">
                                        <span class="description" style="margin-left:6px;font-size:11px;"><?php esc_html_e('Solo letras, números, guiones y guiones bajos.', 'formularios-admin'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="padding:4px 10px 4px 0;font-size:12px;"><?php esc_html_e('Formato de fecha', 'formularios-admin'); ?></th>
                                    <td style="padding:4px 0;">
                                        <select name="settings[<?php echo esc_attr($slug); ?>][radicado][date_format]" style="font-family:monospace;">
                                            <?php
                                            $date_options = [
                                                'Y-m-d' => 'Y-m-d  (' . gmdate('Y-m-d') . ')',
                                                'Ymd'   => 'Ymd    (' . gmdate('Ymd')   . ')',
                                                'Y/m/d' => 'Y/m/d  (' . gmdate('Y/m/d') . ')',
                                                'd-m-Y' => 'd-m-Y  (' . gmdate('d-m-Y') . ')',
                                                'd/m/Y' => 'd/m/Y  (' . gmdate('d/m/Y') . ')',
                                                'Y'     => 'Y      (' . gmdate('Y')     . ')',
                                            ];
                                            foreach ($date_options as $val => $label):
                                            ?>
                                                <option value="<?php echo esc_attr($val); ?>" <?php selected($rad['date_format'], $val); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="description" style="margin-left:6px;font-size:11px;"><?php esc_html_e('Deja vacío para no incluir fecha.', 'formularios-admin'); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="padding:4px 10px 4px 0;font-size:12px;"><?php esc_html_e('Dígitos del consecutivo', 'formularios-admin'); ?></th>
                                    <td style="padding:4px 0;">
                                        <select name="settings[<?php echo esc_attr($slug); ?>][radicado][digits]">
                                            <?php for ($d = 1; $d <= 8; $d++): ?>
                                                <option value="<?php echo $d; ?>" <?php selected($rad['digits'], $d); ?>>
                                                    <?php echo $d; ?> &nbsp;(<?php echo str_pad('1', $d, '0', STR_PAD_LEFT); ?>)
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="padding:4px 10px 4px 0;font-size:12px;"><?php esc_html_e('Vista previa', 'formularios-admin'); ?></th>
                                    <td style="padding:4px 0;font-family:monospace;font-size:12px;color:#0073aa;">
                                        <?php
                                        $preview_parts = array_filter([$rad['prefix'], gmdate($rad['date_format']), str_pad('1', $rad['digits'], '0', STR_PAD_LEFT)]);
                                        echo esc_html(implode('-', $preview_parts));
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </details>
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
                        var section = a.closest('.ff-section');
                        if (!section) return;
                        var group = a.getAttribute('data-group');
                        var check = a.classList.contains('ff-check-all');
                        section.querySelectorAll('input[type=checkbox][data-group="' + group + '"]').forEach(function(cb){
                            cb.checked = check;
                        });
                    });
                });
                function toggleAll(open){
                    document.querySelectorAll('details.ff-cpt-block').forEach(function(d){
                        if (open) { d.setAttribute('open',''); } else { d.removeAttribute('open'); }
                    });
                }
                var ea = document.getElementById('ff-expand-all');
                var ca = document.getElementById('ff-collapse-all');
                if (ea) ea.addEventListener('click', function(e){ e.preventDefault(); toggleAll(true); });
                if (ca) ca.addEventListener('click', function(e){ e.preventDefault(); toggleAll(false); });
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
