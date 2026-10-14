<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo de reservas (estilo BoxMagic): planes con clases y vigencia, horario semanal preestablecido,
 * clases con cupos, reservas/cancelaciones/cambios del alumno, correos de confirmación y recordatorios.
 * Se activa por variante (brand.json → "modules": ["booking"]). Los datos viven en la base de WordPress del cliente.
 *
 * Fechas: las clases se guardan en UTC; el horario semanal y las vigencias de los planes están en la hora del sitio.
 */

const CPNNET_ASISTENTE_BOOKING_OPTION = 'cpnnet_asistente_booking';
const CPNNET_ASISTENTE_WEEKDAYS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

function cpnnet_asistente_booking_enabled(): bool
{
    return in_array('booking', (array) cpnnet_asistente_brand_get('modules'), true);
}

function cpnnet_asistente_bt(string $name): string
{
    global $wpdb;
    return $wpdb->prefix . 'cpnnet_asistente_' . $name;
}

/* ---------- Configuración ---------- */

function cpnnet_asistente_booking_defaults(): array
{
    $base = [
        'plans'          => [],   // [{id, name, classes, days, price}]
        'schedule'       => [],   // [{id, weekday 1-7, time HH:MM, duration, title, capacity, instructor}]
        'cancel_hours'   => 12,   // se puede cancelar o cambiar hasta X horas antes (se devuelve la clase)
        'reminder_hours' => 12,   // recordatorio de clase X horas antes
        'expiry_days'    => 3,    // aviso de plan por vencer X días antes
        'window_days'    => 14,   // se pueden reservar clases de los próximos X días
        'portal_url'     => '',   // página con el shortcode [asistente_reservas]
        'example'        => false, // true = horario/planes de ejemplo aún no confirmados por el estudio
    ];
    $file = CPNNET_ASISTENTE_DIR . 'booking.json';
    if (is_readable($file)) {
        $j = json_decode((string) file_get_contents($file), true);
        if (is_array($j)) {
            $base = array_merge($base, array_intersect_key($j, $base));
        }
    }
    return $base;
}

function cpnnet_asistente_booking_cfg(): array
{
    return array_merge(cpnnet_asistente_booking_defaults(), (array) get_option(CPNNET_ASISTENTE_BOOKING_OPTION, []));
}

