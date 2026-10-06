# Pruebas del plugin

Simulan WordPress y su base de datos sobre SQLite, y usan un servidor falso de Claude y un receptor de webhook. No necesitan WordPress, MySQL ni una API key.

```bash
./wordpress-plugin/build.sh                       # genera dist/cpnnet-asistente.zip
mkdir -p /tmp/t && cd /tmp/t && unzip -q /ruta/al/repo/dist/cpnnet-asistente.zip && cp /ruta/al/repo/tests/*.php .
php -S 127.0.0.1:8098 mock.php &  php -S 127.0.0.1:8097 hook.php &
ANTHROPIC_BASE_URL=http://127.0.0.1:8098 NO_PROXY=127.0.0.1 php test.php   # todas las líneas deben decir OK
```
