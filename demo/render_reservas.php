<?php
// Genera la demo del módulo de reservas con las pantallas REALES del plugin (perfil del alumno, correos, panel del estudio)
// y datos de ejemplo. Uso: demo/build_reservas.sh  (PLUGIN_DIR = carpeta de la variante con "modules": ["booking"])
require __DIR__.'/common.php'; mt_srand(7);
$out = $argv[1] ?? (__DIR__.'/reservas.html');
$db = $GLOBALS['wpdb'];
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, slot_id TEXT DEFAULT '', starts_at TEXT NOT NULL, duration INT DEFAULT 50, title TEXT DEFAULT '', instructor TEXT DEFAULT '', capacity INT DEFAULT 1, booked INT DEFAULT 0, status TEXT DEFAULT 'activa', UNIQUE(slot_id, starts_at))");
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_memberships (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INT NOT NULL, plan_id TEXT DEFAULT '', plan_name TEXT DEFAULT '', credits_total INT DEFAULT 0, credits_used INT DEFAULT 0, starts_on TEXT NOT NULL, ends_on TEXT NOT NULL, amount INT DEFAULT 0, payment_ref TEXT DEFAULT '', status TEXT DEFAULT 'activo', created_at TEXT NOT NULL)");
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_bookings (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INT NOT NULL, user_id INT NOT NULL, membership_id INT NOT NULL, status TEXT DEFAULT 'reservada', created_at TEXT NOT NULL, updated_at TEXT, UNIQUE(session_id, user_id))");
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_notices (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT NOT NULL, ref_id INT NOT NULL, sent_at TEXT NOT NULL, UNIQUE(type, ref_id))");
$GLOBALS['O']['cpnnet_asistente']['visibility'] = 'public'; $GLOBALS['O']['cpnnet_asistente']['whatsapp'] = '56900000000';
// Instructoras de ejemplo en el horario
$cfg = cpnnet_asistente_booking_cfg(); $ins = ['Camila', 'Fernanda', 'Josefa', 'Antonia'];
foreach ($cfg['schedule'] as $i => &$s) { $s['instructor'] = $ins[$i % 4]; } unset($s);
$GLOBALS['O'][CPNNET_ASISTENTE_BOOKING_OPTION] = $cfg;
cpnnet_asistente_generate_sessions();
$people = ['Camila Soto', 'Daniela Muñoz', 'Valentina Ríos', 'Francisca León', 'Josefina Paz', 'Martina Rojas', 'Isidora Vera', 'Catalina Mora', 'Antonia Fuentes', 'Florencia Díaz', 'Trinidad Silva', 'Javiera Pino', 'Rodrigo Lagos', 'Tomás Ibarra', 'Sofía Herrera', 'Paula Reyes'];
$plans = ['plan-8', 'plan-12', 'plan-16', 'plan-12', 'plan-8', 'plan-4'];
$ids = [];
foreach ($people as $i => $p) {
    $email = strtolower(strtr(explode(' ', $p)[0], 'áéíóúñ', 'aeioun')) . '.' . strtolower(strtr(explode(' ', $p)[1], 'áéíóúñ', 'aeioun')) . '@mail.cl';
    $uid = wp_insert_user(['user_email' => $email, 'first_name' => explode(' ', $p)[0], 'display_name' => $p]);
    $plan = cpnnet_asistente_booking_plan($plans[$i % 6]);
    $start = cpnnet_asistente_local_date(time() - mt_rand(0, 20) * 86400);
    cpnnet_asistente_membership_create($uid, $plan['id'], (int) $plan['price'], 'Transferencia ' . (1000 + $i), $start);
    $ids[] = $uid;
}
$sessions = cpnnet_asistente_upcoming_sessions(7);
foreach ($ids as $k => $uid) { $picks = array_rand($sessions, 3); foreach ($picks as $j) { cpnnet_asistente_book($uid, (int) $sessions[$j]['id']); } }
// Una clase llena, para que se vea «Completa»
$full = $sessions[4]; $db->pdo->exec("UPDATE wp_cpnnet_asistente_sessions SET booked = capacity WHERE id = " . (int) $full['id']);
$GLOBALS['MAILS'] = [];

