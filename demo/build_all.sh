#!/usr/bin/env bash
# Genera las demos y las empaqueta en dist/asistente-demo.zip, listo para subir al hosting (cPanel > Administrador de archivos > Extraer).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
"$ROOT/wordpress-plugin/build.sh" >/dev/null
python3 "$ROOT/demo/build.py"
"$ROOT/demo/build_dashboard.sh"
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
mkdir "$T/asistente-demo"
cp "$ROOT/demo/index.html" "$ROOT/demo/chat.html" "$ROOT/demo/panel.html" "$T/asistente-demo/"
# que los buscadores no indexen las demos (si el servidor tiene mod_headers; si no, ignora el bloque)
cat > "$T/asistente-demo/.htaccess" <<'HT'
<IfModule mod_headers.c>
Header set X-Robots-Tag "noindex, nofollow"
</IfModule>
HT
cat > "$T/asistente-demo/robots.txt" <<'RB'
User-agent: *
Disallow: /
RB
mkdir -p "$ROOT/dist"; rm -f "$ROOT/dist/asistente-demo.zip"
(cd "$T" && zip -qr "$ROOT/dist/asistente-demo.zip" asistente-demo)
ls -lh "$ROOT/dist/asistente-demo.zip"
