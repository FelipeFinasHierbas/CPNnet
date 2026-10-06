<?php
if (!defined('ABSPATH')) {
    exit;
}

/* ===== Panel de administración: Ajustes > Asistente CPNnet ===== */

/** Capacidad del área comercial: ver y gestionar leads. Los administradores la tienen siempre. */
add_filter('user_has_cap', function ($allcaps) {
    if (!empty($allcaps['manage_options'])) {
        $allcaps['cpnnet_asistente_leads'] = true;
    }
    return $allcaps;
});

/** Rol «Comercial CPNnet»: entra al WordPress solo para ver el panel y los leads (sin acceso a configuración ni a la API key). */
function cpnnet_asistente_ensure_role(): void
{
    if (!get_role('cpnnet_comercial')) {
        add_role('cpnnet_comercial', 'Comercial CPNnet', ['read' => true, 'cpnnet_asistente_leads' => true]);
    }
}
add_action('init', 'cpnnet_asistente_ensure_role');

add_action('admin_menu', function () {
    $cap = 'cpnnet_asistente_leads';
    add_menu_page('Asistente CPNnet', 'Asistente CPNnet', $cap, 'cpnnet-asistente', 'cpnnet_asistente_page_panel', 'dashicons-format-chat', 58);
    add_submenu_page('cpnnet-asistente', 'Panel comercial', 'Panel', $cap, 'cpnnet-asistente', 'cpnnet_asistente_page_panel');
    add_submenu_page('cpnnet-asistente', 'Leads', 'Leads', $cap, 'cpnnet-asistente-leads', 'cpnnet_asistente_page_leads');
    add_submenu_page('cpnnet-asistente', 'Integración con el CRM', 'Integración CRM', 'manage_options', 'cpnnet-asistente-integracion', 'cpnnet_asistente_page_integracion');
    add_submenu_page('cpnnet-asistente', 'Configuración', 'Configuración', 'manage_options', 'cpnnet-asistente-config', 'cpnnet_asistente_admin_page');
});

/** URL de la pantalla de configuración (por defecto) o de otra pantalla del plugin ('page' en $args). */
function cpnnet_asistente_admin_url(array $args = []): string
{
    return add_query_arg(array_merge(['page' => 'cpnnet-asistente-config'], $args), admin_url('admin.php'));
}

function cpnnet_asistente_admin_guard(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos para esto.', 403);
    }
    check_admin_referer('cpnnet_asistente_admin');
}

function cpnnet_asistente_admin_redirect(string $tab, string $msg, array $extra = []): void
{
    if (strpos($tab, 'cpnnet-asistente') === 0) { // $tab es el slug de otra pantalla del plugin
        $url = add_query_arg(array_merge(['page' => $tab, 'msg' => $msg], $extra), admin_url('admin.php'));
    } else {
        $url = cpnnet_asistente_admin_url(array_merge(['tab' => $tab, 'msg' => $msg], $extra));
    }
    wp_safe_redirect($url);
    exit;
}

function cpnnet_asistente_admin_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $tabs = ['general' => 'General', 'conocimiento' => 'Conocimiento (marcas)', 'reglas' => 'Reglas y empresa', 'uso' => 'Uso y costos'];
    $tab  = isset($_GET['tab'], $tabs[$_GET['tab']]) ? (string) $_GET['tab'] : 'general';
    cpnnet_asistente_wrap_start('Configuración', 'Ajustes del asistente, lo que sabe y cuánto consume.');
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $slug => $label) {
        printf('<a class="nav-tab %s" href="%s">%s</a>', $slug === $tab ? 'nav-tab-active' : '', esc_url(cpnnet_asistente_admin_url(['tab' => $slug])), esc_html($label));
    }
    echo '</h2>';
    $render = 'cpnnet_asistente_tab_' . $tab;
    $render();
    cpnnet_asistente_wrap_end();
}

/* ---------- Pestaña General ---------- */

