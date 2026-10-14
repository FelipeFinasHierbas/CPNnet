<?php
if (!defined('ABSPATH')) {
    exit;
}

/* ===== Panel del estudio: Agenda, Alumnos y pagos, Configuración de reservas ===== */

function cpnnet_asistente_booking_redirect(string $page, string $msg, array $extra = []): void
{
    wp_safe_redirect(add_query_arg(array_merge(['page' => $page, 'msg' => $msg], $extra), admin_url('admin.php')));
}

/* ---------- Formato de texto editable (una línea por elemento) ---------- */

function cpnnet_asistente_plans_to_text(array $plans): string
{
    return implode("\n", array_map(static fn($p) => implode(' | ', [$p['id'], $p['name'], (int) $p['classes'], (int) $p['days'], (int) $p['price']]), $plans));
}

/** «id | Nombre | clases | días | precio» → planes válidos (las líneas mal escritas se ignoran y se informan). */
function cpnnet_asistente_text_to_plans(string $text, array &$bad = []): array
{
    $out = [];
    foreach (preg_split('/\R/', $text) as $n => $line) {
        if (trim($line) === '') {
            continue;
        }
        $p = array_map('trim', explode('|', $line));
        $price = (int) preg_replace('/\D/', '', $p[4] ?? '');
        if (count($p) < 4 || $p[0] === '' || $p[1] === '' || (int) $p[2] < 1 || (int) $p[3] < 1) {
            $bad[] = $n + 1;
            continue;
        }
        $out[] = ['id' => sanitize_title($p[0]), 'name' => sanitize_text_field($p[1]), 'classes' => (int) $p[2], 'days' => (int) $p[3], 'price' => $price];
    }
    return $out;
}

function cpnnet_asistente_schedule_to_text(array $rows): string
{
    return implode("\n", array_map(static fn($s) => implode(' | ', [CPNNET_ASISTENTE_WEEKDAYS[(int) $s['weekday']] ?? '?', $s['time'], (int) $s['duration'], $s['title'], (int) $s['capacity'], $s['instructor'] ?? '']), $rows));
}

