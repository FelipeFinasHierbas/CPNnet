<?php
if (!defined('ABSPATH')) {
    exit;
}

function cpnnet_asistente_models(): array
{
    return [
        'claude-opus-5-5'   => 'Claude Opus 5.5 · el más capaz (mayor costo)',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5 · rápido, costo intermedio',
        'claude-haiku-4-5'  => 'Claude Haiku 4.5 · el más económico',
    ];
}

function cpnnet_asistente_defaults(): array
{
    return [
        'enabled'        => 0,
        'visibility'     => 'admins', // 'admins' = modo de prueba (solo administradores) | 'public' = todos los visitantes
        'api_key'        => '',
        'model'          => 'claude-opus-5-5',
        'effort'         => 'low',
        'whatsapp'       => '',
        'hourly_limit'   => 30,
        'daily_limit'    => 1500,
        'monthly_budget' => 25,
        'save_transcript' => 1,
        'welcome'        => (string) cpnnet_asistente_brand_get('welcome'),
    ];
}

function cpnnet_asistente_get(string $key)
{
    $opts = wp_parse_args(get_option(CPNNET_ASISTENTE_OPTION, []), cpnnet_asistente_defaults());
    return $opts[$key] ?? null;
}

/** La clave se prefiere en wp-config.php: define('CPNNET_ASISTENTE_API_KEY', 'sk-ant-...'); */
function cpnnet_asistente_api_key(): string
{
    if (defined('CPNNET_ASISTENTE_API_KEY') && CPNNET_ASISTENTE_API_KEY) {
        return (string) CPNNET_ASISTENTE_API_KEY;
    }
    return (string) cpnnet_asistente_get('api_key');
}

add_action('admin_init', function () {
    register_setting('cpnnet_asistente_group', CPNNET_ASISTENTE_OPTION, [
        'sanitize_callback' => 'cpnnet_asistente_sanitize',
    ]);
});

function cpnnet_asistente_sanitize($in): array
{
    $old = wp_parse_args(get_option(CPNNET_ASISTENTE_OPTION, []), cpnnet_asistente_defaults());
    $in  = is_array($in) ? $in : [];

    $api_key = trim((string) ($in['api_key'] ?? ''));
    if ($api_key === '') {
        $api_key = (string) $old['api_key']; // campo vacío = conservar la clave guardada
    }
    $effort = in_array($in['effort'] ?? '', ['low', 'medium', 'high'], true) ? $in['effort'] : 'low';
    $model  = array_key_exists($in['model'] ?? '', cpnnet_asistente_models()) ? $in['model'] : 'claude-opus-5-5';

    return [
        'enabled'        => empty($in['enabled']) ? 0 : 1,
        'visibility'     => (($in['visibility'] ?? '') === 'public') ? 'public' : 'admins',
        'api_key'        => $api_key,
        'model'          => $model,
        'effort'         => $effort,
        'whatsapp'       => preg_replace('/\D+/', '', (string) ($in['whatsapp'] ?? '')),
        'hourly_limit'   => max(1, min(500, (int) ($in['hourly_limit'] ?? 30))),
        'daily_limit'    => max(10, min(50000, (int) ($in['daily_limit'] ?? 1500))),
        'monthly_budget' => max(0, min(100000, (float) ($in['monthly_budget'] ?? 25))),
        'save_transcript' => empty($in['save_transcript']) ? 0 : 1,
        'welcome'        => sanitize_textarea_field((string) ($in['welcome'] ?? '')),
    ];
}
