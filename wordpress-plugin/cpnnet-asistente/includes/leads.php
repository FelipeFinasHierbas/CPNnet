<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repositorio de leads, en la base de datos de WordPress del cliente.
 * Es independiente del CRM interno de CPNnet: este plugin no lee ni escribe en el CRM;
 * el CRM toma los datos por la API, el webhook o el CSV (ver includes/integration.php).
 */
const CPNNET_ASISTENTE_STATUSES = [
    'nuevo'      => 'Nuevo',
    'contactado' => 'Contactado',
    'calificado' => 'Calificado',
    'ganado'     => 'Ganado',
    'perdido'    => 'Perdido',
];

function cpnnet_asistente_leads_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'cpnnet_asistente_leads';
}

function cpnnet_asistente_leads_create_table(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table   = cpnnet_asistente_leads_table();
    $charset = $wpdb->get_charset_collate();
    // dbDelta agrega las columnas nuevas en instalaciones existentes sin perder datos.
    dbDelta("CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        uuid CHAR(36) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'nuevo',
        perfil VARCHAR(20) NOT NULL DEFAULT '',
        nombre VARCHAR(190) NOT NULL DEFAULT '',
        empresa VARCHAR(190) NOT NULL DEFAULT '',
        pais VARCHAR(80) NOT NULL DEFAULT '',
        contacto VARCHAR(190) NOT NULL DEFAULT '',
        necesidad TEXT NULL,
        marcas TEXT NULL,
        dimensionamiento TEXT NULL,
        siguiente_paso TEXT NULL,
        pagina VARCHAR(255) NOT NULL DEFAULT '',
        conv_id VARCHAR(40) NOT NULL DEFAULT '',
        transcript LONGTEXT NULL,
        notes TEXT NULL,
        exported_at DATETIME NULL,
        webhook_status VARCHAR(80) NOT NULL DEFAULT '',
        resumen TEXT NULL,
        PRIMARY KEY  (id),
        KEY created_at (created_at),
        KEY status (status),
        KEY uuid (uuid)
    ) {$charset};");
}

