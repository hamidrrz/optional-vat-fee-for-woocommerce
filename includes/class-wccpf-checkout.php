<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCPF_Checkout {
	const FIELD_ID = 'wccpf_enable_fee';
	const SESSION_KEY = 'wccpf_enable_fee';
	const FIELD_PERSON_TYPE = 'wccpf_person_type';
	const FIELD_NATIONAL_CODE = 'wccpf_national_code';
	const FIELD_LEGAL_NAME = 'wccpf_legal_name';
	const FIELD_LEGAL_ID = 'wccpf_legal_id';
	const USER_META_PERSON_TYPE = 'wccpf_person_type';
	const USER_META_NATIONAL_CODE = 'wccpf_national_code';
	const USER_META_LEGAL_NAME = 'wccpf_legal_name';
	const USER_META_LEGAL_ID = 'wccpf_legal_id';
	const META_KEY_ENABLED = 'wccpf_enable_fee';
	const META_KEY_PERCENTAGE = 'wccpf_fee_percentage';
	const META_KEY_AMOUNT = 'wccpf_fee_amount';
	const META_KEY_LABEL = 'wccpf_fee_label';
	const META_KEY_PERSON_TYPE = 'wccpf_person_type';
	const META_KEY_NATIONAL_CODE = 'wccpf_national_code';
	const META_KEY_LEGAL_NAME = 'wccpf_legal_name';
	const META_KEY_LEGAL_ID = 'wccpf_legal_id';

	public static function init() {
		add_action( 'wp', array( __CLASS__, 'maybe_add_block_notice' ) );
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'render_field' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_session_from_posted_data' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'update_session_from_posted_fields' ) );
		add_filter( 'woocommerce_checkout_get_value', array( __CLASS__, 'populate_field_value' ), 10, 2 );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'add_dynamic_fields_fragment' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_fields' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_order_meta' ), 10, 2 );
		add_action( 'woocommerce_checkout_update_user_meta', array( __CLASS__, 'save_user_meta' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function render_field( $checkout ) {
		if ( ! WCCPF_Settings::is_enabled() ) {
			return;
		}

		$percentage  = WCCPF_Settings::get_percentage();
		$label       = self::get_checkout_label( $percentage );
		$description = self::get_checkout_description();

		woocommerce_form_field(
			self::FIELD_ID,
			array(
				'type'        => 'checkbox',
				'class'       => array( 'form-row-wide', 'wccpf-fee-option' ),
				'label'       => $label,
				'required'    => false,
				'description' => $description,
			),
			self::is_selected() ? 1 : 0
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce field markup is generated internally.
		echo self::get_dynamic_fields_container_html();
	}

	public static function update_session_from_posted_data( $posted_data ) {
		if ( ! WCCPF_Settings::is_enabled() ) {
			return;
		}

		if ( wp_doing_ajax() && ! self::has_valid_update_order_review_nonce() ) {
			return;
		}

		parse_str( $posted_data, $data );
		$selected = ! empty( $data[ self::FIELD_ID ] );

		self::set_selected( $selected );
		self::set_person_fields_from_array( $data, $selected );
	}

	public static function update_session_from_posted_fields() {
		if ( ! WCCPF_Settings::is_enabled() ) {
			return;
		}

		if ( ! self::has_valid_checkout_nonce() ) {
			return;
		}

		$data     = self::get_posted_fields_from_request();
		$selected = false;

		if ( array_key_exists( self::FIELD_ID, $data ) ) {
			$selected = wc_string_to_bool( wc_clean( $data[ self::FIELD_ID ] ) );
		}

		self::set_selected( $selected );
		self::set_person_fields_from_array( $data, $selected );
	}

	public static function populate_field_value( $value, $key ) {
		$allowed_keys = array(
			self::FIELD_ID,
			self::FIELD_PERSON_TYPE,
			self::FIELD_NATIONAL_CODE,
			self::FIELD_LEGAL_NAME,
			self::FIELD_LEGAL_ID,
		);

		if ( ! in_array( $key, $allowed_keys, true ) ) {
			return $value;
		}

		$session_value = self::get_session_value( $key );
		if ( null === $session_value ) {
			return $value;
		}

		if ( self::FIELD_ID === $key ) {
			return 'yes' === $session_value ? 1 : 0;
		}

		return $session_value;
	}

	public static function save_order_meta( $order, $data ) {
		if ( ! WCCPF_Settings::is_enabled() ) {
			return;
		}

		if ( ! self::has_valid_checkout_nonce() ) {
			return;
		}

		$selected = wc_string_to_bool( self::get_posted_value( self::FIELD_ID, $data ) );
		$order->update_meta_data( self::META_KEY_ENABLED, $selected ? 'yes' : 'no' );

		if ( ! $selected ) {
			return;
		}

		$person_type = wc_clean( self::get_posted_value( self::FIELD_PERSON_TYPE, $data ) );
		if ( in_array( $person_type, array( 'individual', 'legal' ), true ) ) {
			$order->update_meta_data( self::META_KEY_PERSON_TYPE, $person_type );
		}

		if ( 'individual' === $person_type ) {
			$national_code = self::sanitize_digits( self::get_posted_value( self::FIELD_NATIONAL_CODE, $data ) );
			if ( '' !== $national_code ) {
				$order->update_meta_data( self::META_KEY_NATIONAL_CODE, $national_code );
			}
		}

		if ( 'legal' === $person_type ) {
			$legal_name = sanitize_text_field( self::get_posted_value( self::FIELD_LEGAL_NAME, $data ) );
			$legal_id   = self::sanitize_digits( self::get_posted_value( self::FIELD_LEGAL_ID, $data ) );

			if ( '' !== $legal_name ) {
				$order->update_meta_data( self::META_KEY_LEGAL_NAME, $legal_name );
			}

			if ( '' !== $legal_id ) {
				$order->update_meta_data( self::META_KEY_LEGAL_ID, $legal_id );
			}
		}

		$percentage = WCCPF_Settings::get_percentage();
		$label      = WCCPF_Fees::get_fee_label( $percentage );
		$amount     = WCCPF_Fees::get_fee_amount( WC()->cart, $percentage );

		$order->update_meta_data( self::META_KEY_PERCENTAGE, $percentage );
		$order->update_meta_data( self::META_KEY_LABEL, $label );

		if ( null !== $amount ) {
			$order->update_meta_data( self::META_KEY_AMOUNT, $amount );
		}
	}

	public static function save_user_meta( $customer_id, $data ) {
		if ( ! WCCPF_Settings::is_enabled() || ! $customer_id ) {
			return;
		}

		if ( ! self::has_valid_checkout_nonce() ) {
			return;
		}

		$selected = wc_string_to_bool( self::get_posted_value( self::FIELD_ID, $data ) );
		if ( ! $selected ) {
			return;
		}

		$person_type = wc_clean( self::get_posted_value( self::FIELD_PERSON_TYPE, $data ) );
		if ( ! in_array( $person_type, array( 'individual', 'legal' ), true ) ) {
			return;
		}

		update_user_meta( $customer_id, self::USER_META_PERSON_TYPE, $person_type );

		if ( 'individual' === $person_type ) {
			$national_code = self::sanitize_digits( self::get_posted_value( self::FIELD_NATIONAL_CODE, $data ) );
			if ( '' !== $national_code ) {
				update_user_meta( $customer_id, self::USER_META_NATIONAL_CODE, $national_code );
			}
		}

		if ( 'legal' === $person_type ) {
			$legal_name = sanitize_text_field( self::get_posted_value( self::FIELD_LEGAL_NAME, $data ) );
			$legal_id   = self::sanitize_digits( self::get_posted_value( self::FIELD_LEGAL_ID, $data ) );

			if ( '' !== $legal_name ) {
				update_user_meta( $customer_id, self::USER_META_LEGAL_NAME, $legal_name );
			}

			if ( '' !== $legal_id ) {
				update_user_meta( $customer_id, self::USER_META_LEGAL_ID, $legal_id );
			}
		}
	}

	public static function enqueue_assets() {
		if ( ! WCCPF_Settings::is_enabled() ) {
			return;
		}

		if ( ! is_checkout() || ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) ) {
			return;
		}

		wp_enqueue_style(
			'wccpf-checkout',
			WCCPF_PLUGIN_URL . 'assets/css/wccpf-checkout.css',
			array(),
			WCCPF_VERSION
		);

		wp_enqueue_script(
			'wccpf-checkout',
			WCCPF_PLUGIN_URL . 'assets/js/wccpf-checkout.js',
			array( 'jquery' ),
			WCCPF_VERSION,
			true
		);
	}

	public static function is_selected() {
		$session_value = self::get_session_value( self::SESSION_KEY );
		if ( null === $session_value ) {
			return false;
		}

		return 'yes' === $session_value;
	}

	private static function set_selected( $selected ) {
		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( self::SESSION_KEY, $selected ? 'yes' : 'no' );
	}

	private static function get_session_value( $key ) {
		if ( ! WC()->session ) {
			return null;
		}

		$value = WC()->session->get( $key, null );
		if ( null !== $value || self::SESSION_KEY === $key ) {
			return $value;
		}

		return self::get_user_meta_value_for_field( $key );
	}

	private static function set_session_value( $key, $value ) {
		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( $key, $value );
	}

	private static function clear_person_fields() {
		self::set_session_value( self::FIELD_PERSON_TYPE, null );
		self::set_session_value( self::FIELD_NATIONAL_CODE, null );
		self::set_session_value( self::FIELD_LEGAL_NAME, null );
		self::set_session_value( self::FIELD_LEGAL_ID, null );
	}

	private static function set_person_fields_from_array( $data, $selected ) {
		if ( ! $selected ) {
			self::clear_person_fields();
			return;
		}

		$person_type = '';
		if ( array_key_exists( self::FIELD_PERSON_TYPE, $data ) ) {
			$person_type = wc_clean( wp_unslash( $data[ self::FIELD_PERSON_TYPE ] ) );
		}
		if ( ! in_array( $person_type, array( 'individual', 'legal' ), true ) ) {
			$person_type = '';
		}

		if ( '' !== $person_type || array_key_exists( self::FIELD_PERSON_TYPE, $data ) ) {
			self::set_session_value( self::FIELD_PERSON_TYPE, $person_type );
		}

		$national_code = null;
		$legal_name    = null;
		$legal_id      = null;

		if ( array_key_exists( self::FIELD_NATIONAL_CODE, $data ) ) {
			$national_code = self::sanitize_digits( $data[ self::FIELD_NATIONAL_CODE ] );
		}

		if ( array_key_exists( self::FIELD_LEGAL_NAME, $data ) ) {
			$legal_name = sanitize_text_field( wp_unslash( $data[ self::FIELD_LEGAL_NAME ] ) );
		}

		if ( array_key_exists( self::FIELD_LEGAL_ID, $data ) ) {
			$legal_id = self::sanitize_digits( $data[ self::FIELD_LEGAL_ID ] );
		}

		if ( null !== $national_code ) {
			self::set_session_value( self::FIELD_NATIONAL_CODE, $national_code );
		}

		if ( null !== $legal_name ) {
			self::set_session_value( self::FIELD_LEGAL_NAME, $legal_name );
		}

		if ( null !== $legal_id ) {
			self::set_session_value( self::FIELD_LEGAL_ID, $legal_id );
		}
	}

	private static function sanitize_digits( $value ) {
		$value = wp_unslash( $value );
		return preg_replace( '/\D+/', '', $value );
	}

	public static function add_dynamic_fields_fragment( $fragments ) {
		if ( ! WCCPF_Settings::is_enabled() ) {
			return $fragments;
		}

		if ( ! is_checkout() && ! wp_doing_ajax() ) {
			return $fragments;
		}

		$fragments['div#wccpf-dynamic-fields'] = self::get_dynamic_fields_container_html();

		return $fragments;
	}

	private static function get_dynamic_fields_container_html() {
		$selected    = self::is_selected() ? 'yes' : 'no';
		$person_type = self::get_session_value( self::FIELD_PERSON_TYPE );
		$person_type = is_string( $person_type ) ? $person_type : '';

		$container_class = 'wccpf-dynamic-fields';
		if ( 'yes' !== $selected ) {
			$container_class .= ' wccpf-hidden';
		}

		$html = sprintf(
			'<div id="wccpf-dynamic-fields" class="%s" data-selected="%s" data-person-type="%s" aria-live="polite">',
			esc_attr( $container_class ),
			esc_attr( $selected ),
			esc_attr( $person_type )
		);
		$html .= self::get_dynamic_fields_html();
		$html .= '</div>';

		return $html;
	}

	private static function get_dynamic_fields_html() {
		$person_type = self::get_session_value( self::FIELD_PERSON_TYPE );
		if ( ! in_array( $person_type, array( 'individual', 'legal' ), true ) ) {
			$person_type = '';
		}

		ob_start();

		woocommerce_form_field(
			self::FIELD_PERSON_TYPE,
			array(
				'type'     => 'radio',
				'class'    => array( 'form-row-wide', 'wccpf-person-type' ),
				'label'    => __( 'Person type', 'optional-vat-fee-for-woocommerce' ),
				'options'  => array(
					'individual' => __( 'Individual', 'optional-vat-fee-for-woocommerce' ),
					'legal'      => __( 'Legal entity', 'optional-vat-fee-for-woocommerce' ),
				),
				'required' => true,
			),
			$person_type
		);

		$national_classes = array( 'form-row-wide' );
		if ( 'individual' !== $person_type ) {
			$national_classes[] = 'wccpf-hidden';
		}

		echo '<div class="wccpf-group wccpf-group-individual">';
		woocommerce_form_field(
			self::FIELD_NATIONAL_CODE,
			array(
				'type'              => 'number',
				'class'             => $national_classes,
				'label'             => __( 'National code', 'optional-vat-fee-for-woocommerce' ),
				'required'          => true,
				'custom_attributes' => array(
					'inputmode' => 'numeric',
					'pattern'   => '[0-9]*',
					'autocomplete' => 'off',
				),
			),
			self::get_session_value( self::FIELD_NATIONAL_CODE )
		);
		echo '</div>';

		$legal_classes = array( 'form-row-wide' );
		if ( 'legal' !== $person_type ) {
			$legal_classes[] = 'wccpf-hidden';
		}

		echo '<div class="wccpf-group wccpf-group-legal">';
		woocommerce_form_field(
			self::FIELD_LEGAL_NAME,
			array(
				'type'     => 'text',
				'class'    => $legal_classes,
				'label'    => __( 'Legal entity name', 'optional-vat-fee-for-woocommerce' ),
				'required' => true,
			),
			self::get_session_value( self::FIELD_LEGAL_NAME )
		);

		woocommerce_form_field(
			self::FIELD_LEGAL_ID,
			array(
				'type'              => 'number',
				'class'             => $legal_classes,
				'label'             => __( 'National ID', 'optional-vat-fee-for-woocommerce' ),
				'required'          => true,
				'custom_attributes' => array(
					'inputmode' => 'numeric',
					'pattern'   => '[0-9]*',
					'autocomplete' => 'off',
				),
			),
			self::get_session_value( self::FIELD_LEGAL_ID )
		);
		echo '</div>';

		return ob_get_clean();
	}

	public static function validate_fields( $data, $errors ) {
		if ( ! WCCPF_Settings::is_enabled() || ! self::is_selected() ) {
			return;
		}

		if ( ! self::has_valid_checkout_nonce() ) {
			return;
		}

		$person_type = wc_clean( self::get_posted_value( self::FIELD_PERSON_TYPE, $data ) );
		if ( ! in_array( $person_type, array( 'individual', 'legal' ), true ) ) {
			$errors->add( 'wccpf_person_type', __( 'Please select a person type for VAT.', 'optional-vat-fee-for-woocommerce' ) );
			return;
		}

		if ( 'individual' === $person_type ) {
			$national_code = self::sanitize_digits( self::get_posted_value( self::FIELD_NATIONAL_CODE, $data ) );
			if ( '' === $national_code ) {
				$errors->add( 'wccpf_national_code_required', __( 'Please enter your national code.', 'optional-vat-fee-for-woocommerce' ) );
			} elseif ( ! self::check_national_code( $national_code ) ) {
				$errors->add( 'wccpf_national_code_invalid', __( 'Please enter a valid national code.', 'optional-vat-fee-for-woocommerce' ) );
			}
		}

		if ( 'legal' === $person_type ) {
			$legal_name = sanitize_text_field( self::get_posted_value( self::FIELD_LEGAL_NAME, $data ) );
			$legal_id   = self::sanitize_digits( self::get_posted_value( self::FIELD_LEGAL_ID, $data ) );

			if ( '' === $legal_name ) {
				$errors->add( 'wccpf_legal_name_required', __( 'Please enter the legal entity name.', 'optional-vat-fee-for-woocommerce' ) );
			}

			if ( '' === $legal_id ) {
				$errors->add( 'wccpf_legal_id_required', __( 'Please enter the legal national ID.', 'optional-vat-fee-for-woocommerce' ) );
			} elseif ( ! self::check_national_shenase( $legal_id ) ) {
				$errors->add( 'wccpf_legal_id_invalid', __( 'Please enter a valid legal national ID.', 'optional-vat-fee-for-woocommerce' ) );
			}
		}
	}

	private static function get_posted_value( $key, $data ) {
		if ( isset( $data[ $key ] ) ) {
			return $data[ $key ];
		}

		$value = self::get_request_value( $key );
		if ( '' !== $value && ( self::has_valid_checkout_nonce() || self::has_valid_update_order_review_nonce() ) ) {
			return $value;
		}

		return '';
	}

	private static function has_valid_checkout_nonce() {
		$nonce = filter_input( INPUT_POST, 'woocommerce-process-checkout-nonce', FILTER_UNSAFE_RAW );
		if ( null === $nonce ) {
			$nonce = filter_input( INPUT_POST, 'woocommerce-checkout-nonce', FILTER_UNSAFE_RAW );
		}
		$nonce = is_string( $nonce ) ? sanitize_text_field( $nonce ) : '';

		if ( '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'woocommerce-process-checkout' );
	}

	private static function has_valid_update_order_review_nonce() {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		$nonce = filter_input( INPUT_POST, 'security', FILTER_UNSAFE_RAW );
		$nonce = is_string( $nonce ) ? sanitize_text_field( $nonce ) : '';

		if ( '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'update-order-review' );
	}

	private static function get_posted_fields_from_request() {
		$fields = array(
			self::FIELD_ID,
			self::FIELD_PERSON_TYPE,
			self::FIELD_NATIONAL_CODE,
			self::FIELD_LEGAL_NAME,
			self::FIELD_LEGAL_ID,
		);

		$data = array();
		foreach ( $fields as $field ) {
			if ( filter_has_var( INPUT_POST, $field ) ) {
				$data[ $field ] = self::get_request_value( $field );
			}
		}

		return $data;
	}

	private static function get_request_value( $key ) {
		$value = filter_input( INPUT_POST, $key, FILTER_UNSAFE_RAW );
		if ( null === $value ) {
			return '';
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	private static function get_user_meta_value_for_field( $field ) {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		$user_id = get_current_user_id();

		switch ( $field ) {
			case self::FIELD_PERSON_TYPE:
				$value = get_user_meta( $user_id, self::USER_META_PERSON_TYPE, true );
				$value = is_string( $value ) ? $value : '';
				return in_array( $value, array( 'individual', 'legal' ), true ) ? $value : '';
			case self::FIELD_NATIONAL_CODE:
				$value = get_user_meta( $user_id, self::USER_META_NATIONAL_CODE, true );
				return is_string( $value ) ? preg_replace( '/\D+/', '', $value ) : '';
			case self::FIELD_LEGAL_NAME:
				$value = get_user_meta( $user_id, self::USER_META_LEGAL_NAME, true );
				return is_string( $value ) ? sanitize_text_field( $value ) : '';
			case self::FIELD_LEGAL_ID:
				$value = get_user_meta( $user_id, self::USER_META_LEGAL_ID, true );
				return is_string( $value ) ? preg_replace( '/\D+/', '', $value ) : '';
			default:
				return null;
		}
	}

	private static function check_national_code( $value ) {
		if ( ! preg_match( '/^\d{10}$/', $value ) ) {
			return false;
		}

		for ( $i = 0; $i < 10; $i++ ) {
			if ( preg_match( '/^' . $i . '{10}$/', $value ) ) {
				return false;
			}
		}

		$sum = 0;
		for ( $i = 0; $i < 9; $i++ ) {
			$sum += ( ( 10 - $i ) * intval( substr( $value, $i, 1 ) ) );
		}

		$ret    = $sum % 11;
		$parity = intval( substr( $value, 9, 1 ) );

		return ( ( $ret < 2 && $ret === $parity ) || ( $ret >= 2 && $ret === 11 - $parity ) );
	}

	private static function check_national_shenase( $value ) {
		if ( ! preg_match( '/^\d{11}$/', $value ) ) {
			return false;
		}

		for ( $i = 0; $i <= 9; $i++ ) {
			if ( preg_match( '/^' . $i . '{11}$/', $value ) ) {
				return false;
			}
		}

		$d9      = (int) substr( $value, 9, 1 ) + 2;
		$weights = array( 29, 27, 23, 19, 17, 29, 27, 23, 19, 17 );
		$sum     = 0;

		for ( $i = 0; $i < 10; $i++ ) {
			$sum += ( (int) substr( $value, $i, 1 ) + $d9 ) * $weights[ $i ];
		}

		$sum = $sum % 11;
		if ( 10 === $sum ) {
			$sum = 0;
		}

		return ( (int) substr( $value, 10, 1 ) === $sum );
	}

	private static function get_checkout_label( $percentage ) {
		$template = WCCPF_Settings::get_checkout_label_template();
		if ( false !== strpos( $template, '{percentage}' ) ) {
			$label = str_replace( '{percentage}', $percentage, $template );
		} else {
			/* translators: %s percentage value */
			$label = sprintf( '%s (%s%%)', $template, $percentage );
		}

		$label = apply_filters( 'wccpf_checkout_label', $label, $percentage );

		return wp_kses_post( $label );
	}

	private static function get_checkout_description() {
		$description = apply_filters( 'wccpf_checkout_description', '' );
		$description = is_string( $description ) ? $description : '';

		return wp_kses_post( $description );
	}

	public static function maybe_add_block_notice() {
		if ( ! WCCPF_Settings::is_enabled() ) {
			return;
		}

		if ( ! is_checkout() || ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) ) {
			return;
		}

		if ( ! function_exists( 'has_block' ) ) {
			return;
		}

		$checkout_id = wc_get_page_id( 'checkout' );
		if ( $checkout_id <= 0 ) {
			return;
		}

		$checkout_page = get_post( $checkout_id );
		if ( ! $checkout_page ) {
			return;
		}

		if ( has_block( 'woocommerce/checkout', $checkout_page ) ) {
			wc_add_notice(
				esc_html__( 'The optional VAT checkbox is not available on the block-based checkout. Please use the classic checkout shortcode to enable it.', 'optional-vat-fee-for-woocommerce' ),
				'notice'
			);
		}
	}
}
