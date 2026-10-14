<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Perfil del alumno: shortcode [asistente_reservas] en cualquier página del sitio.
 * Muestra el plan (clases disponibles y vencimiento), las próximas clases (cancelar / cambiar) y el horario con cupos.
 * Sin sesión iniciada muestra el formulario de acceso de WordPress (y «¿olvidaste tu contraseña?»).
 */

function cpnnet_asistente_portal_flash(int $user_id, ?array $set = null): ?array
{
    $k = 'cpnnet_portal_msg_' . $user_id;
    if ($set !== null) {
        set_transient($k, $set, 120);
        return null;
    }
    $v = get_transient($k);
    delete_transient($k);
    return is_array($v) ? $v : null;
}

function cpnnet_asistente_portal_css(): string
{
    $c = cpnnet_asistente_brand()['colors'];
    $ink = $c['ink'] ?? '#1c2540';
    $mut = $c['mut'] ?? '#667089';
    $line = $c['line'] ?? '#e3e9f3';
    return ".cpn-portal{--p:{$c['primary']};--d:{$c['dark']};--a:{$c['accent']};--t:{$c['tint']};--bg:{$c['bg']};--ink:{$ink};--mut:{$mut};--line:{$line}}";
}

/** Formulario POST de una acción del portal (con nonce). */
function cpnnet_asistente_portal_button(string $action, array $fields, string $label, string $class = 'cpn-btn', string $confirm = ''): string
{
    $h = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cpn-inline"' . ($confirm ? ' onsubmit="return confirm(\'' . esc_attr($confirm) . '\')"' : '') . '>'
        . '<input type="hidden" name="action" value="' . esc_attr($action) . '">'
        . '<input type="hidden" name="_wpnonce" value="' . esc_attr(wp_create_nonce($action)) . '">';
    foreach ($fields as $k => $v) {
        $h .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr((string) $v) . '">';
    }
    return $h . '<button type="submit" class="' . esc_attr($class) . '">' . esc_html($label) . '</button></form>';
}

