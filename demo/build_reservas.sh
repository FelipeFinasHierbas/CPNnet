#!/usr/bin/env bash
# Genera demo/<slug>-reservas.html (pantallas reales del módulo de reservas) para una variante con reservas. Uso: demo/build_reservas.sh pilates
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
V="${1:-pilates}"
SLUG="$(python3 -c "import json;print(json.load(open('$ROOT/variants/$V/variant.json'))['slug'])")"
VARIANT="$V" "$ROOT/wordpress-plugin/build.sh" >/dev/null
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
unzip -q "$ROOT/dist/$SLUG.zip" -d "$T"
cp "$ROOT/tests/common.php" "$ROOT/demo/render_reservas.php" "$T/"
PLUGIN_DIR="$SLUG" php "$T/render_reservas.php" "$ROOT/demo/${SLUG%%-*}-reservas.html"