function cpnnet_asistente_uuid(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

/**
 * Guarda un lead. Siempre se guarda (es el repositorio del área comercial); la opción
 * «guardar conversación» solo controla si se incluye la transcripción.
 * @return array{id:int,uuid:string}
 */
function cpnnet_asistente_leads_insert(array $lead, string $resumen, string $conv_id = '', string $pagina = '', array $transcript = []): array
{
    global $wpdb;
    $uuid  = cpnnet_asistente_uuid();
    $now   = current_time('mysql', true); // UTC
    $marcas = $lead['marcas_interes'] ?? [];
    $marcas = is_array($marcas) ? array_values(array_filter(array_map('strval', $marcas))) : [];
    $keep_transcript = (bool) cpnnet_asistente_get('save_transcript');

    $wpdb->insert(cpnnet_asistente_leads_table(), [
        'uuid'             => $uuid,
        'created_at'       => $now,
        'updated_at'       => $now,
        'status'           => 'nuevo',
        'perfil'           => substr((string) ($lead['perfil'] ?? ''), 0, 20),
        'nombre'           => substr((string) ($lead['nombre'] ?? ''), 0, 190),
        'empresa'          => substr((string) ($lead['empresa'] ?? ''), 0, 190),
        'pais'             => substr((string) ($lead['pais'] ?? ''), 0, 80),
        'contacto'         => substr((string) ($lead['contacto'] ?? ''), 0, 190),
        'necesidad'        => (string) ($lead['necesidad'] ?? ''),
        'marcas'           => wp_json_encode($marcas, JSON_UNESCAPED_UNICODE),
        'dimensionamiento' => (string) ($lead['dimensionamiento'] ?? ''),
        'siguiente_paso'   => (string) ($lead['siguiente_paso'] ?? ''),
        'pagina'           => substr($pagina, 0, 255),
        'conv_id'          => substr($conv_id, 0, 40),
        'transcript'       => ($keep_transcript && $transcript) ? wp_json_encode($transcript, JSON_UNESCAPED_UNICODE) : null,
        'resumen'          => $resumen,
    ]);
    return ['id' => (int) $wpdb->insert_id, 'uuid' => $uuid];
}

/** Fila de la base de datos -> estructura pública (la misma para la API, el webhook, el CSV y el panel). */
function cpnnet_asistente_lead_payload(array $r, bool $with_conversation = false): array
{
    $iso = static fn(?string $d): ?string => $d ? str_replace(' ', 'T', $d) . 'Z' : null; // se guardan en UTC
    $out = [
        'id'               => $r['uuid'],
        'created_at'       => $iso($r['created_at'] ?? null),
        'updated_at'       => $iso($r['updated_at'] ?? null),
        'status'           => $r['status'],
        'perfil'           => $r['perfil'],
        'nombre'           => $r['nombre'],
        'empresa'          => $r['empresa'],
        'pais'             => $r['pais'],
        'contacto'         => $r['contacto'],
        'necesidad'        => (string) $r['necesidad'],
        'marcas_interes'   => json_decode((string) $r['marcas'], true) ?: [],
        'dimensionamiento' => (string) $r['dimensionamiento'],
        'siguiente_paso'   => (string) $r['siguiente_paso'],
        'pagina_origen'    => $r['pagina'],
        'notas'            => (string) $r['notes'],
        'exportado_at'     => $iso($r['exported_at'] ?? null),
    ];
    if ($with_conversation) {
        $out['conversacion'] = json_decode((string) $r['transcript'], true) ?: [];
    }
    return $out;
}

function cpnnet_asistente_lead_by(string $col, $value): ?array
{
    global $wpdb;
    $table = cpnnet_asistente_leads_table();
    $col   = $col === 'uuid' ? 'uuid' : 'id';
    $row   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE {$col} = %s", (string) $value), ARRAY_A);
    return $row ?: null;
}

/**
 * Filtros: status, perfil, q (texto), from/to (YYYY-MM-DD, en UTC), updated_since (ISO), exported (0|1).
 * @return array{rows:array<int,array>,total:int}
 */
function cpnnet_asistente_leads_query(array $f = [], int $limit = 20, int $offset = 0): array
{
    global $wpdb;
    $table = cpnnet_asistente_leads_table();
    $where = ['1=1'];
    $args  = [];
    if (!empty($f['status']) && isset(CPNNET_ASISTENTE_STATUSES[$f['status']])) {
        $where[] = 'status = %s';
        $args[]  = $f['status'];
    }
    if (!empty($f['perfil']) && in_array($f['perfil'], ['partner', 'empresa', 'desconocido'], true)) {
        $where[] = 'perfil = %s';
        $args[]  = $f['perfil'];
    }
    if (!empty($f['q'])) {
        $like = '%' . $wpdb->esc_like((string) $f['q']) . '%';
        $where[] = '(nombre LIKE %s OR empresa LIKE %s OR pais LIKE %s OR contacto LIKE %s OR necesidad LIKE %s OR marcas LIKE %s)';
        array_push($args, $like, $like, $like, $like, $like, $like);
    }
    if (!empty($f['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) {
        $where[] = 'created_at >= %s';
        $args[]  = $f['from'] . ' 00:00:00';
    }
    if (!empty($f['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) {
        $where[] = 'created_at <= %s';
        $args[]  = $f['to'] . ' 23:59:59';
    }
    if (!empty($f['updated_since'])) {
        $ts = strtotime((string) $f['updated_since']);
        if ($ts) {
            $where[] = 'updated_at >= %s';
            $args[]  = gmdate('Y-m-d H:i:s', $ts);
        }
    }
    if (isset($f['exported']) && $f['exported'] !== '') {
        $where[] = ((string) $f['exported'] === '1') ? 'exported_at IS NOT NULL' : 'exported_at IS NULL';
    }
    $w = implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$w}";
    $total     = (int) $wpdb->get_var($args ? $wpdb->prepare($count_sql, ...$args) : $count_sql);
    $sql       = "SELECT * FROM {$table} WHERE {$w} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d";
    $rows      = $wpdb->get_results($wpdb->prepare($sql, ...array_merge($args, [$limit, $offset])), ARRAY_A) ?: [];
    return ['rows' => $rows, 'total' => $total];
}

function cpnnet_asistente_lead_update(int $id, array $fields): void
{
    global $wpdb;
    $allowed = ['status', 'notes', 'exported_at', 'webhook_status'];
    $data = array_intersect_key($fields, array_flip($allowed));
    if (isset($data['status']) && !isset(CPNNET_ASISTENTE_STATUSES[$data['status']])) {
        unset($data['status']);
    }
    if (!$data) {
        return;
    }
    $data['updated_at'] = current_time('mysql', true);
    $wpdb->update(cpnnet_asistente_leads_table(), $data, ['id' => $id]);
}

/** Marca leads como exportados (por uuid). @return int cantidad marcada */
function cpnnet_asistente_leads_mark_exported(array $uuids): int
{
    global $wpdb;
    $n = 0;
    foreach ($uuids as $u) {
        $r = cpnnet_asistente_lead_by('uuid', (string) $u);
        if ($r) {
            $wpdb->update(cpnnet_asistente_leads_table(), ['exported_at' => current_time('mysql', true)], ['id' => (int) $r['id']]);
            $n++;
        }
    }
    return $n;
}

/** Celdas que empiezan con = + - @ se neutralizan para evitar inyección de fórmulas al abrir el CSV en Excel. */
function cpnnet_asistente_csv_cell($v): string
{
    $s = is_array($v) ? implode(' | ', array_map('strval', $v)) : (string) $v;
    $s = str_replace(["\r\n", "\r", "\n"], ' ', $s);
    if ($s !== '' && strpbrk($s[0], "=+-@\t") !== false) {
        $s = "'" . $s;
    }
    return '"' . str_replace('"', '""', $s) . '"';
}

/** @param array<int,array> $rows filas de la base de datos */
function cpnnet_asistente_leads_csv(array $rows): string
{
    $cols = ['id', 'created_at', 'status', 'perfil', 'nombre', 'empresa', 'pais', 'contacto', 'necesidad', 'marcas_interes', 'dimensionamiento', 'siguiente_paso', 'pagina_origen', 'notas', 'exportado_at'];
    $out  = "\xEF\xBB\xBF" . implode(',', $cols) . "\r\n"; // BOM para que Excel lea UTF-8
    foreach ($rows as $r) {
        $p = cpnnet_asistente_lead_payload($r);
        $out .= implode(',', array_map(static fn($c) => cpnnet_asistente_csv_cell($p[$c] ?? ''), $cols)) . "\r\n";
    }
    return $out;
}
