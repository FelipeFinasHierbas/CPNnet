<?php
if (!defined('ABSPATH')) {
    exit;
}

/* ===== Panel del área comercial: indicadores, leads, ficha de lead ===== */

/** Fecha UTC de la base de datos -> hora local del sitio. */
function cpnnet_asistente_local(?string $utc, string $fmt = 'd-m-Y H:i'): string
{
    return $utc ? wp_date($fmt, strtotime($utc . ' UTC')) : '—';
}

function cpnnet_asistente_wrap_start(string $title, string $subtitle = ''): void
{
    $notices = [
        'saved' => 'Cambios guardados.', 'deleted' => 'Marca eliminada.', 'reset' => 'Se restauró el contenido original.',
        'invalid' => 'Faltan datos obligatorios.', 'token' => 'Token generado.', 'revoked' => 'Token revocado.',
        'badurl' => 'La URL del webhook no es válida (debe empezar con http:// o https://).',
        'pingok' => 'La prueba llegó al CRM correctamente.', 'pingfail' => 'La prueba no llegó al CRM. Revisa la URL y que el CRM responda con código 2xx.',
        'lead_saved' => 'Lead actualizado.', 'resent' => 'Se reenvió el lead al CRM.', 'resentfail' => 'No se pudo reenviar al CRM; revisa la configuración del webhook.',
        'marked' => 'Lead marcado como exportado.',
    ];
    $logo = esc_url(CPNNET_ASISTENTE_URL . 'assets/img/cpnnet-logo-white.png');
    echo '<div class="wrap cpn-wrap"><div class="cpn-head"><div class="cpn-head-main"><img class="cpn-logo" src="' . $logo . '" alt="CPNnet Security"><span class="cpn-head-sep"></span><div><h1>' . esc_html($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p>' . esc_html($subtitle) . '</p>';
    }
    echo '</div></div></div>';
    if (cpnnet_asistente_get('enabled') && cpnnet_asistente_get('visibility') !== 'public') {
        echo '<div class="cpn-alert">Modo de prueba: el chat está activo pero lo ven solo los administradores. Para abrirlo al público, cámbialo en Configuración > General > «Quién ve el chat».</div>';
    }
    $msg = isset($_GET['msg']) ? (string) $_GET['msg'] : '';
    if ($msg !== '' && isset($notices[$msg])) {
        $bad = in_array($msg, ['invalid', 'badurl', 'pingfail', 'resentfail'], true);
        echo '<div class="notice notice-' . ($bad ? 'error' : 'success') . ' is-dismissible"><p>' . esc_html($notices[$msg]) . '</p></div>';
    }
}

function cpnnet_asistente_wrap_end(): void
{
    echo '</div>';
}

add_action('admin_enqueue_scripts', function () {
    if (isset($_GET['page']) && strpos((string) $_GET['page'], 'cpnnet-asistente') === 0) {
        wp_enqueue_style('cpnnet-asistente-admin', CPNNET_ASISTENTE_URL . 'assets/admin.css', [], CPNNET_ASISTENTE_VERSION);
    }
});

/* ---------- Estadísticas ---------- */

