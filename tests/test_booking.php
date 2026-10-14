<?php
// Módulo de reservas (variantes con "modules": ["booking"], p. ej. pilates). Uso: PLUGIN_DIR=pilates-asistente php test_booking.php
require __DIR__.'/common.php';
if (!cpnnet_asistente_booking_enabled()) { echo "-- esta variante no tiene el módulo de reservas\n"; exit(0); }
$db = $GLOBALS['wpdb'];
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_sessions (id INTEGER PRIMARY KEY AUTOINCREMENT, slot_id TEXT DEFAULT '', starts_at TEXT NOT NULL, duration INT DEFAULT 50, title TEXT DEFAULT '', instructor TEXT DEFAULT '', capacity INT DEFAULT 1, booked INT DEFAULT 0, status TEXT DEFAULT 'activa', UNIQUE(slot_id, starts_at))");
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_memberships (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INT NOT NULL, plan_id TEXT DEFAULT '', plan_name TEXT DEFAULT '', credits_total INT DEFAULT 0, credits_used INT DEFAULT 0, starts_on TEXT NOT NULL, ends_on TEXT NOT NULL, amount INT DEFAULT 0, payment_ref TEXT DEFAULT '', status TEXT DEFAULT 'activo', created_at TEXT NOT NULL)");
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_bookings (id INTEGER PRIMARY KEY AUTOINCREMENT, session_id INT NOT NULL, user_id INT NOT NULL, membership_id INT NOT NULL, status TEXT DEFAULT 'reservada', created_at TEXT NOT NULL, updated_at TEXT, UNIQUE(session_id, user_id))");
$db->pdo->exec("CREATE TABLE wp_cpnnet_asistente_notices (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT NOT NULL, ref_id INT NOT NULL, sent_at TEXT NOT NULL, UNIQUE(type, ref_id))");
$mails = function () { $m = $GLOBALS['MAILS']; $GLOBALS['MAILS'] = []; return $m; };
$cnt = fn($sql) => (int) $db->get_var($sql);

// --- configuración desde booking.json de la variante ---
$cfg = cpnnet_asistente_booking_cfg();
ok('planes y horario de la variante cargados', count($cfg['plans']) >= 3 && count($cfg['schedule']) >= 5);
ok('los datos de ejemplo quedan marcados como tales', $cfg['example'] === true);

// --- generación de clases desde el horario semanal ---
$n1 = cpnnet_asistente_generate_sessions();
$n2 = cpnnet_asistente_generate_sessions();
ok('se generan clases para la ventana de reservas', $n1 > 20);
ok('generar de nuevo no duplica clases', $n2 === 0);
$s = $db->get_row("SELECT * FROM wp_cpnnet_asistente_sessions ORDER BY starts_at LIMIT 1", ARRAY_A);
$local = (new DateTimeImmutable('@' . cpnnet_asistente_ts($s['starts_at'])))->setTimezone(new DateTimeZone('America/Santiago'));
$slot = array_values(array_filter($cfg['schedule'], fn($x) => $x['id'] === $s['slot_id']))[0];
ok('la clase queda a la hora local del horario (guardada en UTC)', $local->format('H:i') === $slot['time'] && (int) $local->format('N') === (int) $slot['weekday']);

// --- texto editable de planes y horario ---
$bad = [];
$plans = cpnnet_asistente_text_to_plans("plan-x | Plan X | 8 | 30 | \$60.000\nmal escrito\n", $bad);
ok('planes: se leen las líneas válidas y se informan las malas', count($plans) === 1 && $plans[0]['price'] === 60000 && $bad === [2]);
$bad = [];
$sch = cpnnet_asistente_text_to_schedule("Miércoles | 7:00 | 50 | Pilates | 8 | Ana\nSábado | 10:00 | 50 | Pilates | 6\nLunes | 25:00 | 50 | X | 8", $bad);
ok('horario: acepta tildes y horas de un dígito; rechaza horas inválidas', count($sch) === 2 && $sch[0]['weekday'] === 3 && $sch[0]['time'] === '07:00' && $bad === [3]);
ok('horario: ida y vuelta texto ↔ datos', cpnnet_asistente_text_to_schedule(cpnnet_asistente_schedule_to_text($sch)) == $sch);