function cpnnet_asistente_booking_plan(string $id): ?array
{
    foreach (cpnnet_asistente_booking_cfg()['plans'] as $p) {
        if ((string) $p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function cpnnet_asistente_portal_url(): string
{
    $u = (string) cpnnet_asistente_booking_cfg()['portal_url'];
    return $u !== '' ? $u : home_url('/');
}

/* ---------- Tablas ---------- */

function cpnnet_asistente_booking_create_tables(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $c = $wpdb->get_charset_collate();
    dbDelta('CREATE TABLE ' . cpnnet_asistente_bt('sessions') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        slot_id VARCHAR(20) NOT NULL DEFAULT '',
        starts_at DATETIME NOT NULL,
        duration SMALLINT UNSIGNED NOT NULL DEFAULT 50,
        title VARCHAR(120) NOT NULL DEFAULT '',
        instructor VARCHAR(120) NOT NULL DEFAULT '',
        capacity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        booked SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        status VARCHAR(12) NOT NULL DEFAULT 'activa',
        PRIMARY KEY  (id),
        UNIQUE KEY slot_start (slot_id,starts_at),
        KEY starts_at (starts_at)
    ) {$c};");
    dbDelta('CREATE TABLE ' . cpnnet_asistente_bt('memberships') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        plan_id VARCHAR(40) NOT NULL DEFAULT '',
        plan_name VARCHAR(120) NOT NULL DEFAULT '',
        credits_total SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        credits_used SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        starts_on DATE NOT NULL,
        ends_on DATE NOT NULL,
        amount INT UNSIGNED NOT NULL DEFAULT 0,
        payment_ref VARCHAR(120) NOT NULL DEFAULT '',
        status VARCHAR(12) NOT NULL DEFAULT 'activo',
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY ends_on (ends_on)
    ) {$c};");
    dbDelta('CREATE TABLE ' . cpnnet_asistente_bt('bookings') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        membership_id BIGINT UNSIGNED NOT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'reservada',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY session_user (session_id,user_id),
        KEY user_id (user_id)
    ) {$c};");
    dbDelta('CREATE TABLE ' . cpnnet_asistente_bt('notices') . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        type VARCHAR(30) NOT NULL,
        ref_id BIGINT UNSIGNED NOT NULL,
        sent_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY type_ref (type,ref_id)
    ) {$c};");
}

/** Rol de alumno: solo puede entrar a su perfil (sin escritorio de WordPress). */
function cpnnet_asistente_booking_ensure_role(): void
{
    if (!get_role('cpnnet_alumno')) {
        add_role('cpnnet_alumno', 'Alumno/a', ['read' => true]);
    }
}

/* ---------- Utilidades de fecha ---------- */

function cpnnet_asistente_now(): int
{
    return (int) current_time('timestamp', true);
}

function cpnnet_asistente_tz(): DateTimeZone
{
    return function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
}

/** Fecha local (Y-m-d) de un instante. */
function cpnnet_asistente_local_date(int $ts): string
{
    return (new DateTimeImmutable('@' . $ts))->setTimezone(cpnnet_asistente_tz())->format('Y-m-d');
}

function cpnnet_asistente_ts(string $utc): int
{
    return (int) strtotime($utc . ' UTC');
}

/** Texto amigable en hora local: «Lunes 20/10 · 07:00». */
function cpnnet_asistente_fmt_session(string $utc): string
{
    $d = (new DateTimeImmutable('@' . cpnnet_asistente_ts($utc)))->setTimezone(cpnnet_asistente_tz());
    return CPNNET_ASISTENTE_WEEKDAYS[(int) $d->format('N')] . ' ' . $d->format('d/m') . ' · ' . $d->format('H:i');
}

function cpnnet_asistente_fmt_date(string $ymd): string
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);
    return $d ? $d->format('d/m/Y') : $ymd;
}

function cpnnet_asistente_money(int $n): string
{
    return '$' . number_format($n, 0, ',', '.');
}

/* ---------- Clases (generadas desde el horario semanal) ---------- */

/** Crea las clases de los próximos días según el horario semanal (idempotente). Devuelve cuántas creó. */
function cpnnet_asistente_generate_sessions(?int $now = null): int
{
    global $wpdb;
    $cfg = cpnnet_asistente_booking_cfg();
    $now = $now ?? cpnnet_asistente_now();
    $tz = cpnnet_asistente_tz();
    $today = (new DateTimeImmutable('@' . $now))->setTimezone($tz)->setTime(0, 0);
    $t = cpnnet_asistente_bt('sessions');
    $created = 0;
    for ($i = 0; $i <= (int) $cfg['window_days']; $i++) {
        $day = $today->modify("+{$i} day");
        foreach ($cfg['schedule'] as $s) {
            if ((int) $s['weekday'] !== (int) $day->format('N') || !preg_match('/^(\d{1,2}):(\d{2})$/', (string) $s['time'], $m)) {
                continue;
            }
            $start = $day->setTime((int) $m[1], (int) $m[2]);
            if ($start->getTimestamp() <= $now) {
                continue;
            }
            $utc = gmdate('Y-m-d H:i:s', $start->getTimestamp());
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE slot_id = %s AND starts_at = %s", (string) $s['id'], $utc));
            if ($exists) {
                continue;
            }
            $wpdb->insert($t, [
                'slot_id'    => (string) $s['id'],
                'starts_at'  => $utc,
                'duration'   => max(1, (int) $s['duration']),
                'title'      => (string) $s['title'],
                'instructor' => (string) ($s['instructor'] ?? ''),
                'capacity'   => max(1, (int) $s['capacity']),
                'booked'     => 0,
                'status'     => 'activa',
            ]);
            $created++;
        }
    }
    return $created;
}

function cpnnet_asistente_session(int $id): ?array
{
    global $wpdb;
    $t = cpnnet_asistente_bt('sessions');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id), ARRAY_A) ?: null;
}

