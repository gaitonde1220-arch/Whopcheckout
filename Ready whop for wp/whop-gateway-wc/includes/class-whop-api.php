<?php
/**
 * Whop REST API client.
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class Whop_API {

	private string $bearer_token;
	private string $base_url;

	public function __construct( string $bearer_token, bool $sandbox = false ) {
		$this->bearer_token = $bearer_token;
		$this->base_url       = $sandbox
			? 'https://sandbox-api.whop.com/api/v1'
			: 'https://api.whop.com/api/v1';
	}

	public function create_plan( array $body ): array {
		return $this->post( '/plans', $body );
	}

	public function create_checkout_configuration( array $body ): array {
		return $this->post( '/checkout_configurations', $body );
	}

	public function get_checkout_configuration( string $id ): array {
		return $this->get( '/checkout_configurations/' . rawurlencode( $id ) );
	}

	public function get_payment( string $id ): array {
		return $this->get( '/payments/' . rawurlencode( $id ) );
	}

	/**
	 * Create a webhook endpoint on the merchant's Whop company.
	 *
	 * @param array $body Webhook payload.
	 * @return array
	 */
	public function create_webhook( array $body ): array {
		return $this->post( '/webhooks', $body );
	}

	/**
	 * List webhooks for troubleshooting.
	 *
	 * @return array
	 */
	public function list_webhooks(): array {
		return $this->get( '/webhooks' );
	}

	/**
	 * Register webhook for this WooCommerce store.
	 *
	 * @param string $company_id Company ID.
	 * @return array{ok:bool,message:string,webhook?:array}
	 */
	public function register_store_webhook( string $company_id ): array {
		$body = [
			'url'         => Whop_GW_Helper::webhook_url(),
			'api_version' => 'v1',
			'enabled'     => true,
			'events'      => [
				'payment.succeeded',
				'payment.failed',
				'refund.created',
			],
		];

		if ( $company_id ) {
			$body['resource_id'] = $company_id;
		}

		$result = $this->create_webhook( $body );

		if ( isset( $result['error'] ) ) {
			return [
				'ok'      => false,
				'message' => $result['error'],
			];
		}

		$secret = (string) ( $result['secret'] ?? $result['signing_secret'] ?? '' );

		return [
			'ok'      => true,
			'message' => __( 'Webhook registered successfully.', 'whop-gateway-wc' ),
			'webhook' => $result,
			'secret'  => $secret,
		];
	}

	/**
	 * @return array{ok:bool,message:string}
	 */
	public function test_connection(): array {
		if ( empty( $this->bearer_token ) ) {
			return [
				'ok'      => false,
				'message' => __( 'No API credentials configured.', 'whop-gateway-wc' ),
			];
		}

		$response = wp_remote_get(
			$this->base_url . '/plans?limit=1',
			[
				'timeout' => 15,
				'headers' => $this->default_headers(),
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'      => false,
				'message' => $response->get_error_message(),
			];
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 === $status ) {
			return [
				'ok'      => true,
				'message' => __( 'API connection successful.', 'whop-gateway-wc' ),
			];
		}

		if ( 401 === $status || 403 === $status ) {
			return [
				'ok'      => false,
				'message' => __( 'API reachable but credentials were rejected.', 'whop-gateway-wc' ),
			];
		}

		return [
			'ok'      => false,
			'message' => sprintf(
				/* translators: %d: HTTP status code */
				__( 'Unexpected API response (HTTP %d).', 'whop-gateway-wc' ),
				$status
			),
		];
	}

	private function post( string $path, array $body ): array {
		$response = wp_remote_post(
			$this->base_url . $path,
			[
				'timeout'     => 30,
				'headers'     => $this->default_headers(),
				'body'        => wp_json_encode( $body ),
				'data_format' => 'body',
			]
		);

		return $this->parse_response( $response );
	}

	private function get( string $path ): array {
		$response = wp_remote_get(
			$this->base_url . $path,
			[
				'timeout' => 30,
				'headers' => $this->default_headers(),
			]
		);

		return $this->parse_response( $response );
	}

	private function default_headers(): array {
		return [
			'Authorization' => 'Bearer ' . $this->bearer_token,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
		];
	}

	private function parse_response( $response ): array {
		if ( is_wp_error( $response ) ) {
			return [
				'error'  => $response->get_error_message(),
				'status' => 0,
			];
		}

		$status       = (int) wp_remote_retrieve_response_code( $response );
		$raw_body     = wp_remote_retrieve_body( $response );
		$decoded_body = json_decode( $raw_body, true ) ?? [];

		if ( $status < 200 || $status >= 300 ) {
			$message = $decoded_body['error']['message']
				?? $decoded_body['message']
				?? sprintf( 'Whop API returned HTTP %d', $status );

			return [
				'error'  => $message,
				'status' => $status,
				'body'   => $decoded_body,
			];
		}

		return $decoded_body;
	}
}
