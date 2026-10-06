# Integración del Asistente con el CRM de CPNnet

El asistente guarda cada lead en la base de datos de WordPress (tabla `wp_cpnnet_asistente_leads`) y lo muestra en el panel **Asistente CPNnet > Leads**. El plugin **nunca escribe en la base de datos del CRM**: es el CRM (o un script suyo) quien toma los datos. Hay tres vías; pueden usarse juntas.

| Vía | Cuándo conviene | Automático |
|---|---|---|
| **CSV** | Importación manual o periódica. | No |
| **API con token** | El CRM (o un cron) consulta los leads nuevos. | Sí, por consulta |
| **Webhook** | El CRM debe enterarse al instante de cada lead. | Sí, en tiempo real |

Todo se configura en **Asistente CPNnet > Integración CRM** (solo administradores).

## 1. CSV
En **Leads**, el botón **Exportar CSV** descarga los leads con los filtros aplicados (estado, perfil, fechas, texto). Es UTF-8 con BOM, así que Excel lo abre bien. Las celdas que empiezan con `=`, `+`, `-` o `@` se neutralizan para evitar inyección de fórmulas.

## 2. API de consulta
Base: `https://SU-SITIO/wp-json/cpnnet-asistente/v1`. Autenticación: `Authorization: Bearer TOKEN` (o `X-API-Key: TOKEN`). El token se genera en el panel y se muestra **una sola vez**; en la base de datos solo se guarda su hash. Tras 20 intentos fallidos desde una IP se bloquea por 10 minutos.

| Método y ruta | Descripción |
|---|---|
| `GET /leads` | Lista de leads. Filtros: `status`, `perfil`, `exported` (0/1), `updated_since` (ISO 8601), `from` / `to` (AAAA-MM-DD), `limit` (máx. 200), `page`, `include=conversation`. |
| `GET /leads/{id}` | Un lead, con la conversación. |
| `POST /leads/mark-exported` | Cuerpo `{"ids": ["uuid", …]}` (máx. 500). Marca los leads como ya importados. |

Patrón recomendado para un cron del CRM (cada 5 a 15 minutos):
1. `GET /leads?exported=0&limit=100`
2. Crear o actualizar cada lead en el CRM, usando `id` como clave para no duplicar.
3. `POST /leads/mark-exported` con los ids importados.

```bash
curl -H "Authorization: Bearer TOKEN" "https://SU-SITIO/wp-json/cpnnet-asistente/v1/leads?exported=0&limit=50"
```

## 3. Webhook
En la pantalla de integración se indica la URL del CRM (por ejemplo un script en el mismo hosting). Por cada lead nuevo el plugin envía un `POST` JSON con timeout de 5 segundos:

```json
{ "event": "lead.created", "sent_at": "2026-10-06T14:05:00+00:00", "data": { …lead… } }
```

- Si el CRM responde con código **2xx**, el lead queda marcado como exportado. Si falla, el lead sigue en el panel y se puede **reenviar** desde su ficha o recuperar por la API (`exported=0`).
- Cabeceras: `X-CPNnet-Event` (`lead.created` o `ping`) y `X-CPNnet-Signature: sha256=<HMAC-SHA256 del cuerpo con el secreto>`.
- El botón **Enviar prueba** manda un evento `ping`.

Verificación de la firma en PHP:

```php
$body = file_get_contents('php://input');
$expected = 'sha256=' . hash_hmac('sha256', $body, 'SECRETO_DEL_PANEL');
if (!hash_equals($expected, $_SERVER['HTTP_X_CPNNET_SIGNATURE'] ?? '')) { http_response_code(401); exit; }
$evento = json_decode($body, true);
if ($evento['event'] === 'lead.created') { /* crear el lead en el CRM con $evento['data'] */ }
http_response_code(200);
```

## Campos de un lead
`id` (uuid), `created_at` y `updated_at` (ISO 8601, UTC), `status` (`nuevo`, `contactado`, `calificado`, `ganado`, `perdido`), `perfil` (`partner`, `empresa`, `desconocido`), `nombre`, `empresa`, `pais`, `contacto`, `necesidad`, `marcas_interes` (lista), `dimensionamiento`, `siguiente_paso`, `pagina_origen`, `notas`, `exportado_at` y, si se pide, `conversacion` (lista de `{role, content}`).

## Lectura directa de la base de datos (opcional)
Como el CRM está en el mismo hosting, también puede leer la tabla `wp_cpnnet_asistente_leads` con una cuenta **de solo lectura**. No debe escribir en ella ni modificar su estructura: el plugin la administra. Para integraciones nuevas se recomienda la API o el webhook, que no dependen de la estructura interna.

## Privacidad
Los leads contienen datos personales y, si está activada la opción, la conversación del visitante. Solo el rol **Comercial CPNnet** y los administradores los ven. Se recomienda mencionarlo en la política de privacidad del sitio y definir cuánto tiempo se conservan.
