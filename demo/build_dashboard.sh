#!/usr/bin/env bash
# Genera demo/panel.html a partir del plugin real (dist/cpnnet-asistente.zip) y datos de ejemplo.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
[ -f "$ROOT/dist/cpnnet-asistente.zip" ] || "$ROOT/wordpress-plugin/build.sh"
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
unzip -q "$ROOT/dist/cpnnet-asistente.zip" -d "$T"
cp "$ROOT/tests/common.php" "$ROOT/demo/render_dashboard.php" "$T/"
php "$T/render_dashboard.php" "$ROOT/demo/panel.html"
