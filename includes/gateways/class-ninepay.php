<?php
/**
 * NinePay
 *
 * Main core
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

class NinePay extends WC_Payment_Gateway {

	/**
	 * Enable log
	 *
	 * @var bool Whether or not logging is enabled
	 */
	public static $log_enabled = false;

	/**
	 * Log instance set
	 *
	 * @var WC_Logger Logger instance
	 */
	public static $log = false;

	/**
	 * NinePay constructor.
	 */
	public function __construct() {
	}

	/**
	 * Shơ notify
	 *
	 * @return void
	 */
	public function show_notify() {
		$screen = get_current_screen();
		if ( isset( $_GET['section'] ) ) {
			$section = sanitize_text_field( wp_unslash( $_GET['section'] ) );
			$valid   = $screen->id === 'woocommerce_page_wc-settings' && ! empty( $section ) && $section === $this->id;
			if ( ! $valid ) {
				return;
			}
		} else {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p><span class="dashicons dashicons-megaphone"></span> Cổng thanh toán <strong>9PAY</strong> cung cấp dịch vụ thanh toán điện tử nhanh chóng, tiện lợi, đa dạng. <a href="https://9pay.vn/" target="_blank">Tìm hiểu thêm</a></p>
		</div>
		<?php
	}


	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level   Optional. Default 'info'.
	 *     emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'qrscan' ) );
		}
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return true;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
	}

	/**
	 * Process_payment
	 *
	 * @param mixed $order_id Order code.
	 * @return array|void
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( isset( $_POST['ninepay_payment_method'] ) ) {
			$ninepay_payment_method = sanitize_text_field( wp_unslash( $_POST['ninepay_payment_method'] ) );
			if ( is_null( $ninepay_payment_method ) ) {
				return;
			}
		} else {
			return;
		}
		$payment_method = $ninepay_payment_method;
		$config_file    = include 'core/config.php';
		$config_lang    = include 'core/lang.php';

		// Check valid transaction.
		$currency       = $this->checkCurrency( $order );
		$payment_config = $this->getSettingPayment();

		if ( $payment_config === false || ! $currency ) {
			// Mark as failed.
			$order->update_status( 'on-hold', __( 'Đơn hàng tạm giữ', 'woocommerce' ) );

			return array(
				'result'   => 'error',
				'redirect' => $this->get_return_url(),
			);
		}

		// ReCalculator total order.
		$fee = ninepay_add_fee( $payment_method, $order->get_total(), $payment_config );
		$this->addFeeOrder( $order, $fee );

		if ( $this->checkMinAmount( $order->get_data(), $config_file, $config_lang, $payment_config )
			|| $this->checkMaxAmount( $order->get_data(), $config_file, $config_lang, $payment_config ) ) {
			// Mark as failed.
			$order->update_status( 'failed', __( 'Đơn hàng tạm giữ', 'woocommerce' ) );

			return array(
				'result'   => 'error',
				'redirect' => $this->get_return_url(),
			);
		}

		$invoice_no = time() + wp_rand( 0, 999999 );
		$order->update_meta_data( '_invoice_no', $invoice_no );

		$return_url = $this->get_return_url( $order );

		$ninepay_payment = new NinePayPayment();
		$result          = $ninepay_payment->payment( $payment_config, $order->get_data(), $invoice_no, $return_url, $payment_method );

		// Mark as pending.
		$order->update_status( 'pending', __( 'Đơn hàng chờ thanh toán', 'woocommerce' ) );

		// Return redirect to payment page.
		return array(
			'result'   => 'success',
			'redirect' => $result,
		);
	}


	/**
     * Add fee order
	 * @param mixed $order Order object.
	 * @param mixed $fee Fee object.
	 */
	private function addFeeOrder( $order, $fee ) {
		// Get the customer country code.

		// Set the array for tax calculations.
		$calculate_tax_for = array(
			'country'  => '',
			'state'    => '',
			'postcode' => '',
			'city'     => '',
		);

		// Get a new instance of the WC_Order_Item_Fee Object.
		$item_fee = new WC_Order_Item_Fee();

		$item_fee->set_name( 'Fee payment method' );
		$item_fee->set_amount( $fee );
		$item_fee->set_tax_class( '' );
		$item_fee->set_tax_status( 'none' );
		$item_fee->set_total( $fee );

		// Calculating Fee taxes.
		$item_fee->calculate_taxes( $calculate_tax_for );

		// Add Fee item to the order.
		$order->add_item( $item_fee );
		$order->calculate_totals();
		$order->save();
	}

	/**
	 * Get payment settings
	 *
	 * @return array|bool
	 */
	private function getSettingPayment() {
		$payment_config = $this->settings;

		if ( empty( $payment_config ) ) {
			return false;
		}

		if ( empty( $payment_config['merchant_key'] ) || empty( $payment_config['merchant_secret_key'] ) ) {
			return false;
		}

		return $payment_config;
	}

	/**
	 * Check Currency
	 *
	 * @param mixed $order Order data.
	 * @return bool
	 */
	private function checkCurrency( $order ) {
		$config_file = include 'core/config.php';

		return in_array( $order->get_data()['currency'], $config_file['CURRENCY'], true );
	}

	/**
	 * Check Min Amount
	 *
	 * @param array $order Order object.
	 * @param array $config_file Config file object.
	 * @param array $config_lang Config lang object.
	 * @param array $payment_config Payment config object.
	 * @return bool
	 */
	private function checkMinAmount( $order, $config_file, $config_lang, $payment_config ) {
		if ( $order['currency'] === NinePayConstance::CURRENCY_VND && $order['total'] < $config_file['min_amount'] ) {
			$lang = $payment_config['ninepay_lang'];
			wc_add_notice( $config_lang[ $lang ]['message_min_value'], 'error' );

			return true;
		}

		return false;
	}

	/**
	 * Check Max Amount
	 *
	 * @param mixed $order Order Object.
	 * @param mixed $config_file Config file object.
	 * @param mixed $config_lang Config language object.
	 * @param mixed $payment_config Payment config object.
	 * @return bool
	 */
	private function checkMaxAmount( $order, $config_file, $config_lang, $payment_config ) {
		if ( $order['currency'] === NinePayConstance::CURRENCY_VND && $order['total'] > $config_file['max_amount'] ) {
			$lang = $payment_config['ninepay_lang'];
			wc_add_notice( $config_lang[ $lang ]['message_max_value'], 'error' );

			return true;
		}

		return false;
	}
}