// --- correos de ejemplo (se generan con las funciones reales) ---
$camila = $ids[0];
$m = cpnnet_asistente_memberships_of($camila)[0];
cpnnet_asistente_mail_payment((int) $m['id']);
$mine = cpnnet_asistente_bookings_of($camila);
cpnnet_asistente_mail_booking((int) $mine[0]['id'], 'reserva');
cpnnet_asistente_mail_booking((int) $mine[0]['id'], 'recordatorio');
$prev = $mine[1];
cpnnet_asistente_mail_booking((int) $mine[1]['id'], 'cambio', ['starts_at' => gmdate('Y-m-d H:i:s', cpnnet_asistente_ts($prev['starts_at']) - 86400)]);
cpnnet_asistente_mail_expiry((int) $m['id']);
cpnnet_asistente_mail_booking((int) $mine[2]['id'], 'clase_cancelada');
$mails = $GLOBALS['MAILS'];

// --- pantallas ---
$GLOBALS['UID'] = $camila;
cpnnet_asistente_portal_flash($camila, ['ok', '¡Listo! Tu clase quedó reservada. Te enviamos la confirmación por correo.']);
$portal = cpnnet_asistente_portal_render();
$_GET = ['cambiar' => (int) $mine[1]['id']]; $portal_change = cpnnet_asistente_portal_render(); $_GET = [];
$GLOBALS['UID'] = 0; $portal_login = cpnnet_asistente_portal_render();
$cap = function ($fn) { $_GET = []; ob_start(); $fn(); return ob_get_clean(); };
$agenda = $cap('cpnnet_asistente_page_agenda');
$alumnos = $cap('cpnnet_asistente_page_alumnos');
$config = $cap('cpnnet_asistente_page_reservas_cfg');

