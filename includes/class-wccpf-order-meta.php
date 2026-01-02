<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCPF_Order_Meta {
	public static function init() {
		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'render_admin_meta' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'render_customer_meta' ) );
		add_filter( 'woocommerce_email_order_meta_fields', array( __CLASS__, 'add_email_meta' ), 10, 3 );
	}

	public static function render_admin_meta( $order ) {
		$meta = self::get_meta( $order );
		if ( empty( $meta ) ) {
			return;
		}

		echo '<div class="wccpf-order-meta">';
		echo '<p><strong>' . esc_html__( 'VAT option:', 'optional-vat-fee-for-woocommerce' ) . '</strong> ' . esc_html( $meta['enabled'] ) . '</p>';

		if ( ! empty( $meta['percentage'] ) ) {
			echo '<p><strong>' . esc_html__( 'VAT percentage:', 'optional-vat-fee-for-woocommerce' ) . '</strong> ' . esc_html( $meta['percentage'] ) . '</p>';
		}

		if ( ! empty( $meta['amount'] ) ) {
			echo '<p><strong>' . esc_html__( 'VAT amount:', 'optional-vat-fee-for-woocommerce' ) . '</strong> ' . wp_kses_post( $meta['amount'] ) . '</p>';
		}

		if ( ! empty( $meta['person_type'] ) ) {
			echo '<p><strong>' . esc_html__( 'Person type:', 'optional-vat-fee-for-woocommerce' ) . '</strong> ' . esc_html( $meta['person_type'] ) . '</p>';
		}

		if ( ! empty( $meta['national_code'] ) ) {
			echo '<p><strong>' . esc_html__( 'National code:', 'optional-vat-fee-for-woocommerce' ) . '</strong> ' . esc_html( $meta['national_code'] ) . '</p>';
		}

		if ( ! empty( $meta['legal_name'] ) ) {
			echo '<p><strong>' . esc_html__( 'Legal entity name:', 'optional-vat-fee-for-woocommerce' ) . '</strong> ' . esc_html( $meta['legal_name'] ) . '</p>';
		}

		if ( ! empty( $meta['legal_id'] ) ) {
			echo '<p><strong>' . esc_html__( 'Legal national ID:', 'optional-vat-fee-for-woocommerce' ) . '</strong> ' . esc_html( $meta['legal_id'] ) . '</p>';
		}

		echo '</div>';
	}

	public static function render_customer_meta( $order ) {
		$meta = self::get_meta( $order );
		if ( empty( $meta ) ) {
			return;
		}

		echo '<section class="woocommerce-wccpf">';
		echo '<h2>' . esc_html__( 'VAT option', 'optional-vat-fee-for-woocommerce' ) . '</h2>';
		echo '<p>' . esc_html( $meta['enabled'] ) . '</p>';

		if ( ! empty( $meta['percentage'] ) ) {
			echo '<p>' . esc_html__( 'Percentage:', 'optional-vat-fee-for-woocommerce' ) . ' ' . esc_html( $meta['percentage'] ) . '</p>';
		}

		if ( ! empty( $meta['amount'] ) ) {
			echo '<p>' . esc_html__( 'Amount:', 'optional-vat-fee-for-woocommerce' ) . ' ' . wp_kses_post( $meta['amount'] ) . '</p>';
		}

		if ( ! empty( $meta['person_type'] ) ) {
			echo '<p>' . esc_html__( 'Person type:', 'optional-vat-fee-for-woocommerce' ) . ' ' . esc_html( $meta['person_type'] ) . '</p>';
		}

		if ( ! empty( $meta['national_code'] ) ) {
			echo '<p>' . esc_html__( 'National code:', 'optional-vat-fee-for-woocommerce' ) . ' ' . esc_html( $meta['national_code'] ) . '</p>';
		}

		if ( ! empty( $meta['legal_name'] ) ) {
			echo '<p>' . esc_html__( 'Legal entity name:', 'optional-vat-fee-for-woocommerce' ) . ' ' . esc_html( $meta['legal_name'] ) . '</p>';
		}

		if ( ! empty( $meta['legal_id'] ) ) {
			echo '<p>' . esc_html__( 'Legal national ID:', 'optional-vat-fee-for-woocommerce' ) . ' ' . esc_html( $meta['legal_id'] ) . '</p>';
		}

		echo '</section>';
	}

	public static function add_email_meta( $fields, $sent_to_admin, $order ) {
		$meta = self::get_meta( $order );
		if ( empty( $meta ) ) {
			return $fields;
		}

		$fields['wccpf_service_fee_option'] = array(
			'label' => __( 'VAT option', 'optional-vat-fee-for-woocommerce' ),
			'value' => $meta['enabled'],
		);

		if ( ! empty( $meta['percentage'] ) ) {
			$fields['wccpf_service_fee_percentage'] = array(
				'label' => __( 'VAT percentage', 'optional-vat-fee-for-woocommerce' ),
				'value' => $meta['percentage'],
			);
		}

		if ( ! empty( $meta['amount'] ) ) {
			$fields['wccpf_service_fee_amount'] = array(
				'label' => __( 'VAT amount', 'optional-vat-fee-for-woocommerce' ),
				'value' => wp_strip_all_tags( $meta['amount'] ),
			);
		}

		if ( ! empty( $meta['person_type'] ) ) {
			$fields['wccpf_person_type'] = array(
				'label' => __( 'Person type', 'optional-vat-fee-for-woocommerce' ),
				'value' => $meta['person_type'],
			);
		}

		if ( ! empty( $meta['national_code'] ) ) {
			$fields['wccpf_national_code'] = array(
				'label' => __( 'National code', 'optional-vat-fee-for-woocommerce' ),
				'value' => $meta['national_code'],
			);
		}

		if ( ! empty( $meta['legal_name'] ) ) {
			$fields['wccpf_legal_name'] = array(
				'label' => __( 'Legal entity name', 'optional-vat-fee-for-woocommerce' ),
				'value' => $meta['legal_name'],
			);
		}

		if ( ! empty( $meta['legal_id'] ) ) {
			$fields['wccpf_legal_id'] = array(
				'label' => __( 'Legal national ID', 'optional-vat-fee-for-woocommerce' ),
				'value' => $meta['legal_id'],
			);
		}

		return $fields;
	}

	private static function get_meta( $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return array();
		}

		$enabled = $order->get_meta( WCCPF_Checkout::META_KEY_ENABLED );
		if ( '' === $enabled ) {
			return array();
		}

		$percentage  = $order->get_meta( WCCPF_Checkout::META_KEY_PERCENTAGE );
		$amount      = $order->get_meta( WCCPF_Checkout::META_KEY_AMOUNT );
		$person_type = $order->get_meta( WCCPF_Checkout::META_KEY_PERSON_TYPE );
		$national    = $order->get_meta( WCCPF_Checkout::META_KEY_NATIONAL_CODE );
		$legal_name  = $order->get_meta( WCCPF_Checkout::META_KEY_LEGAL_NAME );
		$legal_id    = $order->get_meta( WCCPF_Checkout::META_KEY_LEGAL_ID );

		$enabled_text = ( 'yes' === $enabled ) ? __( 'Yes', 'optional-vat-fee-for-woocommerce' ) : __( 'No', 'optional-vat-fee-for-woocommerce' );

		$currency = $order->get_currency();
		$amount   = '' !== $amount ? wc_price( $amount, array( 'currency' => $currency ) ) : '';

		$percentage_text = '' !== $percentage ? sprintf( '%s%%', $percentage ) : '';
		$person_label    = '';
		if ( 'individual' === $person_type ) {
			$person_label = __( 'Individual', 'optional-vat-fee-for-woocommerce' );
		} elseif ( 'legal' === $person_type ) {
			$person_label = __( 'Legal entity', 'optional-vat-fee-for-woocommerce' );
		}

		return array(
			'enabled'    => $enabled_text,
			'percentage' => $percentage_text,
			'amount'     => $amount,
			'person_type' => $person_label,
			'national_code' => $national,
			'legal_name' => $legal_name,
			'legal_id'   => $legal_id,
		);
	}
}
