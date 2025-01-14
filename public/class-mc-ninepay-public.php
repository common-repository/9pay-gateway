<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://9pay.vn/
 * @since      1.0.0
 *
 * @package    9pay-gateway
 * @subpackage 9pay-gateway/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    9pay-gateway
 * @subpackage 9pay-gateway/public
 * @author     MeCode <ndhung110995@gmail.com>
 */
class Mc_Ninepay_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		add_action( 'plugins_loaded', array( $this, 'init_gateway_class' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway_class' ) );
	}

	/**
	 * Init gateway
	 *
	 * @return void
	 */
	public function init_gateway_class() {
		$this->load_core();

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/class-ninepay.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/class-ninepaygateway.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/class-ninepayipn.php';
	}

	/**
	 * Load Core config
	 *
	 * @return void
	 */
	private function load_core() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/core/lib/class-hmacsignature.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/core/lib/class-messagebuilder.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/core/lib/class-ninepayconstance.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/core/lib/class-signatureexception.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/core/config.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/core/lang.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/core/class-ninepaypayment.php';
	}

	/**
	 * Add gateway class
	 *
	 * @param mixed $methods Methods.
	 * @return mixed
	 */
	public function add_gateway_class( $methods ) {
		$methods[] = 'NinePayGateWay';
		return $methods;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mc_Ninepay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mc_Ninepay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mc-ninepay-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mc_Ninepay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mc_Ninepay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mc-ninepay-public.js', array( 'jquery' ), $this->version, false );

	}
}