function cpnnet_asistente_tab_general(): void
{
    $o    = wp_parse_args(get_option(CPNNET_ASISTENTE_OPTION, []), cpnnet_asistente_defaults());
    $name = CPNNET_ASISTENTE_OPTION;
    $key_in_config = defined('CPNNET_ASISTENTE_API_KEY') && CPNNET_ASISTENTE_API_KEY;
    ?>
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
                        <p class="description">Es la clave de <strong>su propia cuenta</strong> en console.anthropic.com: el consumo se factura a esa cuenta. Más seguro: <code>define('CPNNET_ASISTENTE_API_KEY', 'sk-ant-...');</code> en wp-config.php.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Modelo</th>
                <td>
                    <select name="<?php echo esc_attr($name); ?>[model]">
                        <?php foreach (cpnnet_asistente_models() as $v => $l) : ?>
                            <option value="<?php echo esc_attr($v); ?>" <?php selected($o['model'], $v); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Cambiar de modelo es la forma más rápida de bajar el costo. Mira la pestaña «Uso y costos» para ver cuánto gasta cada uno.</p>
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
                <th scope="row">Límites de gasto y uso</th>
                <td>
                    <label>Tope mensual de gasto estimado (USD, 0 = sin tope) <input type="number" min="0" step="1" name="<?php echo esc_attr($name); ?>[monthly_budget]" value="<?php echo esc_attr((string) $o['monthly_budget']); ?>"></label>
                    <p class="description">Al alcanzarlo, el asistente deja de responder hasta el mes siguiente. Es una estimación; el límite exacto se fija también en la consola de Anthropic.</p>
                    <label>Mensajes por hora y por visitante <input type="number" min="1" max="500" name="<?php echo esc_attr($name); ?>[hourly_limit]" value="<?php echo esc_attr((string) $o['hourly_limit']); ?>"></label><br>
                    <label>Mensajes totales por día <input type="number" min="10" max="50000" name="<?php echo esc_attr($name); ?>[daily_limit]" value="<?php echo esc_attr((string) $o['daily_limit']); ?>"></label>
                </td>
            </tr>
            <tr>
                <th scope="row">Conversación del lead</th>
                <td>
                    <label><input type="checkbox" name="<?php echo esc_attr($name); ?>[save_transcript]" value="1" <?php checked($o['save_transcript'], 1); ?>> Guardar la conversación junto a cada lead, para que el área comercial vea el contexto</label>
                    <p class="description">Los leads siempre se guardan en la tabla <code><?php echo esc_html(cpnnet_asistente_leads_table()); ?></code> (visible en phpMyAdmin) y se ven en el panel «Leads». Esta opción solo controla si se incluye la transcripción. Se recomienda mencionarlo en la política de privacidad del sitio.</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <?php
}

/* ---------- Pestaña Conocimiento (marcas) ---------- */

