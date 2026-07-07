<?php
/**
 * WooCommerce Whop payment gateway.
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class WC_Gateway_Whop extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'whop';
		$this->icon               = WHOP_GW_PLUGIN_URL . 'assets/images/whop-logo.svg';
		$this->has_fields         = true;
		$this->method_title       = __( 'Whop Checkout', 'whop-gateway-wc' );
		$this->method_description = __(
			'Accept card and crypto payments through Whop. Orders are confirmed via signed webhooks only.',
			'whop-gateway-wc'
		);
		$this->supports = [ 'products' ];

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			[ $this, 'process_admin_options' ]
		);

		add_action( 'woocommerce_api_whop_webhook', [ $this, 'webhook_handler' ] );
		add_action( 'wp_ajax_whop_gateway_test_connection', [ $this, 'ajax_test_connection' ] );
	}

	public function init_form_fields(): void {
		$webhook_url = Whop_GW_Helper::webhook_url();

		$this->form_fields = [
			'enabled' => [
				'title'   => __( 'Enable / Disable', 'whop-gateway-wc' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Whop Checkout', 'whop-gateway-wc' ),
				'default' => 'no',
			],
			'title' => [
				'title'       => __( 'Title', 'whop-gateway-wc' ),
				'type'        => 'text',
				'description' => __( 'Label shown to customers at checkout.', 'whop-gateway-wc' ),
				'default'     => __( 'Pay with Card or Crypto', 'whop-gateway-wc' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'whop-gateway-wc' ),
				'type'        => 'textarea',
				'description' => __( 'Short description under the payment option.', 'whop-gateway-wc' ),
				'default'     => __(
					'Secure checkout powered by Whop — cards, crypto, and local payment methods.',
					'whop-gateway-wc'
				),
			],
			'api_credentials_section' => [
				'title'       => __( 'API Credentials', 'whop-gateway-wc' ),
				'type'        => 'title',
				'description' => __( 'Create a company API key in Whop → Settings → API Keys.', 'whop-gateway-wc' ),
			],
			'api_key' => [
				'title'       => __( 'API Key', 'whop-gateway-wc' ),
				'type'        => 'password',
				'description' => __( 'Company API key (starts with apik_).', 'whop-gateway-wc' ),
				'default'     => '',
			],
			'company_id' => [
				'title'       => __( 'Company ID', 'whop-gateway-wc' ),
				'type'        => 'text',
				'description' => __( 'Your Whop company ID (e.g. biz_xxxx or comp_xxxx).', 'whop-gateway-wc' ),
				'default'     => '',
			],
			'webhook_section' => [
				'title'       => __( 'Webhooks (required)', 'whop-gateway-wc' ),
				'type'        => 'title',
				'description' => sprintf(
					/* translators: %s: webhook URL */
					__(
						'Add this URL in Whop → Developer → Webhooks (API v1). Subscribe to <strong>payment.succeeded</strong>, <strong>payment.failed</strong>, and <strong>refund.created</strong>:<br><code>%s</code>',
						'whop-gateway-wc'
					),
					esc_html( $webhook_url )
				),
			],
			'webhook_secret' => [
				'title'       => __( 'Webhook Secret', 'whop-gateway-wc' ),
				'type'        => 'password',
				'description' => __( 'Required. Signing secret from your Whop webhook (starts with ws_).', 'whop-gateway-wc' ),
				'default'     => '',
			],
			'sandbox_mode' => [
				'title'       => __( 'Sandbox Mode', 'whop-gateway-wc' ),
				'type'        => 'checkbox',
				'label'       => __( 'Use Whop sandbox environment', 'whop-gateway-wc' ),
				'description' => __( 'Enable for testing. Disable before accepting real payments.', 'whop-gateway-wc' ),
				'default'     => 'no',
			],
			'debug_section' => [
				'title' => __( 'Diagnostics', 'whop-gateway-wc' ),
				'type'  => 'title',
			],
			'debug_mode' => [
				'title'       => __( 'Debug logging', 'whop-gateway-wc' ),
				'type'        => 'checkbox',
				'label'       => __( 'Log debug events', 'whop-gateway-wc' ),
				'description' => __( 'Writes to WooCommerce → Status → Logs (source: whop-gateway-wc).', 'whop-gateway-wc' ),
				'default'     => 'no',
			],
			'license_key' => [
				'title'       => __( 'License key', 'whop-gateway-wc' ),
				'type'        => 'text',
				'description' => __( 'Pro/Agency license for one-click Connect. Also configurable under WooCommerce → Whop Checkout.', 'whop-gateway-wc' ),
				'default'     => '',
			],
			'connect_url' => [
				'title'       => __( 'Connect service URL', 'whop-gateway-wc' ),
				'type'        => 'text',
				'description' => __( 'Your seller-hosted OAuth Connect bridge URL (e.g. https://connect.yourdomain.com). Required for one-click Connect.', 'whop-gateway-wc' ),
				'default'     => '',
			],
			'support_url' => [
				'title'       => __( 'Support URL', 'whop-gateway-wc' ),
				'type'        => 'text',
				'description' => __( 'Optional link shown to store admins for buyer support.', 'whop-gateway-wc' ),
				'default'     => '',
			],
		];
	}

	public function process_admin_options(): void {
		parent::process_admin_options();

		$updates = [];
		$api_key = trim( (string) $this->get_option( 'api_key' ) );
		if ( $api_key ) {
			$updates['api_key'] = Whop_GW_Helper::maybe_encrypt_secret( $api_key );
		}

		$webhook_secret = trim( (string) $this->get_option( 'webhook_secret' ) );
		if ( $webhook_secret ) {
			$updates['webhook_secret'] = Whop_GW_Helper::maybe_encrypt_secret( $webhook_secret );
		}

		if ( $updates ) {
			Whop_GW_Helper::save_settings( $updates );
		}

		$errors = $this->validate_saved_settings();
		foreach ( $errors as $error ) {
			WC_Admin_Settings::add_error( $error );
		}
	}

	/**
	 * @return string[]
	 */
	private function validate_saved_settings(): array {
		$errors = [];

		if ( 'yes' !== $this->get_option( 'enabled' ) ) {
			return $errors;
		}

		if ( ! Whop_GW_Helper::has_credentials() ) {
			$errors[] = __( 'Whop Checkout is enabled but not connected. Use Whop Checkout → Connect or Setup Wizard.', 'whop-gateway-wc' );
			return $errors;
		}

		if ( 'oauth' !== $this->get_option( 'connection_mode' ) && empty( $this->get_option( 'api_key' ) ) ) {
			$errors[] = __( 'Whop Checkout is enabled but API Key is missing.', 'whop-gateway-wc' );
		}

		if ( empty( $this->get_option( 'company_id' ) ) ) {
			$errors[] = __( 'Whop Checkout is enabled but Company ID is missing.', 'whop-gateway-wc' );
		}

		if ( empty( Whop_GW_Helper::get_webhook_secret() ) ) {
			$errors[] = __( 'Whop Checkout is enabled but Webhook Secret is missing. Orders cannot be marked paid without webhooks.', 'whop-gateway-wc' );
		}

		if ( ! is_ssl() && ! self::is_local_dev() ) {
			$errors[] = __( 'Your site is not using HTTPS. Whop webhooks require a public HTTPS URL.', 'whop-gateway-wc' );
		}

		return $errors;
	}

	private static function is_local_dev(): bool {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $host ) ) {
			return false;
		}

		return in_array( $host, [ 'localhost', '127.0.0.1' ], true ) || false !== strpos( $host, '.local' );
	}

	public function is_available(): bool {
		if ( ! parent::is_available() ) {
			return false;
		}

		return Whop_GW_Helper::has_credentials();
	}

	public function admin_options(): void {
		echo '<p><a class="button button-primary" href="' . esc_url( Whop_GW_Helper::admin_page_url( 'connect' ) ) . '">'
			. esc_html__( 'Open Whop Checkout dashboard', 'whop-gateway-wc' ) . '</a></p>';
		parent::admin_options();
		?>
		<p>
			<button type="button" class="button button-secondary" id="whop-gateway-test-connection">
				<?php esc_html_e( 'Test API connection', 'whop-gateway-wc' ); ?>
			</button>
			<span id="whop-gateway-test-result" style="margin-left:8px;"></span>
		</p>
		<script>
		(function () {
			var btn = document.getElementById('whop-gateway-test-connection');
			var out = document.getElementById('whop-gateway-test-result');
			if (!btn) return;
			btn.addEventListener('click', function () {
				out.textContent = '<?php echo esc_js( __( 'Testing…', 'whop-gateway-wc' ) ); ?>';
				out.style.color = '';
				var data = new FormData();
				data.append('action', 'whop_gateway_test_connection');
				data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'whop_gateway_test_connection' ) ); ?>');
				fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: data })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						out.textContent = res && res.data && res.data.message ? res.data.message : '<?php echo esc_js( __( 'Unknown response.', 'whop-gateway-wc' ) ); ?>';
						out.style.color = res && res.success ? '#2271b1' : '#b32d2e';
					})
					.catch(function () {
						out.textContent = '<?php echo esc_js( __( 'Request failed.', 'whop-gateway-wc' ) ); ?>';
						out.style.color = '#b32d2e';
					});
			});
		})();
		</script>
		<?php
	}

	public function ajax_test_connection(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'whop-gateway-wc' ) ], 403 );
		}

		check_ajax_referer( 'whop_gateway_test_connection', 'nonce' );

		if ( ! Whop_GW_Helper::get_bearer_token() ) {
			wp_send_json_error( [ 'message' => __( 'Connect Whop or enter an API key first.', 'whop-gateway-wc' ) ] );
		}

		$result = $this->get_whop_api()->test_connection();

		if ( $result['ok'] ) {
			wp_send_json_success( [ 'message' => $result['message'] ] );
		}

		wp_send_json_error( [ 'message' => $result['message'] ] );
	}

	public function payment_fields(): void {
		echo '<div class="whop-payment-card">';

		if ( $this->description ) {
			echo '<p class="whop-card-description">' . wp_kses_post( $this->description ) . '</p>';
		}

		if ( 'yes' === $this->get_option( 'sandbox_mode' ) ) {
			echo '<span class="whop-sandbox-badge">' . esc_html__( 'Sandbox mode — test payments only', 'whop-gateway-wc' ) . '</span>';
		}

		echo '</div>';
	}

	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order not found. Please try again.', 'whop-gateway-wc' ), 'error' );
			return [ 'result' => 'fail' ];
		}

		if ( $order->is_paid() ) {
			wc_add_notice( __( 'This order has already been paid.', 'whop-gateway-wc' ), 'error' );
			return [ 'result' => 'fail' ];
		}

		$api_key    = Whop_GW_Helper::get_bearer_token();
		$company_id = trim( (string) $this->get_option( 'company_id' ) );

		if ( empty( $api_key ) || empty( $company_id ) ) {
			wc_add_notice(
				__( 'Whop Checkout is not configured. Please contact the store owner.', 'whop-gateway-wc' ),
				'error'
			);
			return [ 'result' => 'fail' ];
		}

		if ( empty( Whop_GW_Helper::get_webhook_secret() ) ) {
			wc_add_notice(
				__( 'Whop Checkout is temporarily unavailable. Please choose another payment method.', 'whop-gateway-wc' ),
				'error'
			);
			Whop_GW_Helper::log( 'Checkout blocked: webhook secret not configured.', 'error' );
			return [ 'result' => 'fail' ];
		}

		$return_token = Whop_GW_Helper::issue_return_token( $order );
		$return_url   = Whop_GW_Helper::build_return_url( $order, $return_token );

		$currency = strtolower( $order->get_currency() );
		$total    = round( (float) $order->get_total(), 2 );

		if ( $total <= 0 ) {
			wc_add_notice( __( 'Invalid order total.', 'whop-gateway-wc' ), 'error' );
			return [ 'result' => 'fail' ];
		}

		$order->update_meta_data( Whop_GW_Helper::META_EXPECTED_TOTAL, (string) $total );
		$order->update_meta_data( Whop_GW_Helper::META_EXPECTED_CURRENCY, $currency );
		$order->save();

		$metadata = Whop_GW_Helper::payment_metadata( $order );

		$plan_body = [
			'company_id'               => $company_id,
			'plan_type'                => 'one_time',
			'release_method'           => 'buy_now',
			'initial_price'            => $total,
			'currency'                 => $currency,
			'adaptive_pricing_enabled' => false,
			'unlimited_stock'          => true,
			'title'                    => sprintf(
				/* translators: 1: order number, 2: site name */
				__( 'Order #%1$s — %2$s', 'whop-gateway-wc' ),
				$order->get_order_number(),
				get_bloginfo( 'name' )
			),
			'metadata'                 => $metadata,
		];

		Whop_GW_Helper::log( sprintf( 'Creating Whop plan for order #%d (%s %s)', $order_id, $total, strtoupper( $currency ) ) );

		$api         = $this->get_whop_api();
		$plan_result = $api->create_plan( $plan_body );

		if ( isset( $plan_result['error'] ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: error message */
					__( 'Payment setup failed: %s', 'whop-gateway-wc' ),
					esc_html( $plan_result['error'] )
				),
				'error'
			);
			Whop_GW_Helper::log( 'create_plan failed for order #' . $order_id . ': ' . $plan_result['error'], 'error' );
			return [ 'result' => 'fail' ];
		}

		$plan_id = $plan_result['id'] ?? '';
		if ( empty( $plan_id ) ) {
			wc_add_notice( __( 'Payment setup failed. Please try again.', 'whop-gateway-wc' ), 'error' );
			return [ 'result' => 'fail' ];
		}

		$checkout_body = [
			'company_id'   => $company_id,
			'plan_id'      => $plan_id,
			'mode'         => 'payment',
			'redirect_url' => $return_url,
			'metadata'     => $metadata,
		];

		$result = $api->create_checkout_configuration( $checkout_body );

		if ( isset( $result['error'] ) ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: error message */
					__( 'Checkout setup failed: %s', 'whop-gateway-wc' ),
					esc_html( $result['error'] )
				),
				'error'
			);
			Whop_GW_Helper::log( 'create_checkout_configuration failed for order #' . $order_id . ': ' . $result['error'], 'error' );
			return [ 'result' => 'fail' ];
		}

		$config_id    = $result['id'] ?? '';
		$purchase_url = $result['purchase_url'] ?? '';

		if ( empty( $purchase_url ) || ! wp_http_validate_url( $purchase_url ) || ! self::is_whop_checkout_url( $purchase_url ) ) {
			wc_add_notice( __( 'Checkout URL was not returned. Please try again.', 'whop-gateway-wc' ), 'error' );
			return [ 'result' => 'fail' ];
		}

		$order->update_meta_data( Whop_GW_Helper::META_PLAN_ID, $plan_id );
		$order->update_meta_data( Whop_GW_Helper::META_CHECKOUT_ID, $config_id );
		$order->update_meta_data( Whop_GW_Helper::META_PURCHASE_URL, esc_url_raw( $purchase_url ) );
		$order->update_status( 'pending', __( 'Awaiting Whop payment.', 'whop-gateway-wc' ) );
		$order->save();

		Whop_GW_Helper::log( sprintf( 'Redirecting order #%d to Whop checkout (config %s)', $order_id, $config_id ) );

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return [
			'result'   => 'success',
			'redirect' => $purchase_url,
		];
	}

	public function webhook_handler(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			status_header( 405 );
			exit;
		}

		$payload = file_get_contents( 'php://input' );
		$secret  = Whop_GW_Helper::get_webhook_secret();
		$headers = Whop_Webhook::headers_from_server();

		if ( ! Whop_Webhook::verify( is_string( $payload ) ? $payload : '', $headers, $secret ) ) {
			Whop_GW_Helper::log( 'Webhook signature verification failed.', 'error' );
			status_header( 401 );
			wp_send_json( [ 'error' => 'invalid_signature' ] );
		}

		Whop_GW_Helper::record_webhook_received();

		$data = json_decode( is_string( $payload ) ? $payload : '', true );
		if ( ! is_array( $data ) ) {
			status_header( 400 );
			exit;
		}

		$event_id      = (string) ( $headers['webhook-id'] ?? ( $data['id'] ?? '' ) );
		$event_type    = (string) ( $data['type'] ?? $data['event'] ?? '' );
		$event_company = (string) ( $data['company_id'] ?? '' );
		$payment       = is_array( $data['data'] ?? null ) ? $data['data'] : [];

		Whop_GW_Helper::log( sprintf( 'Webhook received: %s (%s)', $event_type, $event_id ) );

		if ( $event_id && Whop_GW_Helper::global_event_processed( $event_id ) ) {
			status_header( 200 );
			wp_send_json( [ 'received' => true, 'duplicate' => true ] );
		}

		$configured_company = trim( (string) $this->get_option( 'company_id' ) );
		if ( $event_company && $configured_company && ! hash_equals( $configured_company, $event_company ) ) {
			Whop_GW_Helper::log( 'Webhook company_id mismatch.', 'error' );
			status_header( 200 );
			wp_send_json( [ 'received' => true, 'ignored' => 'company_mismatch' ] );
		}

		switch ( $event_type ) {
			case 'payment.succeeded':
				$this->handle_payment_succeeded( $payment, $event_id );
				break;
			case 'payment.failed':
				$this->handle_payment_failed( $payment );
				break;
			case 'refund.created':
				$this->handle_refund_created( $payment );
				break;
		}

		if ( $event_id ) {
			Whop_GW_Helper::mark_global_event_processed( $event_id );
		}

		status_header( 200 );
		wp_send_json( [ 'received' => true ] );
	}

	private function handle_payment_succeeded( array $payment, string $event_id ): void {
		$payment = Whop_GW_Helper::verify_payment_with_api( $payment );

		$order = Whop_GW_Helper::resolve_order( $payment );
		if ( ! $order ) {
			return;
		}

		if ( 'succeeded' !== ( $payment['status'] ?? 'succeeded' ) && ! empty( $payment['status'] ) ) {
			Whop_GW_Helper::log( 'Ignoring payment webhook with non-success status for order #' . $order->get_id(), 'error' );
			return;
		}

		if ( $event_id && Whop_GW_Helper::order_event_processed( $order, $event_id ) ) {
			return;
		}

		$payment_id = (string) ( $payment['id'] ?? '' );
		$existing   = (string) $order->get_meta( Whop_GW_Helper::META_PAYMENT_ID, true );
		if ( $payment_id && $existing && hash_equals( $existing, $payment_id ) && $order->is_paid() ) {
			return;
		}

		if ( ! Whop_GW_Helper::payment_matches_order( $order, $payment ) ) {
			$order->add_order_note(
				__( 'Whop payment received but amount/currency did not match. Manual review required.', 'whop-gateway-wc' )
			);
			$order->save();
			Whop_GW_Helper::log( 'Payment amount mismatch for order #' . $order->get_id(), 'error' );
			return;
		}

		if ( ! $order->is_paid() ) {
			$order->payment_complete( $payment_id );
			$order->add_order_note(
				sprintf(
					/* translators: %s: Whop payment ID */
					__( 'Payment confirmed via Whop webhook (payment ID: %s).', 'whop-gateway-wc' ),
					esc_html( $payment_id )
				)
			);
		}

		if ( $payment_id ) {
			$order->update_meta_data( Whop_GW_Helper::META_PAYMENT_ID, $payment_id );
		}

		if ( $event_id ) {
			Whop_GW_Helper::mark_order_event_processed( $order, $event_id );
		}

		$order->save();
	}

	private function handle_payment_failed( array $payment ): void {
		$order = Whop_GW_Helper::resolve_order( $payment );
		if ( ! $order || $order->is_paid() ) {
			return;
		}

		$message = $payment['failure_message'] ?? __( 'Payment failed at Whop.', 'whop-gateway-wc' );
		$order->add_order_note(
			sprintf(
				/* translators: %s: failure reason */
				__( 'Whop payment failed: %s', 'whop-gateway-wc' ),
				esc_html( $message )
			)
		);
		if ( ! $order->has_status( [ 'failed', 'cancelled' ] ) ) {
			$order->update_status( 'failed', __( 'Whop payment failed.', 'whop-gateway-wc' ) );
		}
		$order->save();
	}

	private function handle_refund_created( array $refund ): void {
		$order = Whop_GW_Helper::resolve_order( $refund );
		if ( ! $order ) {
			return;
		}

		$order->add_order_note( __( 'Whop refund recorded for this order.', 'whop-gateway-wc' ) );

		if ( $order->is_paid() && ! $order->has_status( [ 'refunded', 'cancelled' ] ) ) {
			$order->update_status(
				'refunded',
				__( 'Order refunded via Whop.', 'whop-gateway-wc' )
			);
		}

		$order->save();
	}

	/**
	 * Attempt to sync payment status from Whop when webhook delivery is delayed.
	 */
	public static function sync_order_from_whop( WC_Order $order ): bool {
		if ( ! Whop_GW_Helper::is_whop_order( $order ) || $order->is_paid() ) {
			return $order->is_paid();
		}

		$payment_id = (string) $order->get_meta( Whop_GW_Helper::META_PAYMENT_ID, true );
		if ( $payment_id ) {
			$remote = Whop_GW_Helper::get_api()->get_payment( $payment_id );
			if ( ! isset( $remote['error'] ) && Whop_GW_Helper::payment_matches_order( $order, $remote ) ) {
				if ( in_array( $remote['status'] ?? '', [ 'succeeded', 'paid', 'completed' ], true ) ) {
					$order->payment_complete( $payment_id );
					$order->add_order_note( __( 'Payment confirmed via Whop API sync.', 'whop-gateway-wc' ) );
					$order->save();
					return true;
				}
			}
		}

		return false;
	}

	public static function render_return_page(): void {
		$order_id = absint( wp_unslash( $_GET['order_id'] ?? '' ) );
		$token    = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );
		$order    = wc_get_order( $order_id );

		if ( ! $order || ! Whop_GW_Helper::return_token_valid( $order, $token ) ) {
			wp_die(
				esc_html__( 'This payment session is invalid or has expired. If you were charged, contact the store with your order number.', 'whop-gateway-wc' ),
				esc_html__( 'Payment session error', 'whop-gateway-wc' ),
				[ 'response' => 403 ]
			);
		}

		if ( $order->is_paid() ) {
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		self::sync_order_from_whop( $order );

		if ( $order->is_paid() ) {
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		$thank_you_url = esc_url( $order->get_checkout_order_received_url() );
		$shop_url      = esc_url( wc_get_page_permalink( 'shop' ) );
		if ( ! $shop_url ) {
			$shop_url = esc_url( home_url( '/' ) );
		}

		$ajax_url     = esc_url( admin_url( 'admin-ajax.php' ) );
		$nonce        = wp_create_nonce( 'whop_order_status_' . $order_id );
		$shop_name    = esc_html( get_bloginfo( 'name' ) );
		$order_number = esc_html( $order->get_order_number() );

		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: no-referrer' );

		include WHOP_GW_PLUGIN_PATH . 'templates/return.php';
	}

	private function get_whop_api(): Whop_API {
		return Whop_GW_Helper::get_api();
	}

	private static function is_whop_checkout_url( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! is_string( $host ) ) {
			return false;
		}

		$host = strtolower( $host );

		return 'whop.com' === $host || ( strlen( $host ) > 9 && '.whop.com' === substr( $host, -9 ) );
	}
}

add_action( 'wp_ajax_nopriv_whop_check_order_status', 'whop_gw_ajax_check_order_status' );
add_action( 'wp_ajax_whop_check_order_status', 'whop_gw_ajax_check_order_status' );

function whop_gw_ajax_check_order_status(): void {
	$order_id = absint( $_POST['order_id'] ?? 0 );
	$token    = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
	$nonce    = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

	if ( ! wp_verify_nonce( $nonce, 'whop_order_status_' . $order_id ) ) {
		wp_send_json_error( [ 'code' => 'invalid_nonce' ], 403 );
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! Whop_GW_Helper::return_token_valid( $order, $token ) ) {
		wp_send_json_error( [ 'code' => 'not_found' ], 404 );
	}

	$rate_key = 'whop_gw_poll_' . $order_id;
	if ( get_transient( $rate_key ) ) {
		wp_send_json_success( [ 'paid' => $order->is_paid() ] );
	}
	set_transient( $rate_key, 1, 1 );

	if ( ! $order->is_paid() ) {
		WC_Gateway_Whop::sync_order_from_whop( $order );
	}

	wp_send_json_success( [ 'paid' => $order->is_paid() ] );
}
