<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Integración con el CRM del cliente (que está en el mismo hosting). Tres vías, de menor a mayor automatización:
 *  1. CSV: botón «Exportar» en el panel de leads.
 *  2. API REST de solo lectura con token: el CRM (o un cron) consulta los leads nuevos.
 *  3. Webhook: el plugin avisa al CRM en el momento en que entra un lead (firmado con HMAC).
 * Este plugin nunca escribe en la base de datos del CRM.
 */
const CPNNET_ASISTENTE_OPT_INTEGRATION = 'cpnnet_asistente_integration';

function cpnnet_asistente_integration(): array
{
    return wp_parse_args(get_option(CPNNET_ASISTENTE_OPT_INTEGRATION, []), [
        'token_hash' => '', 'token_last4' => '', 'token_created' => '', 'webhook_url' => '', 'webhook_secret' => '',
    ]);
}

function cpnnet_asistente_integration_update(array $changes): void
{
    update_option(CPNNET_ASISTENTE_OPT_INTEGRATION, array_merge(cpnnet_asistente_integration(), $changes), false);
}

/* ---------- Token de la API ---------- */

/** Genera un token nuevo; solo se guarda su hash (el valor se muestra una única vez). */
function cpnnet_asistente_token_generate(): string
{
    $token = 'cpn_' . bin2hex(random_bytes(20));
    cpnnet_asistente_integration_update([
        'token_hash'    => hash('sha256', $token),
        'token_last4'   => substr($token, -4),
        'token_created' => current_time('mysql', true),
    ]);
    return $token;
}

function cpnnet_asistente_token_revoke(): void
{
    cpnnet_asistente_integration_update(['token_hash' => '', 'token_last4' => '', 'token_created' => '']);
}

/** @return true|WP_Error */
function cpnnet_asistente_api_auth(WP_REST_Request $req)
{
    $hash = cpnnet_asistente_integration()['token_hash'];
    if ($hash === '') {
        return new WP_Error('cpnnet_no_token', 'La API no está habilitada: genera un token en el panel.', ['status' => 403]);
    }
    // Freno a la fuerza bruta: 20 intentos fallidos por IP cada 10 minutos.
    $key   = 'cpnnet_api_fail_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x');
    $fails = (int) get_transient($key);
    if ($fails >= 20) {
        return new WP_Error('cpnnet_throttled', 'Demasiados intentos. Espera unos minutos.', ['status' => 429]);
    }
    $given = '';
    $auth  = (string) $req->get_header('authorization');
    if (stripos($auth, 'bearer ') === 0) {
        $given = trim(substr($auth, 7));
    } elseif ($req->get_header('x-api-key')) {
        $given = trim((string) $req->get_header('x-api-key'));
    }
    if ($given === '' || !hash_equals($hash, hash('sha256', $given))) {
        set_transient($key, $fails + 1, 10 * MINUTE_IN_SECONDS);
        return new WP_Error('cpnnet_unauthorized', 'Token inválido.', ['status' => 401]);
    }
    return true;
}

/* ---------- API REST (solo lectura + marcar exportado) ---------- */

add_action('rest_api_init', function () {
    $ns = 'cpnnet-asistente/v1';
    register_rest_route($ns, '/leads', [
        'methods' => 'GET', 'callback' => 'cpnnet_asistente_api_leads', 'permission_callback' => 'cpnnet_asistente_api_auth',
    ]);
    register_rest_route($ns, '/leads/mark-exported', [
        'methods' => 'POST', 'callback' => 'cpnnet_asistente_api_mark_exported', 'permission_callback' => 'cpnnet_asistente_api_auth',
    ]);
    register_rest_route($ns, '/leads/(?P<id>[0-9a-fA-F-]{36})', [
        'methods' => 'GET', 'callback' => 'cpnnet_asistente_api_lead', 'permission_callback' => 'cpnnet_asistente_api_auth',
    ]);
});

