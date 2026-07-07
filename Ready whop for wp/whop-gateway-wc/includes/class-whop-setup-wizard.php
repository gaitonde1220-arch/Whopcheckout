<?php
/**
 * Setup wizard for manual configuration.
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class Whop_GW_Setup_Wizard {

	public static function init(): void {
		add_action( 'wp_ajax_whop_gw_wizard_action', [ __CLASS__, 'ajax_wizard_action' ] );
	}

	public static function get_current_step(): int {
		return max( 1, min( 5, absint( $_GET['step'] ?? 1 ) ) );
	}

	public static function render(): void {
		$step = self::get_current_step();
		?>
		<div class="whop-gw-steps">
			<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
				<?php
				$class = 'whop-gw-step';
				if ( $i === $step ) {
					$class .= ' is-active';
				} elseif ( $i < $step ) {
					$class .= ' is-done';
				}
				?>
				<span class="<?php echo esc_attr( $class ); ?>">
					<?php echo esc_html( sprintf( __( 'Step %d', 'whop-gateway-wc' ), $i ) ); ?>
				</span>
			<?php endfor; ?>
		</div>
		<?php

		switch ( $step ) {
			case 1:
				self::render_step_requirements();
				break;
			case 2:
				self::render_step_credentials();
				break;
			case 3:
				self::render_step_webhook();
				break;
			case 4:
				self::render_step_verify();
				break;
			default:
				self::render_step_golive();
		}
	}

	private static function render_step_requirements(): void {
		$checks = Whop_GW_Health::get_checks();
		?>
		<div class="whop-gw-card">
			<h2><?php esc_html_e( 'Step 1 — Requirements', 'whop-gateway-wc' ); ?></h2>
			<p class="whop-gw-muted"><?php esc_html_e( 'Confirm your store meets the minimum requirements.', 'whop-gateway-wc' ); ?></p>
			<?php Whop_GW_Health::render_dashboard(); ?>
			<div class="whop-gw-actions">
				<a class="button button-primary" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) . '&step=2' ); ?>">
					<?php esc_html_e( 'Continue', 'whop-gateway-wc' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	private static function render_step_credentials(): void {
		$settings = Whop_GW_Helper::get_settings();

		if ( isset( $_POST['whop_gw_wizard_credentials'] ) && check_admin_referer( 'whop_gw_wizard_credentials' ) ) {
			$api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );
			Whop_GW_Helper::save_settings(
				[
					'connection_mode' => 'manual',
					'api_key'         => $api_key ? Whop_GW_Helper::maybe_encrypt_secret( $api_key ) : Whop_GW_Helper::get_settings()['api_key'],
					'company_id'      => sanitize_text_field( wp_unslash( $_POST['company_id'] ?? '' ) ),
				]
			);
			$settings = Whop_GW_Helper::get_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Credentials saved.', 'whop-gateway-wc' ) . '</p></div>';
		}

		?>
		<div class="whop-gw-card">
			<h2><?php esc_html_e( 'Step 2 — API credentials', 'whop-gateway-wc' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'whop_gw_wizard_credentials' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="api_key"><?php esc_html_e( 'API Key', 'whop-gateway-wc' ); ?></label></th>
						<td>
							<input type="password" class="regular-text" name="api_key" id="api_key" value="" placeholder="<?php echo esc_attr( ! empty( $settings['api_key'] ) ? __( 'Saved — leave blank to keep', 'whop-gateway-wc' ) : '' ); ?>" autocomplete="off">
						</td>
					</tr>
					<tr>
						<th><label for="company_id"><?php esc_html_e( 'Company ID', 'whop-gateway-wc' ); ?></label></th>
						<td><input type="text" class="regular-text" name="company_id" id="company_id" value="<?php echo esc_attr( $settings['company_id'] ); ?>"></td>
					</tr>
				</table>
				<p class="whop-gw-muted"><?php esc_html_e( 'Find these in Whop → Settings → API Keys.', 'whop-gateway-wc' ); ?></p>
				<p>
					<button type="submit" name="whop_gw_wizard_credentials" class="button button-primary"><?php esc_html_e( 'Save and continue', 'whop-gateway-wc' ); ?></button>
					<a class="button" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) . '&step=3' ); ?>"><?php esc_html_e( 'Next', 'whop-gateway-wc' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	private static function render_step_webhook(): void {
		$settings = Whop_GW_Helper::get_settings();
		$message  = '';

		if ( isset( $_POST['whop_gw_auto_webhook'] ) && check_admin_referer( 'whop_gw_auto_webhook' ) ) {
			if ( ! Whop_GW_Helper::has_credentials() ) {
				$message = __( 'Configure API credentials first.', 'whop-gateway-wc' );
			} else {
				$result = Whop_GW_Helper::get_api()->register_store_webhook( $settings['company_id'] );
				if ( $result['ok'] ) {
					Whop_GW_Helper::save_settings(
						[
							'webhook_secret' => ! empty( $result['secret'] )
								? Whop_GW_Helper::encrypt( (string) $result['secret'] )
								: $settings['webhook_secret'],
							'webhook_id'     => (string) ( $result['webhook']['id'] ?? '' ),
						]
					);
					$settings = Whop_GW_Helper::get_settings();
					$message  = $result['message'];
				} else {
					$message = $result['message'];
				}
			}
		}

		if ( $message ) {
			echo '<div class="notice notice-info"><p>' . esc_html( $message ) . '</p></div>';
		}
		?>
		<div class="whop-gw-card">
			<h2><?php esc_html_e( 'Step 3 — Register webhook', 'whop-gateway-wc' ); ?></h2>
			<p><?php esc_html_e( 'Webhook URL for your store:', 'whop-gateway-wc' ); ?></p>
			<p><code><?php echo esc_html( Whop_GW_Helper::webhook_url() ); ?></code></p>
			<form method="post">
				<?php wp_nonce_field( 'whop_gw_auto_webhook' ); ?>
				<p>
					<button type="submit" name="whop_gw_auto_webhook" class="button button-primary">
						<?php esc_html_e( 'Auto-register webhook via API', 'whop-gateway-wc' ); ?>
					</button>
				</p>
			</form>
			<?php if ( Whop_GW_Helper::get_webhook_secret() ) : ?>
				<p class="whop-gw-status-connected"><?php esc_html_e( 'Webhook secret is configured.', 'whop-gateway-wc' ); ?></p>
			<?php else : ?>
				<p class="whop-gw-muted"><?php esc_html_e( 'If auto-register fails, create the webhook manually in Whop → Developer → Webhooks and paste the secret in WooCommerce payment settings.', 'whop-gateway-wc' ); ?></p>
			<?php endif; ?>
			<div class="whop-gw-actions">
				<a class="button button-primary" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) . '&step=4' ); ?>">
					<?php esc_html_e( 'Continue', 'whop-gateway-wc' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	private static function render_step_verify(): void {
		?>
		<div class="whop-gw-card">
			<h2><?php esc_html_e( 'Step 4 — Verify connection', 'whop-gateway-wc' ); ?></h2>
			<?php Whop_GW_Health::render_dashboard(); ?>
			<div class="whop-gw-actions">
				<button type="button" class="button" id="whop-gw-test-api"><?php esc_html_e( 'Test API connection', 'whop-gateway-wc' ); ?></button>
				<span id="whop-gw-test-api-result"></span>
				<a class="button button-primary" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) . '&step=5' ); ?>">
					<?php esc_html_e( 'Continue', 'whop-gateway-wc' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	private static function render_step_golive(): void {
		$settings = Whop_GW_Helper::get_settings();

		if ( isset( $_POST['whop_gw_wizard_complete'] ) && check_admin_referer( 'whop_gw_wizard_complete' ) ) {
			Whop_GW_Helper::save_settings(
				[
					'sandbox_mode' => isset( $_POST['sandbox_mode'] ) ? 'yes' : 'no',
					'enabled'      => isset( $_POST['enable_gateway'] ) ? 'yes' : 'no',
				]
			);
			if ( isset( $_POST['confirm_test'] ) ) {
				Whop_GW_Helper::mark_setup_complete();
			}
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'whop-gateway-wc' ) . '</p></div>';
			$settings = Whop_GW_Helper::get_settings();
		}
		?>
		<div class="whop-gw-card">
			<h2><?php esc_html_e( 'Step 5 — Sandbox test & go live', 'whop-gateway-wc' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Enable sandbox mode and save.', 'whop-gateway-wc' ); ?></li>
				<li><?php esc_html_e( 'Place a test order using Whop Checkout at checkout.', 'whop-gateway-wc' ); ?></li>
				<li><?php esc_html_e( 'Confirm the order is marked paid with a Whop payment ID.', 'whop-gateway-wc' ); ?></li>
				<li><?php esc_html_e( 'Disable sandbox mode for live sales.', 'whop-gateway-wc' ); ?></li>
			</ol>
			<form method="post" class="whop-gw-checklist">
				<?php wp_nonce_field( 'whop_gw_wizard_complete' ); ?>
				<label><input type="checkbox" name="sandbox_mode" value="1" <?php checked( 'yes', $settings['sandbox_mode'] ); ?>> <?php esc_html_e( 'Sandbox mode enabled (for testing)', 'whop-gateway-wc' ); ?></label>
				<label><input type="checkbox" name="enable_gateway" value="1" <?php checked( 'yes', $settings['enabled'] ); ?>> <?php esc_html_e( 'Enable Whop Checkout gateway', 'whop-gateway-wc' ); ?></label>
				<label><input type="checkbox" name="confirm_test" value="1" <?php checked( Whop_GW_Helper::is_setup_complete() ); ?>> <?php esc_html_e( 'I completed a successful sandbox test order', 'whop-gateway-wc' ); ?></label>
				<p>
					<button type="submit" name="whop_gw_wizard_complete" class="button button-primary"><?php esc_html_e( 'Save and finish', 'whop-gateway-wc' ); ?></button>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>"><?php esc_html_e( 'Open checkout settings', 'whop-gateway-wc' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	public static function ajax_wizard_action(): void {
		check_ajax_referer( 'whop_gw_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'whop-gateway-wc' ) ], 403 );
		}

		wp_send_json_success( [ 'message' => __( 'OK', 'whop-gateway-wc' ) ] );
	}
}
