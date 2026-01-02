<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCPF_Plugin {
	public function __construct() {
		$this->includes();
		$this->init();
	}

	private function includes() {
		require_once WCCPF_PLUGIN_DIR . 'includes/class-wccpf-settings.php';
		require_once WCCPF_PLUGIN_DIR . 'includes/class-wccpf-checkout.php';
		require_once WCCPF_PLUGIN_DIR . 'includes/class-wccpf-fees.php';
		require_once WCCPF_PLUGIN_DIR . 'includes/class-wccpf-order-meta.php';
	}

	private function init() {
		WCCPF_Settings::init();
		WCCPF_Checkout::init();
		WCCPF_Fees::init();
		WCCPF_Order_Meta::init();
	}
}