/** Clases futuras dentro de la ventana de reserva (activas), ordenadas por hora. */
function cpnnet_asistente_upcoming_sessions(int $days = 0, ?int $now = null): array
{
    global $wpdb;
    $cfg = cpnnet_asistente_booking_cfg();
    $now = $now ?? cpnnet_asistente_now();
    $days = $days > 0 ? $days : (int) $cfg['window_days'];
    $t = cpnnet_asistente_bt('sessions');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$t} WHERE status = 'activa' AND starts_at > %s AND starts_at <= %s ORDER BY starts_at ASC, id ASC",
        gmdate('Y-m-d H:i:s', $now),
        gmdate('Y-m-d H:i:s', $now + $days * DAY_IN_SECONDS)
    ), ARRAY_A) ?: [];
}

/* ---------- Planes de alumnos ---------- */

/** Registra un pago: crea el plan del alumno y envía la confirmación. Devuelve el id del plan o un WP_Error. */
function cpnnet_asistente_membership_create(int $user_id, string $plan_id, int $amount, string $payment_ref, string $starts_on = '')
{
    global $wpdb;
    $plan = cpnnet_asistente_booking_plan($plan_id);
    if (!$plan) {
        return new WP_Error('plan', 'El plan no existe.');
    }
    $starts_on = preg_match('/^\d{4}-\d{2}-\d{2}$/', $starts_on) ? $starts_on : cpnnet_asistente_local_date(cpnnet_asistente_now());
    $ends_on = (new DateTimeImmutable($starts_on))->modify('+' . max(1, (int) $plan['days'] - 1) . ' day')->format('Y-m-d');
    $wpdb->insert(cpnnet_asistente_bt('memberships'), [
        'user_id'       => $user_id,
        'plan_id'       => (string) $plan['id'],
        'plan_name'     => (string) $plan['name'],
        'credits_total' => max(1, (int) $plan['classes']),
        'credits_used'  => 0,
        'starts_on'     => $starts_on,
        'ends_on'       => $ends_on,
        'amount'        => max(0, $amount),
        'payment_ref'   => substr($payment_ref, 0, 120),
        'status'        => 'activo',
        'created_at'    => gmdate('Y-m-d H:i:s', cpnnet_asistente_now()),
    ]);
    $id = (int) $wpdb->insert_id;
    cpnnet_asistente_mail_payment($id);
    return $id;
}

function cpnnet_asistente_membership(int $id): ?array
{
    global $wpdb;
    $t = cpnnet_asistente_bt('memberships');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id), ARRAY_A) ?: null;
}

/** Planes activos del alumno (vigentes hoy o que parten después), el que vence primero arriba. */
function cpnnet_asistente_memberships_of(int $user_id): array
{
    global $wpdb;
    $t = cpnnet_asistente_bt('memberships');
    $today = cpnnet_asistente_local_date(cpnnet_asistente_now());
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$t} WHERE user_id = %d AND status = 'activo' AND ends_on >= %s ORDER BY ends_on ASC, id ASC",
        $user_id,
        $today
    ), ARRAY_A) ?: [];
}

/** Plan con clases disponibles que cubre la fecha de la clase. */
function cpnnet_asistente_membership_for(int $user_id, string $session_utc): ?array
{
    $date = cpnnet_asistente_local_date(cpnnet_asistente_ts($session_utc));
    foreach (cpnnet_asistente_memberships_of($user_id) as $m) {
        if ($m['starts_on'] <= $date && $m['ends_on'] >= $date && (int) $m['credits_used'] < (int) $m['credits_total']) {
            return $m;
        }
    }
    return null;
}

