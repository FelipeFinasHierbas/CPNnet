<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prompt de sistema = reglas + datos de la empresa + base de conocimiento (JSON compacto).
 * Debe ser estable entre requests para que el prompt caching funcione: no incluir fechas ni datos por visitante.
 * El contenido sale de lo que el cliente editó en el panel, o de los archivos que trae el plugin.
 */
function cpnnet_asistente_system_prompt(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $kb = wp_json_encode(cpnnet_asistente_compact(cpnnet_asistente_kb()), JSON_UNESCAPED_UNICODE);

    $cached = cpnnet_asistente_rules()
        . "\n\n# Datos de la empresa\n" . cpnnet_asistente_company()
        . "\n\n# Base de conocimiento (JSON, única fuente de verdad)\n" . $kb;
    if (cpnnet_asistente_booking_enabled()) {
        $cfg = cpnnet_asistente_booking_cfg();
        $cached .= "\n\n# Reservas\nTienes la herramienta `ver_horarios` con las clases y cupos reales de los próximos días: úsala cuando pregunten por horarios o disponibilidad, y no inventes horarios."
            . "\nLos alumnos con plan reservan, cambian y cancelan sus clases en su perfil: " . cpnnet_asistente_portal_url()
            . ' (cancelación o cambio hasta ' . (int) $cfg['cancel_hours'] . ' horas antes; reciben confirmación y recordatorio por correo). Tú no reservas ni cobras: para contratar un plan o agendar la clase de prueba, deriva con `derivar_a_ejecutivo`.';
    }
    return $cached;
}

/** Definición de la única herramienta del bot. */
function cpnnet_asistente_tools(): array
{
    $tools = [[
        'name'        => 'derivar_a_ejecutivo',
        'description' => 'Deriva al visitante a ' . cpnnet_asistente_brand_get('handoff_to') . ' por WhatsApp con un resumen de su consulta. '
            . 'Úsala solo cuando el visitante quiera avanzar (' . cpnnet_asistente_brand_get('handoff_when') . ') '
            . 'y haya dado su consentimiento explícito para compartir sus datos con el equipo comercial.',
        'inputSchema' => [
            'type'       => 'object',
            'properties' => [
                'perfil'           => ['type' => 'string', 'enum' => array_keys(cpnnet_asistente_profiles())],
                'nombre'           => ['type' => 'string', 'description' => 'Nombre del visitante'],
                'empresa'          => ['type' => 'string'],
                'pais'             => ['type' => 'string'],
                'contacto'         => ['type' => 'string', 'description' => 'Correo o teléfono del visitante'],
                'necesidad'        => ['type' => 'string', 'description' => 'Necesidad en 1-2 líneas'],
                'marcas_interes'   => ['type' => 'array', 'items' => ['type' => 'string']],
                'dimensionamiento' => ['type' => 'string', 'description' => 'Datos de dimensionamiento levantados (usuarios, sedes, endpoints, etc.)'],
                'siguiente_paso'   => ['type' => 'string', 'description' => 'Siguiente paso sugerido (entry_point de la ' . cpnnet_asistente_brand_get('item') . ')'],
                'consentimiento'   => ['type' => 'boolean', 'description' => 'true solo si el visitante aceptó compartir sus datos con el equipo comercial'],
            ],
            'required'   => ['perfil', 'necesidad', 'consentimiento'],
        ],
    ]];
    if (cpnnet_asistente_booking_enabled()) {
        $tools[] = cpnnet_asistente_booking_tool();
    }
    return $tools;
}
