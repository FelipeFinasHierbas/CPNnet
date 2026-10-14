<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Correos del módulo de reservas, con la marca del estudio (logo, colores, tipografía del sistema).
 * Se envían con wp_mail: en el hosting conviene un plugin SMTP (o el correo del dominio) para que no lleguen a spam.
 */

/** Arma el HTML del correo. $rows = [[etiqueta, valor], …] se muestran como ficha. */
function cpnnet_asistente_mail_html(string $title, string $intro, array $rows = [], array $cta = [], string $outro = ''): string
{
    $b = cpnnet_asistente_brand();
    $c = $b['colors'];
    $ink = $c['ink'] ?? '#1c2540';
    $mut = $c['mut'] ?? '#667089';
    $line = $c['line'] ?? '#e3e9f3';
    $logo = esc_url(cpnnet_asistente_logo_url(true));
    $h = static fn($s) => esc_html((string) $s);
    $table = '';
    foreach ($rows as [$k, $v]) {
        $table .= '<tr><td style="padding:8px 0;color:' . $mut . ';font-size:13px;width:38%;vertical-align:top">' . $h($k) . '</td>'
            . '<td style="padding:8px 0;color:' . $ink . ';font-size:14px;font-weight:600">' . $h($v) . '</td></tr>';
    }
    $button = $cta ? '<p style="margin:26px 0 6px"><a href="' . esc_url($cta[1]) . '" style="display:inline-block;background:' . $c['primary'] . ';color:#fff;text-decoration:none;font-weight:600;padding:12px 26px;border-radius:999px;font-size:14px">' . $h($cta[0]) . '</a></p>' : '';
    return '<!doctype html><html><body style="margin:0;background:' . $c['bg'] . ';padding:24px 12px;font-family:Montserrat,Helvetica,Arial,sans-serif">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
        . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#fff;border-radius:18px;overflow:hidden;border:1px solid ' . $line . '">'
        . '<tr><td style="background:' . $c['primary'] . ';background-image:linear-gradient(135deg,' . $c['primary'] . ',' . $c['dark'] . ');padding:22px 28px"><img src="' . $logo . '" alt="' . $h($b['name']) . '" height="40" style="display:block;height:40px;width:auto"></td></tr>'
        . '<tr><td style="padding:28px">'
        . '<h1 style="margin:0 0 10px;font-size:21px;color:' . $ink . '">' . $h($title) . '</h1>'
        . '<p style="margin:0 0 16px;font-size:14.5px;line-height:1.6;color:' . $ink . '">' . nl2br($h($intro)) . '</p>'
        . ($table ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid ' . $line . ';border-bottom:1px solid ' . $line . ';margin:6px 0">' . $table . '</table>' : '')
        . $button
        . ($outro ? '<p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:' . $mut . '">' . nl2br($h($outro)) . '</p>' : '')
        . '</td></tr>'
        . '<tr><td style="padding:16px 28px;background:' . $c['bg'] . ';color:' . $mut . ';font-size:12px">' . $h($b['name']) . ($b['site'] ? ' · ' . $h($b['site']) : '') . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Envía (o, en pruebas/demos, captura) un correo HTML. */
function cpnnet_asistente_send_mail(string $to, string $subject, string $html): bool
{
    if (!is_email($to)) {
        return false;
    }
    $b = cpnnet_asistente_brand();
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    return (bool) wp_mail($to, $subject . ' · ' . $b['short'], $html, $headers);
}

function cpnnet_asistente_user_info(int $user_id): array
{
    $u = get_userdata($user_id);
    return $u ? ['email' => (string) $u->user_email, 'name' => (string) ($u->first_name ?: $u->display_name)] : ['email' => '', 'name' => ''];
}

function cpnnet_asistente_mail_payment(int $membership_id): bool
{
    $m = cpnnet_asistente_membership($membership_id);
    if (!$m) {
        return false;
    }
    $u = cpnnet_asistente_user_info((int) $m['user_id']);
    $paid = (int) $m['amount'] > 0;
    $rows = [
        ['Plan', $m['plan_name']],
        ['Clases incluidas', (string) $m['credits_total']],
        ['Vigencia', cpnnet_asistente_fmt_date($m['starts_on']) . ' al ' . cpnnet_asistente_fmt_date($m['ends_on'])],
    ];
    if ($paid) {
        $rows[] = ['Monto', cpnnet_asistente_money((int) $m['amount'])];
        $rows[] = ['Comprobante', $m['payment_ref'] ?: '—'];
    }
    $html = cpnnet_asistente_mail_html(
        $paid ? '¡Pago confirmado!' : '¡Tu plan está activo!',
        'Hola ' . $u['name'] . ', ' . ($paid ? 'recibimos tu pago. Tu plan ya está activo' : 'ya activamos tu plan') . ' y puedes reservar tus clases desde tu perfil.',
        $rows,
        ['Reservar mis clases', cpnnet_asistente_portal_url()],
        $paid ? 'Guarda este correo como comprobante.' : ''
    );
    return cpnnet_asistente_send_mail($u['email'], $paid ? 'Pago confirmado' : 'Tu plan está activo', $html);
}

/** $kind: reserva | cambio | cancelacion | clase_cancelada | recordatorio */
function cpnnet_asistente_mail_booking(int $booking_id, string $kind, ?array $previous = null): bool
{
    $b = cpnnet_asistente_booking_with_session($booking_id);
    if (!$b) {
        return false;
    }
    $u = cpnnet_asistente_user_info((int) $b['user_id']);
    $m = cpnnet_asistente_membership((int) $b['membership_id']);
    $cfg = cpnnet_asistente_booking_cfg();
    $when = cpnnet_asistente_fmt_session($b['starts_at']);
    $rows = [['Clase', $b['title']], ['Día y hora', $when], ['Duración', (int) $b['duration'] . ' min']];
    if ($b['instructor']) {
        $rows[] = ['Instructor/a', $b['instructor']];
    }
    $left = $m ? max(0, (int) $m['credits_total'] - (int) $m['credits_used']) : null;
    $plan_row = $m ? [['Clases disponibles en tu plan', $left . ' de ' . (int) $m['credits_total'] . ' (vence el ' . cpnnet_asistente_fmt_date($m['ends_on']) . ')']] : [];
    $policy = 'Puedes cancelar o cambiar tu clase desde tu perfil hasta ' . (int) $cfg['cancel_hours'] . ' horas antes.';
    $cta = ['Ver mi perfil', cpnnet_asistente_portal_url()];
    switch ($kind) {
        case 'reserva':
            [$subject, $title, $intro] = ['Reserva confirmada', '¡Tu clase está reservada!', 'Hola ' . $u['name'] . ', te esperamos.'];
            $rows = array_merge($rows, $plan_row);
            $outro = $policy;
            break;
        case 'cambio':
            [$subject, $title, $intro] = ['Cambio de clase confirmado', 'Cambiamos tu clase', 'Hola ' . $u['name'] . ', tu reserva quedó en el nuevo horario.'];
            if ($previous) {
                $rows[] = ['Antes', cpnnet_asistente_fmt_session($previous['starts_at'])];
            }
            $rows = array_merge($rows, $plan_row);
            $outro = $policy;
            break;
        case 'cancelacion':
            [$subject, $title, $intro] = ['Reserva cancelada', 'Cancelaste tu clase', 'Hola ' . $u['name'] . ', tu reserva quedó cancelada y la clase volvió a tu plan.'];
            $rows = array_merge($rows, $plan_row);
            $outro = '';
            $cta = ['Reservar otra clase', cpnnet_asistente_portal_url()];
            break;
        case 'clase_cancelada':
            [$subject, $title, $intro] = ['Clase cancelada por el estudio', 'Tuvimos que cancelar una clase', 'Hola ' . $u['name'] . ', lamentamos avisarte que el estudio canceló esta clase. La clase volvió a tu plan para que reserves otro horario.'];
            $rows = array_merge($rows, $plan_row);
            $outro = '';
            $cta = ['Elegir otro horario', cpnnet_asistente_portal_url()];
            break;
        case 'recordatorio':
            [$subject, $title, $intro] = ['Recordatorio de clase', 'Te esperamos pronto', 'Hola ' . $u['name'] . ', te recordamos tu próxima clase.'];
            $outro = 'Si no puedes venir, cancela desde tu perfil (hasta ' . (int) $cfg['cancel_hours'] . ' horas antes) para liberar el cupo.';
            break;
        default:
            return false;
    }
    return cpnnet_asistente_send_mail($u['email'], $subject, cpnnet_asistente_mail_html($title, $intro, $rows, $cta, $outro));
}

function cpnnet_asistente_mail_expiry(int $membership_id): bool
{
    $m = cpnnet_asistente_membership($membership_id);
    if (!$m) {
        return false;
    }
    $u = cpnnet_asistente_user_info((int) $m['user_id']);
    $left = max(0, (int) $m['credits_total'] - (int) $m['credits_used']);
    $wa = (string) cpnnet_asistente_get('whatsapp');
    $cta = $wa !== '' ? ['Renovar mi plan por WhatsApp', 'https://wa.me/' . $wa . '?text=' . rawurlencode('Hola, quiero renovar mi plan ' . $m['plan_name'] . '.')] : ['Ver mi perfil', cpnnet_asistente_portal_url()];
    $html = cpnnet_asistente_mail_html(
        'Tu plan está por vencer',
        'Hola ' . $u['name'] . ', tu plan vence pronto. ' . ($left > 0 ? 'Aún tienes clases disponibles: ¡aprovéchalas!' : 'Renueva para seguir reservando tus clases.'),
        [
            ['Plan', $m['plan_name']],
            ['Vence', cpnnet_asistente_fmt_date($m['ends_on'])],
            ['Clases disponibles', $left . ' de ' . (int) $m['credits_total']],
        ],
        $cta
    );
    return cpnnet_asistente_send_mail($u['email'], 'Tu plan vence el ' . cpnnet_asistente_fmt_date($m['ends_on']), $html);
}