/** @return array<string,mixed> */
function cpnnet_asistente_stats(int $days): array
{
    global $wpdb;
    $days = in_array($days, [7, 30, 90], true) ? $days : 30;
    $labels = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $labels[] = wp_date('Y-m-d', time() - $i * DAY_IN_SECONDS);
    }
    $index = array_flip($labels);
    $since = gmdate('Y-m-d H:i:s', time() - ($days + 1) * DAY_IN_SECONDS);

    $ut = cpnnet_asistente_usage_table();
    $usage = $wpdb->get_results($wpdb->prepare("SELECT created_at, conv_id, cost_usd FROM {$ut} WHERE created_at >= %s ORDER BY created_at ASC LIMIT 100000", $since), ARRAY_A) ?: [];
    $conv_day = array_fill_keys($labels, []);
    $all_conv = [];
    $messages = 0;
    $cost = 0.0;
    foreach ($usage as $n => $u) {
        $d = wp_date('Y-m-d', strtotime($u['created_at'] . ' UTC'));
        if (!isset($index[$d])) {
            continue;
        }
        $messages++;
        $cost += (float) $u['cost_usd'];
        $key = $u['conv_id'] !== '' ? $u['conv_id'] : 'm' . $n; // sin id: cada mensaje cuenta como una conversación
        $conv_day[$d][$key] = true;
        $all_conv[$key] = true;
    }

    $lt = cpnnet_asistente_leads_table();
    $leads = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$lt} WHERE created_at >= %s ORDER BY created_at DESC LIMIT 5000", $since), ARRAY_A) ?: [];
    $lead_day = array_fill_keys($labels, 0);
    $perfil = $paises = $marcas = $estados = [];
    $in_period = [];
    foreach ($leads as $l) {
        $d = wp_date('Y-m-d', strtotime($l['created_at'] . ' UTC'));
        if (!isset($index[$d])) {
            continue;
        }
        $in_period[] = $l;
        $lead_day[$d]++;
        $perfil[$l['perfil'] ?: 'desconocido'] = ($perfil[$l['perfil'] ?: 'desconocido'] ?? 0) + 1;
        $estados[$l['status']] = ($estados[$l['status']] ?? 0) + 1;
        $p = trim((string) $l['pais']);
        if ($p !== '') {
            $k = mb_convert_case(mb_strtolower($p), MB_CASE_TITLE);
            $paises[$k] = ($paises[$k] ?? 0) + 1;
        }
        foreach (json_decode((string) $l['marcas'], true) ?: [] as $m) {
            $marcas[(string) $m] = ($marcas[(string) $m] ?? 0) + 1;
        }
    }
    arsort($paises);
    arsort($marcas);
    $convs = count($all_conv);
    return [
        'days'          => $days,
        'labels'        => $labels,
        'conv_series'   => array_map('count', array_values($conv_day)),
        'lead_series'   => array_values($lead_day),
        'conversations' => $convs,
        'messages'      => $messages,
        'leads'         => count($in_period),
        'conversion'    => $convs ? round(count($in_period) / $convs * 100, 1) : 0.0,
        'cost'          => $cost,
        'perfil'        => $perfil,
        'paises'        => array_slice($paises, 0, 6, true),
        'marcas'        => array_slice($marcas, 0, 6, true),
        'estados'       => $estados,
        'recent'        => array_slice($in_period, 0, 6),
    ];
}

/* ---------- Componentes de interfaz ---------- */

function cpnnet_asistente_svg_chart(array $labels, array $convs, array $leads): string
{
    $w = 760; $h = 230; $pl = 36; $pb = 26; $pt = 12; $pr = 8;
    $n = max(1, count($labels));
    $max = max(1, max($convs ?: [0]), max($leads ?: [0]));
    $step = max(1, (int) ceil($max / 4));
    $max = $step * 4;
    $iw = $w - $pl - $pr; $ih = $h - $pt - $pb;
    $slot = $iw / $n; $bw = max(2, min(26, $slot * 0.62));
    $svg = '<svg class="cpn-chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Conversaciones y leads por día" preserveAspectRatio="xMidYMid meet">';
    for ($g = 0; $g <= 4; $g++) {
        $y = $pt + $ih - ($ih * $g / 4);
        $svg .= '<line x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . round($y, 1) . '" y2="' . round($y, 1) . '" class="cpn-grid-line"/>';
        $svg .= '<text x="' . ($pl - 6) . '" y="' . round($y + 4, 1) . '" class="cpn-axis" text-anchor="end">' . ($step * $g) . '</text>';
    }
    $every = max(1, (int) ceil($n / 8));
    foreach ($labels as $i => $day) {
        $x = $pl + $slot * $i + ($slot - $bw) / 2;
        $c = (int) ($convs[$i] ?? 0); $l = (int) ($leads[$i] ?? 0);
        $ch = $ih * $c / $max; $lh = $ih * $l / $max;
        $tip = esc_attr($day . ': ' . $c . ' conversaciones, ' . $l . ' leads');
        $svg .= '<rect x="' . round($x, 1) . '" y="' . round($pt + $ih - $ch, 1) . '" width="' . round($bw, 1) . '" height="' . round($ch, 1) . '" rx="3" class="cpn-bar-conv"><title>' . $tip . '</title></rect>';
        if ($l > 0) {
            $svg .= '<rect x="' . round($x + $bw * 0.2, 1) . '" y="' . round($pt + $ih - $lh, 1) . '" width="' . round($bw * 0.6, 1) . '" height="' . round($lh, 1) . '" rx="3" class="cpn-bar-lead"><title>' . $tip . '</title></rect>';
        }
        if ($i % $every === 0) {
            $svg .= '<text x="' . round($x + $bw / 2, 1) . '" y="' . ($h - 8) . '" class="cpn-axis" text-anchor="middle">' . esc_html(substr($day, 8, 2) . '/' . substr($day, 5, 2)) . '</text>';
        }
    }
    return $svg . '</svg>';
}

