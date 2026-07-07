<?php
/**
 * Admin area bootstrap.
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class Whop_GW_Admin {

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_redirect_to_setup' ] );

		Whop_GW_Connect::init();
		Whop_GW_Setup_Wizard::init();
	}

	public static function register_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Whop Checkout', 'whop-gateway-wc' ),
			__( 'Whop Checkout', 'whop-gateway-wc' ),
			'manage_woocommerce',
			'whop-gateway-wc',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'whop-gateway-wc' ) ) {
			return;
		}

		wp_enqueue_style(
			'whop-gateway-wc-admin',
			WHOP_GW_PLUGIN_URL . 'assets/css/admin.css',
			[],
			WHOP_GW_VERSION
		);

		wp_enqueue_script(
			'whop-gateway-wc-admin',
			WHOP_GW_PLUGIN_URL . 'assets/js/admin.js',
			[],
			WHOP_GW_VERSION,
			true
		);

		wp_localize_script(
			'whop-gateway-wc-admin',
			'whopGwAdmin',
			[
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'whop_gw_admin' ),
				'testConnectionNonce' => wp_create_nonce( 'whop_gateway_test_connection' ),
				'disconnectNonce'   => wp_create_nonce( 'whop_gw_disconnect' ),
			]
		);
	}

	public static function maybe_redirect_to_setup(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( Whop_GW_Helper::is_setup_complete() || Whop_GW_Helper::is_connected() ) {
			return;
		}

		if ( ! get_transient( 'whop_gw_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'whop_gw_activation_redirect' );

		if ( isset( $_GET['page'] ) && 'whop-gateway-wc' === $_GET['page'] ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			return;
		}

		wp_safe_redirect( Whop_GW_Helper::admin_page_url( 'connect' ) );
		exit;
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$tab = sanitize_key( $_GET['tab'] ?? 'connect' );
		if ( ! in_array( $tab, [ 'connect', 'wizard', 'health' ], true ) ) {
			$tab = 'connect';
		}

		settings_errors( 'whop_gw_connect' );
		?>
		<div class="wrap whop-gw-wrap">
			<h1><?php esc_html_e( 'Whop Checkout', 'whop-gateway-wc' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'connect' ) ); ?>" class="nav-tab <?php echo 'connect' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Connect', 'whop-gateway-wc' ); ?>
				</a>
				<a href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'wizard' ) ); ?>" class="nav-tab <?php echo 'wizard' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Setup Wizard', 'whop-gateway-wc' ); ?>
				</a>
				<a href="<?php echo esc_url( Whop_GW_Helper::admin_page_url( 'health' ) ); ?>" class="nav-tab <?php echo 'health' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Health', 'whop-gateway-wc' ); ?>
				</a>
			</nav>

			<?php
			switch ( $tab ) {
				case 'wizard':
					Whop_GW_Setup_Wizard::render();
					break;
				case 'health':
					echo '<div class="whop-gw-card"><h2>' . esc_html__( 'Connection Health', 'whop-gateway-wc' ) . '</h2>';
					Whop_GW_Health::render_dashboard();
					echo '</div>';
					break;
				default:
					Whop_GW_Connect::render_connect_tab();
			}
			?>
		</div>
		<?php
	}
}
