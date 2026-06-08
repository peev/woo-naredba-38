<?php
/**
 * Plugin Name:       НАП Приложение 38 – WooCommerce XML Export
 * Plugin URI:        https://github.com/peev/woo-naredba-38
 * Description:       Генерира стандартизиран XML одиторски файл (Приложение №38) за НАП от WooCommerce поръчки. Безплатен завинаги, с отворен код.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            peev
 * Author URI:        https://github.com/peev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nap38
 * WC requires at least: 6.0
 * WC tested up to:   9.8
 *
 * @package NAP38
 */

defined( 'ABSPATH' ) || exit;

define( 'NAP38_VERSION', '1.0.0' );
define( 'NAP38_PLUGIN_FILE', __FILE__ );
define( 'NAP38_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NAP38_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with WooCommerce features.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', NAP38_PLUGIN_FILE, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', NAP38_PLUGIN_FILE, true );
    }
} );

/**
 * Show admin notice when WooCommerce is missing.
 */
add_action( 'admin_init', function () {
    if ( class_exists( 'WooCommerce' ) ) {
        return;
    }

    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'НАП Приложение 38:', 'nap38' ) . '</strong> ' . esc_html__( 'Изисква активен WooCommerce.', 'nap38' ) . '</p></div>';
    } );
} );

require_once NAP38_PLUGIN_DIR . 'includes/class-nap38-settings.php';
require_once NAP38_PLUGIN_DIR . 'includes/class-nap38-generator.php';
require_once NAP38_PLUGIN_DIR . 'includes/class-nap38-admin.php';

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    NAP38_Settings::init();
    NAP38_Admin::init();
} );
