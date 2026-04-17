<?php
/**
 * Renders the 4-step import wizard admin page.
 *
 * @package Formularios_Admin
 */

if (!defined('ABSPATH')) exit;

class Formularios_Import_Admin_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_page'], 25);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Register "Importar Registros" submenu under "formularios-menu".
     */
    public function register_page(): void {
        add_submenu_page(
            'formularios-menu',
            __('Importar Registros', 'formularios-admin'),
            __('Importar Registros', 'formularios-admin'),
            'manage_options',
            'formularios-import',
            [$this, 'render_page']
        );
    }

    /**
     * Enqueue JS and CSS only on the import page.
     */
    public function enqueue_assets(string $hook): void {
        if ($hook !== 'formularios_page_formularios-import') {
            return;
        }

        $base = plugin_dir_url(dirname(__FILE__));

        wp_enqueue_style(
            'formularios-import',
            $base . 'assets/css/import.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'formularios-import',
            $base . 'assets/js/import.js',
            ['jquery'],
            '1.0.0',
            true
        );

        global $FORMULARIOS_CPTS;

        $cpt_options = [];
        foreach (($FORMULARIOS_CPTS ?: []) as $cpt_slug) {
            $obj = get_post_type_object($cpt_slug);
            if ($obj) {
                $cpt_options[] = [
                    'slug'  => $cpt_slug,
                    'label' => $obj->labels->name,
                ];
            }
        }

        wp_localize_script('formularios-import', 'formulariosImport', [
            'ajax_url'      => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('formularios_import'),
            'cpts'          => $cpt_options,
            'preselect_cpt' => sanitize_key($_GET['cpt'] ?? ''),
            'i18n'     => [
                'step1'        => __('1. Subir JSON', 'formularios-admin'),
                'step2'        => __('2. Mapear Campos', 'formularios-admin'),
                'step3'        => __('3. Opciones', 'formularios-admin'),
                'step4'        => __('4. Importar', 'formularios-admin'),
                'uploading'    => __('Subiendo...', 'formularios-admin'),
                'processing'   => __('Procesando...', 'formularios-admin'),
                'completed'    => __('Completado', 'formularios-admin'),
                'paused'       => __('Pausado', 'formularios-admin'),
                'error'        => __('Error', 'formularios-admin'),
                'created'      => __('Creados', 'formularios-admin'),
                'skipped'      => __('Omitidos', 'formularios-admin'),
                'errors'       => __('Errores', 'formularios-admin'),
                'of'           => __('de', 'formularios-admin'),
                'records'      => __('registros', 'formularios-admin'),
                'select_cpt'   => __('Seleccione un formulario', 'formularios-admin'),
                'select_field' => __('— No importar —', 'formularios-admin'),
                'no_mapping'   => __('Debe mapear al menos un campo.', 'formularios-admin'),
                'confirm_start'=> __('¿Iniciar la importación?', 'formularios-admin'),
                'resume_found' => __('Se encontró una sesión activa. ¿Desea reanudarla?', 'formularios-admin'),
            ],
        ]);
    }

    /**
     * Render the wizard page HTML.
     */
    public function render_page(): void {
        ?>
        <div class="wrap" id="formularios-import-wrap">
            <h1><?php esc_html_e('Importar Registros', 'formularios-admin'); ?></h1>

            <!-- Wizard Step Indicators -->
            <div class="fi-steps">
                <div class="fi-step fi-step-active" data-step="1"><span>1</span> <?php esc_html_e('Subir JSON', 'formularios-admin'); ?></div>
                <div class="fi-step" data-step="2"><span>2</span> <?php esc_html_e('Mapear Campos', 'formularios-admin'); ?></div>
                <div class="fi-step" data-step="3"><span>3</span> <?php esc_html_e('Opciones', 'formularios-admin'); ?></div>
                <div class="fi-step" data-step="4"><span>4</span> <?php esc_html_e('Importar', 'formularios-admin'); ?></div>
            </div>

            <!-- ============ STEP 1: Upload ============ -->
            <div class="fi-panel" id="fi-step-1">
                <h2><?php esc_html_e('Paso 1: Seleccionar formulario y subir JSON', 'formularios-admin'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><label for="fi-cpt-select"><?php esc_html_e('Tipo de formulario', 'formularios-admin'); ?></label></th>
                        <td>
                            <select id="fi-cpt-select" class="regular-text">
                                <option value=""><?php esc_html_e('— Seleccione —', 'formularios-admin'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fi-json-file"><?php esc_html_e('Archivo JSON', 'formularios-admin'); ?></label></th>
                        <td>
                            <input type="file" id="fi-json-file" accept=".json">
                            <p class="description"><?php esc_html_e('Suba un archivo .json con un array de registros.', 'formularios-admin'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="fi-actions">
                    <button type="button" class="button button-primary" id="fi-btn-upload" disabled>
                        <?php esc_html_e('Subir y continuar', 'formularios-admin'); ?>
                    </button>
                    <span class="spinner" id="fi-spinner-upload"></span>
                </p>

                <div class="fi-notice" id="fi-upload-notice" style="display:none;"></div>
            </div>

            <!-- ============ STEP 2: Mapping ============ -->
            <div class="fi-panel" id="fi-step-2" style="display:none;">
                <h2><?php esc_html_e('Paso 2: Mapear campos JSON a campos ACF', 'formularios-admin'); ?></h2>

                <p class="description">
                    <?php esc_html_e('Asocie cada campo del archivo JSON con el campo ACF correspondiente. Los campos no mapeados se ignorarán.', 'formularios-admin'); ?>
                </p>

                <div id="fi-mapping-container">
                    <table class="widefat fi-mapping-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Campo JSON', 'formularios-admin'); ?></th>
                                <th><?php esc_html_e('Muestra', 'formularios-admin'); ?></th>
                                <th><?php esc_html_e('Campo ACF destino', 'formularios-admin'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="fi-mapping-body"></tbody>
                    </table>
                </div>

                <p class="fi-actions">
                    <button type="button" class="button" id="fi-btn-back-1"><?php esc_html_e('← Atrás', 'formularios-admin'); ?></button>
                    <button type="button" class="button button-primary" id="fi-btn-to-options"><?php esc_html_e('Continuar →', 'formularios-admin'); ?></button>
                </p>
            </div>

            <!-- ============ STEP 3: Options ============ -->
            <div class="fi-panel" id="fi-step-3" style="display:none;">
                <h2><?php esc_html_e('Paso 3: Opciones de importación', 'formularios-admin'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Estado de verificación', 'formularios-admin'); ?></th>
                        <td>
                            <fieldset>
                                <label><input type="radio" name="fi_verification" value="unverified" checked> <?php esc_html_e('Todos sin verificar', 'formularios-admin'); ?></label><br>
                                <label><input type="radio" name="fi_verification" value="verified"> <?php esc_html_e('Todos verificados', 'formularios-admin'); ?></label><br>
                                <label>
                                    <input type="radio" name="fi_verification" value="map"> <?php esc_html_e('Mapear desde campo JSON:', 'formularios-admin'); ?>
                                    <select id="fi-verification-field" class="regular-text" disabled></select>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fi-duplicate-key"><?php esc_html_e('Clave para detectar duplicados', 'formularios-admin'); ?></label></th>
                        <td>
                            <select id="fi-duplicate-key" class="regular-text">
                                <option value=""><?php esc_html_e('— Sin detección de duplicados —', 'formularios-admin'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Si un registro con el mismo valor en este campo ya existe, se omitirá.', 'formularios-admin'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Estado de entradas', 'formularios-admin'); ?></th>
                        <td>
                            <label><input type="radio" name="fi_post_status" value="publish" checked> <?php esc_html_e('Publicar', 'formularios-admin'); ?></label><br>
                            <label><input type="radio" name="fi_post_status" value="pending"> <?php esc_html_e('Pendiente de revisión', 'formularios-admin'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fi-title-template"><?php esc_html_e('Plantilla de título', 'formularios-admin'); ?></label></th>
                        <td>
                            <input type="text" id="fi-title-template" class="regular-text" placeholder="Ej: {radicado} - {nombre}">
                            <p class="description"><?php esc_html_e('Use {campo_json} para incluir valores. Dejar vacío para numeración consecutiva.', 'formularios-admin'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fi-file-base-url"><?php esc_html_e('URL base para archivos', 'formularios-admin'); ?></label></th>
                        <td>
                            <input type="url" id="fi-file-base-url" class="regular-text" placeholder="https://ejemplo.com/documentos/">
                            <p class="description"><?php esc_html_e('Para rutas relativas de archivos. Dejar vacío si las URLs son completas.', 'formularios-admin'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Archivos múltiples', 'formularios-admin'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="fi-attach-extra-files" value="1" checked>
                                <?php esc_html_e('Adjuntar archivos adicionales al post', 'formularios-admin'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Cuando un campo contiene varios archivos (ej. HTML con múltiples enlaces), el primero se asigna al campo ACF y los demás se adjuntan como medios del post. No requiere ACF Pro.', 'formularios-admin'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="fi-batch-size"><?php esc_html_e('Tamaño del lote', 'formularios-admin'); ?></label></th>
                        <td>
                            <input type="number" id="fi-batch-size" class="small-text" value="10" min="1" max="50">
                            <p class="description"><?php esc_html_e('Número de registros a procesar por petición AJAX (5-10 recomendado si hay archivos).', 'formularios-admin'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="fi-actions">
                    <button type="button" class="button" id="fi-btn-back-2"><?php esc_html_e('← Atrás', 'formularios-admin'); ?></button>
                    <button type="button" class="button button-primary" id="fi-btn-start-import"><?php esc_html_e('Iniciar Importación', 'formularios-admin'); ?></button>
                </p>
            </div>

            <!-- ============ STEP 4: Import Progress ============ -->
            <div class="fi-panel" id="fi-step-4" style="display:none;">
                <h2><?php esc_html_e('Paso 4: Importación en progreso', 'formularios-admin'); ?></h2>

                <div class="fi-progress-wrap">
                    <div class="fi-progress-bar">
                        <div class="fi-progress-fill" id="fi-progress-fill" style="width:0%"></div>
                    </div>
                    <div class="fi-progress-text" id="fi-progress-text">0 <?php esc_html_e('de', 'formularios-admin'); ?> 0</div>
                </div>

                <div class="fi-stats" id="fi-stats">
                    <div class="fi-stat fi-stat-created">
                        <span class="fi-stat-num" id="fi-stat-created">0</span>
                        <span class="fi-stat-label"><?php esc_html_e('Creados', 'formularios-admin'); ?></span>
                    </div>
                    <div class="fi-stat fi-stat-skipped">
                        <span class="fi-stat-num" id="fi-stat-skipped">0</span>
                        <span class="fi-stat-label"><?php esc_html_e('Omitidos', 'formularios-admin'); ?></span>
                    </div>
                    <div class="fi-stat fi-stat-errors">
                        <span class="fi-stat-num" id="fi-stat-errors">0</span>
                        <span class="fi-stat-label"><?php esc_html_e('Errores', 'formularios-admin'); ?></span>
                    </div>
                </div>

                <div class="fi-actions" id="fi-import-actions">
                    <button type="button" class="button" id="fi-btn-pause"><?php esc_html_e('Pausar', 'formularios-admin'); ?></button>
                    <button type="button" class="button button-primary" id="fi-btn-resume" style="display:none;"><?php esc_html_e('Reanudar', 'formularios-admin'); ?></button>
                </div>

                <div class="fi-log-wrap">
                    <h3><?php esc_html_e('Registro de actividad', 'formularios-admin'); ?></h3>
                    <div class="fi-log" id="fi-log"></div>
                </div>

                <div class="fi-complete-message" id="fi-complete-message" style="display:none;">
                    <h3><?php esc_html_e('Importación completada', 'formularios-admin'); ?></h3>
                    <p id="fi-complete-summary"></p>
                    <p class="fi-actions">
                        <a href="" class="button button-primary" id="fi-view-posts"><?php esc_html_e('Ver registros importados', 'formularios-admin'); ?></a>
                        <button type="button" class="button" id="fi-btn-new-import"><?php esc_html_e('Nueva importación', 'formularios-admin'); ?></button>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}
