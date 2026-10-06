<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Contenido editable del asistente. Los archivos que vienen con el plugin son los valores por defecto;
 * lo que el cliente edite en el panel se guarda en la base de datos de WordPress y tiene prioridad.
 */
const CPNNET_ASISTENTE_OPT_KB      = 'cpnnet_asistente_kb';
const CPNNET_ASISTENTE_OPT_RULES   = 'cpnnet_asistente_rules';
const CPNNET_ASISTENTE_OPT_COMPANY = 'cpnnet_asistente_company';

function cpnnet_asistente_read_file(string $rel): string
{
    $path = CPNNET_ASISTENTE_DIR . $rel;
    return is_readable($path) ? (string) file_get_contents($path) : '';
}

function cpnnet_asistente_kb_default(): array
{
    $kb = json_decode(cpnnet_asistente_read_file('knowledge/brands.json'), true);
    return is_array($kb) ? $kb : ['domains' => [], 'categories' => [], 'brands' => []];
}

function cpnnet_asistente_kb(): array
{
    $kb = get_option(CPNNET_ASISTENTE_OPT_KB, null);
    return (is_array($kb) && !empty($kb['brands'])) ? $kb : cpnnet_asistente_kb_default();
}

function cpnnet_asistente_kb_is_custom(): bool
{
    return is_array(get_option(CPNNET_ASISTENTE_OPT_KB, null));
}

function cpnnet_asistente_rules_default(): string
{
    return trim(cpnnet_asistente_read_file('prompt/system-prompt.md'));
}

function cpnnet_asistente_company_default(): string
{
    return trim(cpnnet_asistente_read_file('knowledge/company.md'));
}

function cpnnet_asistente_rules(): string
{
    $v = trim((string) get_option(CPNNET_ASISTENTE_OPT_RULES, ''));
    return $v !== '' ? $v : cpnnet_asistente_rules_default();
}

function cpnnet_asistente_company(): string
{
    $v = trim((string) get_option(CPNNET_ASISTENTE_OPT_COMPANY, ''));
    return $v !== '' ? $v : cpnnet_asistente_company_default();
}

/** Quita valores vacíos/nulos para no gastar tokens en ellos. */
function cpnnet_asistente_compact($v)
{
    if (!is_array($v)) {
        return $v;
    }
    $out = [];
    foreach ($v as $k => $x) {
        $x = cpnnet_asistente_compact($x);
        if ($x === null || $x === '' || $x === []) {
            continue;
        }
        $out[$k] = $x;
    }
    return $out;
}

/** Estimación gruesa de tokens (≈ 3,5 caracteres por token en español). */
function cpnnet_asistente_approx_tokens(string $text): int
{
    return (int) ceil(mb_strlen($text) / 3.5);
}

/* ---------- Edición de marcas ---------- */

function cpnnet_asistente_lines(string $raw): array
{
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    return array_values(array_filter(array_map(static fn($l) => trim(sanitize_text_field($l)), $lines), static fn($l) => $l !== ''));
}

function cpnnet_asistente_find_brand(array $kb, string $id): ?array
{
    foreach ($kb['brands'] as $b) {
        if (($b['id'] ?? '') === $id) {
            return $b;
        }
    }
    return null;
}

/** Quita una marca de las listas de las categorías. */
function cpnnet_asistente_kb_detach(array $kb, string $id): array
{
    foreach ($kb['categories'] as &$c) {
        foreach (['first_order', 'cross_sell'] as $list) {
            $c[$list] = array_values(array_diff($c[$list] ?? [], [$id]));
        }
    }
    unset($c);
    return $kb;
}

/** Crea o actualiza una marca a partir de los datos del formulario ya sanitizados. */
function cpnnet_asistente_kb_upsert(array $kb, array $brand, ?string $original_id): array
{
    $id = $original_id ?: sanitize_title($brand['name']);
    if ($id === '') {
        $id = 'marca-' . substr(md5($brand['name'] . microtime()), 0, 6);
    }
    $brand['id'] = $id;

    $found = false;
    foreach ($kb['brands'] as $i => $b) {
        if (($b['id'] ?? '') === $id) {
            $kb['brands'][$i] = array_merge($b, $brand); // conserva campos que el formulario no edita
            $found = true;
            break;
        }
    }
    if (!$found) {
        // evitar colisión de ids al crear
        while (cpnnet_asistente_find_brand($kb, $brand['id'])) {
            $brand['id'] .= '-2';
        }
        $id = $brand['id'];
        $kb['brands'][] = $brand;
    }

    $kb = cpnnet_asistente_kb_detach($kb, $id);
    if (($brand['tier'] ?? '') === 'primer_orden') {
        foreach ($kb['categories'] as &$c) {
            if ($c['id'] === ($brand['category'] ?? '')) {
                $c['first_order'][] = $id;
            }
        }
        unset($c);
    }
    return $kb;
}

function cpnnet_asistente_kb_delete(array $kb, string $id): array
{
    $kb = cpnnet_asistente_kb_detach($kb, $id);
    $kb['brands'] = array_values(array_filter($kb['brands'], static fn($b) => ($b['id'] ?? '') !== $id));
    return $kb;
}