function cpnnet_asistente_hbars(array $data, string $empty = 'Sin datos todavía'): string
{
    if (!$data) {
        return '<p class="cpn-empty">' . esc_html($empty) . '</p>';
    }
    $max = max($data);
    $out = '<ul class="cpn-hbars">';
    foreach ($data as $label => $n) {
        $out .= '<li><span class="cpn-hbar-label">' . esc_html((string) $label) . '</span><span class="cpn-hbar-track"><span class="cpn-hbar-fill" style="width:' . round($n / $max * 100) . '%"></span></span><b>' . (int) $n . '</b></li>';
    }
    return $out . '</ul>';
}

function cpnnet_asistente_status_pill(string $status): string
{
    $label = CPNNET_ASISTENTE_STATUSES[$status] ?? $status;
    return '<span class="cpn-pill cpn-st-' . esc_attr($status) . '">' . esc_html($label) . '</span>';
}

function cpnnet_asistente_leads_url(array $args = []): string
{
    return add_query_arg(array_merge(['page' => 'cpnnet-asistente-leads'], $args), admin_url('admin.php'));
}

function cpnnet_asistente_leads_table_html(array $rows): string
{
    if (!$rows) {
        return '<p class="cpn-empty">Aún no hay leads. Cuando un visitante acepte ser contactado, aparecerá aquí.</p>';
    }
    $h = '<table class="cpn-table"><thead><tr><th>Fecha</th><th>Contacto</th><th>Perfil</th><th>Marcas de interés</th><th>País</th><th>Estado</th><th>CRM</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $url = cpnnet_asistente_leads_url(['lead' => (int) $r['id']]);
        $marcas = implode(', ', json_decode((string) $r['marcas'], true) ?: []);
        $crm = $r['exported_at'] ? '<span class="cpn-pill cpn-pill-ok">Exportado</span>' : '<span class="cpn-pill">Pendiente</span>';
        $h .= '<tr><td>' . esc_html(cpnnet_asistente_local($r['created_at'])) . '</td>'
            . '<td><a href="' . esc_url($url) . '"><strong>' . esc_html($r['nombre'] ?: 'Sin nombre') . '</strong></a><br><span class="cpn-sub">' . esc_html($r['empresa']) . '</span></td>'
            . '<td>' . esc_html(ucfirst($r['perfil'])) . '</td><td>' . esc_html($marcas ?: '—') . '</td><td>' . esc_html($r['pais'] ?: '—') . '</td>'
            . '<td>' . cpnnet_asistente_status_pill($r['status']) . '</td><td>' . $crm . '</td></tr>';
    }
    return $h . '</tbody></table>';
}

/* ---------- Pantalla: Panel ---------- */

