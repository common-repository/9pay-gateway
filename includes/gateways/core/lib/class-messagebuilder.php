<?php
require 'class-signatureexception.php';
/**
 * Message Builder
 */
class MessageBuilder {

	/**
	 * Method
	 *
	 * @var string http methods.
	 */
	private $method = 'GET';
	/**
	 * Uri
	 *
	 * @var string Uri to build.
	 */
	private $uri;
	/**
	 * Headers
	 *
	 * @var mixed headers data.
	 */
	private $headers;
	/**
	 * Date
	 *
	 * @var mixed date data.
	 */
	private $date;
	/**
	 * Params
	 *
	 * @var mixed params.
	 */
	private $params;
	/**
	 * Body data
	 *
	 * @var mixed body data.
	 */
	private $body;

	/**
	 * Set data
	 *
	 * @param mixed $date date created.
	 * @param mixed $uri uri.
	 * @param mixed $method method http.
	 * @param mixed $headers headers data.
	 * @return $this
	 */
	public function with( $date, $uri, $method = 'GET', $headers = array() ) {
		$this->date    = $date;
		$this->uri     = $uri;
		$this->method  = $method;
		$this->headers = $headers;
		return $this;
	}

	/**
	 * Set body
	 *
	 * @param mixed $body Body data.
	 * @return $this
	 */
	public function with_body( $body ) {
		if ( ! is_string( $body ) ) {
			$body = wp_json_encode( $body );
		}
		$this->body = $body;

		return $this;
	}

	/**
	 * Set params
	 *
	 * @param array $params All request param.
	 * @return $this
	 */
	public function with_params( array $params = array() ) {
		$this->params = $params;

		return $this;
	}

	/**
	 * Build
	 *
	 * @return string
	 */
	public function build() {
		try {
			$this->validate();
		} catch ( SignatureException $e ) {
			echo esc_html( $e );
		}

		$canonical_headers = $this->canonical_headers();

		if ( $this->method === 'POST' && $this->body ) {
			$canonical_payload = $this->canonical_body();
		} else {
			$canonical_payload = $this->canonical_params();
		}
		$components = array( $this->method, $this->uri, $this->date );
		if ( $canonical_headers ) {
			$components[] = $canonical_headers;
		}
		if ( $canonical_payload ) {
			$components[] = $canonical_payload;
		}

		return implode( "\n", $components );
	}

	/**
	 * Get Instance
	 *
	 * @return MessageBuilder
	 */
	public static function instance() {
		return new MessageBuilder();
	}

	/**
	 * Function  toString
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->build();
	}

	/**
	 * Validate data
	 *
	 * @return void
	 * @throws SignatureException
	 */
	protected function validate() {
		if ( empty( $this->uri ) || empty( $this->date ) ) {
			throw new SignatureException( 'Please pass properties by with function first' );
		}
	}

	/**
	 * Canonical headers
	 *
	 * @return string|void
	 */
	protected function canonical_headers() {
		if ( ! empty( $this->headers ) ) {
			ksort( $this->headers );
			return http_build_query( $this->headers );
		}
	}

	/**
	 * Canonical params
	 *
	 * @return false|string
	 */
	protected function canonical_params() {
		$str = '';
		if ( ! empty( $this->params ) ) {
			ksort( $this->params );
			foreach ( $this->params as $key => $val ) {
				$str .= urlencode( $key ) . '=' . urlencode( $val ) . '&';
			}
			$str = substr( $str, 0, -1 );
		}

		return $str;
	}

	/**
	 * Canonical body
	 *
	 * @return string|void
	 */
	protected function canonical_body() {
		if ( ! empty( $this->body ) ) {
			return base64_encode( hash( 'sha256', $this->body, true ) );
		}
	}
}
