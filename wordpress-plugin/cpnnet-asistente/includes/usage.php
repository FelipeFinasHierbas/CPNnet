<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registro de consumo de tokens y gasto estimado, con tope mensual.
 * Los precios son de lista (USD por millón de tokens) y sirven para ESTIMAR; el cobro real es el de la consola de Anthropic.
 */
const CPNNET_ASISTENTE_DB_VERSION = '3';

function cpnnet_asistente_usage_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'cpnnet_asistente_usage';
}

function cpnnet_asistente_usage_create_table(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table   = cpnnet_asistente_usage_table();
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL,
        model VARCHAR(60) NOT NULL DEFAULT '',
        conv_id VARCHAR(40) NOT NULL DEFAULT '',
        input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
        output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
        cache_read_tokens INT UNSIGNED NOT NULL DEFAULT 0,
        cache_write_tokens INT UNSIGNED NOT NULL DEFAULT 0,
        cost_usd DECIMAL(10,6) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY created_at (created_at),
        KEY conv_id (conv_id)
    ) {$charset};");
    update_option('cpnnet_asistente_db_version', CPNNET_ASISTENTE_DB_VERSION);
}

/** Crea o actualiza las tablas si el plugin se actualizó sin reactivarse. */
add_action('plugins_loaded', function () {
    if (get_option('cpnnet_asistente_db_version') !== CPNNET_ASISTENTE_DB_VERSION) {
        cpnnet_asistente_leads_create_table();
        cpnnet_asistente_usage_create_table();
    }
});

/** USD por millón de tokens: entrada, salida, lectura de caché, escritura de caché (5 min). */
function cpnnet_asistente_prices(string $model): array
{
    $table = [
        'claude-opus-5-5'   => ['in' => 4.0, 'out' => 20.0, 'read' => 0.20, 'write' => 5.0],
        'claude-sonnet-5-5' => ['in' => 2.0, 'out' => 10.0, 'read' => 0.20, 'write' => 2.5],
        'claude-haiku-4-5'  => ['in' => 1.0, 'out' => 5.0,  'read' => 0.10, 'write' => 1.25],
    ];
    foreach ($table as $prefix => $p) {
        if (strpos($model, $prefix) === 0) {
            return $p;
        }
    }
    return $table['claude-opus-5-5']; // modelo desconocido: se estima con el precio más alto
}

/** @param array{input:int,output:int,read:int,write:int} $u */
function cpnnet_asistente_estimate_cost(string $model, array $u): float
{
    $p = cpnnet_asistente_prices($model);
    return ($u['input'] * $p['in'] + $u['output'] * $p['out'] + $u['read'] * $p['read'] + $u['write'] * $p['write']) / 1_000_000;
}

/** Suma el uso de una respuesta de la API (campos de Anthropic\Messages\Usage) a un acumulador. */
function cpnnet_asistente_usage_add(array $acc, $usage): array
{
    $acc['input']  += (int) ($usage->inputTokens ?? 0);
    $acc['output'] += (int) ($usage->outputTokens ?? 0);
    $acc['read']   += (int) ($usage->cacheReadInputTokens ?? 0);
    $acc['write']  += (int) ($usage->cacheCreationInputTokens ?? 0);
    return $acc;
}

function cpnnet_asistente_usage_zero(): array
{
    return ['input' => 0, 'output' => 0, 'read' => 0, 'write' => 0];
}

function cpnnet_asistente_usage_log(string $model, array $u, string $conv_id = ''): void
{
    global $wpdb;
    $wpdb->insert(cpnnet_asistente_usage_table(), [
        'created_at'         => current_time('mysql', true),
        'model'              => substr($model, 0, 60),
        'conv_id'            => substr($conv_id, 0, 40),
        'input_tokens'       => $u['input'],
        'output_tokens'      => $u['output'],
        'cache_read_tokens'  => $u['read'],
        'cache_write_tokens' => $u['write'],
        'cost_usd'           => round(cpnnet_asistente_estimate_cost($model, $u), 6),
    ]);
}

function cpnnet_asistente_month_spend(): float
{
    global $wpdb;
    $table = cpnnet_asistente_usage_table();
    $from  = gmdate('Y-m-01 00:00:00');
    return (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(cost_usd),0) FROM {$table} WHERE created_at >= %s", $from));
}

/** @return array{messages:int,input:int,output:int,read:int,write:int,cost:float} */
function cpnnet_asistente_usage_summary(string $since): array
{
    global $wpdb;
    $table = cpnnet_asistente_usage_table();
    $row   = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) m, COALESCE(SUM(input_tokens),0) i, COALESCE(SUM(output_tokens),0) o,
                COALESCE(SUM(cache_read_tokens),0) r, COALESCE(SUM(cache_write_tokens),0) w, COALESCE(SUM(cost_usd),0) c
         FROM {$table} WHERE created_at >= %s",
        $since
    ), ARRAY_A) ?: [];
    return [
        'messages' => (int) ($row['m'] ?? 0), 'input' => (int) ($row['i'] ?? 0), 'output' => (int) ($row['o'] ?? 0),
        'read' => (int) ($row['r'] ?? 0), 'write' => (int) ($row['w'] ?? 0), 'cost' => (float) ($row['c'] ?? 0),
    ];
}

/** @return array<int,array{day:string,messages:int,cost:float}> */
function cpnnet_asistente_usage_by_day(int $days = 30): array
{
    global $wpdb;
    $table = cpnnet_asistente_usage_table();
    $since = gmdate('Y-m-d 00:00:00', time() - $days * DAY_IN_SECONDS);
    return $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(created_at) day, COUNT(*) messages, SUM(cost_usd) cost FROM {$table}
         WHERE created_at >= %s GROUP BY DATE(created_at) ORDER BY day DESC",
        $since
    ), ARRAY_A) ?: [];
}