$b = cpnnet_asistente_brand(); $c = $b['colors'];
$b64 = fn($f, $mime) => "data:$mime;base64," . base64_encode(file_get_contents(CPNNET_ASISTENTE_DIR . 'assets/' . $f));
$fonts = fn($css) => preg_replace_callback('#url\("fonts/(montserrat-latin-\d+-normal\.woff2)"\)#', fn($x) => 'url("' . $b64('fonts/' . $x[1], 'font/woff2') . '")', $css);
$logoW = $b64('img/' . $b['logo_white'], 'image/png'); $logo = $b64('img/' . $b['logo'], 'image/png');
$fix = fn($h) => str_replace(['ASSET/assets/img/' . $b['logo_white'], 'ASSET/assets/img/' . $b['logo']], [$logoW, $logo], $h);
$portal_css = $fonts(file_get_contents(CPNNET_ASISTENTE_DIR . 'assets/portal.css')) . cpnnet_asistente_portal_css();
$admin_css = $fonts(file_get_contents(CPNNET_ASISTENTE_DIR . 'assets/admin.css')) . cpnnet_asistente_brand_css();
$mail_tabs = ''; $mail_btns = '';
foreach ($mails as $i => $mm) {
    $mail_btns .= '<button type="button" data-mail="' . $i . '"' . ($i ? '' : ' class="on"') . '>' . esc_html(preg_replace('/ · .*$/', '', $mm['subject'])) . '</button>';
    $mail_tabs .= '<div class="mail" data-mail="' . $i . '"' . ($i ? ' hidden' : '') . '><div class="mhead"><b>Para:</b> ' . esc_html($mm['to']) . '<br><b>Asunto:</b> ' . esc_html($mm['subject']) . '</div><iframe srcdoc="' . esc_attr($fix($mm['body'])) . '"></iframe></div>';
}
$name = esc_html($b['name']);
$html = <<<HTML
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Demo · Reservas {$name}</title>
<style>
{$portal_css}
{$admin_css}
*{box-sizing:border-box}body{margin:0;background:{$c['bg']};font:14px/1.5 "Montserrat CPN",Montserrat,-apple-system,"Segoe UI",Roboto,sans-serif;color:{$c['ink']}}
.top{background:linear-gradient(135deg,{$c['primary']},{$c['dark']});color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.top img{height:42px;display:block}.top .t{font-weight:600}.badge{background:#fff;color:{$c['dark']};border-radius:999px;padding:5px 12px;font-size:12px;font-weight:700}
nav.tabs{display:flex;gap:6px;flex-wrap:wrap;padding:16px 24px 0;max-width:1240px;margin:0 auto}
nav.tabs button,.mailnav button{border:1px solid {$c['line']};background:#fff;border-radius:999px;padding:9px 16px;font:600 13px inherit;font-family:inherit;color:{$c['ink']};cursor:pointer}
nav.tabs button.on,.mailnav button.on{background:{$c['primary']};border-color:{$c['primary']};color:#fff}
main{max-width:1240px;margin:0 auto;padding:18px 24px 40px}
.intro{color:{$c['mut']};margin:0 0 16px;max-width:820px}
.site{background:#fff;border-radius:18px;padding:26px;border:1px solid {$c['line']}}
.site .fake{display:flex;gap:18px;align-items:center;border-bottom:1px solid {$c['line']};padding-bottom:14px;margin-bottom:22px;color:{$c['mut']};font-size:13px}
.site .fake img{height:28px}
.duo{display:grid;grid-template-columns:1fr 1fr;gap:18px}@media(max-width:900px){.duo{grid-template-columns:1fr}}
.mailwrap{display:grid;grid-template-columns:240px 1fr;gap:18px}@media(max-width:800px){.mailwrap{grid-template-columns:1fr}}
.mailnav{display:flex;flex-direction:column;gap:8px}.mailnav button{text-align:left;border-radius:12px}
.mail .mhead{background:#fff;border:1px solid {$c['line']};border-radius:12px 12px 0 0;padding:10px 14px;font-size:13px}
.mail iframe{width:100%;height:760px;border:1px solid {$c['line']};border-top:0;border-radius:0 0 12px 12px;background:#fff}
.wpadmin{background:#f0f0f1;border-radius:18px;padding:6px 20px 20px;border:1px solid {$c['line']}}
.wpadmin .button{display:inline-block;border:1px solid #2271b1;border-radius:3px;padding:3px 11px;color:#2271b1;background:#f6f7f7;text-decoration:none;font-size:13px;cursor:pointer;line-height:2}
.wpadmin .button-primary{background:{$c['primary']};border-color:{$c['primary']};color:#fff}.wpadmin .button-link-delete{color:#b32d2e;border-color:#b32d2e}
.wpadmin textarea,.wpadmin input,.wpadmin select{border:1px solid #c3c4c7;border-radius:6px;padding:6px 8px;font:inherit}.wpadmin .large-text{width:100%}.wpadmin .form-table th{text-align:left;padding:10px 12px 10px 0;width:240px;font-weight:600}
.note{font-size:12px;color:{$c['mut']};margin-top:18px}
@media(max-width:640px){nav.tabs{flex-wrap:nowrap;overflow-x:auto;padding:12px 16px 4px;-webkit-overflow-scrolling:touch;scrollbar-width:none}nav.tabs button{flex:none}main{padding:14px 12px 30px}.site{padding:14px}.site .fake{display:none}.wpadmin{padding:4px 8px 12px;overflow-x:auto}.top{padding:12px 16px}.top .t{display:none}.mail iframe{height:620px}}
</style></head><body>
<div class="top"><div style="display:flex;align-items:center;gap:14px"><img src="{$logoW}" alt="{$name}"><span class="t">Reservas, perfil del alumno y correos</span></div><span class="badge">DEMO CON DATOS DE EJEMPLO</span></div>
<nav class="tabs"><button class="on" data-tab="perfil">Perfil del alumno</button><button data-tab="cambio">Cambiar una clase</button><button data-tab="correos">Correos automáticos</button><button data-tab="agenda">Panel: Agenda</button><button data-tab="alumnos">Panel: Alumnos y pagos</button><button data-tab="config">Panel: Configuración</button><button data-tab="acceso">Acceso</button></nav>
<main>
<section data-tab="perfil"><p class="intro">Lo que ve una alumna en el sitio (página «Mi perfil»): su plan con las clases disponibles y el vencimiento, sus próximas clases (cambiar o cancelar hasta 12 h antes) y el horario con cupos en tiempo real.</p><div class="site"><div class="fake"><img src="{$logo}" alt=""> Inicio · Planes · <b>Mi perfil</b></div>{$fix($portal)}</div></section>
<section data-tab="cambio" hidden><p class="intro">Al tocar «Cambiar», la alumna elige otro horario con cupo. No gasta otra clase y libera el cupo anterior.</p><div class="site">{$fix($portal_change)}</div></section>
<section data-tab="correos" hidden><p class="intro">Correos que el sistema envía solo, con la marca del estudio: pago confirmado, reserva, recordatorio de clase, cambio, plan por vencer y clase cancelada por el estudio.</p><div class="mailwrap"><div class="mailnav">{$mail_btns}</div><div>{$mail_tabs}</div></div></section>
<section data-tab="agenda" hidden><p class="intro">Recepción ve la ocupación de cada clase y quiénes vienen. Si cancela una clase, el sistema avisa a cada alumna y le devuelve la clase.</p><div class="wpadmin">{$fix($agenda)}</div></section>
<section data-tab="alumnos" hidden><p class="intro">Recepción registra el pago (transferencia, efectivo, etc.): se activa el plan, se crea el acceso de la alumna y le llega la confirmación por correo.</p><div class="wpadmin">{$fix($alumnos)}</div></section>
<section data-tab="config" hidden><p class="intro">Planes, horario semanal (una línea por clase), plazos y avisos. Las clases se crean solas para las próximas semanas.</p><div class="wpadmin">{$fix($config)}</div></section>
<section data-tab="acceso" hidden><p class="intro">Sin sesión, la página muestra el acceso. La primera vez, la alumna crea su contraseña con el enlace que le llega por correo.</p><div class="site">{$fix($portal_login)}</div></section>
<p class="note">Demo con datos ficticios. Horario, cupos e instructoras son de ejemplo hasta que el estudio los confirme.</p>
</main>
<script>
document.querySelectorAll('nav.tabs button').forEach(function(b){b.onclick=function(){document.querySelectorAll('nav.tabs button').forEach(function(x){x.classList.toggle('on',x===b)});document.querySelectorAll('main>section').forEach(function(s){s.hidden=s.dataset.tab!==b.dataset.tab});scrollTo(0,0)}});
document.querySelectorAll('.mailnav button').forEach(function(b){b.onclick=function(){document.querySelectorAll('.mailnav button').forEach(function(x){x.classList.toggle('on',x===b)});document.querySelectorAll('.mail').forEach(function(m){m.hidden=m.dataset.mail!==b.dataset.mail})}});
document.querySelectorAll('form').forEach(function(f){f.addEventListener('submit',function(e){e.preventDefault();alert('Demo: en el sitio real esta acción se ejecuta.')})});
document.querySelectorAll('main a').forEach(function(a){a.addEventListener('click',function(e){e.preventDefault()})});
</script></body></html>
HTML;
file_put_contents($out, $html);
echo basename($out) . ' ' . round(strlen($html) / 1024) . " KB\n";
