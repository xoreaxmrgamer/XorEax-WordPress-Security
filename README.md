<img width="1536" height="1024" alt="ChatGPT Image 20 ene 2026, 08_36_03 p m" src="https://github.com/user-attachments/assets/7625fad5-e46f-4393-9fb6-e11606742c47" />

🛡️ Protección Anti-Bots y 404 Profesional

Plugin de seguridad ligero y altamente configurable diseñado para proteger sitios WordPress contra escaneos maliciosos, consumo de recursos por parte de bots agresivos y vulnerabilidades de usuarios.

Funcionalidades principales:

1. 🔒 Bloqueo por User-Agent (Lista Negra)
Permite filtrar y bloquear automáticamente el acceso a cualquier visitante que se identifique con un "User-Agent" sospechoso o malicioso.

Cómo funciona: Analiza la cabecera HTTP que el navegador o bot envía al acceder a la web.
Configuración: Incluye una lista predefinida de bots agresivos (como Ahrefs, Semrush, scrapers, herramientas de hacking) que se puede editar libremente desde el panel de ajustes, permitiendo añadir o quitar palabras clave una por línea.
Beneficio: Ahorra ancho de banda y recursos del servidor evitando que bots de SEO o indexadores agresivos rastreen todo el sitio.

2. 🚫 Protección contra Escaneo (Limitador de Errores 404)
Detecta patrones de comportamiento típicos de hackers que buscan vulnerabilidades (fuerza bruta o DDoS de aplicación) probando rutas inexistentes.

Cómo funciona: Monitoriza las peticiones que resultan en error 404 (Página no encontrada).
Configuración: Permite definir un umbral personalizable (ej: más de 10 errores en 5 minutos).
Bloqueo: Si una IP supera el límite configurado, es bloqueada automáticamente durante un tiempo determinado (ej: 1 hora).
Beneficio: Evita que scripts automatizados saturen la base de datos buscando archivos como /wp-login.php, /admin o /config.xml.

3. 🌍 Bloqueo Geográfico por País
Sistema de seguridad que restringe el acceso a la web basándose en la ubicación geográfica de la IP del visitante.

Cómo funciona: Consulta una API externa ligera (ip-api.com) para determinar el país de origen de la IP.
Eficiencia: Utiliza un sistema de caché (Transients) para guardar el país de cada IP durante 24 horas. Si la IP vuelve a entrar, no se vuelve a consultar la API, garantizando que la web no se ralentice.
Configuración: Permite bloquear uno o varios países introduciendo sus códigos ISO (ej: CN, RU, US).
Beneficio: Ideal para阻断 tráfico de regiones donde no hay clientes potenciales pero que suelen ser origen de la mayoría de ataques (bots rusos, chinos, etc.).

4. 👮 Auditoría de Seguridad de Usuarios
Alerta administrativa que detecta vulnerabilidades en la configuración de los usuarios de WordPress.

Cómo funciona: Compara el Nombre de inicio de sesión (Username) con el Nombre a mostrar públicamente (Display Name) de todos los usuarios.
Alerta: Si detecta que son iguales, muestra un aviso destacado en rojo en el escritorio de WordPress.
Acción: El aviso incluye un enlace directo para ir a la edición del usuario y corregirlo rápidamente.
Beneficio: Evita que los hackers puedan adivinar fácilmente el usuario administrador y facilita la prevención de accesos no autorizados.

5. 🔄 Sistema de Actualizaciones Automáticas (Integración GitHub)
Permite mantener el plugin actualizado con las últimas mejoras y parches de seguridad sin necesidad de subir archivos manualmente.

Cómo funciona: Conecta tu instalación de WordPress con tu repositorio de GitHub (XorEax-WordPress-Security) para comprobar versiones.
Interfaz: Muestra las notificaciones de actualización nativas de WordPress.
Control: Permite activar o desactivar las actualizaciones automáticas tanto desde el interruptor en la lista de plugins como desde una casilla específica en el panel de ajustes.
Integridad: Al desactivar el plugin por completo, las actualizaciones automáticas se desactivan automáticamente para evitar errores.

6. ⚙️ Panel de Administración Centralizado
Interfaz de ajustes completa y profesional integrada en el menú nativo de WordPress (Ajustes > Anti-Bots).

Gestión: Permite activar o desactivar cada módulo de seguridad individualmente (Bot, 404, Geo).
Personalización: Ofrece campos para editar listas negras, tiempos de bloqueo, umbrales de errores y frecuencias de análisis sin tocar código.
Información: Incluye secciones de ayuda y enlaces directos al soporte y documentación del autor.

7. 🔗 Enlaces de Acción y Soporte
Mejora la usabilidad dentro del panel de administración de WordPress.

Enlaces rápidos: Debajo del nombre del plugin en la lista de plugins, aparecen accesos directos a Ajustes, Soporte (Repositorio de GitHub) y Documentación.
Navegación: Facilita el acceso a la configuración y recursos de ayuda sin tener que buscar en menús.

### Versión actual: 5.0