// --- alumnos, pago y plan ---
$GLOBALS['O']['cpnnet_asistente']['whatsapp'] = '56900000000';
$ana = wp_insert_user(['user_email' => 'ana@mail.cl', 'first_name' => 'Ana', 'display_name' => 'Ana']);
$luis = wp_insert_user(['user_email' => 'luis@mail.cl', 'first_name' => 'Luis', 'display_name' => 'Luis']);
$mid = cpnnet_asistente_membership_create($ana, 'plan-4', 40000, 'TRX-1');
$m = cpnnet_asistente_membership($mid);
$mail = $mails();
ok('registrar pago crea el plan con sus clases y vigencia', $m['credits_total'] == 4 && $m['status'] === 'activo' && $m['ends_on'] === (new DateTimeImmutable($m['starts_on']))->modify('+29 day')->format('Y-m-d'));
ok('correo de pago confirmado al alumno, con plan, monto y comprobante', count($mail) === 1 && $mail[0]['to'] === 'ana@mail.cl' && str_contains($mail[0]['subject'], 'Pago confirmado') && str_contains($mail[0]['body'], '$40.000') && str_contains($mail[0]['body'], 'TRX-1'));
ok('el correo lleva la marca (nombre y color)', str_contains($mail[0]['body'], cpnnet_asistente_brand()['name']) && str_contains($mail[0]['body'], cpnnet_asistente_brand()['colors']['primary']));
ok('plan inexistente: error', is_wp_error(cpnnet_asistente_membership_create($ana, 'no-existe', 1, '')));

// --- reservas con cupos ---
$now = time();
$mk = function ($hours, $cap) use ($db, $now) { $db->insert('wp_cpnnet_asistente_sessions', ['slot_id' => 't' . mt_rand(), 'starts_at' => gmdate('Y-m-d H:i:s', $now + $hours * 3600), 'duration' => 50, 'title' => 'Pilates Interval Fusion', 'instructor' => 'Cami', 'capacity' => $cap, 'booked' => 0, 'status' => 'activa']); return (int) $db->insert_id; };
$s1 = $mk(48, 1); $s2 = $mk(72, 5); $s3 = $mk(5, 5); $s4 = $mk(30, 5);
$b1 = cpnnet_asistente_book($ana, $s1);
ok('reservar: descuenta una clase del plan y ocupa un cupo', is_int($b1) && cpnnet_asistente_membership($mid)['credits_used'] == 1 && cpnnet_asistente_session($s1)['booked'] == 1);
$mail = $mails();
ok('correo de reserva confirmada con día, hora y clases restantes', count($mail) === 1 && str_contains($mail[0]['subject'], 'Reserva confirmada') && str_contains($mail[0]['body'], '3 de 4'));
ok('no se puede reservar dos veces la misma clase', is_wp_error(cpnnet_asistente_book($ana, $s1)));
ok('sin plan no se puede reservar', is_wp_error($e = cpnnet_asistente_book($luis, $s2)) && $e->code === 'plan');
cpnnet_asistente_membership_create($luis, 'plan-8', 60000, 'TRX-2'); $mails();
ok('clase llena: no deja reservar y no gasta clase', is_wp_error($e = cpnnet_asistente_book($luis, $s1)) && $e->code === 'cupos' && (int) $db->get_var("SELECT credits_used FROM wp_cpnnet_asistente_memberships WHERE user_id = $luis") === 0);
ok('el cupo nunca supera la capacidad', cpnnet_asistente_session($s1)['booked'] == 1);

// --- cambio de clase ---
$b2 = cpnnet_asistente_change($ana, $b1, $s2);
ok('cambiar: mueve la reserva sin gastar otra clase y libera el cupo anterior', is_int($b2) && cpnnet_asistente_membership($mid)['credits_used'] == 1 && cpnnet_asistente_session($s1)['booked'] == 0 && cpnnet_asistente_session($s2)['booked'] == 1);
$mail = $mails();
ok('correo de cambio con el horario anterior', count($mail) === 1 && str_contains($mail[0]['subject'], 'Cambio de clase') && str_contains($mail[0]['body'], 'Antes'));
ok('ahora el otro alumno sí puede tomar el cupo liberado', is_int(cpnnet_asistente_book($luis, $s1))); $mails();