/* ---------- Reservas ---------- */

function cpnnet_asistente_booking_row(int $id): ?array
{
    global $wpdb;
    $t = cpnnet_asistente_bt('bookings');
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id = %d", $id), ARRAY_A) ?: null;
}

/** Próximas reservas vigentes del alumno, con los datos de la clase. */
function cpnnet_asistente_bookings_of(int $user_id): array
{
    global $wpdb;
    $b = cpnnet_asistente_bt('bookings');
    $s = cpnnet_asistente_bt('sessions');
    return $wpdb->get_results($wpdb->prepare(
        "SELECT b.*, s.starts_at, s.title, s.instructor, s.duration FROM {$b} b JOIN {$s} s ON s.id = b.session_id
         WHERE b.user_id = %d AND b.status = 'reservada' AND s.starts_at > %s ORDER BY s.starts_at ASC",
        $user_id,
        gmdate('Y-m-d H:i:s', cpnnet_asistente_now())
    ), ARRAY_A) ?: [];
}

/** Toma un cupo de forma atómica (no sobrepasa la capacidad aunque dos personas reserven a la vez). */
function cpnnet_asistente_take_seat(int $session_id): bool
{
    global $wpdb;
    $t = cpnnet_asistente_bt('sessions');
    return (int) $wpdb->query($wpdb->prepare("UPDATE {$t} SET booked = booked + 1 WHERE id = %d AND status = 'activa' AND booked < capacity", $session_id)) === 1;
}

function cpnnet_asistente_release_seat(int $session_id): void
{
    global $wpdb;
    $t = cpnnet_asistente_bt('sessions');
    $wpdb->query($wpdb->prepare("UPDATE {$t} SET booked = booked - 1 WHERE id = %d AND booked > 0", $session_id));
}

/** Guarda la reserva (reactiva una cancelada de la misma clase si existía). */
function cpnnet_asistente_save_booking(int $session_id, int $user_id, int $membership_id): int
{
    global $wpdb;
    $t = cpnnet_asistente_bt('bookings');
    $now = gmdate('Y-m-d H:i:s', cpnnet_asistente_now());
    $old = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$t} WHERE session_id = %d AND user_id = %d", $session_id, $user_id), ARRAY_A);
    if ($old) {
        $wpdb->update($t, ['status' => 'reservada', 'membership_id' => $membership_id, 'updated_at' => $now], ['id' => (int) $old['id']]);
        return (int) $old['id'];
    }
    $wpdb->insert($t, ['session_id' => $session_id, 'user_id' => $user_id, 'membership_id' => $membership_id, 'status' => 'reservada', 'created_at' => $now]);
    return (int) $wpdb->insert_id;
}

/** Valida que la clase se pueda reservar. Devuelve la fila o un WP_Error. */
function cpnnet_asistente_bookable(int $session_id, int $user_id)
{
    global $wpdb;
    $s = cpnnet_asistente_session($session_id);
    $now = cpnnet_asistente_now();
    $cfg = cpnnet_asistente_booking_cfg();
    if (!$s || $s['status'] !== 'activa') {
        return new WP_Error('clase', 'La clase no existe o fue cancelada.');
    }
    $ts = cpnnet_asistente_ts($s['starts_at']);
    if ($ts <= $now) {
        return new WP_Error('pasada', 'La clase ya comenzó.');
    }
    if ($ts > $now + (int) $cfg['window_days'] * DAY_IN_SECONDS) {
        return new WP_Error('ventana', 'Esa clase aún no está abierta para reservas.');
    }
    $t = cpnnet_asistente_bt('bookings');
    if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE session_id = %d AND user_id = %d AND status = 'reservada'", $session_id, $user_id))) {
        return new WP_Error('repetida', 'Ya tienes reservada esa clase.');
    }
    return $s;
}

