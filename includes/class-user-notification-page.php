<?php
/**
 * Admin page: per-CPT user notification email settings.
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_User_Notification_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_page'], 32);
        add_action('admin_post_formularios_save_user_notifications', [$this, 'handle_save']);
    }

    public function register_page(): void {
        add_submenu_page(
            'formularios-menu',
            __('Notificaciones Usuario', 'formularios-admin'),
            __('Notificaciones Usuario', 'formularios-admin'),
            'manage_options',
            'formularios-user-notifications',
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
            <h1><?php esc_html_e('Notificaciones al Usuario', 'formularios-admin'); ?></h1>
            <p class="description">
                <?php esc_html_e('Configura, para cada formulario, si se debe enviar un email de confirmación al usuario después de diligenciar el formulario. El email se envía inmediatamente cuando el usuario completa el envío en el frontend.', 'formularios-admin'); ?>
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
                <?php wp_nonce_field('formularios_save_user_notifications'); ?>
                <input type="hidden" name="action" value="formularios_save_user_notifications">

                <style>
                    .un-cpt-block { background:#fff; border:1px solid #ccd0d4; border-radius:6px; margin:10px 0; }
                    .un-cpt-block > summary {
                        list-style:none; cursor:pointer; padding:10px 14px;
                        display:flex; align-items:center; gap:10px; flex-wrap:wrap;
                        font-size:14px; font-weight:600; user-select:none;
                    }
                    .un-cpt-block > summary::-webkit-details-marker { display:none; }
                    .un-cpt-block > summary::before {
                        content:"\25B8"; color:#0073aa; font-size:12px; width:12px; display:inline-block;
                        transition:transform .15s ease;
                    }
                    .un-cpt-block[open] > summary::before { transform:rotate(90deg); }
                    .un-cpt-block > summary .dashicons { color:#0073aa; }
                    .un-cpt-block > summary code { font-size:11px; color:#666; font-weight:normal; }
                    .un-cpt-block > summary .un-badge {
                        background:#f0f0f1; border-radius:10px; padding:1px 8px;
                        font-size:11px; font-weight:normal; color:#50575e; margin-left:auto;
                    }
                    .un-cpt-block > summary .un-badge.un-on { background:#d1e7dd; color:#0f5132; }
                    .un-body { padding:14px; }
                    .un-toggle-section { margin:0 0 16px; padding:10px; background:#f6f7f7; border-radius:4px; border-left:3px solid #0073aa; }
                    .un-toggle-section label { display:flex; align-items:center; gap:8px; font-weight:500; font-size:13px; cursor:pointer; margin:0; }
                    .un-toggle-section input[type="checkbox"] { cursor:pointer; }
                    .un-toggle-section .un-status {
                        margin-left:auto; font-size:11px; font-weight:normal; padding:2px 8px;
                        border-radius:3px; background:#e0e0e0; color:#333;
                    }
                    .un-toggle-section[data-enabled="true"] .un-status {
                        background:#d1e7dd; color:#0f5132;
                    }
                    .un-fields { display:grid; grid-template-columns:1fr; gap:12px; }
                    .un-field-group label { display:block; margin-bottom:4px; font-weight:600; font-size:12px; color:#0073aa; }
                    .un-field-group select,
                    .un-field-group input[type="text"] {
                        width:100%; padding:6px 8px; border:1px solid #ccd0d4; border-radius:3px;
                        font-size:13px;
                    }
                    .un-field-group select:focus,
                    .un-field-group input[type="text"]:focus {
                        outline:none; border-color:#0073aa; box-shadow:0 0 0 2px rgba(0,115,170,0.1);
                    }
                    .un-field-group .description {
                        font-size:12px; color:#646970; margin-top:4px; display:block;
                    }
                    .un-editor { margin-top:8px; }
                    .un-editor-label { display:block; margin-bottom:8px; font-weight:600; font-size:12px; color:#0073aa; }
                    .un-placeholder-list { background:#f6f7f7; border:1px solid #ddd; border-radius:3px; padding:10px; margin:8px 0; font-size:12px; line-height:1.5; }
                    .un-placeholder-list strong { display:block; margin-bottom:6px; color:#0073aa; }
                    .un-placeholder-list code {
                        display:inline-block; background:#fff; border:1px solid #ccc; border-radius:2px;
                        padding:1px 4px; margin-right:6px; margin-bottom:4px; font-size:11px; }
                    .un-expand-all { margin:4px 0 10px; font-size:12px; }
                    .un-expand-all a { text-decoration:none; margin-right:12px; }
                </style>

                <p class="un-expand-all">
                    <a href="#" id="un-expand-all"><?php esc_html_e('Expandir todo', 'formularios-admin'); ?></a>
                    <a href="#" id="un-collapse-all"><?php esc_html_e('Colapsar todo', 'formularios-admin'); ?></a>
                </p>

                <?php foreach ($cpts as $cpt):
                    $slug     = $cpt->name;
                    $settings = Formularios_User_Notification_Settings::get_for($slug);
                    $email_fields = Formularios_User_Notification_Settings::get_available_email_fields($slug);
                    $enabled  = $settings['enabled'];
                    $badge_class = $enabled ? 'un-on' : '';
                ?>
                <details class="un-cpt-block" data-cpt="<?php echo esc_attr($slug); ?>">
                    <summary>
                        <span class="dashicons <?php echo esc_attr($cpt->menu_icon ?: 'dashicons-feedback'); ?>"></span>
                        <?php echo esc_html($cpt->labels->name); ?>
                        <code><?php echo esc_html($slug); ?></code>
                        <span class="un-badge <?php echo esc_attr($badge_class); ?>">
                            <?php echo $enabled ? esc_html__('Notificaciones activas', 'formularios-admin') : esc_html__('Desactivadas', 'formularios-admin'); ?>
                        </span>
                    </summary>
                    <div class="un-body">
                        <!-- Enable toggle -->
                        <div class="un-toggle-section" data-enabled="<?php echo $enabled ? 'true' : 'false'; ?>">
                            <label>
                                <input type="checkbox"
                                       class="un-toggle-enabled"
                                       name="settings[<?php echo esc_attr($slug); ?>][enabled]"
                                       value="1"
                                       <?php checked($enabled); ?>>
                                <strong><?php esc_html_e('Enviar email de confirmación al usuario', 'formularios-admin'); ?></strong>
                                <span class="un-status">
                                    <?php echo $enabled ? esc_html__('Activo', 'formularios-admin') : esc_html__('Inactivo', 'formularios-admin'); ?>
                                </span>
                            </label>
                            <p class="description" style="margin:6px 0 0 32px;">
                                <?php esc_html_e('El email se enviará inmediatamente después de que el usuario complete el formulario en el frontend. Se requiere que el formulario tenga un campo de tipo email.', 'formularios-admin'); ?>
                            </p>
                        </div>

                        <?php if (empty($email_fields)): ?>
                            <div class="notice notice-warning" style="margin:10px 0;">
                                <p>
                                    <strong><?php esc_html_e('Atención:', 'formularios-admin'); ?></strong>
                                    <?php esc_html_e('Este formulario no tiene campos de tipo email en sus grupos de campos ACF. Las notificaciones no se enviarán aunque estén habilitadas.', 'formularios-admin'); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="un-fields" style="<?php echo !$enabled ? 'opacity:0.6;pointer-events:none;' : ''; ?>">
                            <!-- Email field selector -->
                            <div class="un-field-group">
                                <label for="email_field_<?php echo esc_attr($slug); ?>">
                                    <?php esc_html_e('Campo de email para enviar la confirmación', 'formularios-admin'); ?>
                                </label>
                                <select id="email_field_<?php echo esc_attr($slug); ?>"
                                        name="settings[<?php echo esc_attr($slug); ?>][email_field]"
                                        <?php disabled(empty($email_fields)); ?>>
                                    <option value=""><?php esc_html_e('— Seleccionar —', 'formularios-admin'); ?></option>
                                    <?php foreach ($email_fields as $f): ?>
                                        <option value="<?php echo esc_attr($f['name']); ?>"
                                                <?php selected($settings['email_field'], $f['name']); ?>>
                                            <?php echo esc_html($f['label']); ?>
                                            <code>(<?php echo esc_html($f['name']); ?>)</code>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="description">
                                    <?php esc_html_e('Elige el campo ACF que contiene el email del usuario.', 'formularios-admin'); ?>
                                </span>
                            </div>

                            <!-- Subject -->
                            <div class="un-field-group">
                                <label for="subject_<?php echo esc_attr($slug); ?>">
                                    <?php esc_html_e('Asunto del email', 'formularios-admin'); ?>
                                </label>
                                <input type="text"
                                       id="subject_<?php echo esc_attr($slug); ?>"
                                       name="settings[<?php echo esc_attr($slug); ?>][subject]"
                                       value="<?php echo esc_attr($settings['subject']); ?>"
                                       placeholder="<?php esc_attr_e('Confirmación de registro en {site_name}', 'formularios-admin'); ?>">
                                <span class="description">
                                    <?php esc_html_e('Usa placeholders como {site_name}, {post_title}, etc.', 'formularios-admin'); ?>
                                </span>
                            </div>

                            <!-- Body HTML -->
                            <div class="un-field-group">
                                <label for="body_html_<?php echo esc_attr($slug); ?>">
                                    <?php esc_html_e('Cuerpo del email', 'formularios-admin'); ?>
                                </label>
                                <div class="un-placeholder-list">
                                    <strong><?php esc_html_e('Placeholders disponibles:', 'formularios-admin'); ?></strong>
                                    <div>
                                        <code>{site_name}</code>
                                        <code>{site_url}</code>
                                        <code>{post_type}</code>
                                        <code>{post_title}</code>
                                        <code>{post_id}</code>
                                        <code>{radicado}</code>
                                        <code>{field_name}</code>
                                        <code>{all_fields}</code>
                                    </div>
                                </div>
                                <?php
                                wp_editor(
                                    $settings['body_html'],
                                    'body_html_' . $slug,
                                    [
                                        'textarea_name' => 'settings[' . $slug . '][body_html]',
                                        'textarea_rows' => 8,
                                        'media_buttons' => false,
                                        'teeny'         => false,
                                        'tinymce'       => [
                                            'toolbar1' => 'bold,italic,underline,|,link,unlink,|,undo,redo',
                                            'toolbar2' => '',
                                        ],
                                        'quicktags'     => ['buttons' => 'strong,em,link,close'],
                                    ]
                                );
                                ?>
                                <span class="description" style="display:block;margin-top:6px;">
                                    <?php esc_html_e('HTML permitido: <strong>, <em>, <a>, <p>, <br>, <ul>, <li>, <table>, etc.', 'formularios-admin'); ?>
                                </span>
                            </div>
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
                // Toggle enable status UI
                document.querySelectorAll('.un-toggle-enabled').forEach(function(cb){
                    function updateUI(){
                        var block = cb.closest('.un-cpt-block');
                        var section = cb.closest('.un-toggle-section');
                        var fields = block.querySelector('.un-fields');
                        if (!section || !block) return;
                        section.setAttribute('data-enabled', cb.checked ? 'true' : 'false');
                        if (fields) {
                            fields.style.opacity = cb.checked ? '1' : '0.6';
                            fields.style.pointerEvents = cb.checked ? 'auto' : 'none';
                        }
                        var badge = block.querySelector('.un-badge');
                        if (badge) {
                            badge.classList.toggle('un-on', cb.checked);
                            badge.textContent = cb.checked 
                                ? '<?php echo esc_js(__('Notificaciones activas', 'formularios-admin')); ?>'
                                : '<?php echo esc_js(__('Desactivadas', 'formularios-admin')); ?>';
                        }
                    }
                    cb.addEventListener('change', updateUI);
                });
                
                // Expand/collapse all
                function toggleAll(open){
                    document.querySelectorAll('details.un-cpt-block').forEach(function(d){
                        if (open) { d.setAttribute('open',''); } else { d.removeAttribute('open'); }
                    });
                }
                var ea = document.getElementById('un-expand-all');
                var ca = document.getElementById('un-collapse-all');
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
        check_admin_referer('formularios_save_user_notifications');

        $submitted = $_POST['settings'] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }

        Formularios_User_Notification_Settings::save_all($submitted);

        wp_safe_redirect(add_query_arg('saved', '1', admin_url('admin.php?page=formularios-user-notifications')));
        exit;
    }
}