function cpnnet_asistente_page_panel(): void
{
    $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
    $s    = cpnnet_asistente_stats($days);
    $days = $s['days'];
    cpnnet_asistente_wrap_start('Panel comercial', 'Cómo está funcionando el asistente y qué leads está generando.');
    echo '<div class="cpn-toolbar"><span>Periodo:</span>';
    foreach ([7 => '7 días', 30 => '30 días', 90 => '90 días'] as $d => $l) {
        printf('<a class="cpn-chip %s" href="%s">%s</a>', $d === $days ? 'is-on' : '', esc_url(add_query_arg(['page' => 'cpnnet-asistente', 'days' => $d], admin_url('admin.php'))), esc_html($l));
    }
    echo '</div>';
    $usd = static fn(float $v): string => 'US$ ' . number_format($v, 2, ',', '.');
    $kpis = [
        ['Conversaciones', number_format_i18n($s['conversations']), 'Visitantes que escribieron'],
        ['Leads', number_format_i18n($s['leads']), 'Aceptaron ser contactados'],
        ['Conversión', number_format($s['conversion'], 1, ',', '.') . ' %', 'Leads sobre conversaciones'],
        ['Mensajes', number_format_i18n($s['messages']), 'Atendidos por el asistente'],
        ['Gasto estimado', $usd($s['cost']), 'Consumo de IA del periodo'],
    ];
    echo '<div class="cpn-kpis">';
    foreach ($kpis as [$label, $value, $hint]) {
        echo '<div class="cpn-kpi"><span class="cpn-kpi-label">' . esc_html($label) . '</span><span class="cpn-kpi-value">' . esc_html($value) . '</span><span class="cpn-kpi-hint">' . esc_html($hint) . '</span></div>';
    }
    echo '</div>';
    echo '<section class="cpn-card"><div class="cpn-card-head"><h3>Conversaciones y leads por día</h3><span class="cpn-legend"><i class="cpn-dot cpn-dot-conv"></i>Conversaciones <i class="cpn-dot cpn-dot-lead"></i>Leads</span></div>'
        . cpnnet_asistente_svg_chart($s['labels'], $s['conv_series'], $s['lead_series']) . '</section>';
    echo '<div class="cpn-grid cpn-grid-3">';
    $est = [];
    foreach (CPNNET_ASISTENTE_STATUSES as $k => $l) { $est[$l] = (int) ($s['estados'][$k] ?? 0); }
    echo '<section class="cpn-card"><h3>Estado de los leads</h3>' . cpnnet_asistente_hbars($est) . '</section>';
    echo '<section class="cpn-card"><h3>Marcas más consultadas</h3>' . cpnnet_asistente_hbars($s['marcas']) . '</section>';
    $perfil = [];
    foreach ($s['perfil'] as $k => $v) { $perfil[ucfirst($k)] = $v; }
    echo '<section class="cpn-card"><h3>Perfil y país</h3>' . cpnnet_asistente_hbars($perfil) . '<h4>Países</h4>' . cpnnet_asistente_hbars($s['paises']) . '</section>';
    echo '</div>';
    echo '<section class="cpn-card"><div class="cpn-card-head"><h3>Últimos leads</h3><a class="button" href="' . esc_url(cpnnet_asistente_leads_url()) . '">Ver todos</a></div>' . cpnnet_asistente_leads_table_html($s['recent']) . '</section>';
    echo '<p class="cpn-foot">Fechas en la hora del sitio. El gasto es una estimación con precios de lista; la factura real está en la consola de la IA.</p>';
    cpnnet_asistente_wrap_end();
}

/* ---------- Pantalla: Leads (lista y ficha) ---------- */

function cpnnet_asistente_lead_filters_from_request(): array
{
    $g = static fn(string $k): string => isset($_GET[$k]) ? sanitize_text_field(wp_unslash((string) $_GET[$k])) : '';
    return ['status' => $g('status'), 'perfil' => $g('perfil'), 'q' => $g('q'), 'from' => $g('from'), 'to' => $g('to')];
}

function cpnnet_asistente_page_leads(): void
{
    if (!empty($_GET['lead'])) {
        cpnnet_asistente_page_lead((int) $_GET['lead']);
        return;
    }
    $f    = cpnnet_asistente_lead_filters_from_request();
    $page = max(1, (int) ($_GET['paged'] ?? 1));
    $per  = 20;
    $res  = cpnnet_asistente_leads_query($f, $per, ($page - 1) * $per);
    $pages = max(1, (int) ceil($res['total'] / $per));
    $keep  = array_filter($f);

    cpnnet_asistente_wrap_start('Leads', 'Todos los contactos que dejó el asistente. Haz clic en uno para ver el detalle y la conversación.');
    echo '<form method="get" class="cpn-filters"><input type="hidden" name="page" value="cpnnet-asistente-leads">';
    echo '<input type="search" name="q" placeholder="Buscar nombre, empresa, país, marca…" value="' . esc_attr($f['q']) . '">';
    echo '<select name="status"><option value="">Todos los estados</option>';
    foreach (CPNNET_ASISTENTE_STATUSES as $k => $l) { echo '<option value="' . esc_attr($k) . '"' . selected($f['status'], $k, false) . '>' . esc_html($l) . '</option>'; }
    echo '</select><select name="perfil"><option value="">Todos los perfiles</option>';
    foreach (['partner' => 'Partner', 'empresa' => 'Empresa', 'desconocido' => 'Desconocido'] as $k => $l) { echo '<option value="' . esc_attr($k) . '"' . selected($f['perfil'], $k, false) . '>' . esc_html($l) . '</option>'; }
    echo '</select><label>Desde <input type="date" name="from" value="' . esc_attr($f['from']) . '"></label><label>Hasta <input type="date" name="to" value="' . esc_attr($f['to']) . '"></label>';
    echo '<button class="button button-primary">Filtrar</button>';
    $csv = wp_nonce_url(add_query_arg(array_merge(['action' => 'cpnnet_asistente_leads_csv'], $keep), admin_url('admin-post.php')), 'cpnnet_asistente_csv');
    echo '<a class="button" href="' . esc_url($csv) . '">Exportar CSV</a></form>';
    echo '<section class="cpn-card"><div class="cpn-card-head"><h3>' . esc_html(number_format_i18n($res['total'])) . ' leads</h3></div>' . cpnnet_asistente_leads_table_html($res['rows']);
    if ($pages > 1) {
        echo '<div class="cpn-pager">';
        for ($p = 1; $p <= $pages; $p++) {
            printf('<a class="cpn-chip %s" href="%s">%d</a>', $p === $page ? 'is-on' : '', esc_url(cpnnet_asistente_leads_url(array_merge($keep, ['paged' => $p]))), $p);
        }
        echo '</div>';
    }
    echo '</section>';
    cpnnet_asistente_wrap_end();
}

