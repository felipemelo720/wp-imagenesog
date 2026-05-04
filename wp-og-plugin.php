<?php
/*
Plugin Name: OG Images Auto
Plugin URI: https://github.com/felipemelo720/satori-og
Description: Genera imágenes OG automáticas para productos WooCommerce
Version: 1.6.0
Author: Felipe Melo
License: GPL2
Update URI: https://github.com/felipemelo720/satori-og
*/

if (!defined('ABSPATH')) exit;

// Definir constantes para auto-actualización
define('OG_IMAGES_VERSION', '1.6.0');
define('OG_IMAGES_PLUGIN_FILE', __FILE__);

// Cargar auto-actualizador
require_once plugin_dir_path(__FILE__) . 'includes/updater.php';

add_action('wp_head', 'og_images_add_meta', 5);

function og_images_add_meta() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    $options = get_option('og_images_settings', array());

    $title = get_the_title();
    $product_id = get_the_ID();
    $price = get_post_meta($product_id, '_regular_price', true);
    $sale_price = get_post_meta($product_id, '_sale_price', true);
    $product_url = get_permalink($product_id);
    $site_name = get_bloginfo('name');

    // Obtener imagen del producto
    $product_image = get_the_post_thumbnail_url($product_id, 'large');

    // Moneda configurable
    $currency = !empty($options['currency']) ? $options['currency'] : '$';
    $price_formatted = $price ? number_format((float)$price, 0, ',', '.') : '0';
    $sale_price_formatted = $sale_price ? number_format((float)$sale_price, 0, ',', '.') : '';

    $params = array(
        'title' => $title,
        'price' => $currency . $price_formatted,
    );

    // Precio de oferta
    if ($sale_price_formatted) {
        $params['salePrice'] = $currency . $sale_price_formatted;
    }

    // Agregar imagen del producto si existe
    if ($product_image) {
        $params['image'] = $product_image;
    }

    if (!empty($options['logo'])) {
        $params['logo'] = esc_url_raw($options['logo']);
    }
    if (!empty($options['bg_color'])) {
        $color = sanitize_hex_color($options['bg_color']);
        $params['bgColor'] = $color ? $color : '#0E252C';
    }
    if (!empty($options['text_color'])) {
        $color = sanitize_hex_color($options['text_color']);
        $params['textColor'] = $color ? $color : '#FFFFFF';
    }
    if (!empty($options['accent_color'])) {
        $color = sanitize_hex_color($options['accent_color']);
        $params['accentColor'] = $color ? $color : '#98CC3F';
    }
    // Template configurable
    if (!empty($options['template'])) {
        $params['template'] = sanitize_text_field($options['template']);
    }
    // Font configurable
    if (!empty($options['font'])) {
        $params['font'] = sanitize_text_field($options['font']);
    }
    // CTA configurable
    if (!empty($options['cta'])) {
        $params['cta'] = sanitize_text_field($options['cta']);
    }

    $api_url = !empty($options['api_url']) ? esc_url_raw($options['api_url']) : 'https://satori-og.vercel.app/api/og';
    $og_url = add_query_arg($params, $api_url);

    echo "\n<!-- OG Images Auto -->\n";
    // fb:app_id configurable
    if (!empty($options['fb_app_id'])) {
        echo '<meta property="fb:app_id" content="' . esc_attr($options['fb_app_id']) . '">' . "\n";
    }
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    $display_price = $sale_price_formatted ? $currency . $sale_price_formatted : $currency . $price_formatted;
    echo '<meta property="og:description" content="' . esc_attr($display_price . ' · ' . $site_name) . '">' . "\n";
    echo '<meta property="og:type" content="product">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($product_url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    echo '<meta property="og:locale" content="es_CL">' . "\n";
    echo '<meta property="og:image" content="' . esc_attr($og_url) . '">' . "\n";
    echo '<meta property="og:image:secure_url" content="' . esc_attr($og_url) . '">' . "\n";
    echo '<meta property="og:image:type" content="image/png">' . "\n";
    echo '<meta property="og:image:width" content="1200">' . "\n";
    echo '<meta property="og:image:height" content="630">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($display_price . ' · ' . $site_name) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_attr($og_url) . '">' . "\n";
    echo "<!-- /OG Images Auto -->\n\n";
}

add_action('admin_menu', 'og_images_add_admin_menu');

function og_images_add_admin_menu() {
    add_options_page('OG Images', 'OG Images', 'manage_options', 'og-images', 'og_images_options_page');
}

add_action('admin_init', 'og_images_register_settings');

function og_images_register_settings() {
    register_setting('og_images_settings_group', 'og_images_settings', 'og_images_sanitize_settings');

    add_settings_section('og_images_main_section', 'Configuración', 'og_images_section_callback', 'og-images');

    $fields = array(
        'api_url' => array('URL de la API', 'Dejar por defecto o usar tu propia API'),
        'logo' => array('URL del Logo', 'URL completa de tu logo (PNG/JPG recomendado 300x120px)'),
        'fb_app_id' => array('Facebook App ID', 'ID de tu app de Facebook (opcional, elimina advertencia)'),
        'currency' => array('Moneda', 'Símbolo de moneda (ej: $, €, S/, USD)'),
        'template' => array('Template', 'Estilo de imagen: default, minimal, gradient, split, promo'),
        'font' => array('Fuente', 'Tipografía: inter, montserrat, opensans, playfair'),
        'cta' => array('Texto CTA', 'Texto del botón de acción (ej: Ver producto, Comprar ahora)'),
        'bg_color' => array('Color de fondo', 'Color de fondo de la imagen'),
        'text_color' => array('Color de texto', 'Color del texto principal'),
        'accent_color' => array('Color de acento', 'Color para precio y elementos destacados'),
    );

    foreach ($fields as $key => $label) {
        add_settings_field($key, $label[0], 'og_images_field_callback', 'og-images', 'og_images_main_section', array('field' => $key, 'description' => $label[1]));
    }
}