function cpnnet_asistente_portal_render(): string
{
    $b = cpnnet_asistente_brand();
    $cfg = cpnnet_asistente_booking_cfg();
    $o = '<div class="cpn-portal">';
    if (!is_user_logged_in()) {
        $o .= '<div class="cpn-card cpn-login"><h2>Mi perfil · ' . esc_html($b['name']) . '</h2><p>Ingresa para ver tu plan y reservar tus clases.</p>'
            . wp_login_form(['echo' => false, 'redirect' => cpnnet_asistente_portal_url(), 'label_username' => 'Correo', 'label_log_in' => 'Ingresar', 'remember' => true])
            . '<p class="cpn-sub"><a href="' . esc_url(wp_lostpassword_url(cpnnet_asistente_portal_url())) . '">¿Primera vez u olvidaste tu contraseña? Crea una nueva aquí</a></p></div></div>';
        return $o;
    }
    $uid = get_current_user_id();
    $me = cpnnet_asistente_user_info($uid);
    if ($msg = cpnnet_asistente_portal_flash($uid)) {
        $o .= '<div class="cpn-msg cpn-msg-' . esc_attr($msg[0]) . '">' . esc_html($msg[1]) . '</div>';
    }
    $o .= '<div class="cpn-hello"><h2>Hola, ' . esc_html($me['name']) . '</h2><a class="cpn-sub" href="' . esc_url(wp_logout_url(cpnnet_asistente_portal_url())) . '">Cerrar sesión</a></div>';

    // Plan(es)
    $plans = cpnnet_asistente_memberships_of($uid);
    $o .= '<div class="cpn-plans">';
    if (!$plans) {
        $wa = (string) cpnnet_asistente_get('whatsapp');
        $o .= '<div class="cpn-card"><h3>No tienes un plan activo</h3><p class="cpn-sub">Para reservar necesitas un plan vigente.</p>'
            . ($wa !== '' ? '<a class="cpn-btn" href="' . esc_url('https://wa.me/' . $wa . '?text=' . rawurlencode('Hola, quiero contratar un plan.')) . '" target="_blank" rel="noopener">Contratar por WhatsApp</a>' : '')
            . '</div>';
    }
    foreach ($plans as $m) {
        $left = max(0, (int) $m['credits_total'] - (int) $m['credits_used']);
        $pct = (int) round(100 * (int) $m['credits_used'] / max(1, (int) $m['credits_total']));
        $o .= '<div class="cpn-card cpn-plan"><span class="cpn-kicker">Mi plan</span><h3>' . esc_html($m['plan_name']) . '</h3>'
            . '<div class="cpn-big">' . $left . '<small> de ' . (int) $m['credits_total'] . ' clases disponibles</small></div>'
            . '<div class="cpn-bar"><span style="width:' . $pct . '%"></span></div>'
            . '<p class="cpn-sub">Vigente del ' . esc_html(cpnnet_asistente_fmt_date($m['starts_on'])) . ' al <strong>' . esc_html(cpnnet_asistente_fmt_date($m['ends_on'])) . '</strong></p></div>';
    }
    $o .= '</div>';

    // Próximas clases
    $mine = cpnnet_asistente_bookings_of($uid);
    $changing = isset($_GET['cambiar']) ? (int) $_GET['cambiar'] : 0;
    $o .= '<div class="cpn-card"><h3>Mis próximas clases</h3>';
    if (!$mine) {
        $o .= '<p class="cpn-sub">Aún no tienes clases reservadas.</p>';
    }
    foreach ($mine as $r) {
        $can = cpnnet_asistente_can_cancel($r);
        $o .= '<div class="cpn-row' . ($changing === (int) $r['id'] ? ' cpn-row-on' : '') . '"><div><strong>' . esc_html(cpnnet_asistente_fmt_session($r['starts_at'])) . '</strong><br><span class="cpn-sub">' . esc_html($r['title'] . ($r['instructor'] ? ' · ' . $r['instructor'] : '')) . '</span></div><div class="cpn-actions">';
        if ($can) {
            $o .= '<a class="cpn-btn cpn-btn-ghost" href="' . esc_url(add_query_arg(['cambiar' => (int) $r['id']], cpnnet_asistente_portal_url())) . '#horario">Cambiar</a>'
                . cpnnet_asistente_portal_button('cpnnet_cancelar', ['booking' => (int) $r['id']], 'Cancelar', 'cpn-btn cpn-btn-ghost', '¿Cancelar esta clase? Volverá a tu plan.');
        } else {
            $o .= '<span class="cpn-sub">Ya no se puede cancelar</span>';
        }
        $o .= '</div></div>';
    }
    $o .= '<p class="cpn-sub cpn-note">Puedes cancelar o cambiar hasta ' . (int) $cfg['cancel_hours'] . ' horas antes de la clase.</p></div>';

    // Horario con cupos
    $mine_sessions = array_map('intval', array_column($mine, 'session_id'));
    $o .= '<div class="cpn-card" id="horario"><h3>' . ($changing ? 'Elige el nuevo horario' : 'Reservar una clase') . '</h3>';
    if ($changing) {
        $o .= '<p class="cpn-sub">Estás cambiando una clase. <a href="' . esc_url(cpnnet_asistente_portal_url()) . '">Volver</a></p>';
    }
    $day = '';
    $sessions = cpnnet_asistente_upcoming_sessions();
    if (!$sessions) {
        $o .= '<p class="cpn-sub">No hay clases programadas por ahora.</p>';
    }
    foreach ($sessions as $s) {
        $label = cpnnet_asistente_fmt_session($s['starts_at']);
        [$d, $time] = explode(' · ', $label);
        if ($d !== $day) {
            $o .= ($day ? '</div>' : '') . '<h4 class="cpn-day">' . esc_html($d) . '</h4><div class="cpn-slots">';
            $day = $d;
        }
        $free = max(0, (int) $s['capacity'] - (int) $s['booked']);
        $mineHere = in_array((int) $s['id'], $mine_sessions, true);
        $o .= '<div class="cpn-slot' . ($free ? '' : ' cpn-full') . ($mineHere ? ' cpn-mine' : '') . '"><div><strong>' . esc_html($time) . '</strong> ' . esc_html($s['title'])
            . '<br><span class="cpn-sub">' . esc_html(($s['instructor'] ? $s['instructor'] . ' · ' : '') . (int) $s['duration'] . ' min') . '</span></div><div class="cpn-actions"><span class="cpn-spots">' . ($mineHere ? 'Reservada' : ($free ? $free . ' cupo' . ($free > 1 ? 's' : '') : 'Completa')) . '</span>';
        if (!$mineHere && $free && $plans) {
            $o .= $changing
                ? cpnnet_asistente_portal_button('cpnnet_cambiar', ['booking' => $changing, 'session' => (int) $s['id']], 'Cambiar aquí')
                : cpnnet_asistente_portal_button('cpnnet_reservar', ['session' => (int) $s['id']], 'Reservar');
        }
        $o .= '</div></div>';
    }
    $o .= ($day ? '</div>' : '') . '</div></div>';
    return $o;
}

