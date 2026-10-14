#!/usr/bin/env bash
# Arma releases/Kind-Force-propuesta.zip: índice + presentación PDF + demos interactivas (chat y reservas).
# Requiere haber generado: presentacion-kindforce/*.pdf (tools/build_pdf_kindforce.js), demo/pilates-chat.html y demo/pilates-reservas.html.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
T="$(mktemp -d)"; trap 'rm -rf "$T"' EXIT
K="$T/Kind-Force-propuesta"; mkdir -p "$K"
sed "s|__LOGO__|$(base64 -w0 "$ROOT/variants/pilates/img/pilates-logo-white.png")|" "$ROOT/presentacion-kindforce/index.template.html" > "$K/index.html"
cp "$ROOT/presentacion-kindforce/Kind-Force-Asistente-y-reservas.pdf" "$K/"
cp "$ROOT/demo/pilates-chat.html" "$K/demo-asistente.html"
cp "$ROOT/demo/pilates-reservas.html" "$K/demo-reservas.html"
rm -f "$ROOT/releases/Kind-Force-propuesta.zip"
(cd "$T" && zip -qr "$ROOT/releases/Kind-Force-propuesta.zip" Kind-Force-propuesta)
ls -lh "$ROOT/releases/Kind-Force-propuesta.zip"
