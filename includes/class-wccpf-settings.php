<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCPF_Settings {
	const OPTION_ENABLED = 'wccpf_enabled';
	const OPTION_PERCENTAGE = 'wccpf_percentage';
	const OPTION_INCLUDE_SHIPPING = 'wccpf_include_shipping';
	const OPTION_FEE_LABEL = 'wccpf_fee_label';
	const OPTION_CHECKOUT_LABEL = 'wccpf_checkout_label';

	public static function init() {
		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_page' ) );
			add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'sanitize_option' ), 10, 3 );
			add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu_link' ), 70 );
			add_filter( 'plugin_action_links_' . plugin_basename( WCCPF_PLUGIN_FILE ), array( __CLASS__, 'add_plugin_action_link' ) );
		}
	}

	public static function add_settings_page( $settings ) {
		if ( ! class_exists( 'WC_Settings_Page' ) ) {
			return $settings;
		}

		if ( ! class_exists( 'WCCPF_Settings_Page' ) ) {
			require_once WCCPF_PLUGIN_DIR . 'includes/class-wccpf-settings-page.php';
		}

		if ( class_exists( 'WCCPF_Settings_Page' ) ) {
			$settings[] = new WCCPF_Settings_Page();
		}
		return $settings;
	}

	public static function add_admin_menu_link() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			__( 'VAT Fee', 'optional-vat-fee-for-woocommerce' ),
			__( 'VAT Fee', 'optional-vat-fee-for-woocommerce' ),
			'manage_woocommerce',
			'wccpf-settings',
			array( __CLASS__, 'render_settings_redirect' )
		);
	}

	public static function add_plugin_action_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-settings&tab=wccpf' ) ),
			esc_html__( 'Settings', 'optional-vat-fee-for-woocommerce' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	public static function render_settings_redirect() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'optional-vat-fee-for-woocommerce' ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=wccpf' ) );
		exit;
	}

	public static function sanitize_option( $value, $option, $raw_value ) {
		if ( empty( $option['id'] ) ) {
			return $value;
		}

		switch ( $option['id'] ) {
			case self::OPTION_PERCENTAGE:
				return self::sanitize_percentage( $raw_value );
			case self::OPTION_FEE_LABEL:
			case self::OPTION_CHECKOUT_LABEL:
				return sanitize_text_field( $raw_value );
			case self::OPTION_ENABLED:
			case self::OPTION_INCLUDE_SHIPPING:
				return wc_string_to_bool( $raw_value ) ? 'yes' : 'no';
			default:
				return $value;
		}
	}

	public static function is_enabled() {
		$enabled = 'yes' === get_option( self::OPTION_ENABLED, 'yes' );
		return (bool) apply_filters( 'wccpf_is_enabled', $enabled );
	}

	public static function get_percentage() {
		$percentage = get_option( self::OPTION_PERCENTAGE, '10' );
		$percentage = wc_format_decimal( $percentage, 4 );
		$percentage = (float) $percentage;
		$percentage = max( 0, min( 100, $percentage ) );

		return (float) apply_filters( 'wccpf_percentage', $percentage );
	}

	public static function include_shipping() {
		$include = 'yes' === get_option( self::OPTION_INCLUDE_SHIPPING, 'no' );
		return (bool) apply_filters( 'wccpf_include_shipping', $include );
	}

	public static function get_fee_label_base() {
		$label = get_option( self::OPTION_FEE_LABEL, __( 'VAT', 'optional-vat-fee-for-woocommerce' ) );
		$label = sanitize_text_field( $label );

		if ( '' === $label ) {
			$label = __( 'VAT', 'optional-vat-fee-for-woocommerce' );
		}

		return $label;
	}

	public static function get_checkout_label_template() {
		$label = get_option( self::OPTION_CHECKOUT_LABEL, __( 'Add {percentage}% VAT', 'optional-vat-fee-for-woocommerce' ) );
		$label = sanitize_text_field( $label );

		if ( '' === $label ) {
			$label = __( 'Add {percentage}% VAT', 'optional-vat-fee-for-woocommerce' );
		}

		return $label;
	}

	private static function sanitize_percentage( $raw_value ) {
		$value = wc_format_decimal( $raw_value, 4 );
		$value = (float) $value;
		$value = max( 0, min( 100, $value ) );

		return $value;
	}
}
