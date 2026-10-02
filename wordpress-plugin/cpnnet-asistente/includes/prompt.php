<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prompt de sistema = reglas (prompt/system-prompt.md) + empresa (knowledge/company.md)
 * + base de conocimiento (knowledge/brands.json). Debe ser estable entre requests para que
 * el prompt caching funcione: no incluir fechas ni datos por visitante.
 */
function cpnnet_asistente_system_prompt(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $read = static function (string $rel): string {
        $path = CPNNET_ASISTENTE_DIR . $rel;
        return is_readable($path) ? (string) file_get_contents($path) : '';
    };

    $brands = json_decode($read('knowledge/brands.json'), true);
    $kb     = is_array($brands) ? wp_json_encode($brands, JSON_UNESCAPED_UNICODE) : '';

    $cached = trim($read('prompt/system-prompt.md'))
        . "\n\n# Datos de la empresa\n" . trim($read('knowledge/company.md'))
        . "\n\n# Base de conocimiento (JSON, única fuente de verdad)\n" . $kb;
    return $cached;
}

/** Definición de la única herramienta del bot. */
function cpnnet_asistente_tools(): array
{
    return [[
        'name'        => 'derivar_a_ejecutivo',
        'description' => 'Deriva al visitante a un ejecutivo de CPNnet por WhatsApp con un resumen de su consulta. '
            . 'Úsala solo cuando el visitante quiera avanzar (cotización, demo, reunión o hablar con alguien) '
            . 'y haya dado su consentimiento explícito para compartir sus datos con el equipo comercial.',
        'inputSchema' => [
            'type'       => 'object',
            'properties' => [
                'perfil'          => ['type' => 'string', 'enum' => ['partner', 'empresa', 'desconocido']],
                'nombre'          => ['type' => 'string', 'description' => 'Nombre del visitante'],
                'empresa'         => ['type' => 'string'],
                'pais'            => ['type' => 'string'],
                'contacto'        => ['type' => 'string', 'description' => 'Correo o teléfono del visitante'],
                'necesidad'       => ['type' => 'string', 'description' => 'Necesidad en 1-2 líneas'],
                'marcas_interes'  => ['type' => 'array', 'items' => ['type' => 'string']],
                'dimensionamiento' => ['type' => 'string', 'description' => 'Datos de dimensionamiento levantados (usuarios, sedes, endpoints, etc.)'],
                'siguiente_paso'  => ['type' => 'string', 'description' => 'Siguiente paso sugerido (entry_point de la marca)'],
                'consentimiento'  => ['type' => 'boolean', 'description' => 'true solo si el visitante aceptó compartir sus datos con el equipo comercial'],
            ],
            'required'   => ['perfil', 'necesidad', 'consentimiento'],
        ],
    ]];
}