/** «Día | HH:MM | minutos | Clase | cupos | Instructor/a» → horario semanal. */
function cpnnet_asistente_text_to_schedule(string $text, array &$bad = []): array
{
    $days = [];
    foreach (CPNNET_ASISTENTE_WEEKDAYS as $k => $v) {
        $days[sanitize_title($v)] = $k;
    }
    $days['miercoles'] = 3;
    $days['sabado'] = 6;
    $out = [];
    foreach (preg_split('/\R/', $text) as $n => $line) {
        if (trim($line) === '') {
            continue;
        }
        $p = array_map('trim', explode('|', $line));
        $wd = $days[sanitize_title(strtr($p[0] ?? '', ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u']))] ?? 0;
        if (!$wd || !preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $p[1] ?? '') || (int) ($p[2] ?? 0) < 1 || ($p[3] ?? '') === '' || (int) ($p[4] ?? 0) < 1) {
            $bad[] = $n + 1;
            continue;
        }
        $time = str_pad($p[1], 5, '0', STR_PAD_LEFT);
        $out[] = ['id' => $wd . '-' . str_replace(':', '', $time) . '-' . substr(sanitize_title($p[3]), 0, 8), 'weekday' => $wd, 'time' => $time, 'duration' => (int) $p[2], 'title' => sanitize_text_field($p[3]), 'capacity' => (int) $p[4], 'instructor' => sanitize_text_field($p[5] ?? '')];
    }
    usort($out, static fn($a, $b) => [$a['weekday'], $a['time']] <=> [$b['weekday'], $b['time']]);
    return $out;
}

/* ---------- Agenda ---------- */

function cpnnet_asistente_page_agenda(): void
{
    global $wpdb;
    $cfg = cpnnet_asistente_booking_cfg();
    cpnnet_asistente_wrap_start('Agenda', 'Clases de los próximos días, ocupación y alumnos inscritos.');
    if (!empty($cfg['example'])) {
        echo '<div class="cpn-alert">El horario y los planes son de ejemplo. Revísalos y guárdalos en «Configuración de reservas» antes de abrir las reservas.</div>';
    }
    $sessions = cpnnet_asistente_upcoming_sessions(7);
    $cap = array_sum(array_column($sessions, 'capacity'));
    $booked = array_sum(array_column($sessions, 'booked'));
    $mt = cpnnet_asistente_bt('memberships');
    $active = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$mt} WHERE status = 'activo' AND ends_on >= %s", cpnnet_asistente_local_date(cpnnet_asistente_now())));
    echo '<div class="cpn-kpis">';
    foreach ([['Clases (7 días)', count($sessions), 'programadas'], ['Reservas', $booked, 'de ' . $cap . ' cupos'], ['Ocupación', $cap ? round(100 * $booked / $cap) . '%' : '—', 'próximos 7 días'], ['Alumnos con plan', $active, 'vigente hoy']] as [$l, $v, $hnt]) {
        echo '<div class="cpn-kpi"><span class="cpn-kpi-label">' . esc_html($l) . '</span><span class="cpn-kpi-value">' . esc_html((string) $v) . '</span><span class="cpn-kpi-hint">' . esc_html($hnt) . '</span></div>';
    }
    echo '</div><section class="cpn-card"><h3>Próximas clases</h3>';
    if (!$sessions) {
        echo '<p>No hay clases programadas. Define el horario semanal en «Configuración de reservas».</p>';
    } else {
        $b = cpnnet_asistente_bt('bookings');
        echo '<table class="cpn-table"><thead><tr><th>Día y hora</th><th>Clase</th><th>Ocupación</th><th>Alumnos</th><th></th></tr></thead><tbody>';
        foreach ($sessions as $s) {
            $names = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM {$b} WHERE session_id = %d AND status = 'reservada' ORDER BY id", (int) $s['id'])) ?: [];
            $names = array_map(static fn($u) => cpnnet_asistente_user_info((int) $u)['name'], $names);
            $pct = (int) round(100 * (int) $s['booked'] / max(1, (int) $s['capacity']));
            echo '<tr><td><strong>' . esc_html(cpnnet_asistente_fmt_session($s['starts_at'])) . '</strong></td><td>' . esc_html($s['title']) . '<br><span class="cpn-sub">' . esc_html($s['instructor']) . '</span></td>'
                . '<td><div class="cpn-occ"><span style="width:' . $pct . '%"></span></div>' . (int) $s['booked'] . ' / ' . (int) $s['capacity'] . '</td>'
                . '<td>' . esc_html($names ? implode(', ', $names) : '—') . '</td><td>'
                . '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'¿Cancelar esta clase? Se avisará por correo a los alumnos y se les devolverá la clase.\')">'
                . '<input type="hidden" name="action" value="cpnnet_cancelar_clase"><input type="hidden" name="session" value="' . (int) $s['id'] . '">' . wp_nonce_field('cpnnet_cancelar_clase', '_wpnonce', true, false)
                . '<button class="button button-small button-link-delete">Cancelar clase</button></form></td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</section>';
    cpnnet_asistente_wrap_end();
}

/* ---------- Alumnos y pagos ---------- */

function cpnnet_asistente_page_alumnos(): void
{
    global $wpdb;
    $cfg = cpnnet_asistente_booking_cfg();
    cpnnet_asistente_wrap_start('Alumnos y pagos', 'Registra un pago para activar el plan del alumno: recibirá la confirmación por correo y el acceso a su perfil.');
    echo '<section class="cpn-card"><h3>Registrar pago</h3>';
    if (!$cfg['plans']) {
        echo '<p>Primero define los planes en «Configuración de reservas».</p>';
    } else {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cpn-form-grid"><input type="hidden" name="action" value="cpnnet_registrar_pago">' . wp_nonce_field('cpnnet_registrar_pago', '_wpnonce', true, false)
            . '<label>Correo del alumno<input type="email" name="email" required></label><label>Nombre<input type="text" name="nombre" required></label>'
            . '<label>Plan<select name="plan">';
        foreach ($cfg['plans'] as $p) {
            echo '<option value="' . esc_attr($p['id']) . '" data-price="' . (int) $p['price'] . '">' . esc_html($p['name'] . ' · ' . $p['classes'] . ' clases · ' . $p['days'] . ' días · ' . cpnnet_asistente_money((int) $p['price'])) . '</option>';
        }
        echo '</select></label><label>Monto pagado (vacío = precio del plan)<input type="number" name="monto" min="0" step="1"></label>'
            . '<label>Comprobante / referencia<input type="text" name="ref" placeholder="Transferencia, boleta, BoxMagic…"></label><label>Inicio del plan<input type="date" name="inicio" value="' . esc_attr(cpnnet_asistente_local_date(cpnnet_asistente_now())) . '"></label>'
            . '<p><button class="button button-primary">Registrar pago y enviar confirmación</button></p></form>';
    }
    echo '</section>';
    $mt = cpnnet_asistente_bt('memberships');
    $rows = $wpdb->get_results("SELECT * FROM {$mt} ORDER BY (status = 'activo') DESC, ends_on ASC LIMIT 300", ARRAY_A) ?: [];
    echo '<section class="cpn-card"><h3>Planes de alumnos</h3>';
    if (!$rows) {
        echo '<p>Aún no hay pagos registrados.</p>';
    } else {
        $st = ['activo' => 'Activo', 'vencido' => 'Vencido', 'anulado' => 'Anulado'];
        echo '<table class="cpn-table"><thead><tr><th>Alumno</th><th>Plan</th><th>Clases</th><th>Vigencia</th><th>Pago</th><th>Estado</th></tr></thead><tbody>';
        foreach ($rows as $m) {
            $u = cpnnet_asistente_user_info((int) $m['user_id']);
            echo '<tr><td><strong>' . esc_html($u['name']) . '</strong><br><span class="cpn-sub">' . esc_html($u['email']) . '</span></td><td>' . esc_html($m['plan_name']) . '</td>'
                . '<td>' . (int) $m['credits_used'] . ' / ' . (int) $m['credits_total'] . ' usadas</td><td>' . esc_html(cpnnet_asistente_fmt_date($m['starts_on']) . ' – ' . cpnnet_asistente_fmt_date($m['ends_on'])) . '</td>'
                . '<td>' . esc_html(cpnnet_asistente_money((int) $m['amount'])) . '<br><span class="cpn-sub">' . esc_html($m['payment_ref']) . '</span></td><td><span class="cpn-pill cpn-st-' . esc_attr($m['status']) . '">' . esc_html($st[$m['status']] ?? $m['status']) . '</span></td></tr>';
        }
        echo '</tbody></table>';
    }
    echo '</section>';
    cpnnet_asistente_wrap_end();
}

/** Busca al alumno por correo o lo crea (rol alumno) y le envía el enlace para crear su contraseña. */
function cpnnet_asistente_ensure_member(string $email, string $name)
{
    $u = get_user_by('email', $email);
    if ($u) {
        return (int) $u->ID;
    }
    $id = wp_insert_user([
        'user_login'   => $email,
        'user_email'   => $email,
        'user_pass'    => wp_generate_password(20),
        'first_name'   => $name,
        'display_name' => $name,
        'role'         => 'cpnnet_alumno',
    ]);
    if (is_wp_error($id)) {
        return $id;
    }
    wp_new_user_notification($id, null, 'user'); // correo de WordPress con el enlace para crear la contraseña
    return (int) $id;
}

/* ---------- Configuración de reservas ---------- */

function cpnnet_asistente_page_reservas_cfg(): void
{
    $cfg = cpnnet_asistente_booking_cfg();
    cpnnet_asistente_wrap_start('Configuración de reservas', 'Planes, horario semanal, plazos y avisos por correo.');
    if (!empty($cfg['example'])) {
        echo '<div class="cpn-alert">Estos datos son de ejemplo. Al guardar quedan como definitivos.</div>';
    }
    $bad = isset($_GET['bad']) ? sanitize_text_field((string) $_GET['bad']) : '';
    if ($bad !== '') {
        echo '<div class="notice notice-error"><p>Algunas líneas no se entendieron y no se guardaron: ' . esc_html($bad) . '.</p></div>';
    }
    $num = static fn($k, $label, $help) => '<tr><th><label for="' . $k . '">' . esc_html($label) . '</label></th><td><input type="number" min="0" id="' . $k . '" name="' . $k . '" value="' . (int) $cfg[$k] . '" class="small-text"> <span class="description">' . esc_html($help) . '</span></td></tr>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="cpnnet_guardar_reservas">' . wp_nonce_field('cpnnet_guardar_reservas', '_wpnonce', true, false)
        . '<section class="cpn-card"><h3>Planes</h3><p class="description">Una línea por plan: <code>id | Nombre | clases | días de vigencia | precio</code>. Ej.: <code>plan-8 | Plan 8 sesiones | 8 | 30 | 60000</code></p>'
        . '<textarea name="plans" rows="7" class="large-text code">' . esc_textarea(cpnnet_asistente_plans_to_text($cfg['plans'])) . '</textarea></section>'
        . '<section class="cpn-card"><h3>Horario semanal</h3><p class="description">Una línea por clase: <code>Día | HH:MM | minutos | Clase | cupos | Instructor/a</code>. Ej.: <code>Lunes | 07:00 | 50 | Pilates Interval Fusion | 8 | Camila</code>. Las clases se crean automáticamente para los próximos días.</p>'
        . '<textarea name="schedule" rows="12" class="large-text code">' . esc_textarea(cpnnet_asistente_schedule_to_text($cfg['schedule'])) . '</textarea></section>'
        . '<section class="cpn-card"><h3>Plazos y avisos</h3><table class="form-table">'
        . $num('cancel_hours', 'Cancelar o cambiar hasta', 'horas antes de la clase (la clase vuelve al plan)')
        . $num('reminder_hours', 'Recordatorio de clase', 'horas antes (correo)')
        . $num('expiry_days', 'Aviso de plan por vencer', 'días antes (correo)')
        . $num('window_days', 'Reservas abiertas', 'días hacia adelante')
        . '<tr><th><label for="portal_url">Página del perfil del alumno</label></th><td><input type="url" id="portal_url" name="portal_url" value="' . esc_attr($cfg['portal_url']) . '" class="regular-text" placeholder="https://…/mi-perfil/"><p class="description">Crea una página con el shortcode <code>[asistente_reservas]</code> y pega aquí su dirección.</p></td></tr>'
        . '</table></section>';
    submit_button('Guardar configuración de reservas');
    echo '</form>';
    cpnnet_asistente_wrap_end();
}

/* ---------- Acciones ---------- */

function cpnnet_asistente_booking_admin_action(string $action): void
{
    $need = $action === 'cpnnet_guardar_reservas' ? 'manage_options' : 'cpnnet_asistente_leads';
    if (!current_user_can($need)) {
        wp_die('No tienes permiso.', '', ['response' => 403]);
    }
    check_admin_referer($action);
    switch ($action) {
        case 'cpnnet_registrar_pago':
            $email = sanitize_email((string) ($_POST['email'] ?? ''));
            $name = sanitize_text_field((string) ($_POST['nombre'] ?? ''));
            $plan = cpnnet_asistente_booking_plan(sanitize_title((string) ($_POST['plan'] ?? '')));
            if (!is_email($email) || $name === '' || !$plan) {
                cpnnet_asistente_booking_redirect('cpnnet-asistente-alumnos', 'invalid');
                return;
            }
            $uid = cpnnet_asistente_ensure_member($email, $name);
            if (is_wp_error($uid)) {
                cpnnet_asistente_booking_redirect('cpnnet-asistente-alumnos', 'invalid');
                return;
            }
            $amount = ($_POST['monto'] ?? '') === '' ? (int) $plan['price'] : (int) $_POST['monto'];
            cpnnet_asistente_membership_create($uid, (string) $plan['id'], $amount, sanitize_text_field((string) ($_POST['ref'] ?? '')), sanitize_text_field((string) ($_POST['inicio'] ?? '')));
            cpnnet_asistente_booking_redirect('cpnnet-asistente-alumnos', 'paid');
            return;
        case 'cpnnet_cancelar_clase':
            cpnnet_asistente_cancel_session((int) ($_POST['session'] ?? 0));
            cpnnet_asistente_booking_redirect('cpnnet-asistente-agenda', 'session_cancelled');
            return;
        case 'cpnnet_guardar_reservas':
            $bad1 = $bad2 = [];
            $cfg = cpnnet_asistente_booking_cfg();
            $cfg['plans'] = cpnnet_asistente_text_to_plans((string) wp_unslash($_POST['plans'] ?? ''), $bad1);
            $cfg['schedule'] = cpnnet_asistente_text_to_schedule((string) wp_unslash($_POST['schedule'] ?? ''), $bad2);
            foreach (['cancel_hours' => [0, 168], 'reminder_hours' => [1, 72], 'expiry_days' => [1, 30], 'window_days' => [1, 60]] as $k => [$min, $max]) {
                $cfg[$k] = max($min, min($max, (int) ($_POST[$k] ?? $cfg[$k])));
            }
            $cfg['portal_url'] = esc_url_raw((string) ($_POST['portal_url'] ?? ''));
            $cfg['example'] = false;
            update_option(CPNNET_ASISTENTE_BOOKING_OPTION, $cfg);
            cpnnet_asistente_generate_sessions();
            $bad = array_merge(array_map(static fn($n) => 'planes línea ' . $n, $bad1), array_map(static fn($n) => 'horario línea ' . $n, $bad2));
            cpnnet_asistente_booking_redirect('cpnnet-asistente-reservas', 'saved', $bad ? ['bad' => implode(', ', $bad)] : []);
            return;
    }
}

if (cpnnet_asistente_booking_enabled()) {
    add_action('admin_menu', function () {
        $cap = 'cpnnet_asistente_leads';
        add_submenu_page('cpnnet-asistente', 'Agenda', 'Agenda', $cap, 'cpnnet-asistente-agenda', 'cpnnet_asistente_page_agenda');
        add_submenu_page('cpnnet-asistente', 'Alumnos y pagos', 'Alumnos y pagos', $cap, 'cpnnet-asistente-alumnos', 'cpnnet_asistente_page_alumnos');
        add_submenu_page('cpnnet-asistente', 'Configuración de reservas', 'Config. reservas', 'manage_options', 'cpnnet-asistente-reservas', 'cpnnet_asistente_page_reservas_cfg');
    }, 20);
    foreach (['cpnnet_registrar_pago', 'cpnnet_cancelar_clase', 'cpnnet_guardar_reservas'] as $a) {
        add_action('admin_post_' . $a, static fn() => cpnnet_asistente_booking_admin_action($a));
    }
}