/** Reserva una clase para el alumno. Devuelve el id de la reserva o un WP_Error. */
function cpnnet_asistente_book(int $user_id, int $session_id)
{
    global $wpdb;
    $s = cpnnet_asistente_bookable($session_id, $user_id);
    if (is_wp_error($s)) {
        return $s;
    }
    $m = cpnnet_asistente_membership_for($user_id, $s['starts_at']);
    if (!$m) {
        return new WP_Error('plan', 'No tienes un plan vigente con clases disponibles para esa fecha.');
    }
    if (!cpnnet_asistente_take_seat($session_id)) {
        return new WP_Error('cupos', 'La clase ya no tiene cupos.');
    }
    $mt = cpnnet_asistente_bt('memberships');
    $ok = (int) $wpdb->query($wpdb->prepare("UPDATE {$mt} SET credits_used = credits_used + 1 WHERE id = %d AND credits_used < credits_total", (int) $m['id'])) === 1;
    if (!$ok) {
        cpnnet_asistente_release_seat($session_id);
        return new WP_Error('plan', 'Tu plan ya no tiene clases disponibles.');
    }
    $id = cpnnet_asistente_save_booking($session_id, $user_id, (int) $m['id']);
    cpnnet_asistente_mail_booking($id, 'reserva');
    return $id;
}

/** ¿Se puede cancelar o cambiar todavía? (hasta X horas antes; el estudio siempre puede). */
function cpnnet_asistente_can_cancel(array $booking_with_session, bool $staff = false): bool
{
    if ($staff) {
        return true;
    }
    $limit = (int) cpnnet_asistente_booking_cfg()['cancel_hours'] * HOUR_IN_SECONDS;
    return cpnnet_asistente_ts($booking_with_session['starts_at']) - cpnnet_asistente_now() >= $limit;
}

function cpnnet_asistente_booking_with_session(int $booking_id): ?array
{
    global $wpdb;
    $b = cpnnet_asistente_bt('bookings');
    $s = cpnnet_asistente_bt('sessions');
    return $wpdb->get_row($wpdb->prepare(
        "SELECT b.*, s.starts_at, s.title, s.instructor, s.duration FROM {$b} b JOIN {$s} s ON s.id = b.session_id WHERE b.id = %d",
        $booking_id
    ), ARRAY_A) ?: null;
}

/** Cancela una reserva y devuelve la clase al plan. */
function cpnnet_asistente_cancel(int $user_id, int $booking_id, bool $staff = false, bool $notify = true)
{
    global $wpdb;
    $b = cpnnet_asistente_booking_with_session($booking_id);
    if (!$b || $b['status'] !== 'reservada' || (!$staff && (int) $b['user_id'] !== $user_id)) {
        return new WP_Error('reserva', 'La reserva no existe.');
    }
    if (!cpnnet_asistente_can_cancel($b, $staff)) {
        return new WP_Error('plazo', 'Ya no se puede cancelar: el plazo es hasta ' . (int) cpnnet_asistente_booking_cfg()['cancel_hours'] . ' horas antes de la clase.');
    }
    $wpdb->update(cpnnet_asistente_bt('bookings'), ['status' => 'cancelada', 'updated_at' => gmdate('Y-m-d H:i:s', cpnnet_asistente_now())], ['id' => $booking_id]);
    cpnnet_asistente_release_seat((int) $b['session_id']);
    $mt = cpnnet_asistente_bt('memberships');
    $wpdb->query($wpdb->prepare("UPDATE {$mt} SET credits_used = credits_used - 1 WHERE id = %d AND credits_used > 0", (int) $b['membership_id']));
    if ($notify) {
        cpnnet_asistente_mail_booking($booking_id, 'cancelacion');
    }
    return true;
}

