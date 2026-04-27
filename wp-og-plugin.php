<?php
/**
 * Plugin Name: OG Image for WooCommerce
 * Plugin URI:  https://github.com/your-username/wp-og-plugin
 * Description: Generates branded Open Graph images for WooCommerce products via Satori OG API
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * Author:      Your Name
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-og-plugin
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_OG_PLUGIN_VERSION', '1.0.0' );
define( 'WP_OG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_OG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WP_OG_PLUGIN_PATH . 'includes/class-settings.php';
require_once WP_OG_PLUGIN_PATH . 'includes/class-og-injector.php';

new WP_OG_Settings();
new WP_OG_Injector();
