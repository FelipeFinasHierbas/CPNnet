# Módulo de reservas (planes, clases con cupos, perfil del alumno y correos)

Disponible para las variantes que lo activan en `brand.json` con `"modules": ["booking"]` (hoy: Kind Force Pilates). Todo funciona dentro del WordPress del cliente: no hay servidores externos ni costos por alumno.

## Qué hace
- **Planes:** cada plan tiene un número de clases, días de vigencia y precio (ej. «Plan 8 sesiones · 8 clases · 30 días · $60.000»). La clase de prueba gratuita es un plan de 1 clase a $0.
- **Horario semanal preestablecido:** una línea por clase (día, hora, duración, clase, cupos, instructor/a). El sistema crea solo las clases de las próximas semanas.
- **Perfil del alumno** (página con el shortcode `[asistente_reservas]`):
  - ve su plan, las clases que le quedan y cuándo vence;
  - reserva clases con cupo;
  - cambia una clase por otro horario sin gastar otra clase;
  - cancela hasta X horas antes y la clase vuelve a su plan.
  
  Los cupos se descuentan de forma segura aunque dos personas reserven al mismo tiempo.
- **Panel del estudio:**
  - **Agenda:** ocupación por clase, quiénes vienen y opción de cancelar una clase completa (avisa a cada alumno y le devuelve la clase).
  - **Alumnos y pagos:** registrar un pago activa el plan, crea el acceso del alumno y envía la confirmación por correo.
  - **Configuración de reservas:** planes, horario, plazos y avisos.
- **Correos automáticos con la marca:**
  - pago confirmado (o «tu plan está activo» si es gratis);
  - reserva, cambio y cancelación;
  - clase cancelada por el estudio;
  - recordatorio de clase (X horas antes);
  - plan por vencer (X días antes), con botón para renovar por WhatsApp.
- **Clase de prueba automática:** el asistente consulta los cupos, ofrece 2 o 3 horarios, pide nombre, correo, teléfono y consentimiento, y agenda la clase de prueba. Para eso crea el acceso de la persona, le asigna el plan «Clase de prueba» de 1 clase a $0 y reserva el cupo. La persona recibe un correo con la marca y un enlace para crear su contraseña; el estudio recibe un aviso por correo y la consulta queda en el panel como «calificado». Hay una clase de prueba por correo. No se agenda en casos de salud (lesiones, embarazo), ni mientras el horario sea de ejemplo: en esos casos el asistente deriva. Se activa en *Configuración de reservas → Clase de prueba*.
- **Asistente del chat:** la herramienta `ver_horarios` da los horarios y cupos reales sin datos de alumnos, y el asistente deriva a recepción o al perfil para reservar. Mientras el horario sea «de ejemplo», el asistente no lo presenta como definitivo.

## Puesta en marcha (unos 20 minutos)
1. Instalar y activar el plugin de la variante (crea las tablas, el rol «Alumno/a» y la tarea horaria).
2. **Correo:** instalar un plugin SMTP (por ejemplo WP Mail SMTP) con el correo del dominio. Sin esto, los correos pueden llegar a spam o no salir del hosting.
3. **Tareas programadas:** WP-Cron se activa cuando alguien visita el sitio. Para que los recordatorios salgan a la hora, crear en cPanel → *Cron Jobs* una tarea cada 15 minutos con `wget -q -O - https://SITIO/wp-cron.php?doing_wp_cron >/dev/null 2>&1`.
4. Crear la página «Mi perfil» con el shortcode `[asistente_reservas]` y pegar su dirección en *Configuración de reservas*.
5. Revisar los planes y el horario (vienen de ejemplo), los plazos (cancelación 12 h, recordatorio 12 h, aviso de vencimiento 3 días, reservas a 14 días) y guardar.
6. Registrar el primer pago en *Alumnos y pagos*. El alumno recibe el correo para crear su contraseña y la confirmación de su plan.

## Lo que todavía no hace (comparado con BoxMagic)
- **Pago en línea** (pendiente: el estudio define la pasarela). Hoy, para contratar un plan, el asistente deriva a recepción; recepción registra el pago y el sistema confirma por correo.
- Lista de espera, congelamiento de planes (hoy se hace a mano), múltiples sedes o salas, app móvil propia y reportes de asistencia.
- **Migración desde BoxMagic.** Se pueden importar alumnos y planes vigentes desde una exportación en CSV.

## Detalles técnicos
- **Tablas:** `wp_cpnnet_asistente_sessions`, `_memberships`, `_bookings` y `_notices` (para no repetir avisos). Las fechas de las clases se guardan en UTC; el horario y las vigencias están en la hora del sitio.
- **Cupos y clases:** se descuentan con `UPDATE … WHERE booked < capacity` / `credits_used < credits_total`, que son operaciones atómicas.
- **Pruebas:** `tests/test_booking.php`, con 50 verificaciones. **Demo:** `demo/build_reservas.sh pilates` genera `demo/pilates-reservas.html` con las pantallas reales.