/** Cambia una reserva a otra clase (mismo plan; no gasta otra clase). */
function cpnnet_asistente_change(int $user_id, int $booking_id, int $new_session_id)
{
    global $wpdb;
    $b = cpnnet_asistente_booking_with_session($booking_id);
    if (!$b || $b['status'] !== 'reservada' || (int) $b['user_id'] !== $user_id) {
        return new WP_Error('reserva', 'La reserva no existe.');
    }
    if (!cpnnet_asistente_can_cancel($b)) {
        return new WP_Error('plazo', 'Ya no se puede cambiar: el plazo es hasta ' . (int) cpnnet_asistente_booking_cfg()['cancel_hours'] . ' horas antes de la clase.');
    }
    $s = cpnnet_asistente_bookable($new_session_id, $user_id);
    if (is_wp_error($s)) {
        return $s;
    }
    $m = cpnnet_asistente_membership((int) $b['membership_id']);
    $date = cpnnet_asistente_local_date(cpnnet_asistente_ts($s['starts_at']));
    if (!$m || $m['status'] !== 'activo' || $m['starts_on'] > $date || $m['ends_on'] < $date) {
        return new WP_Error('plan', 'Tu plan no cubre la fecha de esa clase.');
    }
    if (!cpnnet_asistente_take_seat($new_session_id)) {
        return new WP_Error('cupos', 'La clase ya no tiene cupos.');
    }
    $wpdb->update(cpnnet_asistente_bt('bookings'), ['status' => 'cancelada', 'updated_at' => gmdate('Y-m-d H:i:s', cpnnet_asistente_now())], ['id' => $booking_id]);
    cpnnet_asistente_release_seat((int) $b['session_id']);
    $id = cpnnet_asistente_save_booking($new_session_id, $user_id, (int) $m['id']);
    cpnnet_asistente_mail_booking($id, 'cambio', $b);
    return $id;
}

/** El estudio cancela una clase completa: se anulan las reservas, se devuelven las clases y se avisa a cada alumno. */
function cpnnet_asistente_cancel_session(int $session_id): int
{
    global $wpdb;
    $t = cpnnet_asistente_bt('bookings');
    $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$t} WHERE session_id = %d AND status = 'reservada'", $session_id)) ?: [];
    foreach ($ids as $id) {
        cpnnet_asistente_cancel(0, (int) $id, true, false);
        cpnnet_asistente_mail_booking((int) $id, 'clase_cancelada');
    }
    $wpdb->update(cpnnet_asistente_bt('sessions'), ['status' => 'cancelada'], ['id' => $session_id]);
    return count($ids);
}

/* ---------- Avisos programados (WP-Cron, cada hora) ---------- */

/** Marca un aviso como enviado; devuelve false si ya se había enviado (evita duplicados). */
function cpnnet_asistente_notice_once(string $type, int $ref): bool
{
    global $wpdb;
    $t = cpnnet_asistente_bt('notices');
    if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$t} WHERE type = %s AND ref_id = %d", $type, $ref))) {
        return false;
    }
    $wpdb->insert($t, ['type' => $type, 'ref_id' => $ref, 'sent_at' => gmdate('Y-m-d H:i:s', cpnnet_asistente_now())]);
    return true;
}

