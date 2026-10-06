# Guía del cliente: Asistente comercial de CPNnet

El asistente vive dentro de **su** WordPress y funciona con **su** cuenta de IA. Ustedes controlan el contenido, el gasto y los datos; no dependen de quien lo instaló para cambiar nada.

## 1. Su cuenta de IA (el consumo se factura aquí)
1. Crear una cuenta en **console.anthropic.com** a nombre de CPNnet, con el medio de pago de la empresa.
2. Cargar **créditos prepagados** (si la consola lo ofrece): el gasto máximo es lo que carguen.
3. Fijar un **límite mensual de gasto** en la consola.
4. Crear una **API key** (empieza con `sk-ant-`). Es la contraseña del asistente: no se comparte por correo ni chat.
5. Pegarla en WordPress: Ajustes > Asistente CPNnet > General (o en `wp-config.php`, la opción más segura).

> Los nombres exactos de los menús de la consola pueden cambiar; guíense por lo que vean en pantalla.

## 2. El área comercial: panel y leads
En el menú de WordPress aparece **Asistente CPNnet**:
- **Panel:** conversaciones, leads, conversión, mensajes y gasto estimado, con gráfico diario, estado de los leads, marcas más consultadas y perfil/país (7, 30 o 90 días).
- **Leads:** lista con filtros (estado, perfil, fechas, texto) y botón **Exportar CSV**. Cada lead tiene una ficha con sus datos, la conversación con el asistente, el estado (Nuevo, Contactado, Calificado, Ganado, Perdido) y notas internas.
- **Integración CRM** y **Configuración**: solo para administradores.

Para que una persona del área comercial entre solo al panel, crear su usuario en WordPress (Usuarios > Añadir nuevo) con el rol **Comercial CPNnet**: ve el panel y los leads, y no ve la configuración, la API key ni la integración.

## 3. Qué pueden editar ustedes (Ajustes > Asistente CPNnet)
| Pestaña | Para qué sirve |
|---|---|
| **General** | Activar o desactivar el chat, API key, modelo, WhatsApp que recibe los leads, mensaje de bienvenida, límites de uso y tope mensual de gasto. |
| **Conocimiento (marcas)** | Agregar, editar o eliminar marcas: resumen, problemas que resuelve, diferenciales, cómo se dimensiona, entrada recomendada y preguntas para calificar. Los cambios se aplican al instante. |
| **Reglas y empresa** | Cómo se comporta el asistente (tono, cómo detecta si habla con un partner o una empresa, cuándo deriva) y los datos de la empresa. Es texto en español; hay un botón para restaurar el original. |
| **Uso y costos** | Mensajes atendidos, tokens consumidos, gasto estimado del mes y por día. |

Consejos al editar:
- Escriban como si le explicaran a un colega nuevo del equipo comercial.
- Si un dato no se conoce (por ejemplo, cómo dimensionar una marca), déjenlo vacío: el asistente derivará a un ejecutivo en vez de inventar.
- No pongan precios ni descuentos si no quieren que el asistente los mencione.
- Después de cambiar algo, prueben el chat en el sitio con una pregunta real.

## 4. Cómo controlar el gasto (tokens)
Cada mensaje de un visitante consume tokens de **su** cuenta. Para controlarlo:
- **Modelo:** en General se puede elegir uno más económico.
- **Contenido más corto:** todo el conocimiento viaja en cada mensaje; en las pestañas aparece el tamaño aproximado en tokens.
- **Topes:** tope mensual en el plugin (el asistente deja de responder al alcanzarlo) y límite en la consola de Anthropic. Usen los dos.
- **Seguimiento:** pestaña «Uso y costos». Los dólares son estimados; la factura real está en la consola.

## 5. Datos y privacidad
- La conversación se envía a la API de Anthropic para generar la respuesta. El plugin guarda el consumo de tokens y, de cada lead, sus datos y (si la opción está activada en General) la conversación, en la tabla `wp_cpnnet_asistente_leads` de su base de datos. No guarda las conversaciones que no terminan en lead.
- Los leads solo se crean con el consentimiento del visitante. Solo los ven los administradores y el rol Comercial CPNnet.
- Definan cuánto tiempo conservarán los leads y mencionen el asistente en la política de privacidad del sitio.
- Cómo llevar los leads al CRM (CSV, API o webhook): ver `docs/INTEGRACION-CRM.md`.
- Se recomienda agregar una mención del asistente en la política de privacidad del sitio.

## 6. Si algo falla
- El chat no aparece: verificar que esté activado y que el plugin esté activo.
- El chat responde con un error: revisar que la API key sea válida y que la cuenta tenga créditos.
- «El asistente no está disponible por ahora»: se alcanzó el tope mensual o diario; subirlo en General o esperar al mes siguiente.
