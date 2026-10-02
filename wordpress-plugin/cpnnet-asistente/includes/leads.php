<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Respaldo de leads en una tabla propia del plugin.
 * Es independiente del CRM interno de CPNnet: este plugin no lee ni escribe en el CRM.
 */
function cpnnet_asistente_leads_table(): string
{
    global $wpdb;
    return $wpdb->prefix . 'cpnnet_asistente_leads';
}

function cpnnet_asistente_leads_create_table(): void
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table   = cpnnet_asistente_leads_table();
    $charset = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL,
        perfil VARCHAR(20) NOT NULL DEFAULT '',
        nombre VARCHAR(190) NOT NULL DEFAULT '',
        empresa VARCHAR(190) NOT NULL DEFAULT '',
        pais VARCHAR(80) NOT NULL DEFAULT '',
        contacto VARCHAR(190) NOT NULL DEFAULT '',
        resumen TEXT NOT NULL,
        PRIMARY KEY  (id),
        KEY created_at (created_at)
    ) {$charset};");
}

function cpnnet_asistente_leads_save(array $lead, string $resumen): void
{
    if (!cpnnet_asistente_get('save_leads')) {
        return;
    }
    global $wpdb;
    $wpdb->insert(cpnnet_asistente_leads_table(), [
        'created_at' => current_time('mysql'),
        'perfil'     => substr((string) ($lead['perfil'] ?? ''), 0, 20),
        'nombre'     => substr((string) ($lead['nombre'] ?? ''), 0, 190),
        'empresa'    => substr((string) ($lead['empresa'] ?? ''), 0, 190),
        'pais'       => substr((string) ($lead['pais'] ?? ''), 0, 80),
        'contacto'   => substr((string) ($lead['contacto'] ?? ''), 0, 190),
        'resumen'    => $resumen,
    ]);
}
