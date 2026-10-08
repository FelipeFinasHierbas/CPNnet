#!/usr/bin/env bash
# Genera dist/cpnnet-asistente.zip listo para subir a WordPress (Plugins > Añadir nuevo > Subir,
# o cPanel > Administrador de archivos > wp-content/plugins).
# VARIANT=the-forest ./build.sh  -> dist/forest-asistente.zip (misma base de código, con la marca y el conocimiento de variants/the-forest/).
# Copia la base de conocimiento y el prompt desde el repo, instala el SDK (sin dev) y limpia peso innecesario.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN="$ROOT/wordpress-plugin/cpnnet-asistente"

VARIANT="${VARIANT:-}"
python3 "$ROOT/knowledge/validate.py"
[ -n "$VARIANT" ] && python3 "$ROOT/knowledge/validate.py" "$ROOT/variants/$VARIANT/knowledge/brands.json"
mkdir -p "$PLUGIN/knowledge" "$PLUGIN/prompt"
cp "$ROOT/knowledge/brands.json" "$ROOT/knowledge/company.md" "$PLUGIN/knowledge/"
cp "$ROOT/bot/system-prompt.md" "$PLUGIN/prompt/"

(cd "$PLUGIN" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --optimize-autoloader)

STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
SLUG=cpnnet-asistente
cp -a "$PLUGIN" "$STAGE/cpnnet-asistente"
if [ -n "$VARIANT" ]; then
  V="$ROOT/variants/$VARIANT"
  SLUG="$(python3 -c "import json;print(json.load(open('$V/variant.json'))['slug'])")"
  cp "$V/knowledge/brands.json" "$V/knowledge/company.md" "$STAGE/cpnnet-asistente/knowledge/"
  cp "$V/bot/system-prompt.md" "$STAGE/cpnnet-asistente/prompt/"
  cp "$V/brand.json" "$STAGE/cpnnet-asistente/brand.json"
  cp "$V"/img/* "$STAGE/cpnnet-asistente/assets/img/"
  rm -f "$STAGE/cpnnet-asistente/assets/img/cpnnet-logo"*.png
  python3 - "$V/variant.json" "$STAGE/cpnnet-asistente/cpnnet-asistente.php" <<'PY'
import json, re, sys
v = json.load(open(sys.argv[1])); p = sys.argv[2]
s = open(p, encoding="utf-8").read()
s = re.sub(r"(Plugin Name:\s+).*", lambda m: m.group(1) + v["plugin_name"], s, 1)
s = re.sub(r"(Description:\s+).*", lambda m: m.group(1) + v["description"], s, 1)
s = re.sub(r"(Author:\s+).*", lambda m: m.group(1) + v["author"], s, 1)
open(p, "w", encoding="utf-8").write(s)
PY
  mv "$STAGE/cpnnet-asistente" "$STAGE/$SLUG"
  mv "$STAGE/$SLUG/cpnnet-asistente.php" "$STAGE/$SLUG/$SLUG.php"
fi
cd "$STAGE/$SLUG"
# quitar peso innecesario de vendor/ (repos .git, tests, docs, ejemplos, markdown)
find vendor -name .git -prune -exec rm -rf {} + 2>/dev/null || true
find vendor -type d \( -name tests -o -name Tests -o -name docs -o -name examples -o -name .github \) -prune -exec rm -rf {} + 2>/dev/null || true
find vendor -type f \( -name '*.md' -o -name 'phpunit*' -o -name '.gitattributes' \) -delete
rm -f composer.lock
cd "$ROOT"

mkdir -p "$ROOT/dist"
rm -f "$ROOT/dist/$SLUG.zip"
(cd "$STAGE" && zip -qr "$ROOT/dist/$SLUG.zip" "$SLUG")
ls -lh "$ROOT/dist/$SLUG.zip"
