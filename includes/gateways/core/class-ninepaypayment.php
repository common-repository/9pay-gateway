<?php
/**
 * Payment Core
 *
 * Get Core Payment from 9pay
 *
 * @link       https://9pay.vn
 * @since      1.0.0
 *
 * @package    9pay-payment-method
 * @subpackage 9pay-payment-method/includes/gateways/core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ninepay Payment class
 */
class NinePayPayment {

	/**
	 * Payment function
	 *
	 * @param array $config Config Object.
	 * @param array $order Order object.
	 * @param mixed $invoice_no Invoice no from MC.
	 * @param mixed $return_url Return url.
	 * @param mixed $payment_method Payment method.
	 * @return string
	 */
	public function payment( array $config, array $order, $invoice_no, $return_url, $payment_method ) {
		$config_file         = include 'config.php';
		$merchant_key        = $config['merchant_key'];
		$merchant_secret_key = $config['merchant_secret_key'];
		$end_point           = $config['is_testing'] === 'yes' ? $config_file['environment']['stg_url'] : $config_file['environment']['production_url'];
		$x_forward_url       = $config['is_testing'] === 'yes' ? $config_file['environment']['x_forward_link_test'] : $config_file['environment']['x_forward_link_production'];
		$lang                = !empty($config['ninepay_lang']) ? $config['ninepay_lang']: NinePayConstance::LANG_VI;
		$amount              = $order['total'];
		$description         = 'Thanh toan cho don hang: orderID' . $order['id'];

		$time = time();
		$data = array(
			'merchantKey' => $merchant_key,
			'time'        => $time,
			'invoice_no'  => $invoice_no,
			'amount'      => $amount,
			'description' => $description,
			'back_url'    => $return_url,
			'return_url'  => $return_url,
			'lang'        => NinePayConstance::LANG_VI,
			'currency'    => $order['currency'],
		);

		if ( ! empty( $payment_method ) ) {
			$data['method'] = $payment_method;
		}

		if ( $lang === NinePayConstance::LANG_EN ) {
			$data['lang']        = NinePayConstance::LANG_EN;
			$data['description'] = 'Payment for order number: orderID' . $order['id'];
		}

		$message   = MessageBuilder::instance()
			->with( $time, $x_forward_url, 'POST' )
			->with_params( $data )
			->build();
		$hmacs     = new HMACSignature();
		$signature = $hmacs->sign( $message, $merchant_secret_key );
		$http_data = array(
			'baseEncode' => base64_encode( wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) ),
			'signature'  => $signature,
		);

		return $end_point . '?' . http_build_query( $http_data );
	}
}
