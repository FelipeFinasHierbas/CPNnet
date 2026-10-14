# Asistente para Kindforce Pilates — plan por etapas

Cliente: **Kindforce Pilates**. Hoy reciben ~10 consultas al mes, reservan y cobran con **BoxMagic** y contactan por **WhatsApp** (el mismo número que usan en campañas de Meta). Objetivo: que las consultas crezcan y que ninguna se pierda.

## Qué hace el asistente
- Responde al instante, 24/7, con lo que el estudio definió: planes, cómo empezar, dudas frecuentes.
- Distingue **nuevo alumno** de **alumno actual**.
- Guía a la persona nueva a su **clase de prueba gratuita**.
- Ofrece el **plan de 4 clases ($40.000)** solo cuando la persona quiere seguir sin plan mensual (por ejemplo después de la prueba) o el precio es una barrera, porque ese plan no se publica.
- No da indicaciones médicas: ante lesiones o embarazo sugiere consultar a un profesional y deriva a recepción.
- No inventa horarios, cupos ni precios no cargados. No cobra en el chat.
- Con el **consentimiento** de la persona, deja la consulta lista para recepción por WhatsApp (nombre, contacto, necesidad, plan de interés, disponibilidad) y la guarda en el panel.

## Etapas
| Etapa | Qué incluye | Requisitos | Estado |
|---|---|---|---|
| 1. Web | Widget en el sitio del estudio + panel de consultas | Sitio en WordPress (si no hay, una landing simple) + API key | **Listo** (`pilates-asistente`) |
| 2. WhatsApp | El asistente atiende el WhatsApp del estudio, incluidos los anuncios «clic a WhatsApp» de Meta; recepción toma la conversación cuando hace falta | WhatsApp Business Platform (API de Meta) y un servidor pequeño con HTTPS | Por construir |
| 3. Instagram y Facebook | Respuestas a mensajes directos | App de Meta aprobada para mensajería | Por construir |
| 4. Reservas en el chat | Ver cupos y reservar sin salir de la conversación | Integración con BoxMagic (por confirmar si tiene API) | Por evaluar |

## Puntos a confirmar
1. **Número de WhatsApp.** Hoy se usa en la app y en campañas. Meta permite, con algunos proveedores, conectar la API sin dejar de usar la app en ese número (coexistencia). Hay que verificarlo para este número; si no se puede, se usa un número nuevo solo para el asistente.
2. **BoxMagic.** Consultar a BoxMagic si ofrece API o un link de reserva/compra por plan. En la etapa 1 el asistente deriva a recepción y menciona BoxMagic solo a alumnos actuales.
3. **Planes publicados.** Pasar el contenido de la historia destacada «Planes» (nombre, clases, precio, vigencia) para cargarlo en el panel.
4. **Datos del estudio:** dirección, horarios, tipos de clase, condiciones de la clase de prueba (además de que es gratis), política de cancelación y medios de pago.
5. **Logo y colores** (los actuales son provisionales).

## Costo
- **Modelo de IA (estimación).** Con 10 a 100 conversaciones al mes: unos US$1 a 11 con Opus 5.5 (el modelo por defecto) y menos de US$1 con Haiku 5.5 (habría que agregarlo al selector y probar su calidad). Lo paga el estudio con su propia API key y tiene tope mensual.
- **WhatsApp (etapa 2).** Responder a quien escribe primero entra en la ventana de atención de Meta. Los mensajes que inicia el estudio (recordatorios, seguimiento) usan plantillas que Meta cobra por mensaje.
- **Servidor (etapa 2).** Un servidor pequeño o el mismo hosting con HTTPS.

## Sobre el crecimiento
El asistente **no genera demanda por sí solo**: convierte mejor la que llega, porque responde en segundos, a cualquier hora, y no se le olvida dar seguimiento. El crecimiento viene de combinarlo con las campañas de Meta («clic a WhatsApp» o «enviar mensaje» en Instagram). Cada respuesta inmediata aumenta la probabilidad de que la persona agende.
