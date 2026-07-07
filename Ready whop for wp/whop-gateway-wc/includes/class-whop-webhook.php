<?php
/**
 * Whop webhook signature verification (Standard Webhooks spec).
 *
 * @package Whop_Gateway_WC
 */

defined( 'ABSPATH' ) || exit;

class Whop_Webhook {

	/**
	 * Verify an incoming Whop webhook request.
	 *
	 * @param string $payload Raw request body.
	 * @param array  $headers Request headers (lowercase keys).
	 * @param string $secret  Webhook secret from Whop dashboard.
	 * @return bool
	 */
	public static function verify( string $payload, array $headers, string $secret ): bool {
		if ( empty( $secret ) || '' === $payload ) {
			return false;
		}

		$webhook_id        = $headers['webhook-id'] ?? '';
		$webhook_timestamp = $headers['webhook-timestamp'] ?? '';
		$webhook_signature = $headers['webhook-signature'] ?? '';

		if ( $webhook_id && $webhook_timestamp && $webhook_signature ) {
			return self::verify_standard_webhooks( $payload, $webhook_id, $webhook_timestamp, $webhook_signature, $secret );
		}

		$legacy_signature = $headers['whop-signature'] ?? $headers['x-whop-signature'] ?? '';

		if ( $legacy_signature ) {
			return self::verify_legacy_hmac( $payload, $legacy_signature, $secret );
		}

		return false;
	}

	/**
	 * Normalize request headers to lowercase keys.
	 *
	 * @return array<string, string>
	 */
	public static function headers_from_server(): array {
		$headers = [];

		if ( function_exists( 'getallheaders' ) ) {
			foreach ( getallheaders() as $name => $value ) {
				if ( is_string( $name ) && is_string( $value ) ) {
					$headers[ strtolower( $name ) ] = $value;
				}
			}
		}

		foreach ( $_SERVER as $key => $value ) {
			if ( ! is_string( $value ) || 0 !== strpos( $key, 'HTTP_' ) ) {
				continue;
			}

			$name             = strtolower( str_replace( '_', '-', substr( $key, 5 ) ) );
			$headers[ $name ] = $value;
		}

		return $headers;
	}

	private static function verify_standard_webhooks(
		string $payload,
		string $webhook_id,
		string $webhook_timestamp,
		string $webhook_signature,
		string $secret
	): bool {
		$timestamp = (int) $webhook_timestamp;

		if ( $timestamp <= 0 ) {
			return false;
		}

		if ( abs( time() - $timestamp ) > 300 ) {
			return false;
		}

		$signed_content = $webhook_id . '.' . $webhook_timestamp . '.' . $payload;

		foreach ( self::secret_key_candidates( $secret ) as $key_bytes ) {
			$expected = base64_encode( hash_hmac( 'sha256', $signed_content, $key_bytes, true ) );

			foreach ( explode( ' ', trim( $webhook_signature ) ) as $part ) {
				if ( 0 !== strpos( $part, 'v1,' ) ) {
					continue;
				}

				$received = substr( $part, 3 );
				if ( hash_equals( $expected, $received ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private static function verify_legacy_hmac( string $payload, string $header, string $secret ): bool {
		$received = $header;
		if ( 0 === strpos( $header, 'sha256=' ) ) {
			$received = substr( $header, 7 );
		}

		foreach ( self::secret_key_candidates( $secret ) as $key_bytes ) {
			$expected_hex = hash_hmac( 'sha256', $payload, $key_bytes );
			if ( hash_equals( $expected_hex, $received ) ) {
				return true;
			}

			$expected_b64 = base64_encode( hash_hmac( 'sha256', $payload, $key_bytes, true ) );
			if ( hash_equals( $expected_b64, $received ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whop may provide ws_ secrets or base64-encoded Standard Webhooks secrets.
	 *
	 * @return string[]
	 */
	private static function secret_key_candidates( string $secret ): array {
		$trimmed   = trim( $secret );
		$candidates = [];

		if ( '' !== $trimmed ) {
			$candidates[] = $trimmed;
		}

		$decoded = base64_decode( $trimmed, true );
		if ( false !== $decoded && '' !== $decoded && $decoded !== $trimmed ) {
			$candidates[] = $decoded;
		}

		return array_values( array_unique( $candidates, SORT_REGULAR ) );
	}
}
