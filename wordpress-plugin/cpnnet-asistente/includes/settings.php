<?php
if (!defined('ABSPATH')) {
    exit;
}

function cpnnet_asistente_defaults(): array
{
    return [
        'enabled'        => 0,
        'api_key'        => '',
        'model'          => 'claude-opus-5-5',
        'effort'         => 'low',
        'whatsapp'       => '',
        'hourly_limit'   => 30,
        'daily_limit'    => 1500,
        'save_leads'     => 1,
        'welcome'        => '¡Hola! Soy el asistente virtual de CPNnet Security. ¿Nos escribes como partner/integrador o como empresa que busca una solución de ciberseguridad?',
    ];
}

function cpnnet_asistente_get(string $key)
{
    $opts = wp_parse_args(get_option(CPNNET_ASISTENTE_OPTION, []), cpnnet_asistente_defaults());
    return $opts[$key] ?? null;
}

/** La clave se prefiere en wp-config.php: define('CPNNET_ASISTENTE_API_KEY', 'sk-ant-...'); */
function cpnnet_asistente_api_key(): string
{
    if (defined('CPNNET_ASISTENTE_API_KEY') && CPNNET_ASISTENTE_API_KEY) {
        return (string) CPNNET_ASISTENTE_API_KEY;
    }
    return (string) cpnnet_asistente_get('api_key');
}

add_action('admin_menu', function () {
    add_options_page('Asistente CPNnet', 'Asistente CPNnet', 'manage_options', 'cpnnet-asistente', 'cpnnet_asistente_settings_page');
});

add_action('admin_init', function () {
    register_setting('cpnnet_asistente_group', CPNNET_ASISTENTE_OPTION, [
        'sanitize_callback' => 'cpnnet_asistente_sanitize',
    ]);
});

function cpnnet_asistente_sanitize($in): array
{
    $old = wp_parse_args(get_option(CPNNET_ASISTENTE_OPTION, []), cpnnet_asistente_defaults());
    $in  = is_array($in) ? $in : [];

    $api_key = trim((string) ($in['api_key'] ?? ''));
    if ($api_key === '') {
        $api_key = (string) $old['api_key']; // campo vacío = conservar la clave guardada
    }
    $effort = in_array($in['effort'] ?? '', ['low', 'medium', 'high'], true) ? $in['effort'] : 'low';
    $model  = preg_replace('/[^a-z0-9\.\-]/', '', strtolower((string) ($in['model'] ?? ''))) ?: 'claude-opus-5-5';

    return [
        'enabled'      => empty($in['enabled']) ? 0 : 1,
        'api_key'      => $api_key,
        'model'        => $model,
        'effort'       => $effort,
        'whatsapp'     => preg_replace('/\D+/', '', (string) ($in['whatsapp'] ?? '')),
        'hourly_limit' => max(1, min(500, (int) ($in['hourly_limit'] ?? 30))),
        'daily_limit'  => max(10, min(50000, (int) ($in['daily_limit'] ?? 1500))),
        'save_leads'   => empty($in['save_leads']) ? 0 : 1,
        'welcome'      => sanitize_textarea_field((string) ($in['welcome'] ?? '')),
    ];
}

function cpnnet_asistente_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $o = wp_parse_args(get_option(CPNNET_ASISTENTE_OPTION, []), cpnnet_asistente_defaults());
    $name = CPNNET_ASISTENTE_OPTION;
    $key_in_config = defined('CPNNET_ASISTENTE_API_KEY') && CPNNET_ASISTENTE_API_KEY;
    ?>
    <div class="wrap">
        <h1>Asistente comercial CPNnet</h1>
        <form method="post" action="options.php">
            <?php settings_fields('cpnnet_asistente_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Activar chat</th>
                    <td><label><input type="checkbox" name="<?php echo esc_attr($name); ?>[enabled]" value="1" <?php checked($o['enabled'], 1); ?>> Mostrar el asistente en el sitio</label></td>
                </tr>
                <tr>
                    <th scope="row">API key de Anthropic</th>
                    <td>
                        <?php if ($key_in_config) : ?>
                            <p><strong>Definida en wp-config.php</strong> (CPNNET_ASISTENTE_API_KEY). Es la opción recomendada.</p>
                        <?php else : ?>
                            <input type="password" autocomplete="off" class="regular-text" name="<?php echo esc_attr($name); ?>[api_key]" value="" placeholder="<?php echo $o['api_key'] ? 'Guardada (deja vacío para conservarla)' : 'sk-ant-...'; ?>">
                            <p class="description">Más seguro: agregar <code>define('CPNNET_ASISTENTE_API_KEY', 'sk-ant-...');</code> en wp-config.php.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Modelo</th>
                    <td>
                        <input type="text" class="regular-text" name="<?php echo esc_attr($name); ?>[model]" value="<?php echo esc_attr($o['model']); ?>">
                        <p class="description">Por defecto <code>claude-opus-5-5</code>. Para bajar el costo se puede usar <code>claude-sonnet-5-5</code>.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Esfuerzo de razonamiento</th>
                    <td>
                        <select name="<?php echo esc_attr($name); ?>[effort]">
                            <?php foreach (['low' => 'Bajo (rápido y barato, recomendado)', 'medium' => 'Medio', 'high' => 'Alto'] as $v => $l) : ?>
                                <option value="<?php echo esc_attr($v); ?>" <?php selected($o['effort'], $v); ?>><?php echo esc_html($l); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">WhatsApp que recibe los leads</th>
                    <td>
                        <input type="text" class="regular-text" name="<?php echo esc_attr($name); ?>[whatsapp]" value="<?php echo esc_attr($o['whatsapp']); ?>" placeholder="56912345678">
                        <p class="description">Número en formato internacional, solo dígitos (código de país + número, sin +).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Mensaje de bienvenida</th>
                    <td><textarea class="large-text" rows="3" name="<?php echo esc_attr($name); ?>[welcome]"><?php echo esc_textarea($o['welcome']); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Límites de uso</th>
                    <td>
                        <label>Mensajes por hora y por visitante <input type="number" min="1" max="500" name="<?php echo esc_attr($name); ?>[hourly_limit]" value="<?php echo esc_attr((string) $o['hourly_limit']); ?>"></label><br>
                        <label>Mensajes totales por día (tope de gasto) <input type="number" min="10" max="50000" name="<?php echo esc_attr($name); ?>[daily_limit]" value="<?php echo esc_attr((string) $o['daily_limit']); ?>"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Respaldo de leads</th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr($name); ?>[save_leads]" value="1" <?php checked($o['save_leads'], 1); ?>> Guardar una copia de cada lead en una tabla propia del plugin (<code><?php echo esc_html(cpnnet_asistente_leads_table()); ?></code>, visible en phpMyAdmin). No toca el CRM.</label>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