// --- plazos de cancelación ---
$b3 = cpnnet_asistente_book($ana, $s3); $mails();
ok('reservar una clase de hoy (dentro del plazo de cancelación)', is_int($b3));
ok('no se puede cancelar a menos de X horas', is_wp_error($e = cpnnet_asistente_cancel($ana, $b3)) && $e->code === 'plazo');
ok('no se puede cambiar a menos de X horas', is_wp_error(cpnnet_asistente_change($ana, $b3, $s4)));
ok('otro alumno no puede cancelar una reserva ajena', is_wp_error(cpnnet_asistente_cancel($luis, $b2)));
$r = cpnnet_asistente_cancel($ana, $b2);
ok('cancelar a tiempo: devuelve la clase y el cupo', $r === true && cpnnet_asistente_membership($mid)['credits_used'] == 1 && cpnnet_asistente_session($s2)['booked'] == 0);
ok('correo de cancelación', str_contains($mails()[0]['subject'] ?? '', 'Reserva cancelada'));
ok('se puede volver a reservar una clase cancelada', is_int(cpnnet_asistente_book($ana, $s2)) && $cnt("SELECT COUNT(*) FROM wp_cpnnet_asistente_bookings WHERE session_id = $s2 AND user_id = $ana") === 1); $mails();

// --- plan agotado ---
cpnnet_asistente_book($ana, $s4); cpnnet_asistente_book($ana, $mk(100, 5)); $mails();
ok('con el plan agotado no se puede reservar', is_wp_error($e = cpnnet_asistente_book($ana, $mk(96, 5))) && $e->code === 'plan' && cpnnet_asistente_membership($mid)['credits_used'] == 4);

// --- recordatorios y vencimientos (tarea programada) ---
$t = cpnnet_asistente_booking_tick();
$mail = $mails();
$rem = array_filter($mail, fn($x) => str_contains($x['subject'], 'Recordatorio'));
ok('recordatorio de clase solo para las clases dentro de las próximas X horas', $t['recordatorios'] === 1 && count($rem) === 1 && reset($rem)['to'] === 'ana@mail.cl');
ok('el recordatorio no se repite', cpnnet_asistente_booking_tick()['recordatorios'] === 0);
$db->pdo->exec("UPDATE wp_cpnnet_asistente_memberships SET ends_on = '" . cpnnet_asistente_local_date(time() + 2 * 86400) . "' WHERE id = $mid");
$t = cpnnet_asistente_booking_tick(); $mail = $mails();
ok('aviso de plan por vencer, una sola vez, con botón para renovar por WhatsApp', $t['planes_por_vencer'] === 1 && str_contains($mail[0]['subject'], 'vence') && str_contains($mail[0]['body'], 'wa.me/56900000000') && cpnnet_asistente_booking_tick()['planes_por_vencer'] === 0);
$db->pdo->exec("UPDATE wp_cpnnet_asistente_memberships SET ends_on = '2020-01-01' WHERE id = $mid");
ok('los planes vencidos se cierran', cpnnet_asistente_booking_tick()['planes_vencidos'] === 1 && cpnnet_asistente_membership($mid)['status'] === 'vencido'); $mails();

// --- el estudio cancela una clase ---
$nc = cpnnet_asistente_cancel_session($s1);
$mail = $mails();
ok('cancelar clase: anula reservas, devuelve clases y avisa a cada alumno', $nc === 1 && cpnnet_asistente_session($s1)['status'] === 'cancelada' && count($mail) === 1 && str_contains($mail[0]['subject'], 'cancelada por el estudio') && (int) $db->get_var("SELECT credits_used FROM wp_cpnnet_asistente_memberships WHERE user_id = $luis") === 0);

// --- herramienta del asistente ---
[$txt] = cpnnet_asistente_run_booking_tool(['dias' => 4]);
ok('ver_horarios: lista clases con cupos y el enlace al perfil', str_contains($txt, 'cupos') && str_contains($txt, cpnnet_asistente_portal_url()));
ok('ver_horarios: avisa que el horario es de ejemplo mientras no se confirme', str_contains($txt, 'de ejemplo'));
ok('ver_horarios: no expone datos de alumnos', !str_contains($txt, 'ana@') && !str_contains($txt, 'Ana'));
$names = array_column(cpnnet_asistente_tools(), 'name');
ok('el asistente tiene la herramienta ver_horarios y el prompt habla de reservas', in_array('ver_horarios', $names, true) && str_contains(cpnnet_asistente_system_prompt(), 'ver_horarios'));