function cpnnet_asistente_api_leads(WP_REST_Request $req)
{
    $limit  = max(1, min(200, (int) ($req->get_param('limit') ?: 50)));
    $page   = max(1, (int) ($req->get_param('page') ?: 1));
    $filter = [
        'status'        => (string) $req->get_param('status'),
        'perfil'        => (string) $req->get_param('perfil'),
        'updated_since' => (string) $req->get_param('updated_since'),
        'exported'      => $req->get_param('exported') === null ? '' : (string) $req->get_param('exported'),
        'from'          => (string) $req->get_param('from'),
        'to'            => (string) $req->get_param('to'),
    ];
    $with = (string) $req->get_param('include') === 'conversation';
    $res  = cpnnet_asistente_leads_query($filter, $limit, ($page - 1) * $limit);
    return new WP_REST_Response([
        'data'     => array_map(static fn($r) => cpnnet_asistente_lead_payload($r, $with), $res['rows']),
        'total'    => $res['total'],
        'page'     => $page,
        'per_page' => $limit,
    ]);
}

function cpnnet_asistente_api_lead(WP_REST_Request $req)
{
    $r = cpnnet_asistente_lead_by('uuid', strtolower((string) $req['id']));
    if (!$r) {
        return new WP_Error('cpnnet_not_found', 'Lead no encontrado.', ['status' => 404]);
    }
    return new WP_REST_Response(['data' => cpnnet_asistente_lead_payload($r, true)]);
}

function cpnnet_asistente_api_mark_exported(WP_REST_Request $req)
{
    $ids = $req->get_json_params()['ids'] ?? [];
    if (!is_array($ids) || !$ids || count($ids) > 500) {
        return new WP_Error('cpnnet_bad_request', 'Envía {"ids": [...]} con hasta 500 ids.', ['status' => 400]);
    }
    return new WP_REST_Response(['marked' => cpnnet_asistente_leads_mark_exported(array_map('strtolower', array_map('strval', $ids)))]);
}

/* ---------- Webhook ---------- */

function cpnnet_asistente_webhook_valid_url(string $url): bool
{
    $p = wp_parse_url($url);
    return $url !== '' && !empty($p['host']) && in_array($p['scheme'] ?? '', ['http', 'https'], true);
}

/**
 * Envía un evento firmado al CRM: cabecera X-CPNnet-Signature = sha256=HMAC(cuerpo, secreto).
 * @return array{0:bool,1:string} [ok, estado legible]
 */