/** Acciones del portal (solo con sesión; sin sesión se va al acceso). */
function cpnnet_asistente_portal_action(string $action): void
{
    if (!is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(cpnnet_asistente_portal_url()));
        return;
    }
    if (!wp_verify_nonce((string) ($_POST['_wpnonce'] ?? ''), $action)) {
        wp_die('Enlace vencido. Vuelve a tu perfil e inténtalo de nuevo.', '', ['response' => 403]);
    }
    $uid = get_current_user_id();
    $session = (int) ($_POST['session'] ?? 0);
    $booking = (int) ($_POST['booking'] ?? 0);
    $r = match ($action) {
        'cpnnet_reservar' => cpnnet_asistente_book($uid, $session),
        'cpnnet_cancelar' => cpnnet_asistente_cancel($uid, $booking),
        'cpnnet_cambiar'  => cpnnet_asistente_change($uid, $booking, $session),
        default           => new WP_Error('accion', 'Acción no válida.'),
    };
    $ok = ['cpnnet_reservar' => '¡Listo! Tu clase quedó reservada. Te enviamos la confirmación por correo.', 'cpnnet_cancelar' => 'Cancelaste la clase y volvió a tu plan.', 'cpnnet_cambiar' => '¡Listo! Cambiamos tu clase. Te enviamos la confirmación por correo.'];
    cpnnet_asistente_portal_flash($uid, is_wp_error($r) ? ['error', $r->get_error_message()] : ['ok', $ok[$action] ?? 'Listo.']);
    wp_safe_redirect(cpnnet_asistente_portal_url() . '#horario');
}

if (cpnnet_asistente_booking_enabled()) {
    add_shortcode('asistente_reservas', 'cpnnet_asistente_portal_render');
    add_action('wp_enqueue_scripts', function () {
        global $post;
        if (!($post instanceof WP_Post) || !has_shortcode((string) $post->post_content, 'asistente_reservas')) {
            return; // la hoja de estilos solo se carga en la página del perfil
        }
        wp_enqueue_style('cpnnet-portal', CPNNET_ASISTENTE_URL . 'assets/portal.css', [], CPNNET_ASISTENTE_VERSION);
        wp_add_inline_style('cpnnet-portal', cpnnet_asistente_portal_css());
    });
    foreach (['cpnnet_reservar', 'cpnnet_cancelar', 'cpnnet_cambiar'] as $a) {
        add_action('admin_post_' . $a, static fn() => cpnnet_asistente_portal_action($a));
        add_action('admin_post_nopriv_' . $a, static fn() => cpnnet_asistente_portal_action($a));
    }
}
