# Asistente comercial con IA para CPNnet Security

Chat con IA para www.cpnnetsecurity.com que orienta a partners y empresas sobre el portafolio de ciberseguridad de CPNnet, califica el interés y entrega leads al equipo comercial. Corre como **plugin de WordPress** dentro del hosting del cliente (cPanel), con la **API key de la propia cuenta de CPNnet**.

## Instalar (lo más rápido)
Los paquetes ya construidos están en [`releases/`](releases/):

| Archivo | Para qué | Cómo |
|---|---|---|
| `cpnnet-asistente-0.4.0.zip` | Plugin real (IA, panel comercial, integración CRM) | WordPress > Plugins > Añadir nuevo > Subir plugin. Pasos de configuración: [`wordpress-plugin/README.md`](wordpress-plugin/README.md) y [`docs/GUIA-CLIENTE.md`](docs/GUIA-CLIENTE.md) |
| `asistente-demo.zip` | Demos guionadas (chat + panel), sin PHP ni API key | cPanel > Administrador de archivos > subir y **Extraer**. Ver [`demo/LEEME.md`](demo/LEEME.md) |

Requisitos del plugin: PHP 8.1+, salida HTTPS a `api.anthropic.com`, y una API key de Anthropic en `wp-config.php`: `define('CPNNET_ASISTENTE_API_KEY', 'sk-ant-...');`

**Modo de prueba:** al activar el chat, por defecto lo ven solo los administradores (Configuración > General > «Quién ve el chat»). Se cambia a «Todos los visitantes» cuando las pruebas estén bien.

## Qué hay en el repositorio
| Carpeta | Contenido |
|---|---|
| `wordpress-plugin/` | Plugin (PHP + JS/CSS), `build.sh` que genera el .zip, README técnico |
| `knowledge/` | Base de conocimiento del catálogo (`brands.json`, `company.md`) y su validador |
| `bot/` | Reglas de conversación del asistente (`system-prompt.md`) |
| `demo/` | Demos estáticas (`index`, `chat`, `panel`) y sus scripts de generación |
| `presentacion/` | Presentación para el cliente (HTML fuente + PDF + capturas) |
| `docs/` | Guía del cliente y guía de integración con el CRM |
| `tests/` | Pruebas del plugin (simulan WordPress con SQLite; no necesitan MySQL ni API key) |
| `tools/` | Pruebas de las demos en navegador, y generación del PDF y de las capturas |
| `releases/` | Paquetes instalables ya construidos |

## Desarrollar en local
```bash
# 1. Construir paquetes (PHP 8.1+, composer, zip, unzip, python3)
./release.sh                      # -> releases/*.zip

# 2. Pruebas del plugin (ver tests/README.md)
# 3. Pruebas y PDF (Node 18+)
cd tools && npm run setup         # instala playwright y Chromium
npm run e2e                       # prueba las demos en un navegador
npm run capturas && npm run pdf   # regenera capturas y presentación
```
Si cambias `knowledge/brands.json` o `bot/system-prompt.md`: `python3 knowledge/validate.py` y vuelve a correr `./release.sh`.

Contexto del proyecto, decisiones tomadas y pendientes: [`CLAUDE.md`](CLAUDE.md).
