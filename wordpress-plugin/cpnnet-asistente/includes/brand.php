<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Identidad de la marca del asistente. Por defecto es CPNnet; una variante (p. ej. The Forest)
 * incluye un brand.json en la raíz del plugin que sobrescribe estos valores.
 */
function cpnnet_asistente_brand(): array
{
    static $brand = null;
    if ($brand !== null) {
        return $brand;
    }
    $brand = [
        'name'        => 'CPNnet Security',
        'short'       => 'CPNnet',
        'site'        => 'www.cpnnetsecurity.com',
        'lead_title'  => 'Nuevo lead desde www.cpnnetsecurity.com',
        'menu'        => 'Asistente CPNnet',
        'role_label'  => 'Comercial CPNnet',
        'crm_label'   => 'CRM de CPNnet',
        'item'        => 'marca',
        'items'       => 'marcas',
        'welcome'     => '¡Hola! Soy el asistente virtual de CPNnet Security. ¿Nos escribes como partner/integrador o como empresa que busca una solución de ciberseguridad?',
        'chips'       => ['Soy partner / integrador', 'Busco una solución para mi empresa'],
        'colors'      => ['primary' => '#2b4c8c', 'dark' => '#1b2f5e', 'accent' => '#4f9bd6', 'tint' => '#bcd3ee', 'bg' => '#f4f7fb'],
        'logo'        => 'cpnnet-logo.png',
        'logo_white'  => 'cpnnet-logo-white.png',
    ];
    $file = CPNNET_ASISTENTE_DIR . 'brand.json';
    if (is_readable($file)) {
        $over = json_decode((string) file_get_contents($file), true);
        if (is_array($over)) {
            $colors = array_merge($brand['colors'], is_array($over['colors'] ?? null) ? $over['colors'] : []);
            $brand  = array_merge($brand, $over);
            $brand['colors'] = $colors;
        }
    }
    return $brand;
}

function cpnnet_asistente_brand_get(string $key)
{
    return cpnnet_asistente_brand()[$key] ?? '';
}

/** Variables CSS de color de la marca (se inyectan tras las hojas de estilo). */
function cpnnet_asistente_brand_css(): string
{
    $c = cpnnet_asistente_brand()['colors'];
    $h = static fn($v, $d) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $v) ? $v : $d;
    $p = $h($c['primary'], '#2b4c8c'); $d = $h($c['dark'], '#1b2f5e'); $a = $h($c['accent'], '#4f9bd6');
    $t = $h($c['tint'], '#bcd3ee');    $b = $h($c['bg'], '#f4f7fb');
    return ".cpnnet-chat{--c-primary:$p;--c-dark:$d;--c-accent:$a;--c-bg:$b}"
        . ".cpn-wrap{--p:$p;--d:$d;--a:$a;--t:$t}";
}

function cpnnet_asistente_logo_url(bool $white = false): string
{
    $b = cpnnet_asistente_brand();
    return CPNNET_ASISTENTE_URL . 'assets/img/' . ($white ? $b['logo_white'] : $b['logo']);
}