/** Tarea periódica: genera clases, envía recordatorios de clase y de plan por vencer, cierra planes vencidos. */
function cpnnet_asistente_booking_tick(): array
{
    global $wpdb;
    $cfg = cpnnet_asistente_booking_cfg();
    $now = cpnnet_asistente_now();
    $out = ['clases_creadas' => cpnnet_asistente_generate_sessions($now), 'recordatorios' => 0, 'planes_por_vencer' => 0, 'planes_vencidos' => 0];

    $b = cpnnet_asistente_bt('bookings');
    $s = cpnnet_asistente_bt('sessions');
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT b.id FROM {$b} b JOIN {$s} s ON s.id = b.session_id WHERE b.status = 'reservada' AND s.status = 'activa' AND s.starts_at > %s AND s.starts_at <= %s",
        gmdate('Y-m-d H:i:s', $now),
        gmdate('Y-m-d H:i:s', $now + (int) $cfg['reminder_hours'] * HOUR_IN_SECONDS)
    ), ARRAY_A) ?: [];
    foreach ($rows as $r) {
        if (cpnnet_asistente_notice_once('recordatorio_clase', (int) $r['id'])) {
            cpnnet_asistente_mail_booking((int) $r['id'], 'recordatorio');
            $out['recordatorios']++;
        }
    }

    $mt = cpnnet_asistente_bt('memberships');
    $today = cpnnet_asistente_local_date($now);
    $limit = (new DateTimeImmutable($today))->modify('+' . (int) $cfg['expiry_days'] . ' day')->format('Y-m-d');
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$mt} WHERE status = 'activo' AND ends_on >= %s AND ends_on <= %s", $today, $limit), ARRAY_A) ?: [];
    foreach ($rows as $r) {
        if (cpnnet_asistente_notice_once('plan_por_vencer', (int) $r['id'])) {
            cpnnet_asistente_mail_expiry((int) $r['id']);
            $out['planes_por_vencer']++;
        }
    }
    $out['planes_vencidos'] = (int) $wpdb->query($wpdb->prepare("UPDATE {$mt} SET status = 'vencido' WHERE status = 'activo' AND ends_on < %s", $today));
    return $out;
}

/* ---------- Herramienta del asistente: ver_horarios ---------- */

function cpnnet_asistente_booking_tool(): array
{
    return [
        'name'        => 'ver_horarios',
        'description' => 'Consulta las clases de los próximos días con sus cupos disponibles (datos reales del sistema de reservas). '
            . 'Úsala cuando pregunten por horarios, cupos o disponibilidad. No sirve para reservar: las reservas las hace el alumno en su perfil.',
        'inputSchema' => [
            'type'       => 'object',
            'properties' => [
                'dias' => ['type' => 'integer', 'description' => 'Cuántos días hacia adelante (1 a 7). Por defecto 3.'],
            ],
        ],
    ];
}

function cpnnet_asistente_run_booking_tool(array $input): array
{
    $cfg = cpnnet_asistente_booking_cfg();
    $days = max(1, min(7, (int) ($input['dias'] ?? 3)));
    $rows = cpnnet_asistente_upcoming_sessions($days);
    $lines = [];
    foreach ($rows as $r) {
        $free = max(0, (int) $r['capacity'] - (int) $r['booked']);
        $lines[] = cpnnet_asistente_fmt_session($r['starts_at']) . ' — ' . $r['title'] . ($r['instructor'] ? ' (' . $r['instructor'] . ')' : '') . ' — ' . ($free > 0 ? $free . ' cupos' : 'completa');
    }
    $text = $lines ? implode("\n", $lines) : 'No hay clases programadas en esos días.';
    $text .= "\nReservas, cambios y cancelaciones: en el perfil del alumno, " . cpnnet_asistente_portal_url() . ' (cancelación hasta ' . (int) $cfg['cancel_hours'] . ' h antes).';
    if (!empty($cfg['example'])) {
        $text = "ATENCIÓN: el estudio aún no confirma este horario (es de ejemplo). No lo presentes como definitivo; ofrece que recepción confirme.\n" . $text;
    }
    return [$text, null, false];
}

/* ---------- Registro en WordPress ---------- */

if (cpnnet_asistente_booking_enabled()) {
    add_action('init', 'cpnnet_asistente_booking_ensure_role');
    add_action('cpnnet_asistente_booking_tick', 'cpnnet_asistente_booking_tick');
    add_action('init', function () {
        if (function_exists('wp_next_scheduled') && !wp_next_scheduled('cpnnet_asistente_booking_tick')) {
            wp_schedule_event(time() + 60, 'hourly', 'cpnnet_asistente_booking_tick');
        }
    });
    // Los alumnos no ven la barra ni el escritorio de WordPress: su lugar es el perfil.
    add_filter('show_admin_bar', function ($show) {
        return current_user_can('cpnnet_asistente_leads') || current_user_can('edit_posts') ? $show : false;
    });
}