// --- portal del alumno ---
$GLOBALS['UID'] = 0;
ok('portal sin sesión: formulario de acceso', str_contains(cpnnet_asistente_portal_render(), 'loginform'));
$GLOBALS['UID'] = $luis;
$h = cpnnet_asistente_portal_render();
ok('portal: muestra el plan, clases disponibles y el horario con botones', str_contains($h, 'Plan 8 sesiones') && str_contains($h, '8<small> de 8') && str_contains($h, 'Reservar'));
$_POST = ['_wpnonce' => 'n-cpnnet_reservar', 'session' => $s2];
try { cpnnet_asistente_portal_action('cpnnet_reservar'); } catch (RedirectSignal $e) {}
ok('portal: reservar desde el perfil deja un mensaje de éxito', str_contains(cpnnet_asistente_portal_render(), '¡Listo! Tu clase quedó reservada'));
$_POST = ['_wpnonce' => 'malo', 'session' => $s2];
try { cpnnet_asistente_portal_action('cpnnet_reservar'); $x = false; } catch (Exception $e) { $x = str_contains($e->getMessage(), 'die'); }
ok('portal: rechaza acciones sin nonce válido', $x);
$h = cpnnet_asistente_portal_render();
ok('portal: la clase reservada aparece en «Mis próximas clases» con Cambiar/Cancelar', str_contains($h, 'Cancelar') && str_contains($h, 'Cambiar'));
$GLOBALS['UID'] = $luis; $GLOBALS['USERS'][$luis]['first_name'] = '<b>x</b>';
ok('portal: escapa los datos del alumno', !str_contains(cpnnet_asistente_portal_render(), '<b>x</b>'));

// --- panel del estudio ---
$mails(); $_POST = ['_wpnonce' => 'n', 'email' => 'nueva@mail.cl', 'nombre' => 'Sofía', 'plan' => 'prueba', 'monto' => '', 'ref' => 'Clase gratis', 'inicio' => ''];
try { cpnnet_asistente_booking_admin_action('cpnnet_registrar_pago'); } catch (RedirectSignal $e) {}
$u = get_user_by('email', 'nueva@mail.cl');
ok('registrar pago desde el panel crea el alumno (rol alumno) y le envía el acceso', $u && $u->role === 'cpnnet_alumno' && in_array($u->ID, $GLOBALS['NEWUSER'] ?? [], true) && str_contains($GLOBALS['REDIR'], 'msg=paid'));
$mail = $mails();
ok('…y su plan con la confirmación por correo (sin hablar de pago si es gratis)', count(cpnnet_asistente_memberships_of($u->ID)) === 1 && ($mail[0]['to'] ?? '') === 'nueva@mail.cl' && str_contains($mail[0]['subject'], 'activo') && !str_contains($mail[0]['body'], 'recibimos tu pago'));
$_POST = ['_wpnonce' => 'n', 'plans' => "plan-8 | Plan 8 sesiones | 8 | 30 | 60000", 'schedule' => "Lunes | 07:00 | 50 | Pilates Interval Fusion | 8 | Cami\nfoo", 'cancel_hours' => '6', 'reminder_hours' => '3', 'expiry_days' => '5', 'window_days' => '999', 'portal_url' => 'https://kindforcepilates.com/mi-perfil/'];
try { cpnnet_asistente_booking_admin_action('cpnnet_guardar_reservas'); } catch (RedirectSignal $e) {}
$c = cpnnet_asistente_booking_cfg();
ok('guardar configuración: aplica plazos con límites, deja de ser ejemplo e informa líneas malas', $c['cancel_hours'] === 6 && $c['window_days'] === 60 && $c['example'] === false && count($c['schedule']) === 1 && str_contains($GLOBALS['REDIR'], 'bad='));
$GLOBALS['CAPS'] = ['cpnnet_asistente_leads' => true];
try { cpnnet_asistente_booking_admin_action('cpnnet_guardar_reservas'); $x = false; } catch (Exception $e) { $x = str_contains($e->getMessage(), 'die'); }
ok('recepción (sin manage_options) no puede cambiar la configuración', $x);
ob_start(); cpnnet_asistente_page_agenda(); $h = ob_get_clean();
ok('agenda del estudio: ocupación y botón para cancelar clase', str_contains($h, 'Ocupación') && str_contains($h, 'Cancelar clase'));
ob_start(); cpnnet_asistente_page_alumnos(); $h = ob_get_clean();
ok('alumnos y pagos: formulario y tabla de planes', str_contains($h, 'Registrar pago') && str_contains($h, 'Plan 8 sesiones'));

echo "-- {$GLOBALS['n']} verificaciones de reservas\n"; if (($GLOBALS['n'] ?? 0) < 40) { echo "FAIL el script terminó antes de lo previsto\n"; $GLOBALS['fail'] = 1; }
exit($GLOBALS['fail'] ?? 0);
