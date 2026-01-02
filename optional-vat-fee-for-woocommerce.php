<?php
/**
 * Plugin Name: Optional VAT Fee for WooCommerce
 * Description: Adds an optional VAT percentage on checkout when customers opt in.
 * Version: 1.0.0
 * Author: Hamidreza Rezaei
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: optional-vat-fee-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCCPF_VERSION', '1.0.0' );
define( 'WCCPF_PLUGIN_FILE', __FILE__ );
define( 'WCCPF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCCPF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCCPF_TEXT_DOMAIN', 'optional-vat-fee-for-woocommerce' );

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

function wccpf_missing_wc_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Optional VAT Fee for WooCommerce requires WooCommerce to be installed and active.', 'optional-vat-fee-for-woocommerce' )
	);
}

function wccpf_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wccpf_missing_wc_notice' );
		return;
	}

	require_once WCCPF_PLUGIN_DIR . 'includes/class-wccpf-plugin.php';

	new WCCPF_Plugin();
}

add_action( 'plugins_loaded', 'wccpf_bootstrap' );
