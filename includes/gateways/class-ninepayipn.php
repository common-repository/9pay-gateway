<?php
/**
 * NinePayIPN
 *
 * Process IPN result
 *
 * @link       https://9pay.vn
 * @since      1.0.0
 *
 * @package    9pay-payment-method
 * @subpackage 9pay-payment-method/includes/gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ninepay IPN class
 */
class NinePayIPN {

	/**
	 * Handle IPN function
	 *
	 * @param array $data IPN data received.
	 */
	public function handle_ipn( array $data ) {
		$config_file         = include 'core/config.php';
		$payments            = new WC_Payment_Gateways();
		$ninepay_gateway     = $payments->payment_gateways()['ninepay-gateway'];
		$secret_key_checksum = $ninepay_gateway->settings['checksum_secret_key'];

		$hash_checksum = strtoupper( hash( 'sha256', $data['result'] . $secret_key_checksum ) );

		if ( $hash_checksum !== $data['checksum'] ) {
			return;
		}

		// Payment info.
		$array_params = json_decode( ninepay_url_safe_b64_decode( $data['result'] ), true );

		$str       = $array_params['description'];
		$find_str  = 'orderID';
		$order_pos = strpos( $str, $find_str );
		$order_id  = substr( $str, $order_pos + strlen( $find_str ) );

		// Get Order.
		$order = new WC_Order( $order_id );

		// Check valid invoice_no.
		if ( $order->get_meta( '_invoice_no' ) !== $array_params['invoice_no'] ) {
			return;
		}

		// Update status order.
		if ( in_array( $array_params['status'], $config_file['status']['PAYMENT_SUCCESS'], true ) ) {
			$order->update_status( 'completed', __( 'Đã hoàn thành', 'woocommerce' ) );
			return;
		}

		if ( in_array( $array_params['status'], $config_file['status']['PAYMENT_FAILED'], true ) ) {
			$order->update_status( 'failed', __( 'Giao dịch thất bại', 'woocommerce' ) );
		}
	}
}
