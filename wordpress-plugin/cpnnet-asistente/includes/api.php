<?php
if (!defined('ABSPATH')) {
    exit;
}

use Anthropic\Client;
use Anthropic\Messages\ToolUseBlock;

const CPNNET_ASISTENTE_MAX_HISTORY = 12; // menos historial = menos tokens de entrada por mensaje
const CPNNET_ASISTENTE_MAX_CHARS   = 1500;

add_action('rest_api_init', function () {
    register_rest_route('cpnnet-asistente/v1', '/chat', [
        'methods'             => 'POST',
        'callback'            => 'cpnnet_asistente_handle_chat',
        'permission_callback' => '__return_true', // visitantes anónimos; se protege con límites y verificación de origen
    ]);
});

/** ¿Puede esta persona ver y usar el chat? En modo de prueba, solo los administradores. */
function cpnnet_asistente_can_see_chat(): bool
{
    return cpnnet_asistente_get('visibility') === 'public' || current_user_can('manage_options');
}

function cpnnet_asistente_error(string $msg, int $status): WP_REST_Response
{
    return new WP_REST_Response(['error' => $msg], $status);
}

/** Solo acepta llamadas que vengan de este mismo sitio (Origin/Referer). */
function cpnnet_asistente_same_origin(): bool
{
    $site   = wp_parse_url(home_url(), PHP_URL_HOST);
    $source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    if ($source === '') {
        return false;
    }
    return wp_parse_url($source, PHP_URL_HOST) === $site;
}

/** @return string|null mensaje de error si se excedió algún límite */
function cpnnet_asistente_rate_limit(): ?string
{
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'x';
    $hkey = 'cpnnet_rl_h_' . md5($ip);
    $dkey = 'cpnnet_rl_d_' . gmdate('Ymd');

    $h = (int) get_transient($hkey);
    $d = (int) get_transient($dkey);
    if ($h >= (int) cpnnet_asistente_get('hourly_limit')) {
        return 'Has enviado muchos mensajes seguidos. Intenta de nuevo en un rato o escríbenos por los canales de contacto del sitio.';
    }
    if ($d >= (int) cpnnet_asistente_get('daily_limit')) {
        return 'El asistente no está disponible por hoy. Por favor contáctanos por los canales del sitio.';
    }
    set_transient($hkey, $h + 1, HOUR_IN_SECONDS);
    set_transient($dkey, $d + 1, DAY_IN_SECONDS);
    return null;
}

/** Normaliza el historial que envía el navegador: solo texto, roles válidos, longitud acotada. */
function cpnnet_asistente_clean_messages($raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $clean = [];
    foreach (array_slice($raw, -CPNNET_ASISTENTE_MAX_HISTORY) as $m) {
        if (!is_array($m) || !in_array($m['role'] ?? '', ['user', 'assistant'], true) || !is_string($m['content'] ?? null)) {
            continue;
        }
        $text = trim(mb_substr(wp_strip_all_tags($m['content']), 0, CPNNET_ASISTENTE_MAX_CHARS));
        if ($text === '') {
            continue;
        }
        if ($clean && $clean[count($clean) - 1]['role'] === $m['role']) {
            $clean[count($clean) - 1]['content'] .= "\n" . $text; // la API exige alternar roles
            continue;
        }
        $clean[] = ['role' => $m['role'], 'content' => $text];
    }
    while ($clean && $clean[0]['role'] !== 'user') {
        array_shift($clean);
    }
    return $clean;
}

function cpnnet_asistente_lead_summary(array $in): string
{
    $line = static fn(string $label, $v): string => $label . ': ' . (is_array($v) ? implode(', ', $v) : (string) $v);
    return implode("\n", [
        'Nuevo lead desde www.cpnnetsecurity.com',
        $line('Perfil', $in['perfil'] ?? ''),
        $line('Nombre', $in['nombre'] ?? ''),
        $line('Empresa', $in['empresa'] ?? ''),
        $line('País', $in['pais'] ?? ''),
        $line('Contacto', $in['contacto'] ?? ''),
        $line('Necesidad', $in['necesidad'] ?? ''),
        $line('Marcas de interés', $in['marcas_interes'] ?? []),
        $line('Dimensionamiento', $in['dimensionamiento'] ?? ''),
        $line('Siguiente paso sugerido', $in['siguiente_paso'] ?? ''),
    ]);
}

/**
 * Ejecuta la herramienta derivar_a_ejecutivo.
 * @return array{0:string,1:?string,2:bool} [texto para el modelo, URL de WhatsApp, es_error]
 */
function cpnnet_asistente_run_tool(array $input, array $ctx = []): array
{
    if (empty($input['consentimiento']) || $input['consentimiento'] !== true) {
        return ['Falta el consentimiento explícito del visitante. Pídelo antes de derivar.', null, true];
    }
    $summary = cpnnet_asistente_lead_summary($input);
    // El lead siempre queda en el repositorio del área comercial (panel de WordPress), haya o no WhatsApp.
    $saved = cpnnet_asistente_leads_insert($input, $summary, (string) ($ctx['conv_id'] ?? ''), (string) ($ctx['pagina'] ?? ''), (array) ($ctx['transcript'] ?? []));
    cpnnet_asistente_webhook_send_lead($saved['uuid']); // aviso al CRM, si hay webhook configurado

    $number = (string) cpnnet_asistente_get('whatsapp');
    if ($number === '') {
        return ['Lead registrado. Dile al visitante que un ejecutivo lo contactará pronto por los datos que dejó. No prometas plazos.', null, false];
    }
    $url = 'https://wa.me/' . $number . '?text=' . rawurlencode($summary);
    return ['Lead registrado. Se mostrará al visitante un botón para continuar la conversación por WhatsApp con un ejecutivo. No prometas plazos de respuesta.', $url, false];
}

