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

	private $my_bill_apiKey;
	private $logger;
	private $log_enabled;
	private $logger_context;

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


		$this->id                 = 'wc-my-bill-payment';
		$this->method_title       = 'WC Pay my bill payment';
        $this->method_description = 'My bill payment gateway for woocommerce';
        $this->has_fields         = true;
        $this->supports           = array(
            'products',
        );

        
        $this->logger = wc_get_logger();
        $this->logger_context = array( 'source' => $this->id );

        // Get setting values
        $this->enabled         = $this->get_option( 'enabled' );
        $this->title           = $this->get_option( 'title' );
        $this->description     = $this->get_option( 'description' );
        $this->my_bill_apiKey  = $this->get_option( 'my_bill_apiKey' );
        $this->log_enabled     = $this->get_option( 'log_enabled' );

		$this->init_form_fields();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ), 10, 0);
		add_action( 'woocommerce_api_wc_my_bill_payment', array( $this, 'my_bills_callback_response' ), 10, 0);

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
        
        if ( defined( 'WC_LOG_DIR' ) ) {
            $nmi_pay_log_url = add_query_arg( 'tab', 'logs', add_query_arg( 'page', 'wc-status', admin_url( 'admin.php' ) ) );
            $nmi_pay_log_key = 'nmi_pay-' . sanitize_file_name( wp_hash( 'nmi_pay' ) ) . '-log';
            $nmi_pay_log_url = add_query_arg( 'log_file', $nmi_pay_log_key, $nmi_pay_log_url );
        
            $label .= ' | ' . sprintf( __( '%1$sView Log%2$s', 'wc-my-bill-payment' ), '<a href="' . esc_url( $nmi_pay_log_url ) . '">', '</a>' );
        }
        
		$this->form_fields = array(
            'enabled' => array(
				'title'       => __( 'Enable/Disable', 'wc-my-bill-payment' ),
				'label'       => __( 'Enable credit card payment with MoreFlo', 'wc-my-bill-payment' ),
				'type'        => 'checkbox',
				'description' => '',
                'default'     => 'no'
            ),
			'title' => array(
				'title'       => __( 'Title', 'wc-my-bill-payment' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout within the credit card payment type.', 'wc-my-bill-payment' ),
				'default'     => __( 'Pay with credit card', 'wc-my-bill-payment' )
			),
			'description' => array(
				'title'       => __( 'Description', 'wc-my-bill-payment' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout within the credit card payment type.', 'wc-my-bill-payment' ),
				'default'     => __( 'Pay with credit card.', 'wc-my-bill-payment' )
			),
            'my_bill_apiKey' => array(
				'title'       => __( 'My Bills apiKey', 'wc-my-bill-payment' ),
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

        return array(
			'result' => 'success',
            'redirect' => get_site_url().'/?wc-api=Wc_My_Bill_Payment&event_type=proccess&order_id='.$order_id//get_site_url() sometime it might cause an issue for network path enable site
        );

	}

		/*
    * Callback methods
    */
	public function my_bills_callback_response()
    {
		if($_REQUEST['event_type']){
            $event_type = $_REQUEST['event_type'];
            
			$order_id;
			// $payid;

            if(isset($_REQUEST['order_id'])){
                $order_id = sanitize_text_field($_REQUEST['order_id']);
            }
			if(isset($_REQUEST['orderId'])){
                $order_id = sanitize_text_field($_REQUEST['orderId']);
            }
			// if(isset($_REQUEST['payid'])){
            //     $payid = sanitize_text_field($_REQUEST['payid']);
            // }

			if($event_type == 'proccess'){
				$this->write_Woo_logs('proccess payment');
				$this->processPayment($order_id);
			}elseif($event_type == 'success'){
				$this->write_Woo_logs('proccess completed');
				$this->successPayment($order_id);
			}else{

			}
        }
    }

	public function processPayment($order_id)
    {

        $endpoint = 'https://secure.mybills.lk/api/sch/payments';
		$site_title = (get_bloginfo( 'name' ) != null) ? get_bloginfo( 'name' ) : ' ';
        $site_description = (get_bloginfo( 'description' ) != null) ? get_bloginfo( 'description' ) : ' ';

		// we need it to get any order detailes
		$order = wc_get_order( $order_id );

		// Get Order Totals $0.00
		$my_bills_currency = $order->get_currency();
		$my_bills_total = $order->get_total();
		$woodecimaloptions = get_option('woocommerce_price_num_decimals');
		if($woodecimaloptions == 0){
			$my_bills_total = number_format((float)$my_bills_total, 2, '.', '');
		}else{
			$my_bills_total = $this->formateAmount($my_bills_total);
		}

		// Get Order User, Billing & Shipping Addresses
		$my_bills_first_name = $order->get_billing_first_name();
		$my_bills_last_name = $order->get_billing_last_name();
		$my_bills_email = $order->get_billing_email();
		$my_bills_phone = $order->get_billing_phone();
			
		// /*
		// * Array with parameters for API interaction
		// */
		$body = [
			'amount'  => $my_bills_total,
			'classOrCourse' => $site_title,//Need to verify this value
			'studentName' => $my_bills_first_name.' '.$my_bills_last_name,
			'email' => $my_bills_email,
			'description' => $site_description,
			'phoneNo' => $my_bills_phone,
			'indexNumber' => $order_id,
			'apiKey' => $this->my_bill_apiKey
		];
			
		$body = wp_json_encode( $body );
		
		$this->write_Woo_logs('json body');
		$this->write_Woo_logs($body);
		
		$args = array(
			'body'        => $body,
			'headers'     => [
					'Content-Type' => 'application/json',
				],
				'timeout'     => 60,
				'redirection' => 5,
				'blocking'    => true,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'data_format' => 'body'
			);

		/*
		* Your API interaction could be built with wp_remote_post()
		*/
		$response = wp_remote_post( $endpoint, $args );
		
		$this->write_Woo_logs('Response');
		$this->write_Woo_logs($response);
		// wp_die();
	
		if( !is_wp_error( $response ) ) {
	
			$body = json_decode( $response['body'], true );

			$this->write_Woo_logs('body');
			$this->write_Woo_logs($body);
	
			// it could be different depending on your payment processor
			if ($response['response']['code'] == '201' ) {

				update_post_meta($order_id, '_mybills_payment_id', $body['id']);//Create security calls

				$url = 'https://secure.mybills.lk/pay/'.$body['id'];

				$this->write_Woo_logs('End_process_payment');
				$this->write_Woo_logs('Start redirecting to '.$url);
				$this->write_Woo_logs($url);
				// Payment created and redirecting payment page
				wp_redirect($url);
				exit;
	
			} else {
				$this->write_Woo_logs('payment creation error');
				wc_add_notice(  'Please try again.', 'error' );
				return;
			}
	
		} else {
			$this->write_Woo_logs('payment response error');
			wc_add_notice(  'Connection error.', 'error' );
			return;
		}
    } 

	public function successPayment($order_id)
    {
		if(isset($order_id)){
			$order = new WC_Order($order_id);
			// $payid = get_post_meta($order_id, '_mybills_payment_id');
			// update_post_meta($order_id, '_mybills_payment_id', $body['id']);//Create security calls

			// if(isset($_POST['reference_no'])){
			// 	$order->set_transaction_id($_POST['reference_no']);
			// }

			$order->payment_complete();
			$order->add_order_note( __( 'Order payment received', MOREFLO_CHECKOUT ) );//New translation
			$this-write_Woo_logs('Payment completed');//Logs

			WC()->cart->empty_cart();
			$thankyouURL = $order->get_checkout_order_received_url();
			wp_safe_redirect($thankyouURL);

			$returnResult['success'] = true;
			$returnResult['successCode'] = "200";
	
			wp_send_json( $returnResult, $returnResult['successCode'] );//Send received notification to server

		}else{
			$this->write_Woo_logs('Return object');//Logs
			$this->write_Woo_logs($_POST);//Logs
			$this->write_Woo_logs($_REQUEST);//Logs
		}
 
    }

	public function formateAmount($amount)
    {
        // Adding decimal point zeros if required based on woocommerece settings
        $woodecimaloptions = get_option('woocommerce_price_num_decimals');

        $nmiamountformats = [0 => '00', 1 => '0'];
        foreach ($nmiamountformats as $key => $value) {
            if($key == $woodecimaloptions)
            {
                $amount = $amount.$value;
            }
        }
        if($woodecimaloptions > 0)
        {
            if($woodecimaloptions >= 3)
            {
                $amount = substr($amount, 0, -($woodecimaloptions - 2) );
            }
        }

        return $amount;
    }

	public function write_Woo_logs($details)
    {
        if ( $this->log_enabled == 'yes' ) {//record if only log enable this method to run 
            $this->logger->debug(wc_print_r("************", true), $this->logger_context);
            $this->logger->debug(wc_print_r($details, true), $this->logger_context);
            $this->logger->debug(wc_print_r("************", true), $this->logger_context);
        }
    }

}
