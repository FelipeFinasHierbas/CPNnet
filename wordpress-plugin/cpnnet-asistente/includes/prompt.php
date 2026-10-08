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
    return $cached;
}

/** Definición de la única herramienta del bot. */
function cpnnet_asistente_tools(): array
{
    return [[
        'name'        => 'derivar_a_ejecutivo',
        'description' => 'Deriva al visitante a un ejecutivo de ' . cpnnet_asistente_brand_get('short') . ' por WhatsApp con un resumen de su consulta. '
            . 'Úsala solo cuando el visitante quiera avanzar (cotización, demo, reunión o hablar con alguien) '
            . 'y haya dado su consentimiento explícito para compartir sus datos con el equipo comercial.',
        'inputSchema' => [
            'type'       => 'object',
            'properties' => [
                'perfil'           => ['type' => 'string', 'enum' => ['partner', 'empresa', 'desconocido']],
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
}
