<?php
/**
 * Signature Exception
 *
 * Process Exception
 *
 * @link       https://9pay.vn
 * @since      1.0.0
 *
 * @package    9pay-payment-method
 * @subpackage 9pay-payment-method/includes/gateways/core/lib
 */
class SignatureException extends Exception {

	/**
	 * Construct
	 *
	 * @param mixed          $message Message.
	 * @param mixed          $code Code.
	 * @param Exception|null $previous Exception message.
	 */
	public function __construct( $message, $code = 0, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}

}
