# Plugin de WordPress: Asistente comercial CPNnet

Chat con IA (Claude) para www.cpnnetsecurity.com. Corre dentro de WordPress en el cPanel: no necesita Node ni otro servidor.

## Generar el .zip
```bash
./wordpress-plugin/build.sh      # requiere PHP 8.1+, composer y zip
# -> dist/cpnnet-asistente.zip
```
El script copia la base de conocimiento (`knowledge/`) y el prompt (`bot/system-prompt.md`) dentro del plugin, instala el SDK de Anthropic y limpia el peso innecesario (~4 MB).

## Instalar en el cPanel
1. WordPress > Plugins > Añadir nuevo > Subir plugin > `cpnnet-asistente.zip` > Activar.
   (Alternativa: cPanel > Administrador de archivos > `public_html/wp-content/plugins/` > subir y extraer.)
2. Requisitos del hosting: PHP 8.1 o superior (cPanel > "Select PHP Version" / MultiPHP Manager) y que el servidor pueda hacer conexiones HTTPS salientes a `api.anthropic.com`.
3. **API key:** agregar en `wp-config.php`, antes de la línea `/* That's all, stop editing! */`:
   ```php
   define('CPNNET_ASISTENTE_API_KEY', 'sk-ant-...');
   ```
4. Ajustes > Asistente CPNnet > General: «Quién ve el chat» (empieza en **Solo administradores**: modo de prueba), número de WhatsApp (solo dígitos, con código de país), modelo, mensaje de bienvenida, límites y tope mensual de gasto. Marcar "Activar chat" al final, probar como administrador y, si todo está bien, cambiar «Quién ve el chat» a «Todos los visitantes».
5. El cliente puede editar marcas, reglas y datos de la empresa desde las otras pestañas, sin tocar código (ver `docs/GUIA-CLIENTE.md`).

## Cómo funciona
- El navegador solo habla con `/wp-json/cpnnet-asistente/v1/chat` del propio sitio; la API key nunca sale del servidor.
- Protecciones: solo mismo origen, honeypot, límite por visitante/hora y tope diario de mensajes (tope de gasto).
- Cuando el visitante da su consentimiento y quiere avanzar, el bot llama a la herramienta `derivar_a_ejecutivo`: se muestra un botón de WhatsApp con el resumen del lead y, si está activado, se guarda una copia en la tabla `wp_cpnnet_asistente_leads` (visible en phpMyAdmin). **No se lee ni escribe en el CRM interno.**
- El prompt usa caching: la base de conocimiento (~14k tokens) se cobra como lectura de caché tras la primera consulta.

## Identidad visual
- **Tipografía:** Montserrat (licencia SIL OFL 1.1, ver `assets/fonts/OFL.txt`), incluida en el plugin: no se carga desde servidores externos. Se usa en el widget del chat y en el panel; las cabeceras del panel y del chat llevan el logo de CPNnet Security (`assets/img/`).
- **Logo:** extraído del catálogo de CPNnet. Para cambiarlo, reemplazar `assets/img/cpnnet-logo.png` (fondos claros) y `cpnnet-logo-white.png` (fondos oscuros) por versiones de mayor resolución si están disponibles.

## Panel comercial e integración con el CRM
- **Repositorio:** cada lead se guarda en `wp_cpnnet_asistente_leads` (con uuid, estado, notas, conversación opcional y marca de exportado).
- **Panel:** menú «Asistente CPNnet» con Panel (indicadores y gráficos), Leads (filtros, ficha, estado, notas, CSV), Integración CRM y Configuración. Rol «Comercial CPNnet» para el área comercial (solo panel y leads).
- **CRM:** CSV, API REST con token (`/wp-json/cpnnet-asistente/v1/leads`) y webhook firmado (HMAC-SHA256). El plugin no escribe en la base de datos del CRM. Detalle en `docs/INTEGRACION-CRM.md`.

## Contenido y tokens
- **Valores por defecto:** `knowledge/brands.json`, `knowledge/company.md` y `bot/system-prompt.md` se empaquetan en el .zip.
- **Edición por el cliente:** lo que se edite en el panel (pestañas Conocimiento y Reglas) se guarda en la base de datos de WordPress y tiene prioridad sobre los archivos; hay botones para restaurar los originales.
- **Consumo:** cada respuesta registra tokens, gasto estimado y el id de conversación en la tabla `wp_cpnnet_asistente_usage` (pestaña «Uso y costos» y Panel). Hay un tope mensual configurable.
- **Ahorro de tokens:** el prompt va marcado para caché, el conocimiento se envía compacto (sin campos vacíos), el historial se limita a 12 mensajes y cada respuesta a 800 tokens.

## Demo y presentación
- **Demos online (sin PHP, sin API key):** `demo/index.html` (portada), `demo/chat.html` (chat guionado) y `demo/panel.html` (panel comercial navegable, con las pantallas reales del plugin y datos de ejemplo). Cada archivo es autocontenido (incluye Montserrat y el logo). `./demo/build_all.sh` las regenera y crea `dist/asistente-demo.zip` para subir al hosting; para editar el chat, modificar `demo/template.html`.
- `presentacion/Asistente-comercial-CPNnet.pdf`: presentación para el cliente (fuente: `presentacion/presentacion.html`).