<img width="541" height="100" alt="1" src="https://github.com/user-attachments/assets/5b155b3a-a6b8-4907-8a38-d2d02735b529" />
<img width="228" height="273" alt="2" src="https://github.com/user-attachments/assets/93e01c4a-dca8-4cd1-a028-8ed4e0864729" />
<img width="930" height="694" alt="3" src="https://github.com/user-attachments/assets/6a5a0a99-db27-4482-8cc8-6b0de1d3c254" />
<img width="1064" height="883" alt="33" src="https://github.com/user-attachments/assets/b62fb4db-eb89-40c1-b9f2-e4ab0c6d0570" />

------------------------
###Changelog###
------------------------

###5.0
*Fecha de lanzamiento: 20 de Enero de 2026*

**Nuevo**:

Bloqueo Geográfico: Sistema completo para bloquear el acceso basándose en el país de origen de la IP.
API de Geolocalización: Integración con ip-api.com para detectar países de forma ligera y gratuita.
Sistema de Caché IP: Almacena el país de cada IP visitante durante 24 horas para evitar ralentizaciones en peticiones repetidas.

**Mejoras**:

Nueva sección en Ajustes para gestionar la lista negra de países mediante códigos ISO.

###4.5
*Fecha de lanzamiento: 20 de Enero de 2026*

**Añadido**:

Control en Ajustes: Nueva casilla "Activar actualizaciones automáticas" dentro del panel de configuración del plugin, permitiendo activar o desactivar esta función sin necesidad de desactivar el plugin completo.
Gestión Inteligente: Al desactivar el plugin, el sistema elimina automáticamente el plugin de la lista de actualizaciones automáticas para evitar actualizaciones fantasma.

**Corregido**:

Sincronización completa entre el interruptor de la lista de plugins y la casilla de configuración interna. El usuario ahora tiene control total y claro sobre cuándo se actualiza el plugin.

###4.4
*Fecha de lanzamiento: 20 de Enero de 2026*

**Corregido**:

Implementada la inyección forzada del atributo update-supported para garantizar la compatibilidad con el sistema nativo de actualizaciones automáticas de WordPress 6.0+.
Añadido script JavaScript para asegurar que la tabla del plugin reciba la clase CSS necesaria para mostrar el interruptor (toggle) de actualizaciones automáticas.
Mejorado el sistema de detección de versiones para manejar correctamente el estado de "sin actualizaciones" (no_update).

**Notas**:

Si el interruptor no aparece en la lista de plugins, asegúrese de estar utilizando WordPress 6.0 o superior, ya que la interfaz de actualización por plugin individual no está disponible en versiones anteriores.

###4.3
*Fecha de lanzamiento: 20 de Enero de 206*

**Añadido**:

Interruptor de Actualizaciones Automáticas: Implementación completa del sistema para activar o desactivar las actualizaciones automáticas directamente desde la lista dePlugins de WordPress.
Filtro auto_update_plugin para permitir la gestión manual por parte del usuario.

**Corregido**:

Corregido un error por el que el control de actualizaciones automáticas no aparecía en la interfaz.

### 4.2.2
*Fecha de lanzamiento: 20 de Enero de 2026*

**Corregido**:

Restaurada la sección de Información al final de la página de Ajustes, que se había perdido en versiones anteriores.
Se vuelve a mostrar el aviso sobre el almacenamiento temporal de IPs y la recomendación de revisar los logs del servidor.
Pequeñas mejoras de legibilidad en el panel de configuración.

### 4.2
*Fecha de lanzamiento: 20 de Enero de 2026*

****Añadido**:**
*   **Enlaces de acción en Plugins:** Se han añadido enlaces directos debajo del nombre del plugin en la lista de plugins:
    *   Enlace a **Ajustes** (configuración del plugin).
    *   Enlace a **Soporte** (Repositorio de GitHub).
    *   Enlace a **Documentación** (Perfil de GitHub).
*   **Control de Actualizaciones Automáticas:** Habilitada la compatibilidad nativa para activar/desactivar las actualizaciones automáticas directamente desde la lista de plugins de WordPress.
*   **Metadatos del Autor:** Actualizada la información del cabecera del plugin con créditos actualizados (XorEax MrGamer) y enlaces a redes sociales (YouTube y GitHub).

****Cambios**:**
*   Renombrado el archivo principal del plugin a `proteccion-anti-bots.php` para estandarizar la nomenclatura.
*   Actualizadas las credenciales de la API de GitHub para apuntar al repositorio oficial `XorEax-WordPress-Security`.

****Corregido**:**
*   Ajustes menores en la detección de la versión remota para asegurar una sincronización correcta con el nuevo nombre del archivo.

***

### Versiones anteriores

### 4.1
*   **Mejora:** Los nombres de usuario detectados como inseguros ahora se muestran en **negrita y rojo** para destacarlos visualmente.
*   **Mejora:** El mensaje de alerta ahora incluye un enlace directo clickeable a la "sección de usuarios" para facilitar la corrección rápida.

### 4.0
*   **Nuevo:** Sistema de actualizaciones automáticas integrado vía GitHub.
*   **Nuevo:** Panel de administración completo en Ajustes > Anti-Bots.
*   **Nuevo:** Configuración personalizable para listas negras y límites de 404.
*   **Seguridad:** Implementada alerta de segurida
