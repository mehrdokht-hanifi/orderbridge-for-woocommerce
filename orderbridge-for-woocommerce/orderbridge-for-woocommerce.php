<?php
/**
 * Plugin Name: OrderBridge for WooCommerce
 * Description: Reliable WooCommerce order synchronization with external ERP systems using signed webhooks, retries, and an audit log.
 * Version: 1.0.0
 * Author: Mehrdokht Hanifi
 * License: GPL-2.0-or-later
 * Text Domain: orderbridge-for-woocommerce
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.0
 */

defined( 'ABSPATH' ) || exit;

define( 'OBWC_VERSION', '1.0.0' );
define( 'OBWC_FILE', __FILE__ );
define( 'OBWC_PATH', plugin_dir_path( __FILE__ ) );

require_once OBWC_PATH . 'includes/class-obwc-crypto.php';
require_once OBWC_PATH . 'includes/class-obwc-logger.php';
require_once OBWC_PATH . 'includes/class-obwc-payload.php';
require_once OBWC_PATH . 'includes/class-obwc-client.php';
require_once OBWC_PATH . 'includes/class-obwc-sync.php';
require_once OBWC_PATH . 'includes/class-obwc-rest.php';
require_once OBWC_PATH . 'includes/class-obwc-admin.php';
require_once OBWC_PATH . 'includes/class-obwc-plugin.php';

register_activation_hook( __FILE__, array( 'OBWC_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OBWC_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'OrderBridge requires WooCommerce.', 'orderbridge-for-woocommerce' ) . '</p></div>';
		} );
		return;
	}
	OBWC_Plugin::instance();
} );

add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
