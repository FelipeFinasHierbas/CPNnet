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
4. Ajustes > Asistente CPNnet > General: número de WhatsApp (solo dígitos, con código de país), modelo, mensaje de bienvenida, límites y tope mensual de gasto. Marcar "Activar chat" al final.
5. El cliente puede editar marcas, reglas y datos de la empresa desde las otras pestañas, sin tocar código (ver `docs/GUIA-CLIENTE.md`).

## Cómo funciona
- El navegador solo habla con `/wp-json/cpnnet-asistente/v1/chat` del propio sitio; la API key nunca sale del servidor.
- Protecciones: solo mismo origen, honeypot, límite por visitante/hora y tope diario de mensajes (tope de gasto).
- Cuando el visitante da su consentimiento y quiere avanzar, el bot llama a la herramienta `derivar_a_ejecutivo`: se muestra un botón de WhatsApp con el resumen del lead y, si está activado, se guarda una copia en la tabla `wp_cpnnet_asistente_leads` (visible en phpMyAdmin). **No se lee ni escribe en el CRM interno.**
- El prompt usa caching: la base de conocimiento (~14k tokens) se cobra como lectura de caché tras la primera consulta.

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
- `demo/dashboard.html`: demo navegable del panel comercial (pantallas reales del plugin con datos de ejemplo; abrir con doble clic). Regenerar con `./demo/build_dashboard.sh`.
- `demo/index.html`: demo guionada del chat, de una sola página (abrir con doble clic; no necesita internet ni API key). Regenerar con `python3 demo/build.py` tras editar `demo/template.html`.
- `presentacion/Asistente-comercial-CPNnet.pdf`: presentación para el cliente (fuente: `presentacion/presentacion.html`).
