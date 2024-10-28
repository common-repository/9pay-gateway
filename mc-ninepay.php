<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link
 * @since             1.0.0
 * @package           9pay-payment-method
 *
 * @wordpress-plugin
 * Plugin Name:       9Pay Gateway
 * Plugin URI:
 * Description:       Tích hợp cổng thanh toán 9PAY vào phương thức thanh toán của woocomerce
 * Version:           2.0
 * Author:            9Pay
 * Author URI:
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nine-pay
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'NINEPAY_MC_9PAY_VERSION', '2.0' );
define( 'NINEPAY_MC_9PAY_PLUGIN_URL', esc_url( plugins_url( '', __FILE__ ) ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mc-ninepay-activator.php
 */
if ( ! function_exists( 'activate_mc_9pay' ) ) {
	/**
	 * Active 9pay plugin
	 *
	 * @return void
	 */
	function activate_mc_9pay() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-mc-ninepay-activator.php';
		Mc_Ninepay_Activator::activate();
	}
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mc-ninepay-deactivator.php
 */
if ( ! function_exists( 'deactivate_mc_9pay' ) ) {
	/**
	 * Deactive 9pay plugin
	 *
	 * @return void
	 */
	function deactivate_mc_9pay() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-mc-ninepay-deactivator.php';
		Mc_Ninepay_Deactivator::deactivate();
	}
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if ( ! function_exists( 'run_mc_9pay' ) ) {
	/**
	 * Run 9pay plugin
	 *
	 * @return void
	 */
	function run_mc_9pay() {
		$plugin = new Mc_Ninepay();
		$plugin->run();
	}
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

	register_activation_hook( __FILE__, 'activate_mc_9pay' );
	register_deactivation_hook( __FILE__, 'deactivate_mc_9pay' );
	require plugin_dir_path( __FILE__ ) . 'includes/class-mc-ninepay.php';

	run_mc_9pay();
}

if ( ! function_exists( 'mc_9pay_installed_notice' ) ) {
	/**
	 * Installed 9pay notice
	 *
	 * @return void
	 */
	function mc_9pay_installed_notice() {
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			$class   = 'notice notice-error';
			$message = __( 'Plugin cổng thanh toán 9PAY cần Woocommerce kích hoạt trước khi sử dụng. Vui lòng kiểm tra Woocommerce', 'qr_auto' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}
}
add_action( 'admin_notices', 'mc_9pay_installed_notice' );


/*[CORE] Handle IPN*/
add_filter(
	'rest_url_prefix',
	function() {
		return 'api';
	}
);

add_action(
	'rest_api_init',
	function() {

		if ( isset( $_REQUEST['result'], $_REQUEST['checksum'] ) ) {

			$result = sanitize_text_field( wp_unslash( $_REQUEST['result'] ) );

			if ( ! is_string( $result ) ) {
				return;
			}

			$checksum = sanitize_text_field( wp_unslash( $_REQUEST['checksum'] ) );
			if ( ! is_string( $checksum ) ) {
				return;
			}

			register_rest_route(
				'nine-pay/v1',
				'/result-ipn',
				array(
					'methods'  => 'POST',
					'callback' => function() use ( $result, $checksum ) {
						$handle_ipn = new NinePayIPN();
						$handle_ipn->handle_ipn(
							array(
								'result'   => $result,
								'checksum' => $checksum,
							)
						);
					},
				)
			);

		}
	}
);

if ( ! function_exists( 'ninepay_url_safe_b64_decode' ) ) {
	/**
	 * Safe Url Base 64 decode
	 *
	 * @param string $input encoded String.
	 * @return false|string
	 */
	function ninepay_url_safe_b64_decode( $input ) {
		$remainder = \strlen( $input ) % 4;
		if ( $remainder ) {
			$padlen = 4 - $remainder;
			$input .= \str_repeat( '=', $padlen );
		}
		return \base64_decode( \strtr( $input, '-_', '+/' ) );
	}
}


/*[ADMIN] Handle show invoice_no*/
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function( $order ) {
		$invoice_no = get_post_meta( $order->get_id(), '_invoice_no', true );

		if ( $invoice_no ) {
			echo '<p><strong>Mã yêu cầu:</strong> <br/>';
			echo esc_html( $invoice_no );
			echo '</p>';
		}
	},
	10,
	1
);


/*[THEME] handle show payment method*/
add_filter( 'woocommerce_gateway_description', 'ninepay_gateway_custom_fields', 20, 2 );
if ( ! function_exists( 'ninepay_gateway_custom_fields' ) ) {
	/**
	 * 9Pay custom fields
	 *
	 * @param mixed $description Description.
	 * @param mixed $payment_id Payment ID.
	 * @return mixed|string
	 */
	function ninepay_gateway_custom_fields( $description, $payment_id ) {
		if ( 'ninepay-gateway' === $payment_id ) {
			$payment     = new WC_Payment_Gateways();
			$settings    = $payment->get_available_payment_gateways()['ninepay-gateway']->settings;
			$config_lang = include 'includes/gateways/core/lang.php';
			$lang        = $settings['ninepay_lang'];

			$payment_method = ninepay_get_payment_method( $settings );
			ob_start();

			echo '<div  class="ninepay-gateway-fields" style="padding:10px 0; width: 100%">';

			woocommerce_form_field(
				'ninepay_payment_method',
				array(
					'type'     => 'select',
					'label'    => $config_lang[ $lang ]['description_payment_method'],
					'class'    => array( 'form-row-wide' ),
					'required' => true,
					'options'  => $payment_method,
				),
				''
			);

			echo '<div>';

			$description .= ob_get_clean();
		}

		return $description;
	}
}

/**
 * @param array $settings
 * @return array
 */
if ( ! function_exists( 'ninepay_get_payment_method' ) ) {
	/**
	 * 9pay get Method
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	function ninepay_get_payment_method( array $settings ) {
		$config_lang = include 'includes/gateways/core/lang.php';
		$lang        = $settings['ninepay_lang'];
		$result      = array();

		/*wallet*/
		if ( $settings['ninepay_payment_method_wallet'] === 'yes' ) {
			$result[ NinePayConstance::METHOD_WALLET ] = $config_lang[ $lang ][ NinePayConstance::METHOD_WALLET ];
		}

		/*atm*/
		if ( $settings['ninepay_payment_method_atm'] === 'yes' ) {
			$result[ NinePayConstance::METHOD_ATM ] = $config_lang[ $lang ][ NinePayConstance::METHOD_ATM ];
		}

		/*credit*/
		if ( $settings['ninepay_payment_method_credit'] === 'yes' ) {
			$result[ NinePayConstance::METHOD_CREDIT ] = $config_lang[ $lang ][ NinePayConstance::METHOD_CREDIT ];
		}

		/*collection*/
		if ( $settings['ninepay_payment_method_collection'] === 'yes' ) {
			$result[ NinePayConstance::METHOD_COLLECTION ] = $config_lang[ $lang ][ NinePayConstance::METHOD_COLLECTION ];
		}

		return $result;
	}
}

if ( ! function_exists( 'ninepay_get_foreign_exc_rate' ) ) {
	/**
	 * 9Pay get foreign exec rate
	 *
	 * @param mixed $currency Currency.
	 * @return mixed
	 */
	function ninepay_get_foreign_exc_rate( $currency ) {
		$config_file    = include 'includes/gateways/core/config.php';
		$ninepay        = new NinePayGateway();
		$payment_config = $ninepay->settings;
		$is_testing     = $payment_config['is_testing'];

		if ( $is_testing === 'yes' ) {
			$host         = $config_file['environment']['exc_rate_host_sand'];
			$forward_link = $config_file['environment']['x_forward_link_test'];
		} else {
			$host         = $config_file['environment']['exc_rate_host_production'];
			$forward_link = $config_file['environment']['x_forward_link_production'];
		}

		$merchant_key        = $payment_config['merchant_key'];
		$merchant_secret_key = $payment_config['merchant_secret_key'];
		$time                = time();

		$data = array(
			'merchantKey' => $merchant_key,
			'time'        => $time,
		);

		$message   = MessageBuilder::instance()
			->with( $time, $forward_link, 'POST' )
			->with_params( $data )
			->build();
		$hmacs     = new HMACSignature();
		$signature = $hmacs->sign( $message, $merchant_secret_key );
		$http_data = array(
			'baseEncode' => base64_encode( wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) ),
			'signature'  => $signature,
		);

		$exec_rate_url = $host . '?' . http_build_query( $http_data );

		$result = wp_remote_retrieve_body( wp_remote_get( $exec_rate_url ) );

		$all_currencies = json_decode( $result, true );
		if($currency === "VND") return 1;
		return $all_currencies[ $currency ]['rate'];
	}
}

