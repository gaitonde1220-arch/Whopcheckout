<?php
/**
 * Connect tab view.
 *
 * @var bool   $connected
 * @var bool   $can_connect
 * @var array  $settings
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="whop-gw-card">
	<h2><?php esc_html_e( 'Connect your Whop', 'whop-gateway-wc' ); ?></h2>

	<?php if ( $connected ) : ?>
		<p class="whop-gw-status-connected">
			<?php
			printf(
				/* translators: %s: company id */
				esc_html__( 'Connected to %s', 'whop-gateway-wc' ),
				esc_html( $settings['company_id'] )
			);
			?>
		</p>
		<?php Whop_GW_Health::render_dashboard(); ?>
		<div class="whop-gw-actions">
			<a class="button button-primary" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) . '&step=5' ); ?>">
				<?php esc_html_e( 'Run sandbox test checklist', 'whop-gateway-wc' ); ?>
			</a>
			<button type="button" class="button" id="whop-gw-disconnect">
				<?php esc_html_e( 'Disconnect', 'whop-gateway-wc' ); ?>
			</button>
		</div>
	<?php else : ?>
		<p class="whop-gw-muted">
			<?php esc_html_e( 'One-click setup — no API keys or webhook URLs to copy. Requires a Pro or Agency license key.', 'whop-gateway-wc' ); ?>
		</p>

		<form method="post" style="margin-bottom:20px;">
			<?php wp_nonce_field( 'whop_gw_save_connect_settings' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="license_key"><?php esc_html_e( 'License key', 'whop-gateway-wc' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" name="license_key" id="license_key" value="<?php echo esc_attr( $settings['license_key'] ); ?>" placeholder="WGWC-PRO-XXXX">
						<p class="description"><?php esc_html_e( 'From your purchase confirmation email.', 'whop-gateway-wc' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="connect_url"><?php esc_html_e( 'Connect service URL', 'whop-gateway-wc' ); ?></label></th>
					<td>
						<input type="url" class="regular-text" name="connect_url" id="connect_url" value="<?php echo esc_attr( $settings['connect_url'] ); ?>" placeholder="https://connect.yourdomain.com">
						<p class="description"><?php esc_html_e( 'OAuth bridge URL provided by your plugin seller (e.g. https://connect.yourdomain.com). Leave empty if using manual setup only.', 'whop-gateway-wc' ); ?></p>
					</td>
				</tr>
			</table>
			<button type="submit" name="whop_gw_save_connect_settings" class="button"><?php esc_html_e( 'Save connect settings', 'whop-gateway-wc' ); ?></button>
		</form>

		<?php if ( $can_connect ) : ?>
			<a href="<?php echo esc_url( Whop_GW_Connect::get_start_url() ); ?>" class="button button-primary whop-gw-connect-btn">
				<?php esc_html_e( 'Connect your Whop', 'whop-gateway-wc' ); ?>
			</a>
		<?php elseif ( Whop_GW_Helper::license_allows_connect( $settings['license_key'] ) && ! Whop_GW_Connect::has_connect_service() ) : ?>
			<p class="whop-gw-status-disconnected">
				<?php esc_html_e( 'Enter your Connect service URL above (from your plugin seller), then save.', 'whop-gateway-wc' ); ?>
			</p>
			<a class="button" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) ); ?>">
				<?php esc_html_e( 'Use manual setup wizard instead', 'whop-gateway-wc' ); ?>
			</a>
		<?php else : ?>
			<p class="whop-gw-status-disconnected">
				<?php esc_html_e( 'Enter a valid Pro or Agency license key to enable Connect.', 'whop-gateway-wc' ); ?>
			</p>
			<a class="button" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) ); ?>">
				<?php esc_html_e( 'Use manual setup wizard instead', 'whop-gateway-wc' ); ?>
			</a>
		<?php endif; ?>
	<?php endif; ?>
</div>

<div class="whop-gw-card">
	<h2><?php esc_html_e( 'Advanced', 'whop-gateway-wc' ); ?></h2>
	<p class="whop-gw-muted"><?php esc_html_e( 'Manual API configuration and WooCommerce payment settings.', 'whop-gateway-wc' ); ?></p>
	<div class="whop-gw-actions">
		<a class="button" href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) ); ?>"><?php esc_html_e( 'Setup Wizard', 'whop-gateway-wc' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=whop' ) ); ?>"><?php esc_html_e( 'Payment settings', 'whop-gateway-wc' ); ?></a>
	</div>
</div>
