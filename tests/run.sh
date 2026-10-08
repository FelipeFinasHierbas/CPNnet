#!/usr/bin/env bash
# Corre las pruebas contra el .zip de la base (CPNnet) y contra el de cada variante.
# Uso: tests/run.sh   (requiere haber corrido wordpress-plugin/build.sh y, para variantes, VARIANT=<v> build.sh)
set -uo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
T="$(mktemp -d)"; PIDS=""; trap 'kill $PIDS 2>/dev/null || true; rm -rf "$T"' EXIT
cp "$ROOT"/tests/*.php "$T"/
unzip -q "$ROOT/dist/cpnnet-asistente.zip" -d "$T"
for z in "$ROOT"/dist/*-asistente.zip; do [ "$(basename "$z")" = cpnnet-asistente.zip ] || unzip -q "$z" -d "$T"; done
cd "$T"
php -S 127.0.0.1:8098 mock.php >/dev/null 2>&1 </dev/null & PIDS="$!"
php -S 127.0.0.1:8097 hook.php >/dev/null 2>&1 </dev/null & PIDS="$PIDS $!"
sleep 1
export ANTHROPIC_BASE_URL=http://127.0.0.1:8098 NO_PROXY=127.0.0.1
echo "== base CPNnet =="; php test.php | tail -4; php test_brand.php | tail -6
for d in "$T"/*/; do n="$(basename "$d")"; [ "$n" = cpnnet-asistente ] && continue
  echo "== variante $n =="; PLUGIN_DIR="$n" php test_brand.php | tail -12; done
