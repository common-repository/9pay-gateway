<?php
/**
 * HMACSignature
 *
 * HMAC Signature
 *
 * @link       https://9pay.vn
 * @since      1.0.0
 *
 * @package    9pay-payment-method
 * @subpackage 9pay-payment-method/includes/gateways/core/lib
 */
class HMACSignature {

	/**
	 * Sign function
	 *
	 * @param mixed $message Message to sign.
	 * @param mixed $key Key to sign.
	 * @return string
	 */
	public function sign( $message, $key ) {
		$signature = hash_hmac( 'sha256', $message, $key, true );
		return base64_encode( $signature );
	}

	/**
	 * Verify sign
	 *
	 * @param mixed $signature Signature data.
	 * @param mixed $message Message signed.
	 * @param mixed $key key used.
	 * @return bool
	 */
	public function verify( $signature, $message, $key ) {
		$valid_signature = $this->sign( $message, $key );
		return ! strcmp( $valid_signature, $signature );
	}
}
