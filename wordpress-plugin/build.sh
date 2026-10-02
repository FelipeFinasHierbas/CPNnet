#!/usr/bin/env bash
# Genera dist/cpnnet-asistente.zip listo para subir a WordPress (Plugins > Añadir nuevo > Subir,
# o cPanel > Administrador de archivos > wp-content/plugins).
# Copia la base de conocimiento y el prompt desde el repo, instala el SDK (sin dev) y limpia peso innecesario.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/wordpress-plugin/cpnnet-asistente"

python3 "$ROOT/knowledge/validate.py"
mkdir -p "$PLUGIN/knowledge" "$PLUGIN/prompt"
cp "$ROOT/knowledge/brands.json" "$ROOT/knowledge/company.md" "$PLUGIN/knowledge/"
cp "$ROOT/bot/system-prompt.md" "$PLUGIN/prompt/"

(cd "$PLUGIN" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --optimize-autoloader)

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
cp -a "$PLUGIN" "$STAGE/cpnnet-asistente"
cd "$STAGE/cpnnet-asistente"
# quitar peso innecesario de vendor/ (repos .git, tests, docs, ejemplos, markdown)
find vendor -name .git -prune -exec rm -rf {} + 2>/dev/null || true
find vendor -type d \( -name tests -o -name Tests -o -name docs -o -name examples -o -name .github \) -prune -exec rm -rf {} + 2>/dev/null || true
find vendor -type f \( -name '*.md' -o -name 'phpunit*' -o -name '.gitattributes' \) -delete
rm -f composer.lock
cd "$ROOT"

mkdir -p "$ROOT/dist"
rm -f "$ROOT/dist/cpnnet-asistente.zip"
(cd "$STAGE" && zip -qr "$ROOT/dist/cpnnet-asistente.zip" cpnnet-asistente)
ls -lh "$ROOT/dist/cpnnet-asistente.zip"
