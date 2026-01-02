<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WCCPF_Settings_Page' ) || ! class_exists( 'WC_Settings_Page' ) ) {
	return;
}

class WCCPF_Settings_Page extends WC_Settings_Page {
	public function __construct() {
		$this->id    = 'wccpf';
		$this->label = __( 'VAT Fee', 'optional-vat-fee-for-woocommerce' );

		parent::__construct();
	}

	public function get_settings() {
		$settings = array(
			array(
				'title' => __( 'Optional VAT Fee', 'optional-vat-fee-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Add an optional VAT percentage at checkout when customers opt in.', 'optional-vat-fee-for-woocommerce' ),
				'id'    => 'wccpf_section_start',
			),
			array(
				'title'   => __( 'Enable', 'optional-vat-fee-for-woocommerce' ),
				'id'      => WCCPF_Settings::OPTION_ENABLED,
				'type'    => 'checkbox',
				'default' => 'yes',
				'desc'    => __( 'Enable the optional VAT checkbox on checkout.', 'optional-vat-fee-for-woocommerce' ),
			),
			array(
				'title'    => __( 'Percentage', 'optional-vat-fee-for-woocommerce' ),
				'id'       => WCCPF_Settings::OPTION_PERCENTAGE,
				'type'     => 'text',
				'default'  => '10',
				'css'      => 'width:80px;',
				'desc'     => __( 'Percentage applied to the fee base (0-100).', 'optional-vat-fee-for-woocommerce' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Include shipping', 'optional-vat-fee-for-woocommerce' ),
				'id'      => WCCPF_Settings::OPTION_INCLUDE_SHIPPING,
				'type'    => 'checkbox',
				'default' => 'no',
				'desc'    => __( 'Include shipping costs (excluding tax) in the fee base.', 'optional-vat-fee-for-woocommerce' ),
			),
			array(
				'title'    => __( 'Checkout checkbox label', 'optional-vat-fee-for-woocommerce' ),
				'id'       => WCCPF_Settings::OPTION_CHECKOUT_LABEL,
				'type'     => 'text',
				'default'  => __( 'Add {percentage}% VAT', 'optional-vat-fee-for-woocommerce' ),
				'desc'     => __( 'Shown on checkout. Use {percentage} as a placeholder.', 'optional-vat-fee-for-woocommerce' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Fee label', 'optional-vat-fee-for-woocommerce' ),
				'id'       => WCCPF_Settings::OPTION_FEE_LABEL,
				'type'     => 'text',
				'default'  => __( 'VAT', 'optional-vat-fee-for-woocommerce' ),
				'desc'     => __( 'Shown in order totals; percentage is appended automatically.', 'optional-vat-fee-for-woocommerce' ),
				'desc_tip' => true,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wccpf_section_end',
			),
		);

		return apply_filters( 'wccpf_settings', $settings );
	}
}
