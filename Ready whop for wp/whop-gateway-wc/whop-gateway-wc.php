<?php
/**
 * Plugin Name:       Whop Checkout
 * Plugin URI:        https://whop.com
 * Description:       Whop Checkout for WooCommerce — guided setup wizard and webhook-verified payments. No OAuth server required.
 * Version:           5.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   9.9
 * Author:            Your Brand
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whop-gateway-wc
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'WHOP_GW_VERSION', '5.0.1' );
define( 'WHOP_GW_PLUGIN_FILE', __FILE__ );
define( 'WHOP_GW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WHOP_GW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, 'whop_gw_activate' );

function whop_gw_activate(): void {
	if ( ! whop_gw_is_wc_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'Whop Checkout requires WooCommerce to be installed and active.', 'whop-gateway-wc' ),
			esc_html__( 'Plugin activation failed', 'whop-gateway-wc' ),
			[ 'back_link' => true ]
		);
	}

	set_transient( 'whop_gw_activation_redirect', 1, 30 );
}

function whop_gw_is_wc_active(): bool {
	if ( class_exists( 'WooCommerce' ) ) {
		return true;
	}

	$active_plugins = (array) get_option( 'active_plugins', [] );
	if ( in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network_plugins = (array) get_site_option( 'active_sitewide_plugins', [] );
		if ( isset( $network_plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		}
	}

	return false;
}

add_action( 'before_woocommerce_init', function () {
	if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
		'custom_order_tables',
		__FILE__,
		true
	);
} );

add_action( 'plugins_loaded', 'whop_gw_bootstrap', 20 );

function whop_gw_bootstrap(): void {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'whop_gw_missing_wc_notice' );
		return;
	}

	require_once WHOP_GW_PLUGIN_PATH . 'includes/class-whop-webhook.php';
	require_once WHOP_GW_PLUGIN_PATH . 'includes/class-whop-api.php';
	require_once WHOP_GW_PLUGIN_PATH . 'includes/class-whop-helper.php';
	require_once WHOP_GW_PLUGIN_PATH . 'includes/class-whop-health.php';
	require_once WHOP_GW_PLUGIN_PATH . 'includes/class-whop-setup-wizard.php';
	require_once WHOP_GW_PLUGIN_PATH . 'includes/class-whop-admin.php';
	require_once WHOP_GW_PLUGIN_PATH . 'includes/class-wc-gateway-whop.php';

	add_filter( 'woocommerce_payment_gateways', function ( $gateways ) {
		$gateways[] = 'WC_Gateway_Whop';
		return $gateways;
	} );

	if ( is_admin() ) {
		Whop_GW_Admin::init();
	}
}

function whop_gw_missing_wc_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Whop Checkout requires WooCommerce to be active.', 'whop-gateway-wc' )
		. '</p></div>';
}

add_action( 'admin_notices', 'whop_gw_admin_configuration_notices' );

function whop_gw_admin_configuration_notices(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! class_exists( 'Whop_GW_Helper' ) ) {
		return;
	}

	$settings = Whop_GW_Helper::get_settings();
	if ( 'yes' !== $settings['enabled'] ) {
		return;
	}

	$issues = [];

	if ( ! Whop_GW_Helper::has_credentials() ) {
		$issues[] = __( 'Whop is not configured. Open Whop Checkout and run the Setup Wizard.', 'whop-gateway-wc' );
	} elseif ( empty( Whop_GW_Helper::get_webhook_secret() ) ) {
		$issues[] = __( 'Webhook Secret is missing. Orders will never be marked as paid.', 'whop-gateway-wc' );
	}

	if ( ! Whop_GW_Helper::is_https_ready() ) {
		$issues[] = __( 'Your site is not using HTTPS. Whop webhooks require HTTPS.', 'whop-gateway-wc' );
	}

	foreach ( $issues as $issue ) {
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Whop Checkout:', 'whop-gateway-wc' ) . '</strong> ' . esc_html( $issue ) . '</p></div>';
	}
}

add_action( 'template_redirect', 'whop_gw_handle_return_page', 5 );

function whop_gw_handle_return_page(): void {
	if ( ! isset( $_GET['whop-return'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['whop-return'] ) ) ) {
		return;
	}

	if ( ! class_exists( 'WC_Gateway_Whop' ) ) {
		whop_gw_bootstrap();
	}

	if ( ! class_exists( 'WC_Gateway_Whop' ) ) {
		wp_die( esc_html__( 'WooCommerce is required.', 'whop-gateway-wc' ) );
	}

	WC_Gateway_Whop::render_return_page();
	exit;
}

add_action( 'template_redirect', 'whop_gw_protect_unpaid_thankyou_page', 20 );

function whop_gw_protect_unpaid_thankyou_page(): void {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
		return;
	}

	global $wp;
	$order_id = absint( $wp->query_vars['order-received'] ?? 0 );
	$order    = wc_get_order( $order_id );

	if ( ! $order || ! Whop_GW_Helper::is_whop_order( $order ) || $order->is_paid() ) {
		return;
	}

	$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
	if ( ! hash_equals( $order->get_order_key(), $key ) ) {
		return;
	}

	WC_Gateway_Whop::sync_order_from_whop( $order );
	if ( $order->is_paid() ) {
		return;
	}

	$token = (string) $order->get_meta( Whop_GW_Helper::META_RETURN_TOKEN, true );
	if ( $token && Whop_GW_Helper::return_token_valid( $order, $token ) ) {
		wp_safe_redirect( Whop_GW_Helper::build_return_url( $order, $token ) );
		exit;
	}

	wc_add_notice(
		__( 'Your payment is still being confirmed. Please wait a moment or contact the store if you were charged.', 'whop-gateway-wc' ),
		'notice'
	);
	wp_safe_redirect( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) );
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_checkout() && ! is_checkout_pay_page() ) {
		return;
	}

	wp_enqueue_style(
		'whop-gateway-wc',
		WHOP_GW_PLUGIN_URL . 'assets/css/whop-gateway.css',
		[],
		WHOP_GW_VERSION
	);
} );
