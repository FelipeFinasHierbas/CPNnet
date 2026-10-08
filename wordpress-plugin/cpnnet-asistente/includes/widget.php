<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', function () {
    if (!cpnnet_asistente_get('enabled') || is_admin() || !cpnnet_asistente_can_see_chat()) {
        return;
    }
    $test_mode = cpnnet_asistente_get('visibility') !== 'public';
    wp_enqueue_style('cpnnet-asistente', CPNNET_ASISTENTE_URL . 'assets/chat.css', [], CPNNET_ASISTENTE_VERSION);
    wp_enqueue_script('cpnnet-asistente', CPNNET_ASISTENTE_URL . 'assets/chat.js', [], CPNNET_ASISTENTE_VERSION, ['in_footer' => true, 'strategy' => 'defer']);
    wp_add_inline_script('cpnnet-asistente', 'window.CPNNET_ASISTENTE = ' . wp_json_encode([
        'endpoint' => esc_url_raw(rest_url('cpnnet-asistente/v1/chat')),
        'welcome'  => (string) cpnnet_asistente_get('welcome'),
        'logo'     => esc_url_raw(CPNNET_ASISTENTE_URL . 'assets/img/cpnnet-logo-white.png'),
        'testMode' => $test_mode,
        // En modo de prueba la llamada va autenticada (cookie + nonce de REST) para que el servidor sepa que es un administrador.
        // En modo público no se envía nonce: una página en caché con un nonce vencido rompería el chat.
        'nonce'    => $test_mode ? wp_create_nonce('wp_rest') : '',
    ]) . ';', 'before');
});