function cpnnet_asistente_webhook_post(string $event, array $data): array
{
    $cfg = cpnnet_asistente_integration();
    if (!cpnnet_asistente_webhook_valid_url($cfg['webhook_url'])) {
        return [false, 'sin webhook'];
    }
    $body = wp_json_encode(['event' => $event, 'sent_at' => gmdate('c'), 'data' => $data], JSON_UNESCAPED_UNICODE);
    $res  = wp_remote_post($cfg['webhook_url'], [
        'timeout' => 5,
        'headers' => [
            'Content-Type'          => 'application/json',
            'X-CPNnet-Event'        => $event,
            'X-CPNnet-Signature'    => 'sha256=' . hash_hmac('sha256', $body, $cfg['webhook_secret']),
            'User-Agent'            => 'CPNnet-Asistente/' . CPNNET_ASISTENTE_VERSION,
        ],
        'body'    => $body,
    ]);
    if (is_wp_error($res)) {
        return [false, 'error: ' . substr($res->get_error_message(), 0, 60)];
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    return [$code >= 200 && $code < 300, 'HTTP ' . $code];
}

/** Avisa al CRM de un lead nuevo y registra el resultado en el lead. No interrumpe el chat si falla. */
function cpnnet_asistente_webhook_send_lead(string $uuid): void
{
    $cfg = cpnnet_asistente_integration();
    if (!cpnnet_asistente_webhook_valid_url($cfg['webhook_url'])) {
        return;
    }
    $row = cpnnet_asistente_lead_by('uuid', $uuid);
    if (!$row) {
        return;
    }
    try {
        [$ok, $status] = cpnnet_asistente_webhook_post('lead.created', cpnnet_asistente_lead_payload($row, true));
    } catch (\Throwable $e) {
        [$ok, $status] = [false, 'error'];
    }
    $fields = ['webhook_status' => $status];
    if ($ok) {
        $fields['exported_at'] = current_time('mysql', true);
    }
    cpnnet_asistente_lead_update((int) $row['id'], $fields);
}

/* ---------- Pantalla «Integración» ---------- */

add_action('admin_post_cpnnet_asistente_token_new', function () {
    cpnnet_asistente_admin_guard();
    $token = cpnnet_asistente_token_generate();
    set_transient('cpnnet_new_token_' . get_current_user_id(), $token, 120); // se muestra una sola vez
    cpnnet_asistente_admin_redirect('cpnnet-asistente-integracion', 'token');
});

add_action('admin_post_cpnnet_asistente_token_revoke', function () {
    cpnnet_asistente_admin_guard();
    cpnnet_asistente_token_revoke();
    cpnnet_asistente_admin_redirect('cpnnet-asistente-integracion', 'revoked');
});

add_action('admin_post_cpnnet_asistente_webhook_save', function () {
    cpnnet_asistente_admin_guard();
    $url = esc_url_raw(trim((string) wp_unslash($_POST['webhook_url'] ?? '')));
    $cfg = cpnnet_asistente_integration();
    $secret = $cfg['webhook_secret'] !== '' ? $cfg['webhook_secret'] : bin2hex(random_bytes(16));
    if (!empty($_POST['regen_secret'])) {
        $secret = bin2hex(random_bytes(16));
    }
    if ($url !== '' && !cpnnet_asistente_webhook_valid_url($url)) {
        cpnnet_asistente_admin_redirect('cpnnet-asistente-integracion', 'badurl');
    }
    cpnnet_asistente_integration_update(['webhook_url' => $url, 'webhook_secret' => $secret]);
    cpnnet_asistente_admin_redirect('cpnnet-asistente-integracion', 'saved');
});

add_action('admin_post_cpnnet_asistente_webhook_test', function () {
    cpnnet_asistente_admin_guard();
    [$ok] = cpnnet_asistente_webhook_post('ping', ['mensaje' => 'Prueba de conexión desde el Asistente CPNnet']);
    cpnnet_asistente_admin_redirect('cpnnet-asistente-integracion', $ok ? 'pingok' : 'pingfail');
});

function cpnnet_asistente_page_integracion(): void
{
    $cfg  = cpnnet_asistente_integration();
    $new  = get_transient('cpnnet_new_token_' . get_current_user_id());
    if ($new) {
        delete_transient('cpnnet_new_token_' . get_current_user_id());
    }
    $base = esc_url_raw(rest_url('cpnnet-asistente/v1'));
    cpnnet_asistente_wrap_start('Integración con el CRM', 'Tres formas de llevar los leads al CRM de CPNnet. Este plugin nunca escribe en la base de datos del CRM.');
    ?>
    <div class="cpn-grid cpn-grid-2">
        <section class="cpn-card">
            <h3>1 · Exportar a CSV</h3>
            <p>Desde <a href="<?php echo esc_url(cpnnet_asistente_admin_url(['page' => 'cpnnet-asistente-leads'])); ?>">Leads</a>, el botón «Exportar CSV» descarga los leads con los filtros aplicados. Se abre directo en Excel y la mayoría de los CRM lo importan.</p>
        </section>
        <section class="cpn-card">
            <h3>2 · API de consulta (token)</h3>
            <?php if ($new) : ?>
                <div class="cpn-alert">Copia este token ahora; por seguridad no se vuelve a mostrar:<br><code class="cpn-token"><?php echo esc_html($new); ?></code></div>
            <?php endif; ?>
            <p>Estado: <?php echo $cfg['token_hash'] !== '' ? '<span class="cpn-pill cpn-pill-ok">Activa</span> token terminado en <code>…' . esc_html($cfg['token_last4']) . '</code>' : '<span class="cpn-pill">Sin token</span>'; ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cpn-inline">
                <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
                <input type="hidden" name="action" value="cpnnet_asistente_token_new">
                <button class="button button-primary"><?php echo $cfg['token_hash'] !== '' ? 'Generar token nuevo (invalida el anterior)' : 'Generar token'; ?></button>
            </form>
            <?php if ($cfg['token_hash'] !== '') : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cpn-inline" onsubmit="return confirm('¿Revocar el token? El CRM dejará de poder consultar.');">
                    <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
                    <input type="hidden" name="action" value="cpnnet_asistente_token_revoke">
                    <button class="button">Revocar</button>
                </form>
            <?php endif; ?>
            <h4>Ejemplos</h4>
<pre class="cpn-code">curl -H "Authorization: Bearer TU_TOKEN" \
  "<?php echo esc_html($base); ?>/leads?exported=0&amp;limit=50"

# Marcar como ya importados en el CRM
curl -X POST -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"ids":["UUID1","UUID2"]}' \
  "<?php echo esc_html($base); ?>/leads/mark-exported"</pre>
            <p class="description">Filtros de <code>/leads</code>: <code>status</code>, <code>perfil</code>, <code>exported</code> (0/1), <code>updated_since</code> (ISO 8601), <code>from</code>/<code>to</code> (AAAA-MM-DD), <code>limit</code> (máx. 200), <code>page</code>, <code>include=conversation</code>. Detalle de un lead: <code>/leads/{id}</code>.</p>
        </section>
    </div>
    <section class="cpn-card">
        <h3>3 · Webhook (aviso inmediato al CRM)</h3>
        <p>Cuando entra un lead, el plugin envía un <code>POST</code> JSON a la URL que indiques (por ejemplo, un script del CRM en el mismo hosting). Si responde con éxito (2xx), el lead se marca como exportado.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
            <input type="hidden" name="action" value="cpnnet_asistente_webhook_save">
            <table class="form-table" role="presentation">
                <tr><th scope="row">URL del webhook</th><td><input type="url" class="regular-text" name="webhook_url" placeholder="https://crm.ejemplo.cl/api/leads" value="<?php echo esc_attr($cfg['webhook_url']); ?>"></td></tr>
                <tr><th scope="row">Secreto de firma</th><td>
                    <?php if ($cfg['webhook_secret'] !== '') : ?><input type="text" readonly class="regular-text code" value="<?php echo esc_attr($cfg['webhook_secret']); ?>"><br><?php endif; ?>
                    <label><input type="checkbox" name="regen_secret" value="1"> Generar un secreto nuevo</label>
                    <p class="description">El CRM valida que el aviso viene de aquí: cabecera <code>X-CPNnet-Signature: sha256=…</code> = HMAC-SHA256 del cuerpo con este secreto.</p>
                </td></tr>
            </table>
            <?php submit_button('Guardar webhook', 'primary', 'submit', false); ?>
        </form>
        <?php if (cpnnet_asistente_webhook_valid_url($cfg['webhook_url'])) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cpn-inline" style="margin-top:10px">
                <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
                <input type="hidden" name="action" value="cpnnet_asistente_webhook_test">
                <button class="button">Enviar prueba</button>
            </form>
        <?php endif; ?>
        <h4>Ejemplo del aviso</h4>
<pre class="cpn-code">{
  "event": "lead.created",
  "sent_at": "2026-10-06T14:05:00+00:00",
  "data": {
    "id": "8f6c…", "created_at": "2026-10-06T14:04:51Z", "status": "nuevo",
    "perfil": "empresa", "nombre": "Ana Pérez", "empresa": "ACME", "pais": "Chile",
    "contacto": "ana@acme.cl", "necesidad": "…", "marcas_interes": ["Sophos XDR/MDR"],
    "dimensionamiento": "~120 usuarios, 15 servidores", "siguiente_paso": "…",
    "pagina_origen": "/", "conversacion": [ {"role": "user", "content": "…"} ]
  }
}</pre>
    </section>
    <?php
    cpnnet_asistente_wrap_end();
}
