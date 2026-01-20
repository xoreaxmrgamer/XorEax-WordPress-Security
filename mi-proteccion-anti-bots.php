<?php
/*
Plugin Name: Protección Anti-Bots y 404 Profesional
Description: Bloqueo de bots maliciosos, limitación de errores 404 y alertas de seguridad para nombres de usuario.
Version: 3.0 (Final)
Author: Tu Nombre
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

class Proteccion_Anti_Bots {

    public function __construct() {
        // 1. Agregar menú de administración
        add_action( 'admin_menu', array( $this, 'agregar_menu_admin' ) );
        add_action( 'admin_init', array( $this, 'registrar_ajustes' ) );

        // 2. Alerta de seguridad (Usuario igual a Display Name)
        add_action( 'admin_notices', array( $this, 'alerta_usuario_publico' ) );

        // 3. Lógica de bloqueo (solo si el plugin está activado en ajustes)
        if ( $this->get_opcion('plugin_activo') == '1' ) {
            add_action( 'init', array( $this, 'bloquear_bots_conocidos' ) );
            add_action( 'template_redirect', array( $this, 'verificar_404_y_bloquear' ) );
        }
    }

    // ------------------------------------------------------------------
    // FUNCIONES DE ADMINISTRACIÓN (PANEL DE AJUSTES)
    // ------------------------------------------------------------------

    public function agregar_menu_admin() {
        add_options_page(
            'Anti-Bots y Protección 404', 
            'Anti-Bots',                  
            'manage_options',             
            'mi-proteccion-anti-bots',    
            array( $this, 'pagina_opciones' ) 
        );
    }

    public function registrar_ajustes() {
        register_setting( 'mi_grupo_ajustes', 'mi_proteccion_ajustes' );
    }

    public function get_opcion( $clave ) {
        $opciones = get_option( 'mi_proteccion_ajustes' );
        // Si la opción no existe, devuelve vacío
        return isset( $opciones[ $clave ] ) ? $opciones[ $clave ] : '';
    }

    public function pagina_opciones() {
        // Valores por defecto para mostrar en el textarea si está vacío
        $lista_por_defecto = "semrush\nahrefs\nmj12bot\ndotbot\nmegaIndex\nlinkdex\nblekkobot\nextlinks\nranksonic\nbot.php\nsqlmap\nhavij\nnikto\nmasscan\nzgrab\nnmap\nwpscan\ncurl\nwget\npython-requests\njava\nperl\nlibwww";
        
        $valor_lista = $this->get_opcion('lista_negra_agentes');
        if ( empty( $valor_lista ) ) {
            $valor_lista = $lista_por_defecto;
        }

        ?>
        <div class="wrap">
            <h1>⚔️ Configuración de Protección Anti-Bots</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'mi_grupo_ajustes' );
                do_settings_sections( 'mi_grupo_ajustes' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Estado del Plugin</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mi_proteccion_ajustes[plugin_activo]" value="1" <?php checked( $this->get_opcion('plugin_activo'), '1' ); ?>>
                                <strong>Activar protección</strong>
                            </label>
                            <p class="description">Desmarca esto para desactivar temporalmente el bloqueo sin borrar la configuración.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Bloqueo por User-Agent</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mi_proteccion_ajustes[activar_user_agent]" value="1" <?php checked( $this->get_opcion('activar_user_agent'), '1' ); ?>>
                                Activar bloqueo de Bots conocidos
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Lista Negra (User-Agents)</th>
                        <td>
                            <textarea name="mi_proteccion_ajustes[lista_negra_agentes]" rows="12" cols="50" class="large-text code"><?php echo esc_textarea( $valor_lista ); ?></textarea>
                            <p class="description">Lista de palabras clave (una por línea) que bloquearán el acceso si aparecen en el User-Agent.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Protección contra Escaneo (404)</th>
                        <td>
                            <label>
                                <input type="checkbox" name="mi_proteccion_ajustes[activar_limitador_404]" value="1" <?php checked( $this->get_opcion('activar_limitador_404'), '1' ); ?>>
                                Activar limitador de errores 404
                            </label>
                            <p class="description">Bloquea IPs que generan demasiados errores de "Página no encontrada".</p>
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
            <h3>ℹ️ Información</h3>
            <p>Las IPs bloqueadas se almacenan en la base de datos temporalmente. Para ver logs detallados de ataques, se recomienda usar un plugin de seguridad dedicado o revisar los registros del servidor (cPanel/Plesk).</p>
        </div>
        <?php
    }


    // ------------------------------------------------------------------
    // ALERTA DE SEGURIDAD (NOMBRE USUARIO = DISPLAY NAME)
    // ------------------------------------------------------------------

    public function alerta_usuario_publico() {
        // Solo mostrar a administradores
        if ( ! current_user_can( 'manage_options' ) ) return;

        $usuarios = get_users();
        $usuarios_inseguros = array();

        foreach ( $usuarios as $usuario ) {
            // Comparar login (user_login) con nombre público (display_name)
            if ( strtolower( $usuario->user_login ) === strtolower( $usuario->display_name ) ) {
                $usuarios_inseguros[] = $usuario->user_login;
            }
        }

        // Si hay usuarios inseguros, mostrar aviso
        if ( ! empty( $usuarios_inseguros ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>⚠️ Alerta de Seguridad Crítica:</strong> Los siguientes usuarios tienen su <strong>Nombre de inicio de sesión</strong> igual al <strong>Nombre a mostrar</strong>. Esto facilita los ataques de fuerza bruta:</p>
                <ul style="list-style: disc inside; margin-left: 10px; color: #b32d2e;">
                    <?php foreach ( $usuarios_inseguros as $inseguro ) : ?>
                        <li><strong><?php echo esc_html( $inseguro ); ?></strong></li>
                    <?php endforeach; ?>
                </ul>
                <p>Ve a <strong>Usuarios > Todos los usuarios > Editar</strong> y cambia el campo "Nombre a mostrar públicamente".</p>
            </div>
            <?php
        }
    }


    // ------------------------------------------------------------------
    // LÓGICA DE BLOQUEO (USER AGENT Y 404)
    // ------------------------------------------------------------------

    // Estrategia 1: Bloqueo por User-Agent
    public function bloquear_bots_conocidos() {
        if ( $this->get_opcion('activar_user_agent') != '1' ) return;

        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        $lista_raw = $this->get_opcion('lista_negra_agentes');
        
        // Si la lista está vacía (no se ha guardado config), usar default hardcoded
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

    // Estrategia 2: Rate Limiting (Errores 404)
    public function verificar_404_y_bloquear() {
        if ( ! is_404() ) return;
        if ( $this->get_opcion('activar_limitador_404') != '1' ) return;

        $ip = $this->obtener_ip_real();
        $transient_block = 'bloqueo_temporal_' . md5( $ip );
        $transient_count = 'contador_404_' . md5( $ip );

        // 1. Verificar si YA está bloqueado
        if ( get_transient( $transient_block ) ) {
            $this->bloquear_acceso();
        }

        // 2. Configuración
        $limite = intval( $this->get_opcion('limite_404') ?: 10 );
        $ventana_minutos = intval( $this->get_opcion('tiempo_ventana') ?: 5 );
        $tiempo_bloqueo_horas = intval( $this->get_opcion('tiempo_bloqueo') ?: 1 );

        // 3. Contar errores
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
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private function bloquear_acceso() {
        status_header( 403 );
        nocache_headers();
        // Mensaje minimalista para no dar información al atacante
        die( 'Acceso denegado.' );
    }

}

// Inicializar el plugin
new Proteccion_Anti_Bots();
?>