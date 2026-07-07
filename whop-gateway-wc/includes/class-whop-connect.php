<?php
/**
 * One-click Whop Connect via seller-hosted OAuth bridge.
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class Whop_GW_Connect {

	public static function init(): void {
		add_action( 'admin_init', [ __CLASS__, 'handle_oauth_return' ] );
		add_action( 'wp_ajax_whop_gw_disconnect', [ __CLASS__, 'ajax_disconnect' ] );
	}

	public static function get_connect_base_url(): string {
		$url = trim( (string) Whop_GW_Helper::get_settings()['connect_url'] );
		return $url ?: Whop_GW_Helper::CONNECT_URL_DEFAULT;
	}

	public static function has_connect_service(): bool {
		return '' !== trim( self::get_connect_base_url() );
	}

	public static function get_start_url(): string {
		$settings = Whop_GW_Helper::get_settings();

		return add_query_arg(
			[
				'site_url'    => home_url( '/' ),
				'return_url'  => self::get_plugin_callback_url(),
				'license_key' => $settings['license_key'],
				'state'       => wp_create_nonce( 'whop_gw_connect_start' ),
			],
			trailingslashit( self::get_connect_base_url() ) . 'start'
		);
	}

	public static function get_plugin_callback_url(): string {
		return add_query_arg(
			[
				'page'          => 'whop-gateway-wc',
				'tab'           => 'connect',
				'whop_connect'  => '1',
			],
			admin_url( 'admin.php' )
		);
	}

	public static function handle_oauth_return(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( empty( $_GET['whop_connect'] ) || empty( $_GET['setup_token'] ) ) {
			return;
		}

		$connect_nonce = sanitize_text_field( wp_unslash( $_GET['connect_nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $connect_nonce, 'whop_gw_connect_start' ) ) {
			add_settings_error(
				'whop_gw_connect',
				'connect_nonce_invalid',
				__( 'Connect session could not be verified. Please start again from Connect your Whop.', 'whop-gateway-wc' ),
				'error'
			);
			wp_safe_redirect( Whop_GW_Helper::admin_page_url( 'connect' ) );
			exit;
		}

		if ( empty( $_GET['page'] ) || 'whop-gateway-wc' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		$setup_token = sanitize_text_field( wp_unslash( $_GET['setup_token'] ) );

		if ( get_transient( 'whop_gw_used_setup_' . md5( $setup_token ) ) ) {
			wp_safe_redirect( Whop_GW_Helper::admin_page_url( 'connect' ) );
			exit;
		}

		$result = self::exchange_setup_token( $setup_token );

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'whop_gw_connect',
				'connect_failed',
				$result->get_error_message(),
				'error'
			);
			return;
		}

		if ( empty( $result['company_id'] ) || empty( $result['access_token'] ) ) {
			add_settings_error(
				'whop_gw_connect',
				'connect_incomplete',
				__( 'Whop connected but required credentials were missing. Try again or use manual setup.', 'whop-gateway-wc' ),
				'error'
			);
			return;
		}

		if ( empty( $result['webhook_secret'] ) ) {
			add_settings_error(
				'whop_gw_connect',
				'connect_webhook_missing',
				__( 'Connected to Whop but webhook secret was not returned. Register webhook in Setup Wizard step 3.', 'whop-gateway-wc' ),
				'warning'
			);
		}

		set_transient( 'whop_gw_used_setup_' . md5( $setup_token ), 1, HOUR_IN_SECONDS );

		Whop_GW_Helper::save_oauth_connection( $result );
		Whop_GW_Helper::mark_setup_complete();

		set_transient(
			'whop_gw_connect_notice_' . get_current_user_id(),
			__( 'Whop connected successfully. Run a sandbox test order before going live.', 'whop-gateway-wc' ),
			30
		);

		wp_safe_redirect( Whop_GW_Helper::admin_page_url( 'connect' ) );
		exit;
	}

	/**
	 * @return array<string, string>|WP_Error
	 */
	public static function exchange_setup_token( string $setup_token ) {
		$body = [
			'setup_token' => $setup_token,
			'site_url'    => home_url( '/' ),
		];

		$headers = [ 'Content-Type' => 'application/json' ];
		$bridge  = Whop_GW_Helper::get_connect_bridge_secret();
		if ( $bridge ) {
			$headers['X-Whop-Gw-Bridge'] = hash_hmac( 'sha256', $setup_token . '|' . home_url( '/' ), $bridge );
		}

		$response = wp_remote_post(
			trailingslashit( self::get_connect_base_url() ) . 'exchange',
			[
				'timeout' => 30,
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $data ) || empty( $data['success'] ) ) {
			$message = is_array( $data ) ? ( $data['error'] ?? __( 'Connect exchange failed.', 'whop-gateway-wc' ) ) : __( 'Connect exchange failed.', 'whop-gateway-wc' );
			return new WP_Error( 'whop_connect_exchange', (string) $message );
		}

		return [
			'company_id'     => (string) ( $data['company_id'] ?? '' ),
			'access_token'   => (string) ( $data['access_token'] ?? '' ),
			'refresh_token'  => (string) ( $data['refresh_token'] ?? '' ),
			'webhook_secret' => (string) ( $data['webhook_secret'] ?? '' ),
			'webhook_id'     => (string) ( $data['webhook_id'] ?? '' ),
		];
	}

	public static function ajax_disconnect(): void {
		check_ajax_referer( 'whop_gw_disconnect', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'whop-gateway-wc' ) ], 403 );
		}

		Whop_GW_Helper::save_settings(
			[
				'connection_mode' => 'manual',
				'access_token'    => '',
				'refresh_token'   => '',
				'company_id'      => '',
				'webhook_secret'  => '',
				'webhook_id'      => '',
			]
		);

		delete_option( Whop_GW_Helper::OPTION_SETUP_COMPLETE );

		wp_send_json_success( [ 'message' => __( 'Disconnected from Whop.', 'whop-gateway-wc' ) ] );
	}

	public static function render_connect_tab(): void {
		$settings   = Whop_GW_Helper::get_settings();
		$connected   = Whop_GW_Helper::is_connected() && 'oauth' === $settings['connection_mode'];
		$can_connect = Whop_GW_Helper::license_allows_connect( $settings['license_key'] )
			&& self::has_connect_service();

		$notice = get_transient( 'whop_gw_connect_notice_' . get_current_user_id() );
		if ( $notice ) {
			delete_transient( 'whop_gw_connect_notice_' . get_current_user_id() );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
		}

		if ( isset( $_POST['whop_gw_save_connect_settings'] ) && check_admin_referer( 'whop_gw_save_connect_settings' ) ) {
			$license     = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
			$connect_url = esc_url_raw( trim( wp_unslash( $_POST['connect_url'] ?? '' ) ) );
			Whop_GW_Helper::save_settings(
				[
					'license_key' => $license,
					'connect_url' => $connect_url,
				]
			);
			$settings    = Whop_GW_Helper::get_settings();
			$can_connect = Whop_GW_Helper::license_allows_connect( $license ) && self::has_connect_service();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Connect settings saved.', 'whop-gateway-wc' ) . '</p></div>';
		}

		include WHOP_GW_PLUGIN_PATH . 'includes/admin/views/connect.php';
	}
}