function cpnnet_asistente_tab_conocimiento(): void
{
    $kb     = cpnnet_asistente_kb();
    $cats   = [];
    foreach ($kb['categories'] as $c) {
        $cats[$c['id']] = $c['name'];
    }
    $edit_id = isset($_GET['brand']) ? sanitize_text_field((string) $_GET['brand']) : '';
    $tokens  = cpnnet_asistente_approx_tokens(cpnnet_asistente_system_prompt());
    ?>
    <p>Aquí se edita lo que el asistente sabe de cada marca. Los cambios se aplican de inmediato en el chat.
       Todo este contenido viaja en cada mensaje (con caché, a una fracción del precio): hoy son <strong>≈ <?php echo esc_html(number_format_i18n($tokens)); ?> tokens</strong>.
       Textos más breves = menos costo.</p>
    <?php if ($edit_id !== '') :
        cpnnet_asistente_brand_form($kb, $cats, $edit_id === 'nueva' ? null : cpnnet_asistente_find_brand($kb, $edit_id));
        return;
    endif; ?>

    <p>
        <a class="button button-primary" href="<?php echo esc_url(cpnnet_asistente_admin_url(['tab' => 'conocimiento', 'brand' => 'nueva'])); ?>">Agregar marca</a>
        <?php if (cpnnet_asistente_kb_is_custom()) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Restaurar el catálogo original? Se perderán tus cambios en las marcas.');">
                <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
                <input type="hidden" name="action" value="cpnnet_asistente_kb_reset">
                <button class="button">Restaurar catálogo original</button>
            </form>
        <?php endif; ?>
    </p>
    <table class="widefat striped" style="max-width:980px">
        <thead><tr><th>Marca</th><th>Tipo</th><th>Categoría</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($kb['brands'] as $b) : ?>
            <tr>
                <td><strong><?php echo esc_html($b['name']); ?></strong></td>
                <td><?php echo $b['tier'] === 'primer_orden' ? 'Primer orden' : 'Cross-selling'; ?></td>
                <td><?php echo esc_html($cats[$b['category']] ?? $b['category']); ?></td>
                <td>
                    <a class="button button-small" href="<?php echo esc_url(cpnnet_asistente_admin_url(['tab' => 'conocimiento', 'brand' => $b['id']])); ?>">Editar</a>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar esta marca?');">
                        <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
                        <input type="hidden" name="action" value="cpnnet_asistente_kb_delete">
                        <input type="hidden" name="brand_id" value="<?php echo esc_attr($b['id']); ?>">
                        <button class="button button-small button-link-delete">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function cpnnet_asistente_brand_form(array $kb, array $cats, ?array $b): void
{
    $b = $b ?? ['name' => '', 'tier' => 'primer_orden', 'category' => array_key_first($cats)];
    $lines = static fn(string $k): string => implode("\n", (array) ($b[$k] ?? []));
    $row = static function (string $label, string $html, string $help = ''): void {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . $html . ($help ? '<p class="description">' . esc_html($help) . '</p>' : '') . '</td></tr>';
    };
    ?>
    <h3><?php echo !empty($b['id']) ? 'Editar marca' : 'Nueva marca'; ?></h3>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
        <input type="hidden" name="action" value="cpnnet_asistente_kb_save">
        <input type="hidden" name="brand_id" value="<?php echo esc_attr($b['id'] ?? ''); ?>">
        <table class="form-table" role="presentation">
            <?php
            $row('Nombre', '<input type="text" class="regular-text" name="name" required value="' . esc_attr($b['name']) . '">');
            $tier = '<select name="tier"><option value="primer_orden"' . selected($b['tier'], 'primer_orden', false) . '>Primer orden</option><option value="cross_selling"' . selected($b['tier'], 'cross_selling', false) . '>Cross-selling</option></select>';
            $row('Tipo', $tier);
            $opts = '';
            foreach ($cats as $id => $label) {
                $opts .= '<option value="' . esc_attr($id) . '"' . selected($b['category'] ?? '', $id, false) . '>' . esc_html($label) . '</option>';
            }
            $row('Categoría', '<select name="category">' . $opts . '</select>');
            $row('Frase corta', '<input type="text" class="large-text" name="tagline" value="' . esc_attr($b['tagline'] ?? '') . '">');
            $row('Resumen', '<textarea class="large-text" rows="5" name="summary">' . esc_textarea($b['summary'] ?? '') . '</textarea>', 'Qué es y para quién. Es la base de lo que el asistente dirá de esta marca.');
            $row('Problemas que resuelve', '<textarea class="large-text" rows="5" name="problems">' . esc_textarea($lines('problems')) . '</textarea>', 'Uno por línea.');
            $row('Por qué elegirla (diferenciales)', '<textarea class="large-text" rows="5" name="differentiators">' . esc_textarea($lines('differentiators')) . '</textarea>', 'Uno por línea.');
            $row('Ideal para', '<textarea class="large-text" rows="3" name="ideal_for">' . esc_textarea($lines('ideal_for')) . '</textarea>', 'Uno por línea.');
            $row('Capacidades', '<textarea class="large-text" rows="3" name="capabilities">' . esc_textarea($lines('capabilities')) . '</textarea>', 'Uno por línea (opcional).');
            $row('Cómo se dimensiona', '<textarea class="large-text" rows="3" name="sizing">' . esc_textarea($b['sizing'] ?? '') . '</textarea>', 'Qué datos hay que levantar para cotizar. Si no se sabe, déjalo vacío: el asistente derivará a un ejecutivo.');
            $row('Entrada recomendada', '<input type="text" class="large-text" name="entry_point" value="' . esc_attr($b['entry_point'] ?? '') . '">', 'Primer paso comercial (assessment, piloto, POV, workshop…).');
            $row('Preguntas para calificar', '<textarea class="large-text" rows="3" name="qualifying_questions">' . esc_textarea($lines('qualifying_questions')) . '</textarea>', 'Una por línea.');
            ?>
        </table>
        <?php submit_button('Guardar marca'); ?>
        <a href="<?php echo esc_url(cpnnet_asistente_admin_url(['tab' => 'conocimiento'])); ?>">← Volver a la lista</a>
    </form>
    <?php
}

add_action('admin_post_cpnnet_asistente_kb_save', function () {
    cpnnet_asistente_admin_guard();
    $kb   = cpnnet_asistente_kb();
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    if ($name === '') {
        cpnnet_asistente_admin_redirect('conocimiento', 'invalid');
    }
    $cat_ids = array_column($kb['categories'], 'id');
    $post    = static fn(string $k): string => (string) wp_unslash($_POST[$k] ?? '');
    $brand = [
        'name'                => $name,
        'tier'                => $post('tier') === 'cross_selling' ? 'cross_selling' : 'primer_orden',
        'category'            => in_array($post('category'), $cat_ids, true) ? $post('category') : ($cat_ids[0] ?? ''),
        'tagline'             => sanitize_text_field($post('tagline')),
        'summary'             => sanitize_textarea_field($post('summary')),
        'problems'            => cpnnet_asistente_lines($post('problems')),
        'differentiators'     => cpnnet_asistente_lines($post('differentiators')),
        'ideal_for'           => cpnnet_asistente_lines($post('ideal_for')),
        'capabilities'        => cpnnet_asistente_lines($post('capabilities')),
        'sizing'              => sanitize_textarea_field($post('sizing')),
        'entry_point'         => sanitize_text_field($post('entry_point')),
        'qualifying_questions' => cpnnet_asistente_lines($post('qualifying_questions')),
    ];
    $original = sanitize_text_field($post('brand_id')) ?: null;
    if ($original !== null && !cpnnet_asistente_find_brand($kb, $original)) {
        $original = null;
    }
    $kb = cpnnet_asistente_kb_upsert($kb, $brand, $original);
    update_option(CPNNET_ASISTENTE_OPT_KB, $kb, false);
    cpnnet_asistente_admin_redirect('conocimiento', 'saved');
});

add_action('admin_post_cpnnet_asistente_kb_delete', function () {
    cpnnet_asistente_admin_guard();
    $id = sanitize_text_field(wp_unslash($_POST['brand_id'] ?? ''));
    update_option(CPNNET_ASISTENTE_OPT_KB, cpnnet_asistente_kb_delete(cpnnet_asistente_kb(), $id), false);
    cpnnet_asistente_admin_redirect('conocimiento', 'deleted');
});

add_action('admin_post_cpnnet_asistente_kb_reset', function () {
    cpnnet_asistente_admin_guard();
    delete_option(CPNNET_ASISTENTE_OPT_KB);
    cpnnet_asistente_admin_redirect('conocimiento', 'reset');
});

/* ---------- Pestaña Reglas y empresa ---------- */

function cpnnet_asistente_tab_reglas(): void
{
    ?>
    <p>Aquí se define <strong>cómo se comporta</strong> el asistente (tono, cómo detecta si habla con un partner o una empresa, cuándo deriva a un ejecutivo) y los <strong>datos de la empresa</strong>.
       Escríbelos en español, en lenguaje natural. Si algo queda mal, el botón «Restaurar» vuelve al texto original.</p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
        <input type="hidden" name="action" value="cpnnet_asistente_rules_save">
        <h3>Reglas del asistente (~<?php echo esc_html(number_format_i18n(cpnnet_asistente_approx_tokens(cpnnet_asistente_rules()))); ?> tokens)</h3>
        <textarea class="large-text code" rows="22" name="rules"><?php echo esc_textarea(cpnnet_asistente_rules()); ?></textarea>
        <h3>Datos de la empresa (~<?php echo esc_html(number_format_i18n(cpnnet_asistente_approx_tokens(cpnnet_asistente_company()))); ?> tokens)</h3>
        <textarea class="large-text code" rows="14" name="company"><?php echo esc_textarea(cpnnet_asistente_company()); ?></textarea>
        <?php submit_button('Guardar'); ?>
    </form>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('¿Restaurar reglas y datos de la empresa originales? Se perderán tus cambios.');">
        <?php wp_nonce_field('cpnnet_asistente_admin'); ?>
        <input type="hidden" name="action" value="cpnnet_asistente_rules_reset">
        <button class="button">Restaurar textos originales</button>
    </form>
    <?php
}

add_action('admin_post_cpnnet_asistente_rules_save', function () {
    cpnnet_asistente_admin_guard();
    $clean = static fn(string $k): string => mb_substr(trim(sanitize_textarea_field(wp_unslash($_POST[$k] ?? ''))), 0, 30000);
    update_option(CPNNET_ASISTENTE_OPT_RULES, $clean('rules'), false);
    update_option(CPNNET_ASISTENTE_OPT_COMPANY, $clean('company'), false);
    cpnnet_asistente_admin_redirect('reglas', 'saved');
});

add_action('admin_post_cpnnet_asistente_rules_reset', function () {
    cpnnet_asistente_admin_guard();
    delete_option(CPNNET_ASISTENTE_OPT_RULES);
    delete_option(CPNNET_ASISTENTE_OPT_COMPANY);
    cpnnet_asistente_admin_redirect('reglas', 'reset');
});

/* ---------- Pestaña Uso y costos ---------- */

function cpnnet_asistente_tab_uso(): void
{
    $budget = (float) cpnnet_asistente_get('monthly_budget');
    $month  = cpnnet_asistente_month_spend();
    $since  = gmdate('Y-m-d 00:00:00', time() - 30 * DAY_IN_SECONDS);
    $s      = cpnnet_asistente_usage_summary($since);
    $avg    = $s['messages'] ? $s['cost'] / $s['messages'] : 0.0;
    $usd    = static fn(float $v): string => 'US$ ' . number_format($v, 2, ',', '.');
    $n      = static fn(int $v): string => number_format_i18n($v);
    ?>
    <p>Consumo registrado por este plugin. Los valores en dólares son <strong>estimaciones</strong> con precios de lista; la factura real está en la consola de Anthropic de la cuenta dueña de la API key.</p>
    <table class="widefat striped" style="max-width:720px">
        <tr><th>Gasto estimado este mes</th><td><strong><?php echo esc_html($usd($month)); ?></strong><?php echo $budget > 0 ? ' de un tope de ' . esc_html($usd($budget)) . ' (' . esc_html((string) round($month / $budget * 100)) . '%)' : ' (sin tope configurado)'; ?></td></tr>
        <tr><th>Últimos 30 días: mensajes atendidos</th><td><?php echo esc_html($n($s['messages'])); ?></td></tr>
        <tr><th>Últimos 30 días: gasto estimado</th><td><?php echo esc_html($usd($s['cost'])); ?> (≈ <?php echo esc_html($usd($avg)); ?> por mensaje)</td></tr>
        <tr><th>Tokens de entrada / salida</th><td><?php echo esc_html($n($s['input'])); ?> / <?php echo esc_html($n($s['output'])); ?></td></tr>
        <tr><th>Tokens de caché (lectura / escritura)</th><td><?php echo esc_html($n($s['read'])); ?> / <?php echo esc_html($n($s['write'])); ?></td></tr>
    </table>
    <h3>Por día</h3>
    <table class="widefat striped" style="max-width:520px">
        <thead><tr><th>Día</th><th>Mensajes</th><th>Gasto estimado</th></tr></thead>
        <tbody>
        <?php foreach (cpnnet_asistente_usage_by_day(30) as $d) : ?>
            <tr><td><?php echo esc_html($d['day']); ?></td><td><?php echo esc_html($d['messages']); ?></td><td><?php echo esc_html($usd((float) $d['cost'])); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Cómo bajar el costo</h3>
    <ul style="list-style:disc;margin-left:20px">
        <li>Cambiar a un modelo más económico (pestaña General).</li>
        <li>Acortar el contenido: cada marca, y las reglas, se envían en cada mensaje.</li>
        <li>Mantener el esfuerzo de razonamiento en «Bajo».</li>
        <li>Fijar el tope mensual y créditos prepagados en la consola de Anthropic.</li>
    </ul>
    <?php
}
