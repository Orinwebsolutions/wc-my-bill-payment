<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/Orinwebsolutions
 * @since      1.0.0
 *
 * @package    Wc_My_Bill_Payment
 * @subpackage Wc_My_Bill_Payment/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wc_My_Bill_Payment
 * @subpackage Wc_My_Bill_Payment/includes
 * @author     Amila Priyankara <amilapriyankara16@gmail.com>
 */
class Wc_My_Bill_Payment extends WC_Payment_Gateway {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wc_My_Bill_Payment_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WC_MY_BILL_PAYMENT_VERSION' ) ) {
			$this->version = WC_MY_BILL_PAYMENT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wc-my-bill-payment';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();


		$this->id                 = 'wc-my-bill-payment';
		$this->method_title       = 'WC Pay my bill payment';
        $this->method_description = 'My bill payment gateway for woocommerce';
        $this->has_fields         = true;
        $this->supports           = array(
            'products',
        );

        
        // $this->logger = wc_get_logger();
        // $this->logger_context = array( 'source' => $this->id );

        // Get setting values
        $this->enabled         = $this->get_option( 'enabled' );
        $this->title           = $this->get_option( 'title' );
        $this->description     = $this->get_option( 'description' );
        $this->my_bill_apiKey     = $this->get_option( 'my_bill_apiKey' );
        // $this->log_enabled     = $this->get_option( 'log_enabled' );

		$this->init_form_fields();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wc_My_Bill_Payment_Loader. Orchestrates the hooks of the plugin.
	 * - Wc_My_Bill_Payment_i18n. Defines internationalization functionality.
	 * - Wc_My_Bill_Payment_Admin. Defines all hooks for the admin area.
	 * - Wc_My_Bill_Payment_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-my-bill-payment-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-my-bill-payment-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-my-bill-payment-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wc-my-bill-payment-public.php';

		$this->loader = new Wc_My_Bill_Payment_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wc_My_Bill_Payment_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wc_My_Bill_Payment_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wc_My_Bill_Payment_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, $plugin_admin, 'process_admin_options', 10, 0 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wc_My_Bill_Payment_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_api_Wc_My_Bill_Payment', $plugin_public, 'my_bills_callback_response',10,0 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wc_My_Bill_Payment_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function init_form_fields(){
        $label = __( 'Enable Logging', 'wc-my-bill-payment' );
        $description = __( 'Enable the logging of errors.', 'wc-my-bill-payment' );
        
        // if ( defined( 'WC_LOG_DIR' ) ) {
        //     $nmi_pay_log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
        //     $nmi_pay_log_key = 'nmi_pay-' . sanitize_file_name( wp_hash( 'nmi_pay' ) ) . '-log';
        //     $nmi_pay_log_url = add_query_arg( 'log_file', $nmi_pay_log_key, $nmi_pay_log_url );
        
        //     $label .= ' | ' . sprintf( __( '%1$sView Log%2$s', 'wc-my-bill-payment' ), '<a href="' . esc_url( $nmi_pay_log_url ) . '">', '</a>' );
        // }
        
		$this->form_fields = array(
            'enabled' => array(
				'title'       => __( 'Enable/Disable', 'wc-my-bill-payment' ),
				'label'       => __( 'Enable credit card payment with MoreFlo', 'wc-my-bill-payment' ),
				'type'        => 'checkbox',
				'description' => '',
                'default'     => 'no'
            ),
			'title' => array(
				'title'       => __( 'title', 'wc-my-bill-payment' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout within the credit card payment type.', 'wc-my-bill-payment' ),
				'default'     => __( 'Pay with credit card', 'wc-my-bill-payment' )
			),
			'description' => array(
				'title'       => __( 'description', 'wc-my-bill-payment' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout within the credit card payment type.', 'wc-my-bill-payment' ),
				'default'     => __( 'Pay with credit card.', 'wc-my-bill-payment' )
			),
            'my_bill_apiKey' => array(
				'title'       => __( 'My  Bills apiKey', 'wc-my-bill-payment' ),
				'type'        => 'text',
				'description' => __( 'This is the sandbox account Terminal-ID', 'wc-my-bill-payment' ),
				'default'     => ''
			),          
            'log_enabled' => array(
                'title'       => __( 'Debug Log', 'wc-my-bill-payment' ),
                'label'       => $label,
                'description' => $description,
                'type'        => 'checkbox',
                'default'     => 'no'
            )
        );
        
    }

	/**
    * You will need it if you want your custom credit card form, Step 4 is about it
    */
	public function payment_fields() {
		if ( $this->description ) {
			echo wpautop( wp_kses_post( $this->description ) );
		}
	}

	/*
		* Fields validation, more in Step 5
	*/
	public function validate_fields() {

	

	}

	/*
	* We're processing the payments here, everything about it is in Step 5
	*/
	public function process_payment( $order_id ) {

		global $woocommerce;

		$this->write_Woo_logs('process_payment');
		
		$nminonce =  wp_create_nonce('nmi_callback_mode');
		update_post_meta($order_id, '_nmi_payment_page', $nminonce);//Create security calls

		return array(
			'result' => SUCCESS,
			'redirect' => get_site_url().'/?wc-api=NmiPayGateway&event_type=proccess&order_id='.$order_id//get_site_url() sometime it might cause an issue for network path enable site
		);

	}

}
