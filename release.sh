#!/usr/bin/env bash
# Genera los paquetes instalables y los copia a releases/ con su versión:
#   releases/cpnnet-asistente-<versión>.zip  -> plugin de WordPress (Plugins > Añadir nuevo > Subir)
#   releases/asistente-demo.zip              -> demos estáticas (cPanel > Administrador de archivos > Extraer)
# Requisitos: PHP 8.1+, composer, zip, unzip, python3.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
"$ROOT/demo/build_all.sh"
VER="$(grep -m1 'Version:' "$ROOT/wordpress-plugin/cpnnet-asistente/cpnnet-asistente.php" | sed 's/.*Version:[[:space:]]*//')"
mkdir -p "$ROOT/releases"
cp "$ROOT/dist/cpnnet-asistente.zip" "$ROOT/releases/cpnnet-asistente-$VER.zip"
cp "$ROOT/dist/asistente-demo.zip" "$ROOT/releases/asistente-demo.zip"
ls -lh "$ROOT/releases"
