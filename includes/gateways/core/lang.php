<?php
/**
 * Lang Core
 *
 * Get Core Lange from 9pay
 *
 * @link       https://9pay.vn
 * @since      1.0.0
 *
 * @package    9pay-payment-method
 * @subpackage 9pay-payment-method/includes/gateways/core
 */

return array(
	'vi' => array(
		NinePayConstance::METHOD_WALLET     => 'Ví điện tử 9Pay',
		NinePayConstance::METHOD_ATM        => 'Thẻ nội địa',
		NinePayConstance::METHOD_CREDIT     => 'Thẻ quốc tế',
		NinePayConstance::METHOD_COLLECTION => 'Chuyển khoản ngân hàng',
		NinePayConstance::METHOD_BNPL       => 'Mua trước trả sau',

		'description_payment_method'        => 'Hãy chọn phương thức thanh toán',
		'message_min_value'                 => 'Giá trị giao dịch không được nhỏ hơn 2000 VNĐ',
		'message_max_value'                 => 'Giá trị giao dịch không được lớn hơn 200,000,000 VNĐ',
	),

	'en' => array(
		NinePayConstance::METHOD_WALLET     => '9Pay ewallet',
		NinePayConstance::METHOD_ATM        => 'Local debit card',
		NinePayConstance::METHOD_CREDIT     => 'International card',
		NinePayConstance::METHOD_COLLECTION => 'Bank transfer',
		NinePayConstance::METHOD_BNPL       => 'Buy now Pay later',

		'description_payment_method'        => 'Please choose payment method',
		'message_min_value'                 => 'Transaction value must not be less than 1000 VND',
		'message_max_value'                 => 'Transaction value must not be more than 200,000,000 VND',
	),
);
