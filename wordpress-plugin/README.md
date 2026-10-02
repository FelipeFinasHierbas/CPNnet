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
4. Ajustes > Asistente CPNnet: número de WhatsApp (solo dígitos, con código de país), mensaje de bienvenida y límites de uso. Marcar "Activar chat" al final.

## Cómo funciona
- El navegador solo habla con `/wp-json/cpnnet-asistente/v1/chat` del propio sitio; la API key nunca sale del servidor.
- Protecciones: solo mismo origen, honeypot, límite por visitante/hora y tope diario de mensajes (tope de gasto).
- Cuando el visitante da su consentimiento y quiere avanzar, el bot llama a la herramienta `derivar_a_ejecutivo`: se muestra un botón de WhatsApp con el resumen del lead y, si está activado, se guarda una copia en la tabla `wp_cpnnet_asistente_leads` (visible en phpMyAdmin). **No se lee ni escribe en el CRM interno.**
- El prompt usa caching: la base de conocimiento (~14k tokens) se cobra como lectura de caché tras la primera consulta.

## Actualizar el conocimiento
Editar `knowledge/brands.json` (o `bot/system-prompt.md`), correr `knowledge/validate.py` y volver a generar el .zip.
