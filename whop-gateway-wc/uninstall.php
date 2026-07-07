<?php
/**
 * Uninstall Whop Checkout.
 *
 * @package Whop_Gateway_WC
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'woocommerce_whop_settings' );
delete_option( 'whop_gw_processed_webhook_events' );
delete_option( 'whop_gw_last_webhook_at' );
delete_option( 'whop_gw_setup_complete' );
