<?php
/*
Plugin Name: Protección Anti-Bots y 404 Profesional
Description: Bloqueo de bots maliciosos, limitación de errores 404 y alertas de seguridad. Incluye sistema de actualizaciones.
Version: 4.0
Author: XorEax MrGamer
GITHUB: https://github.com/xoreaxmrgamer/XorEax-WordPress-Security
Youtube: https://www.youtube.com/@xoreaxmrgamer
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

class Proteccion_Anti_Bots {

    // Variables para actualizaciones
    public $plugin_slug;
    public $repo_user;
    public $repo_name;

    public function __construct() {
        // 1. Agregar menú de administración
        add_action( 'admin_menu', array( $this, 'agregar_menu_admin' ) );
        add_action( 'admin_init', array( $this, 'registrar_ajustes' ) );

        // 2. Alerta de seguridad (Usuario igual a Display Name)
        add_action( 'admin_notices', array( $this, 'alerta_usuario_publico' ) );

        // 3. Sistema de Actualizaciones
        $this->init_actualizaciones();

        // 4. Lógica de bloqueo (solo si el plugin está activado en ajustes)
        if ( $this->get_opcion('plugin_activo') == '1' ) {
            add_action( 'init', array( $this, 'bloquear_bots_conocidos' ) );
            add_action( 'template_redirect', array( $this, 'verificar_404_y_bloquear' ) );
        }
    }

    // ==============================================================================
    // SISTEMA DE ACTUALIZACIONES (VÍA GITHUB)
    // ==============================================================================

    public function init_actualizaciones() {
        // --- CONFIGURACIÓN DE GITHUB ---
        // IMPORTANTE: Edita estos valores con tus datos de GitHub reales
        $this->plugin_slug = 'proteccion-anti-bots/proteccion-anti-bots.php';
        $this->repo_user   = 'xoreaxmrgamer';
        $this->repo_name   = 'XorEax-WordPress-Security';
        // -------------------------------

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();

        if ( $remote_version ) {
            $local_version = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->plugin_slug )['Version'];

            if ( version_compare( $local_version, $remote_version, '<' ) ) {
                $obj = new stdClass();
                $obj->slug = $this->plugin_slug;
                $obj->new_version = $remote_version;
                $obj->url = 'https://github.com/' . $this->repo_user . '/' . $this->repo_name;
                // Enlace de descarga del ZIP del repositorio
                $obj->package = 'https://github.com/' . $this->repo_user . '/' . $this->repo_name . '/archive/refs/heads/main.zip';
                $transient->response[$this->plugin_slug] = $obj;
            }
        }

        return $transient;
    }

    public function plugin_info( $false, $action, $args ) {
        if ( $args->slug !== $this->plugin_slug ) {
            return $false;
        }

        $remote_version = $this->get_remote_version();
        if ( ! $remote_version ) return $false;

        $info = new stdClass();
        $info->name = 'Protección Anti-Bots';
        $info->slug = $this->plugin_slug;
        $info->version = $remote_version;
        $info->author = 'Tu Nombre';
        $info->requires = '5.0';
        $info->tested = '6.4';
        $info->downloaded = 0;
        $info->last_updated = date('Y-m-d');
        $info->sections = array(
            'description' => 'Plugin de seguridad personalizado para bloqueo de bots y corrección de usuarios vulnerables.',
            'changelog' => '<h4>Versión ' . $remote_version . '</h4><ul><li>Actualización vía GitHub.</li></ul>'
        );
        $info->download_link = 'https://github.com/' . $this->repo_user . '/' . $this->repo_name . '/archive/refs/heads/main.zip';

        return $info;
    }

    private function get_remote_version() {
        // URL del archivo RAW para leer la versión desde la cabecera del comentario
        $request = wp_remote_get( 'https://raw.githubusercontent.com/' . $this->repo_user . '/' . $this->repo_name . '/main/proteccion-anti-bots.php' );
        
        if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != '200' ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $request );
        preg_match( '/Version:\s*(.*)/', $body, $matches );
        
        return isset( $matches[1] ) ? trim( $matches[1] ) : false;
    }

    // ==============================================================================
    // PANEL DE ADMINISTRACIÓN (AJUSTES)
    // ==============================================================================

    public function agregar_menu_admin() {
        add_options_page(
            'Anti-Bots y Protección 404', 
            'Anti-Bots',                  
            'manage_options',             
            'proteccion-anti-bots',    
            array( $this, 'pagina_opciones' ) 
        );
    }

    public function registrar_ajustes() {
        register_setting( 'mi_grupo_ajustes', 'mi_proteccion_ajustes' );
    }

    public function get_opcion( $clave ) {
        $opciones = get_option( 'mi_proteccion_ajustes' );
        return isset( $opciones[ $clave ] ) ? $opciones[ $clave ] : '';
    }

    public function pagina_opciones() {
        // Lista por defecto de User-Agents
        $lista_por_defecto = "semrush\nahrefs\nmj12bot\ndotbot\nmegaIndex\nlinkdex\nblekkobot\nextlinks\nranksonic\nbot.php\nsqlmap\nhavij\nnikto\nmasscan\nzgrab\nnmap\nwpscan\ncurl\nwget\npython-requests\njava\nperl\nlibwww";
        $valor_lista = $this->get_opcion('lista_negra_agentes');
        if ( empty( $valor_lista ) ) { $valor_lista = $lista_por_defecto; }
        ?>
        <div class="wrap">
            <h1>⚔️ Configuración de Protección Anti-Bots</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'mi_grupo_ajustes' ); do_settings_sections( 'mi_grupo_ajustes' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Estado del Plugin</th>
                        <td>
                            <label><input type="checkbox" name="mi_proteccion_ajustes[plugin_activo]" value="1" <?php checked( $this->get_opcion('plugin_activo'), '1' ); ?>> <strong>Activar protección</strong></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Bloqueo por User-Agent</th>
                        <td>
                            <label><input type="checkbox" name="mi_proteccion_ajustes[activar_user_agent]" value="1" <?php checked( $this->get_opcion('activar_user_agent'), '1' ); ?>> Activar bloqueo de Bots conocidos</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lista Negra (User-Agents)</th>
                        <td>
                            <textarea name="mi_proteccion_ajustes[lista_negra_agentes]" rows="12" cols="50" class="large-text code"><?php echo esc_textarea( $valor_lista ); ?></textarea>
                            <p class="description">Una palabra por línea.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Protección contra Escaneo (404)</th>
                        <td>
                            <label><input type="checkbox" name="mi_proteccion_ajustes[activar_limitador_404]" value="1" <?php checked( $this->get_opcion('activar_limitador_404'), '1' ); ?>> Activar limitador de errores 404</label>
                        </td>
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
            <p><em>Este plugin busca actualizaciones en GitHub. Si has modificado el código localmente, asegúrate de subir los cambios a tu repositorio para mantener la sincronización.</em></p>
        </div>
        <?php
    }

    // ==============================================================================
    // ALERTAS Y BLOQUEOS
    // ==============================================================================

    public function alerta_usuario_publico() {
        // Solo mostrar a administradores
        if ( ! current_user_can( 'manage_options' ) ) return;

        $usuarios = get_users();
        $usuarios_inseguros = array();

        foreach ( $usuarios as $usuario ) {
            if ( strtolower( $usuario->user_login ) === strtolower( $usuario->display_name ) ) {
                $usuarios_inseguros[] = $usuario->user_login;
            }
        }

        // Si hay usuarios inseguros, mostrar aviso
        if ( ! empty( $usuarios_inseguros ) ) {
            // Generar el enlace hacia la sección de usuarios
            $enlace_usuarios = admin_url( 'users.php' );
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>⚠️ Alerta de Seguridad:</strong> Los siguientes usuarios tienen el nombre de login igual al nombre público:</p>
                <ul style="list-style: disc inside; margin-left: 10px;">
                    <?php foreach ( $usuarios_inseguros as $inseguro ) : ?>
                        <!-- Estilo inline para forzar Rojo y Negrita -->
                        <li><strong style="color: #dc3232; font-weight: bold;"><?php echo esc_html( $inseguro ); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <!-- Texto con enlace clickeable -->
                <p>Por favor, corrígelo en la <a href="<?php echo esc_url( $enlace_usuarios ); ?>">sección de usuarios</a>.</p>
            </div>
            <?php
        }
    }

    public function bloquear_bots_conocidos() {
        if ( $this->get_opcion('activar_user_agent') != '1' ) return;
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $lista_raw = $this->get_opcion('lista_negra_agentes');
        
        if ( empty( $lista_raw ) ) {
            $blocked_agents = array( 'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'megaIndex', 'linkdex', 'blekkobot', 'extlinks', 'ranksonic', 'bot.php', 'sqlmap', 'havij', 'nikto', 'masscan', 'zgrab', 'nmap', 'wpscan', 'curl', 'wget', 'python-requests', 'java', 'perl', 'libwww' );
        } else {
            $blocked_agents = array_filter( array_map( 'trim', explode( "\n", $lista_raw ) ) );
        }

        foreach ( $blocked_agents as $agent ) {
            if ( ! empty( $agent ) && stripos( $ua, $agent ) !== false ) {
                $this->bloquear_acceso();
            }
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
        if ( $count === false ) {
            set_transient( $transient_count, 1, $ventana_minutos * MINUTE_IN_SECONDS );
        } else {
            $count++;
            set_transient( $transient_count, $count, $ventana_minutos * MINUTE_IN_SECONDS );
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

    private function bloquear_acceso() {
        status_header( 403 );
        nocache_headers();
        die( 'Acceso denegado.' );
    }
}

// Inicializar el plugin
new Proteccion_Anti_Bots();
?>

