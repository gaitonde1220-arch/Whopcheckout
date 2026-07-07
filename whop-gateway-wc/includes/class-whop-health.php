<?php
/**
 * Connection health checks.
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class Whop_GW_Health {

	/**
	 * @return array<string, array{status:string,label:string,value:string}>
	 */
	public static function get_checks(): array {
		$settings     = Whop_GW_Helper::get_settings();
		$last_webhook = Whop_GW_Helper::get_last_webhook_at();

		$has_webhook_secret = '' !== Whop_GW_Helper::get_webhook_secret();

		$checks = [
			'https' => [
				'status' => Whop_GW_Helper::is_https_ready() ? 'ok' : 'fail',
				'label'  => __( 'HTTPS', 'whop-gateway-wc' ),
				'value'  => Whop_GW_Helper::is_https_ready()
					? __( 'Ready', 'whop-gateway-wc' )
					: __( 'Required for webhooks', 'whop-gateway-wc' ),
			],
			'woocommerce' => [
				'status' => class_exists( 'WooCommerce' ) ? 'ok' : 'fail',
				'label'  => __( 'WooCommerce', 'whop-gateway-wc' ),
				'value'  => class_exists( 'WooCommerce' ) ? __( 'Active', 'whop-gateway-wc' ) : __( 'Missing', 'whop-gateway-wc' ),
			],
			'checkout' => [
				'status' => Whop_GW_Helper::uses_block_checkout() ? 'warn' : 'ok',
				'label'  => __( 'Checkout', 'whop-gateway-wc' ),
				'value'  => Whop_GW_Helper::uses_block_checkout()
					? __( 'Blocks detected — use classic checkout', 'whop-gateway-wc' )
					: __( 'Classic checkout', 'whop-gateway-wc' ),
			],
			'credentials' => [
				'status' => Whop_GW_Helper::has_credentials() ? 'ok' : 'fail',
				'label'  => __( 'Credentials', 'whop-gateway-wc' ),
				'value'  => Whop_GW_Helper::has_credentials()
					? ( 'oauth' === $settings['connection_mode'] ? __( 'Connected via Whop', 'whop-gateway-wc' ) : __( 'Manual API configured', 'whop-gateway-wc' ) )
					: __( 'Not configured', 'whop-gateway-wc' ),
			],
			'webhook_secret' => [
				'status' => $has_webhook_secret ? 'ok' : 'fail',
				'label'  => __( 'Webhook secret', 'whop-gateway-wc' ),
				'value'  => $has_webhook_secret ? __( 'Set', 'whop-gateway-wc' ) : __( 'Missing', 'whop-gateway-wc' ),
			],
			'last_webhook' => [
				'status' => $last_webhook > 0 ? 'ok' : 'warn',
				'label'  => __( 'Last webhook', 'whop-gateway-wc' ),
				'value'  => $last_webhook > 0
					? sprintf(
						/* translators: %s: human time diff */
						__( '%s ago', 'whop-gateway-wc' ),
						human_time_diff( $last_webhook, time() )
					)
					: __( 'None received yet', 'whop-gateway-wc' ),
			],
			'sandbox' => [
				'status' => 'yes' === $settings['sandbox_mode'] ? 'warn' : 'ok',
				'label'  => __( 'Sandbox', 'whop-gateway-wc' ),
				'value'  => 'yes' === $settings['sandbox_mode']
					? __( 'Test mode ON', 'whop-gateway-wc' )
					: __( 'Live mode', 'whop-gateway-wc' ),
			],
		];

		if ( Whop_GW_Helper::has_credentials() ) {
			$cached = get_transient( 'whop_gw_health_api' );
			if ( false === $cached || ! is_array( $cached ) ) {
				$cached = Whop_GW_Helper::get_api()->test_connection();
				set_transient( 'whop_gw_health_api', $cached, 60 );
			}

			$checks['api'] = [
				'status' => ! empty( $cached['ok'] ) ? 'ok' : 'fail',
				'label'  => __( 'Whop API', 'whop-gateway-wc' ),
				'value'  => (string) ( $cached['message'] ?? '' ),
			];
		}

		return $checks;
	}

	public static function render_dashboard(): void {
		$checks = self::get_checks();
		?>
		<div class="whop-gw-health-grid">
			<?php foreach ( $checks as $check ) : ?>
				<div class="whop-gw-health-item is-<?php echo esc_attr( $check['status'] ); ?>">
					<div class="whop-gw-health-label"><?php echo esc_html( $check['label'] ); ?></div>
					<div class="whop-gw-health-value"><?php echo esc_html( $check['value'] ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public static function all_critical_passing(): bool {
		foreach ( self::get_checks() as $id => $check ) {
			if ( in_array( $id, [ 'checkout', 'last_webhook', 'sandbox' ], true ) ) {
				continue;
			}
			if ( 'fail' === $check['status'] ) {
				return false;
			}
		}

		return true;
	}
}
