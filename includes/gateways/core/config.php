<?php
/**
 * Config Core
 *
 * Get Core config from 9pay
 *
 * @link       https://9pay.vn
 * @since      1.0.0
 *
 * @package    9pay-payment-method
 * @subpackage 9pay-payment-method/includes/gateways/core
 */

return array(
	'environment'                => array(
		'production_url'            => 'https://payment.9pay.vn/portal',
		'x_forward_link_production' => 'https://payment.9pay.vn/payments/create',

		// Sandbox.
		'stg_url'                   => 'https://sand-payment.9pay.vn/portal/create/order',
		'x_forward_link_test'       => 'https://sand-payment.9pay.vn/payments/create',

		'exc_rate_host_production'  => 'https://payment.9pay.vn/exchange-rate',
		'exc_rate_host_sand'        => 'https://sand-payment.9pay.vn/exchange-rate',
	),
	'min_amount'                 => 2000,
	'min_amount_wallet_vnd'      => 4000,
	'max_amount_wallet_vnd'      => 100000000,
	'min_amount_atm_card_vnd'    => 10000,
	'max_amount_atm_card_vnd'    => 200000000,
	'min_amount_credit_card_vnd' => 10000,
	'max_amount_credit_card_vnd' => 200000000,
	'min_amount_collection_vnd'  => 10000,
	'max_amount_collection_vnd'  => 200000000,
	'min_amount_bnpl_vnd'        => 10000,
	'max_amount_bnpl_vnd'        => 25000000,
	'max_amount'                 => 200000000,
	'status'                     => array(
		'PAYMENT_SUCCESS' => array( 1, 2, 4, 5 ),
		'PAYMENT_FAILED'  => array( 6, 7, 8, 10, 12, 14, 15 ),
	),
	'PAYMENT_CANCEL'             => 8,
	'PAYMENT_REVIEW'             => 3,
	'PAYMENT_TIMEOUT'            => 15,
	'CURRENCY'                   => array(
		'VND',
		'USD',
		'EUR',
		'GBP',
		'CNY',
		'JPY',
	),
	'NOT_HAS_IPN'                => array( 'MB', 'STB', 'VPB' ),
);
