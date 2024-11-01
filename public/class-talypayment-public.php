<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.google.com
 * @since      1.0.0
 *
 * @package    Talypayment
 * @subpackage Talypayment/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Talypayment
 * @subpackage Talypayment/public
 * @author     Taly Payment 
 */
class Talypayment_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Talypayment_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Talypayment_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/talypayment-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Talypayment_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Talypayment_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/talypayment-public.js', array( 'jquery' ), $this->version, false );
	}


    public function getProductSplitAmount(){
		global $woocommerce;
		global $product;
		$id = $product->get_id();
		$amount = $product->get_price();
		$name = $product->get_name();
		$currency = get_woocommerce_currency();
		$token = $this->getAccessToken();
		
		$all_gateways = WC()->payment_gateways->payment_gateways();
		$allowed_gateways = $all_gateways['talypayment'];
		$settings = $allowed_gateways->settings;
		$mode =  $settings['paymentmethodmode'];

		$showNote = $settings['show_pdp_page_text'];

		if($showNote && $showNote == 'yes')
		{
			$note = $settings['pdp_page_text'];
		
			if($mode=='live'){
				$apiurl = $settings['live_mode_url'];
			}else{
				$apiurl = $settings['test_mode_url'];
			}
			
			$request = array(
					'name' => $name,
					'quantity' => 1,
					'unitPrice' => round($amount,2),
					'currency' => $currency,
			);			

			$request = json_encode($request,true);			

			$curl = $apiurl.'/accounts/payment/calcPromotedInstallments';
				
			$headers = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer '.$token,
			);		

			$respon = wp_remote_post($curl, array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'sslverify' => false,
					'blocking' => true,
					'headers' => $headers,
					'body' => $request,
					'cookies' => array()
				)
			);	
			
			$html = '';
			if ( !is_wp_error( $respon ) ) {
		
				if(isset($respon['body'])){
					$response = json_decode($respon['body'],true);
					$i=0;
					$count = count($response);
					$amt = 0;
					$logo = plugin_dir_url( dirname( __FILE__ ) )."public/image/logo.svg";
					foreach($response as $value){
						$currencySymbol = $value['currency'];
						$amt = $value['currency'].' '.$value['amount'];
					}

					$note = str_replace(array('#term#', '#amount#'), array(esc_html($count), esc_html($amt)), $note);
					?>
					<div class='finance-talypay-box'>
						<div class="pdp-option">
							<div class="pdp-optiontext">
								<h4 style="float:left;"><?=$note;?> <a class="example-icon-14" href="#popup$i">Tell me more</a><p class="interestdiv">0% interest, no fees anytime.</p>
								</h4>
							</div>
							<div class="pdp-optionimg">
								<span class="rytcontent"><img  src="<?=$logo?>"></span>
							</div>
							<a href="#popup$i"><div class="mobile-info"></div></a>
						</div>
						<br>
						<div id="popup$i" class="overlay">
						<div class="popup">
							<div class="closediv">
							<a class="close" href="#">&times;</a></div>
							<div class="content">
								<div class="talypay">

								<h1>Buy now, pay over time.</h1>
								<div class="sub-head">0% interest. 100% Sharia-compliant.</div>
								<ul class="taly-list">
									<li>Select <img src="<?=$logo?>" class="talyicon"> at checkout.</li>
									<li>Choose to split your payment into 4 or pay later in 30 days.</li>
									<li>You’ll be redirected to<a target="_blank" href="https://taly.io"> Taly’s website</a>.  Create an account with just your phone number.</li>
									<li>Complete your first order. We’ll send SMS reminders before your next payment is due.</li>
								</ul>
								<div class="payment-sec">
									<h1 class="payment-heading">Taly payment options</h1>
									<p class="pay-heading">Split in 4 payments</p>
									<ul class="paylist">
										<li class="circle circle-quarter active">
										<p class="first payhead"> Today</p>
										<p class="list-content payhead">1st payment</p>
										</li>
										<li class="circle circle-half">
										<p class="first">After 30 days</p>
										<p class="list-content">2nd payment</p>
										</li>
										<li class="circle circle-three-quarter">
											<p class="first">After 60 days</p>
										<p class="list-content">3rd payment</p>
										</li>
										<li class="circle circle-full">
										<p class="first">After 90 days</p>
										<p class="list-content">4th payment</p>
										</li>
									</ul>
									
								</div>
								<!-- partial -->
								<div class="payment-sec">
									<p class="pay-heading">Pay Later</p>
									<ul class="paylist half">
										<li class="circle circle-empty active">
										<p class="first payhead">Today</p>
										<p class="list-content payhead">No payment</p>
										</li>
										<li class="circle circle-full black-circle">
										<p class="first">After 30 days</p>
										<p class="list-content">Full payment</p>
										</li>
									</ul>
								</div>
								<!-- partial -->
								<div class="paybottom">
								<div class="fl tagbg">Questions? <a href="https://www.dev-taly.io/faq" class="alink" target="_blank">Visit our FAQs</a></span></div>
									<div class="fr cust_pay tagbg">
										<ul class="paymethod">
											<a href=""><li class="s1"></li></a>
											<a href=""><li class="s2"></li></a>
											<a href=""><li class="s3"></li></a>
											<a href=""><li class="s4"></li></a>
										</ul>
									</div>
								</div>
								<!-- partial -->
								</div>
							</div>
						</div>
					</div>
					<?php
				}
			}
		}				
	}	

	public function getAccessToken(){
		$all_gateways = WC()->payment_gateways->payment_gateways();
		$allowed_gateways = $all_gateways['talypayment'];
		$settings = $allowed_gateways->settings;
		$username =  $settings['username'];
		$password = $settings['password'];

		$mode =  $settings['paymentmethodmode'];
	
		if($mode=='live'){
			$apiurl = $settings['live_mode_url'];
		}else{
			$apiurl = $settings['test_mode_url'];
		}
		
		$request = array(
				'grant_type' => 'password',
				'username' => $username,
				'password' => $password,
				'scope'=> 'ui',
		);	;	

		$curl = $apiurl.'/uaa/oauth/token';
		
		$headers = array(
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'User-Agent'    => 'Your App Name (www.yourapp.com)',
			'Authorization' => 'Basic bWVyY2hhbnQ6c2VjcmV0',
		);
					
		$respon = wp_remote_post($curl, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'sslverify' => false,
				'blocking' => true,
				'headers' => $headers,
				'body' => $request,
				'cookies' => array()
			)
		);

		if ( !is_wp_error( $respon ) ) {
			
			if(isset($respon['body'])){
				$response = json_decode($respon['body'],true);
				return $response['access_token'];
			}
		}     			
	}

}
