<?php
/**
 * Shared helpers for Whop Checkout orders and settings.
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class Whop_GW_Helper {

	public const META_RETURN_TOKEN      = '_whop_gw_return_token';
	public const META_RETURN_EXPIRES    = '_whop_gw_return_expires';
	public const META_EXPECTED_TOTAL    = '_whop_gw_expected_total';
	public const META_EXPECTED_CURRENCY = '_whop_gw_expected_currency';
	public const META_PLAN_ID           = '_whop_gw_plan_id';
	public const META_CHECKOUT_ID       = '_whop_gw_checkout_config_id';
	public const META_PURCHASE_URL      = '_whop_gw_purchase_url';
	public const META_PAYMENT_ID        = '_whop_gw_payment_id';
	public const META_WEBHOOK_EVENTS    = '_whop_gw_webhook_event_ids';

	public const OPTION_WEBHOOK_EVENTS  = 'whop_gw_processed_webhook_events';
	public const OPTION_LAST_WEBHOOK    = 'whop_gw_last_webhook_at';
	public const OPTION_SETUP_COMPLETE  = 'whop_gw_setup_complete';
	public const RETURN_TOKEN_TTL       = DAY_IN_SECONDS;

	public const CONNECT_URL_DEFAULT = '';
	private const ENCRYPT_PREFIX       = 'wgenc1:';

	/**
	 * Default Connect service URL (empty = seller must configure or use filter).
	 */
	public static function get_connect_bridge_secret(): string {
		return (string) apply_filters( 'whop_gw_connect_bridge_secret', '' );
	}

	public static function get_connect_url_default(): string {
		return (string) apply_filters( 'whop_gw_connect_url_default', self::CONNECT_URL_DEFAULT );
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_settings(): array {
		$settings = get_option( 'woocommerce_whop_settings', [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return wp_parse_args(
			$settings,
			[
				'enabled'          => 'no',
				'connection_mode'  => 'manual',
				'api_key'          => '',
				'access_token'     => '',
				'refresh_token'    => '',
				'company_id'       => '',
				'webhook_secret'   => '',
				'webhook_id'       => '',
				'license_key'      => '',
				'connect_url'      => self::get_connect_url_default(),
				'support_url'      => '',
				'sandbox_mode'     => 'no',
				'debug_mode'       => 'no',
			]
		);
	}

	public static function save_settings( array $updates ): void {
		$settings = self::get_settings();
		foreach ( $updates as $key => $value ) {
			$settings[ $key ] = $value;
		}
		update_option( 'woocommerce_whop_settings', $settings, false );
	}

	public static function get_bearer_token(): string {
		$settings = self::get_settings();

		if ( 'oauth' === $settings['connection_mode'] && ! empty( $settings['access_token'] ) ) {
			return self::decrypt( (string) $settings['access_token'] );
		}

		$api_key = (string) ( $settings['api_key'] ?? '' );
		if ( self::is_encrypted_value( $api_key ) ) {
			return self::decrypt( $api_key );
		}

		return $api_key;
	}

	public static function get_webhook_secret(): string {
		$settings = self::get_settings();
		$secret   = (string) ( $settings['webhook_secret'] ?? '' );

		if ( self::is_encrypted_value( $secret ) ) {
			return self::decrypt( $secret );
		}

		return $secret;
	}

	private static function is_encrypted_value( string $value ): bool {
		return '' !== $value && 0 === strpos( $value, self::ENCRYPT_PREFIX );
	}

	public static function maybe_encrypt_secret( string $value ): string {
		if ( '' === $value || self::is_encrypted_value( $value ) ) {
			return $value;
		}

		return self::encrypt( $value );
	}

	public static function has_credentials(): bool {
		$settings = self::get_settings();

		if ( empty( $settings['company_id'] ) || empty( self::get_webhook_secret() ) ) {
			return false;
		}

		if ( 'oauth' === $settings['connection_mode'] ) {
			return ! empty( $settings['access_token'] ) && '' !== self::get_bearer_token();
		}

		return ! empty( $settings['api_key'] );
	}

	public static function is_connected(): bool {
		return self::has_credentials();
	}

	public static function is_configured(): bool {
		$settings = self::get_settings();

		return 'yes' === $settings['enabled'] && self::has_credentials();
	}

	public static function is_sandbox(): bool {
		return 'yes' === self::get_settings()['sandbox_mode'];
	}

	public static function is_debug(): bool {
		return 'yes' === self::get_settings()['debug_mode'];
	}

	public static function get_api(): Whop_API {
		return new Whop_API(
			self::get_bearer_token(),
			self::is_sandbox()
		);
	}

	public static function webhook_url(): string {
		return home_url( '/?wc-api=whop_webhook' );
	}

	public static function admin_page_url( string $tab = 'connect' ): string {
		return admin_url( 'admin.php?page=whop-gateway-wc&tab=' . rawurlencode( $tab ) );
	}

	public static function record_webhook_received(): void {
		update_option( self::OPTION_LAST_WEBHOOK, time(), false );
	}

	public static function get_last_webhook_at(): int {
		return (int) get_option( self::OPTION_LAST_WEBHOOK, 0 );
	}

	public static function is_setup_complete(): bool {
		return 'yes' === get_option( self::OPTION_SETUP_COMPLETE, 'no' );
	}

	public static function mark_setup_complete(): void {
		update_option( self::OPTION_SETUP_COMPLETE, 'yes', false );
	}

	public static function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return self::ENCRYPT_PREFIX . 'plain:' . base64_encode( $plain );
		}

		$key = wp_salt( 'auth' );
		$iv  = openssl_random_pseudo_bytes( 16 );
		$enc = openssl_encrypt( $plain, 'AES-256-CBC', hash( 'sha256', $key, true ), OPENSSL_RAW_DATA, $iv );

		if ( false === $enc ) {
			return self::ENCRYPT_PREFIX . 'plain:' . base64_encode( $plain );
		}

		return self::ENCRYPT_PREFIX . base64_encode( $iv . $enc );
	}

	public static function decrypt( string $encoded ): string {
		if ( '' === $encoded ) {
			return '';
		}

		if ( self::is_encrypted_value( $encoded ) ) {
			$payload = substr( $encoded, strlen( self::ENCRYPT_PREFIX ) );

			if ( 0 === strpos( $payload, 'plain:' ) ) {
				$decoded = base64_decode( substr( $payload, 6 ), true );
				return false === $decoded ? '' : $decoded;
			}

			$encoded = $payload;
		}

		$raw = base64_decode( $encoded, true );
		if ( false === $raw ) {
			return '';
		}

		if ( 0 === strpos( $raw, 'plain:' ) ) {
			return substr( $raw, 6 );
		}

		if ( strlen( $raw ) < 17 || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$key = wp_salt( 'auth' );
		$iv  = substr( $raw, 0, 16 );
		$enc = substr( $raw, 16 );
		$dec = openssl_decrypt( $enc, 'AES-256-CBC', hash( 'sha256', $key, true ), OPENSSL_RAW_DATA, $iv );

		return false === $dec ? '' : $dec;
	}

	public static function save_oauth_connection( array $data ): void {
		$webhook_secret = (string) ( $data['webhook_secret'] ?? '' );

		self::save_settings(
			[
				'connection_mode' => 'oauth',
				'company_id'      => sanitize_text_field( (string) ( $data['company_id'] ?? '' ) ),
				'access_token'    => self::encrypt( (string) ( $data['access_token'] ?? '' ) ),
				'refresh_token'   => self::encrypt( (string) ( $data['refresh_token'] ?? '' ) ),
				'webhook_secret'  => $webhook_secret ? self::encrypt( $webhook_secret ) : '',
				'webhook_id'      => sanitize_text_field( (string) ( $data['webhook_id'] ?? '' ) ),
				'api_key'         => '',
			]
		);
	}

	public static function log( string $message, string $level = 'info' ): void {
		if ( ! self::is_debug() && 'error' !== $level ) {
			return;
		}

		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->log( $level, $message, [ 'source' => 'whop-gateway-wc' ] );
	}

	public static function issue_return_token( WC_Order $order ): string {
		$token   = wp_generate_password( 48, false, false );
		$expires = time() + self::RETURN_TOKEN_TTL;

		$order->update_meta_data( self::META_RETURN_TOKEN, $token );
		$order->update_meta_data( self::META_RETURN_EXPIRES, (string) $expires );
		$order->save();

		return $token;
	}

	public static function return_token_valid( WC_Order $order, string $token ): bool {
		if ( empty( $token ) ) {
			return false;
		}

		$stored = (string) $order->get_meta( self::META_RETURN_TOKEN, true );
		if ( empty( $stored ) || ! hash_equals( $stored, $token ) ) {
			return false;
		}

		$expires = (int) $order->get_meta( self::META_RETURN_EXPIRES, true );
		if ( $expires > 0 && time() > $expires ) {
			return false;
		}

		return true;
	}

	public static function build_return_url( WC_Order $order, string $token ): string {
		return add_query_arg(
			[
				'whop-return' => '1',
				'order_id'    => $order->get_id(),
				'token'       => $token,
			],
			home_url( '/' )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function payment_metadata( WC_Order $order ): array {
		return [
			'wc_order_id'   => (string) $order->get_id(),
			'wc_order_key'  => $order->get_order_key(),
			'wc_site_url'   => home_url( '/' ),
			'wc_store_name' => get_bloginfo( 'name' ),
		];
	}

	public static function is_whop_order( WC_Order $order ): bool {
		return 'whop' === $order->get_payment_method();
	}

	public static function is_https_ready(): bool {
		return is_ssl() || self::is_local_dev();
	}

	public static function is_local_dev(): bool {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $host ) ) {
			return false;
		}

		return in_array( $host, [ 'localhost', '127.0.0.1' ], true ) || false !== strpos( $host, '.local' );
	}

	public static function uses_block_checkout(): bool {
		if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) ) {
			return false;
		}

		return \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default();
	}

	public static function global_event_processed( string $event_id ): bool {
		if ( empty( $event_id ) ) {
			return false;
		}

		$processed = get_option( self::OPTION_WEBHOOK_EVENTS, [] );
		if ( ! is_array( $processed ) ) {
			return false;
		}

		return in_array( $event_id, $processed, true );
	}

	public static function mark_global_event_processed( string $event_id ): void {
		if ( empty( $event_id ) ) {
			return;
		}

		$processed = get_option( self::OPTION_WEBHOOK_EVENTS, [] );
		if ( ! is_array( $processed ) ) {
			$processed = [];
		}

		$processed[] = $event_id;
		$processed   = array_values( array_unique( $processed ) );

		if ( count( $processed ) > 500 ) {
			$processed = array_slice( $processed, -500 );
		}

		update_option( self::OPTION_WEBHOOK_EVENTS, $processed, false );
	}

	public static function order_event_processed( WC_Order $order, string $event_id ): bool {
		if ( empty( $event_id ) ) {
			return false;
		}

		$processed = $order->get_meta( self::META_WEBHOOK_EVENTS, true );
		if ( ! is_array( $processed ) ) {
			$processed = [];
		}

		return in_array( $event_id, $processed, true );
	}

	public static function mark_order_event_processed( WC_Order $order, string $event_id ): void {
		if ( empty( $event_id ) ) {
			return;
		}

		$processed = $order->get_meta( self::META_WEBHOOK_EVENTS, true );
		if ( ! is_array( $processed ) ) {
			$processed = [];
		}

		$processed[] = $event_id;
		$order->update_meta_data( self::META_WEBHOOK_EVENTS, array_values( array_unique( $processed ) ) );
	}

	public static function payment_matches_order( WC_Order $order, array $payment ): bool {
		$expected_total    = (float) $order->get_meta( self::META_EXPECTED_TOTAL, true );
		$expected_currency = strtolower( (string) $order->get_meta( self::META_EXPECTED_CURRENCY, true ) );

		if ( $expected_total <= 0 ) {
			$expected_total    = round( (float) $order->get_total(), 2 );
			$expected_currency = strtolower( $order->get_currency() );
		}

		$paid_currency = strtolower( (string) ( $payment['currency'] ?? '' ) );
		$paid_amount   = self::extract_payment_amount( $payment );

		if ( $paid_currency && $expected_currency && $paid_currency !== $expected_currency ) {
			return false;
		}

		if ( null === $paid_amount ) {
			return false;
		}

		return abs( $paid_amount - $expected_total ) < 0.01;
	}

	public static function extract_payment_amount( array $payment ): ?float {
		foreach ( [ 'total', 'amount', 'subtotal', 'initial_price' ] as $key ) {
			if ( isset( $payment[ $key ] ) && is_numeric( $payment[ $key ] ) ) {
				return round( (float) $payment[ $key ], 2 );
			}
		}

		return null;
	}

	public static function resolve_order( array $payload ): ?WC_Order {
		$metadata = $payload['metadata'] ?? [];
		$order_id = absint( $metadata['wc_order_id'] ?? 0 );

		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		if ( ! self::is_whop_order( $order ) ) {
			return null;
		}

		$expected_key = (string) ( $metadata['wc_order_key'] ?? '' );
		if ( $expected_key && ! hash_equals( $order->get_order_key(), $expected_key ) ) {
			return null;
		}

		return $order;
	}

	public static function verify_payment_with_api( array $payment ): array {
		$payment_id = (string) ( $payment['id'] ?? '' );
		if ( empty( $payment_id ) || ! self::has_credentials() ) {
			return $payment;
		}

		$remote = self::get_api()->get_payment( $payment_id );
		if ( isset( $remote['error'] ) ) {
			return $payment;
		}

		return is_array( $remote ) ? $remote : $payment;
	}

	public static function license_allows_connect( string $license_key ): bool {
		$license_key = trim( $license_key );
		if ( empty( $license_key ) ) {
			return false;
		}

		return (bool) preg_match( '/^WGWC-(PRO|AGENCY)-/i', $license_key );
	}
}