function og_images_sanitize_settings($input) {
    $sanitized = array();
    if (!empty($input['api_url'])) {
        $sanitized['api_url'] = esc_url_raw($input['api_url']);
    }
    if (!empty($input['logo'])) {
        $sanitized['logo'] = esc_url_raw($input['logo']);
    }
    if (!empty($input['fb_app_id'])) {
        $sanitized['fb_app_id'] = sanitize_text_field($input['fb_app_id']);
    }
    if (!empty($input['currency'])) {
        $sanitized['currency'] = sanitize_text_field($input['currency']);
    }
    if (!empty($input['template'])) {
        $valid_templates = array('default', 'minimal', 'gradient', 'split', 'promo');
        $val = sanitize_text_field($input['template']);
        $sanitized['template'] = in_array($val, $valid_templates) ? $val : 'default';
    }
    if (!empty($input['font'])) {
        $valid_fonts = array('inter', 'montserrat', 'opensans', 'playfair');
        $val = sanitize_text_field($input['font']);
        $sanitized['font'] = in_array($val, $valid_fonts) ? $val : 'inter';
    }
    if (!empty($input['cta'])) {
        $sanitized['cta'] = sanitize_text_field($input['cta']);
    }
    if (!empty($input['bg_color'])) {
        $sanitized['bg_color'] = sanitize_hex_color($input['bg_color']);
    }
    if (!empty($input['text_color'])) {
        $sanitized['text_color'] = sanitize_hex_color($input['text_color']);
    }
    if (!empty($input['accent_color'])) {
        $sanitized['accent_color'] = sanitize_hex_color($input['accent_color']);
    }
    return $sanitized;
}

function og_images_section_callback() {
    echo '<p>Configura la apariencia de las imágenes OG generadas automáticamente.</p>';
}

function og_images_field_callback($args) {
    $options = get_option('og_images_settings', array());
    $field = $args['field'];
    $defaults = array(
        'api_url' => 'https://satori-og.vercel.app/api/og',
        'logo' => '',
        'currency' => '$',
        'template' => 'default',
        'font' => 'inter',
        'cta' => 'Ver producto',
        'bg_color' => '#0E252C',
        'text_color' => '#FFFFFF',
        'accent_color' => '#98CC3F',
    );
    $value = isset($options[$field]) ? $options[$field] : (isset($defaults[$field]) ? $defaults[$field] : '');

    if (in_array($field, array('bg_color', 'text_color', 'accent_color'))) {
        echo '<input type="color" name="og_images_settings[' . esc_attr($field) . ']" value="' . esc_attr($value) . '">';
    } elseif ($field === 'template') {
        $templates = array('default' => 'Default', 'minimal' => 'Minimal', 'gradient' => 'Gradient', 'split' => 'Split', 'promo' => 'Promo');
        echo '<select name="og_images_settings[' . esc_attr($field) . ']">';
        foreach ($templates as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    } elseif ($field === 'font') {
        $fonts = array('inter' => 'Inter', 'montserrat' => 'Montserrat', 'opensans' => 'Open Sans', 'playfair' => 'Playfair Display');
        echo '<select name="og_images_settings[' . esc_attr($field) . ']">';
        foreach ($fonts as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    } else {
        echo '<input type="text" name="og_images_settings[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" class="regular-text">';
    }
    echo '<p class="description">' . esc_html($args['description']) . '</p>';
}

function og_images_options_page() {
    $options = get_option('og_images_settings', array());
    $api_url = !empty($options['api_url']) ? $options['api_url'] : 'https://satori-og.vercel.app/api/og';
    $currency = !empty($options['currency']) ? $options['currency'] : '$';
    $template = !empty($options['template']) ? $options['template'] : 'default';
    $font = !empty($options['font']) ? $options['font'] : 'inter';
    $cta = !empty($options['cta']) ? $options['cta'] : 'Ver producto';
    $preview_params = array(
        'title' => 'Producto de Ejemplo',
        'price' => $currency . '12.990',
        'salePrice' => $currency . '9.990',
        'logo' => isset($options['logo']) ? $options['logo'] : '',
        'bgColor' => isset($options['bg_color']) ? $options['bg_color'] : '#0E252C',
        'textColor' => isset($options['text_color']) ? $options['text_color'] : '#FFFFFF',
        'accentColor' => isset($options['accent_color']) ? $options['accent_color'] : '#98CC3F',
        'template' => $template,
        'font' => $font,
        'cta' => $cta,
    );
    $preview_url = add_query_arg($preview_params, $api_url);
?>
    <div class="wrap">
        <h1>OG Images - Configuración</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('og_images_settings_group');
            do_settings_sections('og-images');
            submit_button('Guardar cambios');
            ?>
        </form>
        <hr>
        <h2>Vista previa</h2>
        <p>Así se verá la imagen OG generada:</p>
        <div style="background: #f0f0f1; padding: 20px; border-radius: 8px; display: inline-block;">
            <img src="<?php echo esc_url($preview_url); ?>" style="max-width: 600px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        </div>
        <p class="description">Guarda los cambios para actualizar la vista previa.</p>
        <hr>
        <h2>Cómo probar</h2>
        <ol>
            <li>Abre cualquier producto de tu tienda</li>
            <li>Ve el código fuente (Ctrl+U) y busca "og:image"</li>
            <li>Usa el <a href="https://developers.facebook.com/tools/debug/" target="_blank">Facebook Debugger</a> para verificar</li>
            <li>Comparte el link en WhatsApp o Facebook para ver el resultado</li>
        </ol>
    </div>
<?php
}
