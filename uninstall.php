<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wccpf_enabled' );
delete_option( 'wccpf_percentage' );
delete_option( 'wccpf_include_shipping' );
delete_option( 'wccpf_fee_label' );
delete_option( 'wccpf_checkout_label' );
