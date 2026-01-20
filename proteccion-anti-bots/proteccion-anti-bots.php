<?php
/*
Plugin Name: Protección Anti-Bots y 404 Profesional
Description: Bloqueo de bots maliciosos, limitación de errores 404, alertas de seguridad y bloqueo geográfico. Incluye sistema de actualizaciones.
Version: 5.0
Author: XorEax MrGamer
Update URI: https://github.com/xoreaxmrgamer/XorEax-WordPress-Security
GITHUB: https://github.com/xoreaxmrgamer/XorEax-WordPress-Security
Youtube: https://www.youtube.com/@xoreaxmrgamer
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

// Definir la ruta del archivo principal
if ( ! defined( 'PROTECCION_ANTI_BOTS_FILE' ) ) {
    define( 'PROTECCION_ANTI_BOTS_FILE', plugin_basename( __FILE__ ) );
}

class Proteccion_Anti_Bots {

    public $plugin_slug;
    public $repo_user;
    public $repo_name;

    public function __construct() {
        // 1. Gestionar el estado de las auto-actualizaciones al activar/desactivar el plugin
        register_activation_hook( __FILE__, array( $this, 'al_activar_plugin' ) );
        register_deactivation_hook( __FILE__, array( $this, 'al_desactivar_plugin' ) );

        // 2. Forzar compatibilidad con Auto-Updates
        add_filter( 'plugin_row_meta', array( $this, 'forzar_soporte_auto_update' ), 10, 2 );

        // 3. Control del interruptor basado en los Ajustes del plugin
        add_filter( 'auto_update_plugin', array( $this, 'controlar_auto_update' ), 10, 2 );

        // 4. Enlaces de acción
        add_filter( 'plugin_action_links_' . PROTECCION_ANTI_BOTS_FILE, array( $this, 'agregar_enlaces_plugin' ) );

        // 5. Menú de administración
        add_action( 'admin_menu', array( $this, 'agregar_menu_admin' ) );
        add_action( 'admin_init', array( $this, 'registrar_ajustes' ) );

        // 6. Alertas
        add_action( 'admin_notices', array( $this, 'alerta_usuario_publico' ) );

        // 7. Actualizaciones (Check de versión)
        $this->init_actualizaciones();

        // 8. Bloqueos (solo si el plugin está activado en ajustes)
        if ( $this->get_opcion('plugin_activo') == '1' ) {
            // Ejecutar chequeo geográfico muy temprano
            add_action( 'init', array( $this, 'verificar_bloqueo_geografico' ), 1 );
            
            add_action( 'init', array( $this, 'bloquear_bots_conocidos' ) );
            add_action( 'template_redirect', array( $this, 'verificar_404_y_bloquear' ) );
        }
    }

    // --- 1. GESTIÓN DE AUTO-UPDATES ---
    public function al_activar_plugin() { }
    public function al_desactivar_plugin() {
        $auto_updates = get_option( 'auto_update_plugins', array() );
        if ( in_array( PROTECCION_ANTI_BOTS_FILE, $auto_updates ) ) {
            $auto_updates = array_diff( $auto_updates, array( PROTECCION_ANTI_BOTS_FILE ) );
            update_option( 'auto_update_plugins', $auto_updates );
        }
    }

    // --- 2. FORZAR SOPORTE AUTO-UPDATE ---
    public function forzar_soporte_auto_update( $plugin_meta, $plugin_file ) {
        if ( $plugin_file !== PROTECCION_ANTI_BOTS_FILE ) { return $plugin_meta; }
        $plugin_meta[] = '<script>jQuery(function(){ jQuery("tr[data-plugin=\'' . PROTECCION_ANTI_BOTS_FILE . '\']").addClass("update-supported"); });</script>';
        return $plugin_meta;
    }

    // --- 3. CONTROL DEL INTERRUPTOR ---
    public function controlar_auto_update( $update, $item ) {
        if ( $item->plugin !== PROTECCION_ANTI_BOTS_FILE ) { return $update; }
        return ( $this->get_opcion('activar_auto_update_plugin') == '1' ) ? true : false;
    }

    // --- 4. ENLACES VISUALES ---
    public function agregar_enlaces_plugin( $links ) {
        $enlaces_ajustes = array(
            'ajustes' => '<a href="' . admin_url( 'options-general.php?page=proteccion-anti-bots' ) . '">Ajustes</a>',
            'soporte' => '<a href="https://github.com/xoreaxmrgamer/XorEax-WordPress-Security" target="_blank">Soporte</a>',
            'docs'    => '<a href="https://github.com/xoreaxmrgamer" target="_blank">Documentación</a>',
        );
        return array_merge( $enlaces_ajustes, $links );
    }

    // --- SISTEMA DE ACTUALIZACIONES ---
    public function init_actualizaciones() {
        $this->plugin_slug = PROTECCION_ANTI_BOTS_FILE;
        $this->repo_user   = 'xoreaxmrgamer';
        $this->repo_name   = 'XorEax-WordPress-Security';

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) { return $transient; }
        $remote_version = $this->get_remote_version();
        if ( $remote_version ) {
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_slug );
            $local_version = $plugin_data['Version'];

            if ( version_compare( $local_version, $remote_version, '<' ) ) {
                $obj = new stdClass();
                $obj->slug = dirname( $this->plugin_slug );
                $obj->plugin = $this->plugin_slug;
                $obj->new_version = $remote_version;
                $obj->url = 'https://github.com/' . $this->repo_user . '/' . $this->repo_name;
                $obj->package = 'https://github.com/' . $this->repo_user . '/' . $this->repo_name . '/archive/refs/heads/main.zip';
                $obj->update_supported = true;
                $transient->response[$this->plugin_slug] = $obj;
            } else {
                if ( isset( $transient->no_update ) && ! isset( $transient->no_update[$this->plugin_slug] ) ) {
                     $obj_no_update = new stdClass();
                     $obj_no_update->slug = dirname( $this->plugin_slug );
                     $obj_no_update->plugin = $this->plugin_slug;
                     $obj_no_update->new_version = $local_version;
                     $obj_no_update->url = 'https://github.com/' . $this->repo_user . '/' . $this->repo_name;
                     $obj_no_update->package = '';
                     $obj_no_update->update_supported = true;
                     $transient->no_update[$this->plugin_slug] = $obj_no_update;
                }
            }
        }
        return $transient;
    }

    public function plugin_info( $false, $action, $args ) {
        $slug_plugin = dirname( $this->plugin_slug );
        if ( $args->slug !== $slug_plugin ) { return $false; }
        $remote_version = $this->get_remote_version();
        if ( ! $remote_version ) { return $false; }

        $info = new stdClass();
        $info->name = 'Protección Anti-Bots y 404 Profesional';
        $info->slug = $slug_plugin;
        $info->version = $remote_version;
        $info->author = '<a href="https://github.com/xoreaxmrgamer">XorEax MrGamer</a>';
        $info->homepage = 'https://github.com/xoreaxmrgamer/XorEax-WordPress-Security';
        $info->requires = '5.0';
        $info->tested = '6.4';
        $info->sections = array(
            'description' => 'Plugin de seguridad avanzada para WordPress.',
            'changelog' => '<h4>Versión ' . $remote_version . '</h4><ul><li>Nuevo: Bloqueo Geográfico por Países.</li><li>Nuevo: Detección automática de país mediante API externa con caché.</li></ul>'
        );
        $info->download_link = 'https://github.com/' . $this->repo_user . '/' . $this->repo_name . '/archive/refs/heads/main.zip';
        return $info;
    }

    private function get_remote_version() {
        $request = wp_remote_get( 'https://raw.githubusercontent.com/' . $this->repo_user . '/' . $this->repo_name . '/main/proteccion-anti-bots/proteccion-anti-bots.php' );
        if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != '200' ) { return false; }
        $body = wp_remote_retrieve_body( $request );
        preg_match( '/Version:\s*(.*)/', $body, $matches );
        return isset( $matches[1] ) ? trim( $matches[1] ) : false;
    }

    // --- NUEVO: LÓGICA DE BLOQUEO GEOGRÁFICO ---
    public function verificar_bloqueo_geografico() {
        if ( $this->get_opcion('activar_bloqueo_geo') != '1' ) return;

        $ip = $this->obtener_ip_real();
        // No bloquear IPs locales
        if ( $ip == '127.0.0.1' || $ip == '::1' ) return;

        // 1. Comprobar si ya tenemos el país en caché (Transient)
        $cache_key = 'pais_ip_' . md5( $ip );
        $pais_codigo = get_transient( $cache_key );

        // 2. Si no está en caché, consultar API
        if ( $pais_codigo === false ) {
            // Usamos ip-api.com (API gratuita y ligera)
            $response = wp_remote_get( 'http://ip-api.com/json/' . $ip . '?fields=countryCode' );
            
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
                $data = json_decode( wp_remote_retrieve_body( $response ) );
                if ( isset( $data->countryCode ) ) {
                    $pais_codigo = $data->countryCode;
                    // Guardar en caché por 24 horas para no ralentizar
                    set_transient( $cache_key, $pais_codigo, 24 * HOUR_IN_SECONDS );
                }
            }
        }

        // 3. Verificar si el país está en la lista negra
        if ( $pais_codigo ) {
            $lista_paises_raw = $this->get_opcion('lista_paises_bloqueados');
            $paises_bloqueados = array_filter( array_map( 'trim', explode( "\n", $lista_paises_raw ) ) );

            if ( in_array( strtoupper( $pais_codigo ), $paises_bloqueados ) ) {
                $this->bloquear_acceso( 'Acceso denegado por razón geográfica.' );
            }
        }
    }

    // --- PANEL DE AJUSTES ---
    public function agregar_menu_admin() {
        add_options_page( 'Anti-Bots y Protección 404', 'Anti-Bots', 'manage_options', 'proteccion-anti-bots', array( $this, 'pagina_opciones' ) );
    }

    public function registrar_ajustes() { register_setting( 'mi_grupo_ajustes', 'mi_proteccion_ajustes' ); }

    public function get_opcion( $clave ) {
        $opciones = get_option( 'mi_proteccion_ajustes' );
        return isset( $opciones[ $clave ] ) ? $opciones[ $clave ] : '';
    }

    public function pagina_opciones() {
        $lista_por_defecto = "semrush\nahrefs\nmj12bot\ndotbot\nmegaIndex\nlinkdex\nblekkobot\nextlinks\nranksonic\nbot.php\nsqlmap\nhavij\nnikto\nmasscan\nzgrab\nnmap\nwpscan\ncurl\nwget\npython-requests\njava\nperl\nlibwww";
        $valor_lista = $this->get_opcion('lista_negra_agentes');
        if ( empty( $valor_lista ) ) { $valor_lista = $lista_por_defecto; }
        ?>
        <div class="wrap">
            <h1>⚔️ Configuración de Protección Anti-Bots</h1>
            <p>Plugin desarrollado por <a href="https://github.com/xoreaxmrgamer" target="_blank">XorEax MrGamer</a> | <a href="https://www.youtube.com/@xoreaxmrgamer" target="_blank">Canal de YouTube</a></p>
            
            <form method="post" action="options.php">
                <?php settings_fields( 'mi_grupo_ajustes' ); do_settings_sections( 'mi_grupo_ajustes' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Estado del Plugin</th>
                        <td><label><input type="checkbox" name="mi_proteccion_ajustes[plugin_activo]" value="1" <?php checked( $this->get_opcion('plugin_activo'), '1' ); ?>> <strong>Activar protección</strong></label></td>
                    </tr>
                    <tr>
                        <th scope="row">Actualizaciones Automáticas</th>
                        <td>
                            <label><input type="checkbox" name="mi_proteccion_ajustes[activar_auto_update_plugin]" value="1" <?php checked( $this->get_opcion('activar_auto_update_plugin'), '1' ); ?>> <strong>Activar actualizaciones automáticas</strong></label>
                        </td>
                    </tr>

                    <!-- NUEVA SECCIÓN GEOGRÁFICA -->
                    <tr>
                        <th scope="row">Bloqueo Geográfico</th>
                        <td>
                            <label><input type="checkbox" name="mi_proteccion_ajustes[activar_bloqueo_geo]" value="1" <?php checked( $this->get_opcion('activar_bloqueo_geo'), '1' ); ?>> <strong>Activar bloqueo por Países</strong></label>
                            <p class="description">Bloquea el acceso basándose en la ubicación del visitante. Requiere conexión a internet para consultar la IP la primera vez.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Países a Bloquear</th>
                        <td>
                            <textarea name="mi_proteccion_ajustes[lista_paises_bloqueados]" rows="5" cols="50" class="large-text code" placeholder="Ej: CN&#10;RU&#10;KP"><?php echo esc_textarea( $this->get_opcion('lista_paises_bloqueados') ); ?></textarea>
                            <p class="description">Introduce los códigos de país (ISO 3166-1 alpha-2), uno por línea. Ej: <strong>US</strong> (EE.UU), <strong>CN</strong> (China), <strong>RU</strong> (Rusia). <a href="https://es.wikipedia.org/wiki/ISO_3166-1" target="_blank">Ver lista completa de códigos</a>.</p>
                        </td>
                    </tr>
                    <!-- FIN SECCIÓN GEOGRÁFICA -->

                    <tr>
                        <th scope="row">Bloqueo por User-Agent</th>
                        <td><label><input type="checkbox" name="mi_proteccion_ajustes[activar_user_agent]" value="1" <?php checked( $this->get_opcion('activar_user_agent'), '1' ); ?>> Activar bloqueo de Bots conocidos</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Lista Negra (User-Agents)</th>
                        <td><textarea name="mi_proteccion_ajustes[lista_negra_agentes]" rows="12" cols="50" class="large-text code"><?php echo esc_textarea( $valor_lista ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">Protección contra Escaneo (404)</th>
                        <td><label><input type="checkbox" name="mi_proteccion_ajustes[activar_limitador_404]" value="1" <?php checked( $this->get_opcion('activar_limitador_404'), '1' ); ?>> Activar limitador de errores 404</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Límite de Errores</th>
                        <td>
                            <label>Número de errores 404 permitidos:</label>
                            <input type="number" name="mi_proteccion_ajustes[limite_404]" value="<?php echo esc_attr( $this->get_opcion('limite_404') ?: 10 ); ?>" min="1" max="100" class="small-text">
                            <label> en un periodo de </label>
                            <input type="number" name="mi_proteccion_ajustes[tiempo_ventana]" value="<?php echo esc_attr( $this->get_opcion('tiempo_ventana') ?: 5 ); ?>" min="1" max="60" class="small-text">
                            <label> minutos.</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tiempo de Bloqueo</th>
                        <td>
                            <label>Bloquear IP por:</label>
                            <input type="number" name="mi_proteccion_ajustes[tiempo_bloqueo]" value="<?php echo esc_attr( $this->get_opcion('tiempo_bloqueo') ?: 1 ); ?>" min="1" max="24" class="small-text">
                            <label> hora(s).</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h3>ℹ️ Información</h3>
            <p>Las IPs bloqueadas se almacenan en la base de datos temporalmente (Transients). Para ver logs detallados de ataques, se recomienda usar un plugin de seguridad dedicado o revisar los registros del servidor (cPanel/Plesk).</p>
            <p><em>Este plugin busca actualizaciones en GitHub. Si has modificado el código localmente, asegúrate de subir los cambios a tu repositorio para mantener la sincronización.</em></p>
        </div>
        <?php
    }

    // --- LÓGICA DE SEGURIDAD ---
    public function alerta_usuario_publico() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $usuarios = get_users(); $usuarios_inseguros = array();
        foreach ( $usuarios as $usuario ) {
            if ( strtolower( $usuario->user_login ) === strtolower( $usuario->display_name ) ) { $usuarios_inseguros[] = $usuario->user_login; }
        }
        if ( ! empty( $usuarios_inseguros ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>⚠️ Alerta de Seguridad:</strong> Los siguientes usuarios tienen el nombre de login igual al nombre público:</p>
                <ul style="list-style: disc inside; margin-left: 10px;">
                    <?php foreach ( $usuarios_inseguros as $inseguro ) : ?>
                        <li><strong style="color: #dc3232; font-weight: bold;"><?php echo esc_html( $inseguro ); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <p>Por favor, corrígelo en la <a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">sección de usuarios</a>.</p>
            </div>
            <?php
        }
    }

    public function bloquear_bots_conocidos() {
        if ( $this->get_opcion('activar_user_agent') != '1' ) return;
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $lista_raw = $this->get_opcion('lista_negra_agentes');
        if ( empty( $lista_raw ) ) { $blocked_agents = array( 'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'megaIndex', 'linkdex', 'blekkobot', 'extlinks', 'ranksonic', 'bot.php', 'sqlmap', 'havij', 'nikto', 'masscan', 'zgrab', 'nmap', 'wpscan', 'curl', 'wget', 'python-requests', 'java', 'perl', 'libwww' ); }
        else { $blocked_agents = array_filter( array_map( 'trim', explode( "\n", $lista_raw ) ) ); }
        foreach ( $blocked_agents as $agent ) {
            if ( ! empty( $agent ) && stripos( $ua, $agent ) !== false ) { $this->bloquear_acceso(); }
        }
    }

    public function verificar_404_y_bloquear() {
        if ( ! is_404() ) return;
        if ( $this->get_opcion('activar_limitador_404') != '1' ) return;
        $ip = $this->obtener_ip_real();
        $transient_block = 'bloqueo_temporal_' . md5( $ip );
        $transient_count = 'contador_404_' . md5( $ip );
        if ( get_transient( $transient_block ) ) { $this->bloquear_acceso(); }
        $limite = intval( $this->get_opcion('limite_404') ?: 10 );
        $ventana_minutos = intval( $this->get_opcion('tiempo_ventana') ?: 5 );
        $tiempo_bloqueo_horas = intval( $this->get_opcion('tiempo_bloqueo') ?: 1 );
        $count = get_transient( $transient_count );
        if ( $count === false ) { set_transient( $transient_count, 1, $ventana_minutos * MINUTE_IN_SECONDS ); }
        else {
            $count++; set_transient( $transient_count, $count, $ventana_minutos * MINUTE_IN_SECONDS );
            if ( $count > $limite ) {
                set_transient( $transient_block, true, $tiempo_bloqueo_horas * HOUR_IN_SECONDS );
                $this->bloquear_acceso();
            }
        }
    }

    private function obtener_ip_real() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) { return $_SERVER['HTTP_CLIENT_IP']; }
        elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { return $_SERVER['HTTP_X_FORWARDED_FOR']; }
        else { return $_SERVER['REMOTE_ADDR']; }
    }

    // Modificado para permitir mensaje personalizado o genérico
    private function bloquear_acceso( $mensaje = 'Acceso denegado.' ) {
        status_header( 403 );
        nocache_headers();
        die( $mensaje );
    }
}

new Proteccion_Anti_Bots();
?>
