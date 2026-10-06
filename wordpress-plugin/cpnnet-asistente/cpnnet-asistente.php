<?php
/**
 * Plugin Name:       CPNnet Asistente Comercial
 * Description:       Chat con IA (Claude) para www.cpnnetsecurity.com: orienta a partners y empresas sobre el portafolio de CPNnet y deriva los leads por WhatsApp.
 * Version:           0.3.0
 * Requires PHP:      8.1
 * Requires at least: 6.0
 * Author:            CPNnet Security
 * Text Domain:       cpnnet-asistente
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CPNNET_ASISTENTE_VERSION', '0.3.0');
define('CPNNET_ASISTENTE_DIR', plugin_dir_path(__FILE__));
define('CPNNET_ASISTENTE_URL', plugin_dir_url(__FILE__));
define('CPNNET_ASISTENTE_OPTION', 'cpnnet_asistente');

if (is_readable(CPNNET_ASISTENTE_DIR . 'vendor/autoload.php')) {
    require_once CPNNET_ASISTENTE_DIR . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Asistente CPNnet:</strong> falta la carpeta <code>vendor/</code> del plugin (SDK de Anthropic). Reinstala el .zip generado con build.sh.</p></div>';
    });
    return;
}

require_once CPNNET_ASISTENTE_DIR . 'includes/settings.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/leads.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/usage.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/kb.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/prompt.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/api.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/widget.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/integration.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/admin.php';
require_once CPNNET_ASISTENTE_DIR . 'includes/dashboard.php';

register_activation_hook(__FILE__, 'cpnnet_asistente_activate');

function cpnnet_asistente_activate(): void
{
    cpnnet_asistente_leads_create_table();
    cpnnet_asistente_usage_create_table();
    cpnnet_asistente_ensure_role();
    if (get_option(CPNNET_ASISTENTE_OPTION) === false) {
        add_option(CPNNET_ASISTENTE_OPTION, cpnnet_asistente_defaults());
    }
}
