# CLAUDE.md — Asistente comercial CPNnet Security

Contexto para continuar el trabajo. Idioma de trabajo con el usuario: **español**. No crear pull requests salvo que lo pida. Commits con los trailers de atribución habituales.

## Qué es
CPNnet Security es un mayorista/socio de ciberseguridad de Latinoamérica (Colombia, Perú y la región; 18+ años). Vende por canal (integradores/partners) y también atiende empresas finales. Portafolio (del catálogo PDF): 3 dominios, 6 categorías, 25 marcas. Este proyecto entrega un **chat comercial con IA** para su sitio WordPress (cPanel), un **panel para el área comercial** y la **integración con su CRM** (también alojado en el mismo hosting).

## Decisiones tomadas (no re-litigar sin preguntar al usuario)
- **Plugin de WordPress en PHP**, no servidor aparte. Usa el SDK oficial `anthropic-ai/sdk` (vendor incluido en el .zip) + Guzzle. Para tocar código de la API, usar el skill `claude-api`.
- **Cada cliente paga con su propia API key** (constante `CPNNET_ASISTENTE_API_KEY` en wp-config o ajustes). Independiente del plan del usuario.
- Modelo por defecto `claude-opus-5-5`, esfuerzo `low`; selector Opus 5.5 / Sonnet 5.5 / Haiku 4.5. Con Opus 5.5 el pensamiento no se puede desactivar y `tool_choice` forzado da 400 (no se usa). **Pendiente de decidir con el usuario:** pasar el valor por defecto a Sonnet 5.5 por costo.
- **Caché de prompt** en el bloque de sistema (~13 mil tokens de conocimiento); tope mensual en USD; historial 12 mensajes; max 800 tokens de salida.
- El bot **solo usa el catálogo**: no inventa precios/plazos/condiciones; detecta partner vs empresa; el lead sale con **consentimiento** por la herramienta `derivar_a_ejecutivo` (WhatsApp wa.me + repositorio propio).
- **El plugin NO escribe en la base de datos del CRM.** Integración por CSV, API REST con token (hash) y webhook firmado HMAC. Docs: `docs/INTEGRACION-CRM.md`.
- Contenido editable por el cliente desde el panel (opciones de WP con prioridad sobre los archivos empaquetados): marcas, reglas, empresa.
- Identidad: logo extraído del catálogo (`assets/img/`, baja resolución ~300px) y **Montserrat** local (OFL) en widget, panel, demos y PDF. Colores `#2b4c8c / #1b2f5e / #4f9bd6`.

## Variante The Forest (0.5.0)
Mismo plugin con otra marca: `variants/the-forest/` (`variant.json`, `brand.json`, `knowledge/`, `bot/`, `img/`, `demo/scenarios.json`). La capa `includes/brand.php` lee un `brand.json` opcional en la raíz del plugin (nombre, colores, textos, chips, términos «servicio/servicios» en vez de «marca/marcas»). `VARIANT=the-forest ./wordpress-plugin/build.sh` → `dist/forest-asistente.zip` (carpeta/archivo `forest-asistente`); no activar las dos variantes a la vez (el plugin lo detecta y avisa). Se mantienen sin cambio los identificadores técnicos (namespace REST `cpnnet-asistente/v1`, cabeceras `X-CPNnet-*`, campo `marcas_interes`). El contenido de The Forest es **provisional**: solo lo conocido (servicios, ForestBot©, Agile Biodiversa, etapas del proyecto); lo demás figura «por confirmar» y el bot no lo inventa. Logo = wordmark provisional generado; colores verdes provisionales. `tests/run.sh` prueba base y variantes; demo: `demo/build_variant.py the-forest`.
**Falta de The Forest:** URL del sitio, logo/colores reales, detalle de cada servicio, tecnologías, modalidad con partners, permiso para citar el caso CPNnet, WhatsApp, tono.

## Variante Kind Force Pilates (rubro bienestar, 0.6.0)
`variants/pilates/`: perfiles `nuevo`/`alumno` (clave `profiles` de brand.json; el enum de la herramienta, filtros y etiquetas salen de ahí), `handoff_to` = recepción, `sizing_label` = «Disponibilidad y frecuencia», `wa_label`. Datos reales: reservas y cobros en **BoxMagic**, contacto por WhatsApp (mismo número de campañas Meta), ~10 consultas/mes, planes en la historia destacada «Planes» de IG (no cargados) y un **plan de 4 clases por $40.000 que no se publica** (el bot lo ofrece solo si quieren seguir sin plan mensual, p. ej. tras la prueba, o el precio es barrera). Nombre real: **Kind Force Pilates** (kindforcepilates.com, Av. Los Leones 292 local 1, Providencia; lema «Fuerza con propósito, energía en equilibrio»; clase Pilates Interval Fusion 50 min). **Clase de prueba gratuita**. Planes del sitio oficial (leídos vía buscador: el sitio e Instagram están bloqueados en el entorno cloud): 8/12/16 sesiones $60.000/$80.000/$100.000; 90/180/365 días −10/20/30 %. Colores corporativos indicados por el usuario: **blanco, grafito y morado** (hex aproximados: morado `#6c4bb4`, grafito `#2b2b30`, lavanda `#a78bdb`; pedir los oficiales), `texture: grain`, logotipo tipográfico generado: **reemplazar por el logo oficial**. Presentación: `presentacion-kindforce/` (16:9 y móvil 9:16; `tools/capturas_kindforce.js`, `tools/build_pdf_kindforce.js`, `build_kit.sh`). Plan por etapas (web → WhatsApp API → IG/FB → reservas BoxMagic): `docs/PLAN-PILATES.md`. Demo: `python3 demo/build_variant.py pilates`.
Claves de marca nuevas (todas opcionales, con valores CPNnet por defecto): `profiles`, `handoff_to` (si falta: «un ejecutivo de <short>»), `handoff_when`, `sizing_label`, `wa_label`, `inbox_label`. El resumen del lead omite líneas vacías.

