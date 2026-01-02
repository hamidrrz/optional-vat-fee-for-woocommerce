<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCPF_Fees {
	public static function init() {
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_fee' ) );
	}

	public static function add_fee( $cart ) {
		if ( ! $cart || ! is_a( $cart, 'WC_Cart' ) ) {
			return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! is_checkout() && ! self::is_checkout_ajax() ) {
			return;
		}

		if ( $cart->is_empty() ) {
			return;
		}

		if ( ! WCCPF_Settings::is_enabled() ) {
			return;
		}

		if ( ! WCCPF_Checkout::is_selected() ) {
			return;
		}

		$percentage = WCCPF_Settings::get_percentage();
		if ( $percentage <= 0 ) {
			return;
		}

		$amount = self::get_fee_amount( $cart, $percentage );
		if ( $amount <= 0 ) {
			return;
		}

		$label = self::get_fee_label( $percentage );

		$cart->add_fee( $label, $amount, false );
	}

	public static function get_fee_amount( $cart, $percentage ) {
		if ( ! $cart || ! is_a( $cart, 'WC_Cart' ) ) {
			return null;
		}

		$base = (float) $cart->get_cart_contents_total();
		if ( WCCPF_Settings::include_shipping() ) {
			$base += (float) $cart->get_shipping_total();
		}

		$base = max( 0, $base );
		if ( $base <= 0 || $percentage <= 0 ) {
			return 0;
		}

		$amount = $base * ( (float) $percentage / 100 );
		$amount = round( $amount, wc_get_price_decimals() );

		return $amount;
	}

	public static function get_fee_label( $percentage ) {
		$label = WCCPF_Settings::get_fee_label_base();
		$label = sprintf( '%s (%s%%)', $label, $percentage );

		return apply_filters( 'wccpf_fee_label', $label, $percentage );
	}

	private static function is_checkout_ajax() {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		$action = filter_input( INPUT_GET, 'wc-ajax', FILTER_UNSAFE_RAW );
		if ( null === $action ) {
			$action = filter_input( INPUT_POST, 'wc-ajax', FILTER_UNSAFE_RAW );
		}
		$action = is_string( $action ) ? sanitize_key( $action ) : '';

		if ( 'update_order_review' !== $action ) {
			return false;
		}

		$nonce = filter_input( INPUT_POST, 'security', FILTER_UNSAFE_RAW );
		$nonce = is_string( $nonce ) ? sanitize_text_field( $nonce ) : '';

		if ( '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'update-order-review' );
	}
}