function cpnnet_asistente_handle_chat(WP_REST_Request $req)
{
    if (!cpnnet_asistente_get('enabled')) {
        return cpnnet_asistente_error('El asistente no está activo.', 503);
    }
    if (!cpnnet_asistente_same_origin()) {
        return cpnnet_asistente_error('Solicitud no permitida.', 403);
    }
    if (!cpnnet_asistente_can_see_chat()) {
        return cpnnet_asistente_error('El asistente está en modo de prueba y solo lo ven los administradores.', 403);
    }
    $key = cpnnet_asistente_api_key();
    if ($key === '') {
        return cpnnet_asistente_error('El asistente no está configurado.', 503);
    }
    $params = $req->get_json_params();
    if (!empty($params['website'])) { // honeypot: los humanos no completan este campo
        return cpnnet_asistente_error('Solicitud no permitida.', 400);
    }
    $messages = cpnnet_asistente_clean_messages($params['messages'] ?? null);
    if (!$messages || end($messages)['role'] !== 'user') {
        return cpnnet_asistente_error('Mensaje vacío.', 400);
    }
    $budget = (float) cpnnet_asistente_get('monthly_budget');
    if ($budget > 0 && cpnnet_asistente_month_spend() >= $budget) {
        return cpnnet_asistente_error('El asistente no está disponible por ahora. Por favor contáctanos por los canales del sitio.', 503);
    }
    if ($limit = cpnnet_asistente_rate_limit()) {
        return cpnnet_asistente_error($limit, 429);
    }

    $conv_id = isset($params['conversation_id']) && preg_match('/^[A-Za-z0-9-]{8,40}$/', (string) $params['conversation_id']) ? (string) $params['conversation_id'] : '';
    $page    = substr((string) (wp_parse_url((string) ($params['page'] ?? ''), PHP_URL_PATH) ?: ''), 0, 255);

    @set_time_limit(90);
    $model = (string) cpnnet_asistente_get('model');

    try {
        $client = new Client(apiKey: $key, requestOptions: ['maxRetries' => 1]);

        $args = [
            'model'     => $model,
            'maxTokens' => 800,
            // Bloque de sistema estable + cache_control: la base de conocimiento (~14k tokens) se cobra
            // como lectura de caché en las conversaciones siguientes.
            'system'    => [[
                'type'         => 'text',
                'text'         => cpnnet_asistente_system_prompt(),
                'cacheControl' => ['type' => 'ephemeral'],
            ]],
            'tools'     => cpnnet_asistente_tools(),
        ];
        if (stripos($model, 'haiku') === false) {
            $args['outputConfig'] = ['effort' => (string) cpnnet_asistente_get('effort')];
        }

        $transcript = $messages; // historial en texto plano, para guardarlo junto al lead
        $ctx = ['conv_id' => $conv_id, 'pagina' => $page, 'transcript' => $transcript];
        $whatsapp_url = null;
        $usage = cpnnet_asistente_usage_zero();
        for ($i = 0; $i < 3; $i++) {
            $args['messages'] = $messages;
            $response = $client->messages->create(...$args);
            $usage = cpnnet_asistente_usage_add($usage, $response->usage);

            if ($response->stopReason === 'refusal') {
                cpnnet_asistente_usage_log($model, $usage, $conv_id);
                return new WP_REST_Response(['reply' => 'No puedo ayudarte con esa consulta. ¿Quieres que te derive con un ejecutivo de CPNnet?', 'whatsapp_url' => null]);
            }
            if ($response->stopReason !== 'tool_use') {
                break;
            }
            $results = [];
            foreach ($response->content as $block) {
                if ($block instanceof ToolUseBlock) {
                    $input = is_array($block->input) ? $block->input : [];
                    [$text, $url, $is_error] = $block->name === 'derivar_a_ejecutivo'
                        ? cpnnet_asistente_run_tool($input, $ctx)
                        : ['Herramienta desconocida.', null, true];
                    $whatsapp_url = $url ?? $whatsapp_url;
                    $results[] = ['type' => 'tool_result', 'toolUseID' => $block->id, 'content' => $text, 'isError' => $is_error];
                }
            }
            $messages[] = ['role' => 'assistant', 'content' => $response->content];
            $messages[] = ['role' => 'user', 'content' => $results];
        }

        $reply = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $reply .= $block->text;
            }
        }
        cpnnet_asistente_usage_log($model, $usage, $conv_id);
        if ($response->stopReason === 'max_tokens') {
            $reply = trim($reply) . '…';
        }
        return new WP_REST_Response(['reply' => trim($reply), 'whatsapp_url' => $whatsapp_url]);
    } catch (\Throwable $e) {
        error_log('[cpnnet-asistente] ' . get_class($e) . ': ' . $e->getMessage());
        return cpnnet_asistente_error('El asistente tuvo un problema. Intenta nuevamente en unos minutos.', 502);
    }
}