if ( ! function_exists( 'ninepay_add_checkout_script' ) ) {
	/**
	 * 9pay add checkout script
	 *
	 * @return void
	 */
	function ninepay_add_checkout_script() {
		global $woocommerce;
		$language              = get_locale();
		$config_file           = include 'includes/gateways/core/config.php';
		$total_cart            = $woocommerce->cart->total;
		$currency              = get_option( 'woocommerce_currency' );
		$exc_rate              = ninepay_get_foreign_exc_rate( $currency );
		$min_amount_wallet_vnd = $config_file['min_amount_wallet_vnd'];
		$max_amount_wallet_vnd = $config_file['max_amount_wallet_vnd'];

		$min_amount_atm_card_vnd = $config_file['min_amount_atm_card_vnd'];
		$max_amount_atm_card_vnd = $config_file['max_amount_atm_card_vnd'];

		$min_amount_credit_card_vnd = $config_file['min_amount_credit_card_vnd'];
		$max_amount_credit_card_vnd = $config_file['max_amount_credit_card_vnd'];

		$min_amount_collection_vnd = $config_file['min_amount_collection_vnd'];
		$max_amount_collection_vnd = $config_file['max_amount_collection_vnd'];

		$min_amount_bnpl_vnd = $config_file['min_amount_bnpl_vnd'];
		$max_amount_bnpl_vnd = $config_file['max_amount_bnpl_vnd'];
		?>
		<script type="text/javascript">
			let totalCart = parseFloat("<?php echo esc_html( $total_cart ); ?>");
			let currency = "<?php echo esc_html( $currency ); ?>";
			let language = "<?php echo esc_html( $language ); ?>";
			let excRate = Number("<?php echo esc_html( $exc_rate ); ?>");

			let minAmountWalletVnd = parseFloat("<?php echo esc_html( $min_amount_wallet_vnd ); ?>");
			let maxAmountWalletVnd = parseFloat("<?php echo esc_html( $max_amount_wallet_vnd ); ?>");

			let minAmountAtmCardVnd = parseFloat("<?php echo esc_html( $min_amount_atm_card_vnd ); ?>");
			let maxAmountAtmCardVnd = parseFloat("<?php echo esc_html( $max_amount_atm_card_vnd ); ?>");

			let minAmountCreditCardVnd = parseFloat("<?php echo esc_html( $min_amount_credit_card_vnd ); ?>");
			let maxAmountCreditCardVnd = parseFloat("<?php echo esc_html( $max_amount_credit_card_vnd ); ?>");

			let minAmountCollectionVnd = parseFloat("<?php echo esc_html( $min_amount_collection_vnd ); ?>");
			let maxAmountCollectionVnd = parseFloat("<?php echo esc_html( $max_amount_collection_vnd ); ?>");

			let minAmountBnplVnd = parseFloat("<?php echo esc_html( $min_amount_bnpl_vnd ); ?>");
			let maxAmountBnplVnd = parseFloat("<?php echo esc_html( $max_amount_bnpl_vnd ); ?>");


			jQuery(document).on("updated_checkout", function(){
				jQuery('#place_order').click(function (e) {
					jQuery(jQuery('.woocommerce-NoticeGroup')[0]).empty();
					let paymentMethod = jQuery('#ninepay_payment_method').val();

					if (paymentMethod === "WALLET" && currency === "VND") {
						if (totalCart < minAmountWalletVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối thiểu chấp nhận thanh toán là ${minAmountWalletVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The minimum amount accepted for payment is ${minAmountWalletVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
						if (totalCart > maxAmountWalletVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối đa chấp nhận thanh toán là ${maxAmountWalletVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The maximum amount accepted for payment is ${maxAmountWalletVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
					}

					if (paymentMethod === "ATM_CARD" && currency === "VND") {
						if (totalCart < minAmountAtmCardVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối thiểu chấp nhận thanh toán là ${minAmountAtmCardVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The minimum amount accepted for payment is ${minAmountAtmCardVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
						if (totalCart > maxAmountAtmCardVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối đa chấp nhận thanh toán là ${maxAmountAtmCardVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The maximum amount accepted for payment is ${maxAmountAtmCardVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
					}

					if (paymentMethod === "CREDIT_CARD" && currency === "VND") {
						if (totalCart < minAmountCreditCardVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối thiểu chấp nhận thanh toán là ${minAmountCreditCardVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The minimum amount accepted for payment is ${minAmountCreditCardVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
						if (totalCart > maxAmountCreditCardVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối đa chấp nhận thanh toán là ${maxAmountCreditCardVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The maximum amount accepted for payment is ${maxAmountCreditCardVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
					}

					if (paymentMethod === "COLLECTION" && currency === "VND") {
						if (totalCart < minAmountCollectionVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối thiểu chuyển khoản là ${minAmountCollectionVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The minimum amount to transfer is ${minAmountCollectionVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(error);
							e.preventDefault();
						}
						if (totalCart > maxAmountCollectionVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối thiểu chuyển khoản là ${maxAmountCollectionVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The maximum amount to transfer is ${maxAmountCollectionVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
					}

					if (paymentMethod === "BUY_NOW_PAY_LATER" && currency === "VND") {
						if (totalCart < minAmountBnplVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối thiểu mua trước trả sau là ${minAmountBnplVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The minimum amount to buy now pay later is ${minAmountBnplVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(error);
							e.preventDefault();
						}
						if (totalCart > maxAmountBnplVnd) {
							let errorMessage = '';
							if (language === 'vi') {
								errorMessage = `Số tiền tối thiểu mua trước trả sau là ${maxAmountBnplVnd.toLocaleString()} đ. Vui lòng chọn phương thức thanh toán khác.`;
							} else {
								errorMessage = `The maximum amount to buy now pay later is ${maxAmountBnplVnd.toLocaleString()} VND. Please choose another payment method.`;
							}
							let errorElement = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
							ninePayShowError(errorElement);
							e.preventDefault();
						}
					}

					if (currency !== "VND") {
						let minAmountWalletForeign = Number((minAmountWalletVnd / excRate).toFixed(2));
						let maxAmountWalletForeign = Number((maxAmountWalletVnd / excRate).toFixed(2));

						let minAmountAtmCardForeign = Number((minAmountAtmCardVnd / excRate).toFixed(2));
						let maxAmountAtmCardForeign = Number((maxAmountAtmCardVnd / excRate).toFixed(2));

						let minAmountCreditCardForeign = Number((minAmountCreditCardVnd / excRate).toFixed(2));
						let maxAmountCreditCardForeign = Number((maxAmountCreditCardVnd / excRate).toFixed(2));

						let minAmountCollectionForeign = Number((minAmountCollectionVnd / excRate).toFixed(2));

						let minAmountBnplForeign = Number((minAmountBnplVnd / excRate).toFixed(2));
						let maxAmountBnplForeign = Number((maxAmountBnplVnd / excRate).toFixed(2));

						if (paymentMethod === "WALLET") {
							if (totalCart < minAmountWalletForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối thiểu chấp nhận thanh toán là ${minAmountWalletForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The minimum amount accepted for payment is ${minAmountWalletForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
							if (totalCart > maxAmountWalletForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối đa chấp nhận thanh toán là ${maxAmountWalletForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The maximum amount accepted for payment is ${maxAmountWalletForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
						}

						if (paymentMethod === "ATM_CARD") {
							if (totalCart < minAmountAtmCardForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối thiểu chấp nhận thanh toán là ${minAmountAtmCardForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The minimum amount accepted for payment is ${minAmountAtmCardForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
							if (totalCart > maxAmountAtmCardForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối đa chấp nhận thanh toán là ${maxAmountAtmCardForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The maximum amount accepted for payment is ${maxAmountAtmCardForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
						}

						if (paymentMethod === "CREDIT_CARD") {
							if (totalCart < minAmountCreditCardForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối thiểu chấp nhận thanh toán là ${minAmountCreditCardForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The minimum amount accepted for payment is ${minAmountCreditCardForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
							if (totalCart > maxAmountCreditCardForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối đa chấp nhận thanh toán là ${maxAmountCreditCardForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The maximum amount accepted for payment is ${maxAmountCreditCardForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
						}

						if (paymentMethod === "COLLECTION") {
							if (totalCart < minAmountCollectionForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối thiểu chuyển khoản là ${minAmountCollectionForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The minimum amount to transfer is ${minAmountCollectionForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
						}

						if (paymentMethod === "BUY_NOW_PAY_LATER") {
							if (totalCart < minAmountBnplForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối thiểu chấp nhận thanh toán là ${minAmountBnplForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The minimum amount accepted for payment is ${minAmountBnplForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
							if (totalCart > maxAmountBnplForeign) {
								let errorMessage = '';
								if (language === 'vi') {
									errorMessage = `Số tiền tối đa chấp nhận thanh toán là ${maxAmountBnplForeign.toLocaleString()} ${currency}. Vui lòng chọn phương thức thanh toán khác.`;
								} else {
									errorMessage = `The maximum amount accepted for payment is ${maxAmountBnplForeign.toLocaleString()} ${currency}. Please choose another payment method.`;
								}
								let error = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
									<ul class="woocommerce-error" role="alert">
										<li>${errorMessage}</li>
									</ul>
								</div>`;
								ninePayShowError(error);
								e.preventDefault();
							}
						}

					}
				});
			});

			function ninePayShowError(errorElement) {
				jQuery('form.woocommerce-checkout').prepend(errorElement);
				jQuery(jQuery('form.woocommerce-checkout .woocommerce-NoticeGroup')[0]).focus();
				jQuery('html, body').animate({ scrollTop: jQuery(jQuery('form.woocommerce-checkout .woocommerce-NoticeGroup')[0]).offset().top - 200 }, 'slow');
			}
		</script type="text/javascript">
		<?php
	}
}
add_action( 'woocommerce_after_checkout_form', 'ninepay_add_checkout_script' );


/*[THEME] Handle add fee*/
add_action( 'woocommerce_cart_calculate_fees', 'ninepay_custom_handling_fee', 10, 1 );
if ( ! function_exists( 'ninepay_custom_handling_fee' ) ) {
	/**
	 * 9pay handling fee
	 *
	 * @param mixed $cart Cart data.
	 * @return void
	 */
	function ninepay_custom_handling_fee( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( 'ninepay-gateway' === WC()->session->get( 'chosen_payment_method' ) ) {
			$payment     = new WC_Payment_Gateways();
			
			$ninepay_payment_method = sanitize_text_field( wp_unslash( $_POST['ninepay_payment_method'] ) );
   
			$config      = $payment->get_available_payment_gateways()['ninepay-gateway']->settings;
			$config_lang = include 'includes/gateways/core/lang.php';
			$lang        = $config['ninepay_lang'];
			if(empty($_POST['post_data'])) {
				return;
			}
			$post_data = sanitize_text_field( wp_unslash( $_POST['post_data'] ) );
			if ( is_null( $post_data ) ) {
				return;
			}

			parse_str( $post_data, $result );

			$total_amount = $cart->cart_contents_total;

			$fee = !empty($ninepay_payment_method) ? ninepay_add_fee( $ninepay_payment_method, $total_amount, $config ) : 0;

			if ( $fee !== 0 ) {
				if ( $lang === NinePayConstance::LANG_VI ) {
					$txt_arg = 'Phí thanh toán qua ' . $config_lang[ $lang ][ $ninepay_payment_method ];
					$cart->add_fee( $txt_arg, $fee, true );
				} else {
					$txt_arg = 'Payment fee via ' . $config_lang[ $lang ][ $ninepay_payment_method ];
					$cart->add_fee( $txt_arg, $fee, true );
				}
			}
		}
	}
}

if ( ! function_exists( 'ninepay_add_fee' ) ) {
	/**
	 * 9pay add fee
	 *
	 * @param mixed $payment_method Payment method.
	 * @param mixed $amount Amount value.
	 * @param array $config Config Object.
	 * @return float|int
	 */
	function ninepay_add_fee( $payment_method, $amount, $config ) {
		switch ( $payment_method ) {
			case NinePayConstance::METHOD_WALLET:
				return ninepay_handle_charge( $amount, $config['ninepay_payment_method_wallet_fee_percent'], $config['ninepay_payment_method_wallet_fee_fixed'] );

			case NinePayConstance::METHOD_ATM:
				return ninepay_handle_charge( $amount, $config['ninepay_payment_method_atm_fee_percent'], $config['ninepay_payment_method_atm_fee_fixed'] );

			case NinePayConstance::METHOD_CREDIT:
				return ninepay_handle_charge( $amount, $config['ninepay_payment_method_credit_fee_percent'], $config['ninepay_payment_method_credit_fee_fixed'] );

			case NinePayConstance::METHOD_COLLECTION:
				return ninepay_handle_charge( $amount, $config['ninepay_payment_method_collection_fee_percent'], $config['ninepay_payment_method_collection_fee_fixed'] );

			case NinePayConstance::METHOD_BNPL:
				return ninepay_handle_charge( $amount, $config['ninepay_payment_method_bnpl_fee_percent'], $config['ninepay_payment_method_bnpl_fee_fixed'] );

			default:
				return 0;
		}
	}
}

if ( ! function_exists( 'ninepay_handle_charge' ) ) {
	/**
	 * 9pay handle charge
	 *
	 * @param mixed $amount Amount.
	 * @param mixed $fee_percent Fee percent.
	 * @param mixed $fee_fixed Fee fixed.
	 * @return float
	 */
	function ninepay_handle_charge( $amount, $fee_percent, $fee_fixed ) {
		$fee_percent = empty( $fee_percent ) || ! is_numeric( $fee_percent ) ? 0 : $fee_percent;
		$fee_fixed   = empty( $fee_fixed ) || ! is_numeric( $fee_fixed ) ? 0 : $fee_fixed;

		$result = $fee_fixed + ( $fee_percent * $amount / 100 );

		return round( $result, 2 );
	}
}


/*[THEME] Reload cart when choice payment gateway or payment method*/
add_action(
	'wp_footer',
	function() {
		if ( is_checkout() && ! is_wc_endpoint_url() ) :
			?>
		<script type="text/javascript">
			jQuery( function($){
				let checkoutForm = $('form.checkout');

				/*Reset when choose payment gateway*/
				checkoutForm.on('change', 'input[name="payment_method"]', function(){
					$(document.body).trigger('update_checkout');
				});

				/*Reset when choose payment method*/
				checkoutForm.on('change', 'select[name="ninepay_payment_method"]', function(){
					var a = $(this).val();
					$(document.body).trigger('update_checkout');
					$('select[name="ninepay_payment_method"] option[value='+a+']').prop('selected', true);
					// Once checkout has been updated.
					$('body').on('updated_checkout', function(){
							// Restoring the chosen option value.
							$('select[name="ninepay_payment_method"] option[value='+a+']').prop('selected', true);
					});		
				});
			});
		</script>
			<?php
	endif;
	}
);