function cpnnet_asistente_page_lead(int $id): void
{
    $r = cpnnet_asistente_lead_by('id', $id);
    cpnnet_asistente_wrap_start($r ? ($r['nombre'] ?: 'Lead sin nombre') : 'Lead no encontrado', $r ? trim($r['empresa'] . ' · ' . ($r['pais'] ?: '')) : '');
    echo '<p><a href="' . esc_url(cpnnet_asistente_leads_url()) . '">← Volver a leads</a></p>';
    if (!$r) {
        echo '<p class="cpn-empty">No existe este lead.</p>';
        cpnnet_asistente_wrap_end();
        return;
    }
    $marcas = implode(', ', json_decode((string) $r['marcas'], true) ?: []);
    $contact = esc_html($r['contacto'] ?: '—');
    if (is_email($r['contacto'])) {
        $contact = '<a href="mailto:' . esc_attr($r['contacto']) . '">' . esc_html($r['contacto']) . '</a>';
    }
    $row = static fn(string $l, string $v): string => '<div class="cpn-field"><span>' . esc_html($l) . '</span><div>' . $v . '</div></div>';
    echo '<div class="cpn-grid cpn-grid-2"><section class="cpn-card"><h3>Datos del lead</h3><div class="cpn-fields">'
        . $row('Perfil', esc_html(ucfirst($r['perfil'])))
        . $row('Contacto', $contact)
        . $row('Empresa', esc_html($r['empresa'] ?: '—'))
        . $row('País', esc_html($r['pais'] ?: '—'))
        . $row('Marcas de interés', esc_html($marcas ?: '—'))
        . $row('Necesidad', nl2br(esc_html((string) $r['necesidad'] ?: '—')))
        . $row('Dimensionamiento', nl2br(esc_html((string) $r['dimensionamiento'] ?: '—')))
        . $row('Siguiente paso sugerido', nl2br(esc_html((string) $r['siguiente_paso'] ?: '—')))
        . $row('Página de origen', esc_html($r['pagina'] ?: '—'))
        . $row('Fecha', esc_html(cpnnet_asistente_local($r['created_at'])))
        . '</div></section>';

    echo '<div><section class="cpn-card"><h3>Seguimiento</h3><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('cpnnet_asistente_leads');
    echo '<input type="hidden" name="action" value="cpnnet_asistente_lead_save"><input type="hidden" name="lead_id" value="' . (int) $r['id'] . '">';
    echo '<label class="cpn-label">Estado</label><select name="status">';
    foreach (CPNNET_ASISTENTE_STATUSES as $k => $l) { echo '<option value="' . esc_attr($k) . '"' . selected($r['status'], $k, false) . '>' . esc_html($l) . '</option>'; }
    echo '</select><label class="cpn-label">Notas internas</label><textarea name="notes" rows="5" class="large-text">' . esc_textarea((string) $r['notes']) . '</textarea>';
    submit_button('Guardar', 'primary', 'submit', false);
    echo '</form></section>';

    echo '<section class="cpn-card"><h3>Envío al CRM</h3><p>' . ($r['exported_at'] ? '<span class="cpn-pill cpn-pill-ok">Exportado</span> ' . esc_html(cpnnet_asistente_local($r['exported_at'])) : '<span class="cpn-pill">Pendiente</span>') . '</p>';
    if ($r['webhook_status'] !== '') {
        echo '<p class="cpn-sub">Último aviso por webhook: ' . esc_html($r['webhook_status']) . '</p>';
    }
    foreach (['cpnnet_asistente_lead_resend' => 'Reenviar al CRM (webhook)', 'cpnnet_asistente_lead_mark' => 'Marcar como exportado'] as $act => $lbl) {
        if ($act === 'cpnnet_asistente_lead_resend' && !cpnnet_asistente_webhook_valid_url(cpnnet_asistente_integration()['webhook_url'])) {
            continue;
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cpn-inline">';
        wp_nonce_field('cpnnet_asistente_leads');
        echo '<input type="hidden" name="action" value="' . esc_attr($act) . '"><input type="hidden" name="lead_id" value="' . (int) $r['id'] . '"><button class="button">' . esc_html($lbl) . '</button></form>';
    }
    echo '</section></div></div>';

    $tr = json_decode((string) $r['transcript'], true) ?: [];
    echo '<section class="cpn-card"><h3>Conversación</h3>';
    if (!$tr) {
        echo '<p class="cpn-empty">No se guardó la conversación de este lead (se puede activar en Configuración).</p>';
    } else {
        echo '<div class="cpn-chat">';
        foreach ($tr as $m) {
            echo '<div class="cpn-bubble cpn-bubble-' . ($m['role'] === 'user' ? 'user' : 'bot') . '">' . nl2br(esc_html((string) $m['content'])) . '</div>';
        }
        echo '</div><p class="cpn-foot">Se muestra hasta el momento en que el visitante aceptó ser contactado.</p>';
    }
    echo '</section>';
    cpnnet_asistente_wrap_end();
}

/* ---------- Acciones (admin-post) ---------- */

function cpnnet_asistente_leads_guard(): void
{
    if (!current_user_can('cpnnet_asistente_leads')) {
        wp_die('No tienes permisos para esto.', 403);
    }
    check_admin_referer('cpnnet_asistente_leads');
}

add_action('admin_post_cpnnet_asistente_lead_save', function () {
    cpnnet_asistente_leads_guard();
    $id = (int) ($_POST['lead_id'] ?? 0);
    cpnnet_asistente_lead_update($id, [
        'status' => sanitize_text_field(wp_unslash($_POST['status'] ?? '')),
        'notes'  => mb_substr(sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')), 0, 5000),
    ]);
    cpnnet_asistente_admin_redirect('cpnnet-asistente-leads', 'lead_saved', ['lead' => $id]);
});

add_action('admin_post_cpnnet_asistente_lead_resend', function () {
    cpnnet_asistente_leads_guard();
    $id  = (int) ($_POST['lead_id'] ?? 0);
    $row = cpnnet_asistente_lead_by('id', $id);
    $ok  = false;
    if ($row) {
        cpnnet_asistente_webhook_send_lead($row['uuid']);
        $ok = (bool) (cpnnet_asistente_lead_by('id', $id)['exported_at'] ?? null);
    }
    cpnnet_asistente_admin_redirect('cpnnet-asistente-leads', $ok ? 'resent' : 'resentfail', ['lead' => $id]);
});

add_action('admin_post_cpnnet_asistente_lead_mark', function () {
    cpnnet_asistente_leads_guard();
    $id  = (int) ($_POST['lead_id'] ?? 0);
    $row = cpnnet_asistente_lead_by('id', $id);
    if ($row) {
        cpnnet_asistente_leads_mark_exported([$row['uuid']]);
    }
    cpnnet_asistente_admin_redirect('cpnnet-asistente-leads', 'marked', ['lead' => $id]);
});

/** Contenido del CSV para los filtros dados (separado para poder probarlo). */
function cpnnet_asistente_csv_for(array $filters): string
{
    return cpnnet_asistente_leads_csv(cpnnet_asistente_leads_query($filters, 5000, 0)['rows']);
}

add_action('admin_post_cpnnet_asistente_leads_csv', function () {
    if (!current_user_can('cpnnet_asistente_leads')) {
        wp_die('No tienes permisos para esto.', 403);
    }
    check_admin_referer('cpnnet_asistente_csv');
    $csv = cpnnet_asistente_csv_for(cpnnet_asistente_lead_filters_from_request());
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-cpnnet-' . gmdate('Ymd-His') . '.csv"');
    echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput
    exit;
});