## Módulo de reservas (0.7.0, estilo BoxMagic)
Activado por variante con `"modules": ["booking"]` en brand.json (Kind Force sí; CPNnet/The Forest no). Archivos: `includes/booking.php` (tablas, planes, horario semanal → clases, reservar/cambiar/cancelar con cupos atómicos, tarea horaria de recordatorios/vencimientos, herramienta `ver_horarios`), `booking-mail.php` (correos HTML con la marca vía `wp_mail`), `booking-portal.php` (shortcode `[asistente_reservas]`, acciones `admin_post_cpnnet_*` con nonce), `booking-admin.php` (Agenda, Alumnos y pagos, Configuración de reservas; config en texto «una línea por elemento»), `assets/portal.css` (todo bajo `.cpn-portal`). Config por defecto: `variants/<v>/booking.json` (lo copia build.sh); `example: true` hasta que el estudio guarde (el bot avisa que el horario no está confirmado). Alumnos = usuarios de WP con rol `cpnnet_alumno` (sin barra de admin). Pago en línea **no** incluido (recepción registra el pago). Docs: `docs/RESERVAS.md`; pruebas `tests/test_booking.php` (50); demo `demo/build_reservas.sh pilates`.

## Estructura y comandos
Ver `README.md`. Resumen: `./release.sh` construye `releases/*.zip`; `tests/` (SQLite, ver `tests/README.md`; el script falla si ejecuta menos de 50 verificaciones, para detectar salidas silenciosas); `tools/` (Node: `npm run e2e|capturas|pdf`).
- `wordpress-plugin/build.sh` copia `knowledge/` y `bot/system-prompt.md` dentro del plugin (carpetas `knowledge/` y `prompt/` del plugin están en .gitignore: son copias generadas), corre `composer install --no-dev` y recorta `vendor/`.
- Las demos se generan con `demo/build.py` (chat + portada) y `demo/build_dashboard.sh` (panel con las pantallas **reales** del plugin renderizadas con datos de ejemplo).

## Trampas conocidas
- Las rutas de `@font-face` en `chat.css`/`admin.css` son relativas al CSS: `url("fonts/...")` (no `../fonts`). Los scripts de las demos las incrustan en base64 con una regex sobre esa forma.
- `composer` puede instalar el SDK desde fuente (git); `build.sh` quita `.git` y tests de `vendor/` para dejar el zip en ~4,6 MB.
- En las pruebas, `wp_safe_redirect` lanza `RedirectSignal` (en WordPress la redirección va seguida de `exit`): hay que atraparla al llamar a los handlers de `admin_post_*`.
- Las pruebas de `tests/` simulan WP con SQLite: no prueban SQL específico de MySQL (`dbDelta`, etc.). Falta una prueba en un WordPress real.
- Fechas: se guardan en UTC y se muestran en la hora del sitio (`wp_date`).
- Los datos del cliente (leads) son personales: el panel está protegido por la capacidad `cpnnet_asistente_leads` (rol «Comercial CPNnet» y administradores).

## Pendientes
1. **Probar en un WordPress real** (old-site / sitio de CPNnet) con una API key real; revisar el aspecto del widget con el tema del sitio. (Desde el entorno cloud el dominio estaba bloqueado por la red: nunca se pudo leer www.cpnnetsecurity.com.)
2. Logo en alta resolución/vector si el cliente lo tiene.
3. Decidir modelo por defecto (costo) y fijar tope mensual con el cliente.
4. Elegir la vía de integración con el CRM (CSV → API/webhook) con el equipo técnico del CRM; mapear campos.
5. Política de privacidad (mencionar el asistente, retención de leads, transcripción).
6. ~~Modo de prueba~~ **Hecho en 0.4.0**: `visibility` = `admins` (por defecto) | `public`; el endpoint rechaza con 403 a quien no sea administrador y el widget no se carga; en modo de prueba el widget envía el nonce de REST (en público no, para no romper páginas en caché).
7. Número de WhatsApp de los leads.
8. Catálogo: la fuente de `knowledge/brands.json` es el PDF «Catálogo CPNnet Security Para web y clientes» (el usuario conserva el original). Marcas de cross-selling no tienen `sizing`/`entry_point` en el catálogo: el bot deriva a un ejecutivo.
