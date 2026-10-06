<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    if (!cpnnet_asistente_get('enabled') || is_admin()) {
        return;
    }
    wp_enqueue_style('cpnnet-asistente', CPNNET_ASISTENTE_URL . 'assets/chat.css', [], CPNNET_ASISTENTE_VERSION);
    wp_enqueue_script('cpnnet-asistente', CPNNET_ASISTENTE_URL . 'assets/chat.js', [], CPNNET_ASISTENTE_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
    wp_add_inline_script('cpnnet-asistente', 'window.CPNNET_ASISTENTE = ' . wp_json_encode([
        'endpoint' => esc_url_raw(rest_url('cpnnet-asistente/v1/chat')),
        'welcome'  => (string) cpnnet_asistente_get('welcome'),
        'logo'     => esc_url_raw(CPNNET_ASISTENTE_URL . 'assets/img/cpnnet-logo-white.png'),
    ]) . ';', 'before');
});
