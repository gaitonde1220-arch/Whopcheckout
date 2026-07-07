<?php
/**
 * Customer return page after Whop checkout.
 *
 * @var string $shop_name
 * @var string $order_number
 * @var string $thank_you_url
 * @var string $shop_url
 * @var string $ajax_url
 * @var string $nonce
 * @var string $token
 * @var int    $order_id
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex,nofollow">
	<title><?php esc_html_e( 'Confirming payment', 'whop-gateway-wc' ); ?></title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; min-height: 100vh; display: grid; place-items: center; }
		.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px; max-width: 440px; width: calc(100% - 32px); text-align: center; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
		.spinner { width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #ff6423; border-radius: 50%; animation: spin .8s linear infinite; margin: 0 auto 20px; }
		.spinner.hidden { display: none; }
		@keyframes spin { to { transform: rotate(360deg); } }
		h1 { font-size: 20px; margin: 0 0 8px; }
		p { color: #64748b; font-size: 14px; line-height: 1.5; margin: 0; }
		a { color: #ff6423; text-decoration: none; }
		a:hover { text-decoration: underline; }
		.actions { margin-top: 18px; display: flex; flex-direction: column; gap: 10px; }
	</style>
</head>
<body>
	<div class="card">
		<div class="spinner" id="whop-spinner"></div>
		<h1 id="whop-title"><?php esc_html_e( 'Confirming your payment', 'whop-gateway-wc' ); ?></h1>
		<p id="whop-message">
			<?php
			printf(
				/* translators: 1: shop name, 2: order number */
				esc_html__( '%1$s is verifying order #%2$s. This usually takes a few seconds.', 'whop-gateway-wc' ),
				$shop_name,
				$order_number
			);
			?>
		</p>
		<div class="actions" id="whop-actions" style="display:none;">
			<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Return to shop', 'whop-gateway-wc' ); ?></a>
		</div>
	</div>
	<script>
	(function () {
		var thankyou = <?php echo wp_json_encode( $thank_you_url ); ?>;
		var attempts = 0;
		var maxAttempts = 120;
		var spinner = document.getElementById('whop-spinner');
		var title = document.getElementById('whop-title');
		var message = document.getElementById('whop-message');
		var actions = document.getElementById('whop-actions');

		function showWaiting() {
			if (spinner) spinner.classList.add('hidden');
			if (title) title.textContent = <?php echo wp_json_encode( __( 'Payment is still processing', 'whop-gateway-wc' ) ); ?>;
			if (message) message.textContent = <?php echo wp_json_encode( __( 'Your payment was submitted. If you were charged, the store will confirm shortly. You can safely close this page.', 'whop-gateway-wc' ) ); ?>;
			if (actions) actions.style.display = 'flex';
		}

		function poll() {
			attempts++;
			fetch(<?php echo wp_json_encode( $ajax_url ); ?>, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'whop_check_order_status',
					order_id: <?php echo (int) $order_id; ?>,
					token: <?php echo wp_json_encode( $token ); ?>,
					nonce: <?php echo wp_json_encode( $nonce ); ?>
				}).toString()
			})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data && data.success && data.data && data.data.paid) {
					window.location.href = thankyou;
					return;
				}
				if (attempts < maxAttempts) {
					setTimeout(poll, 2500);
				} else {
					showWaiting();
				}
			})
			.catch(function () {
				if (attempts < maxAttempts) {
					setTimeout(poll, 2500);
				} else {
					showWaiting();
				}
			});
		}

		poll();
	})();
	</script>
</body>
</html>
