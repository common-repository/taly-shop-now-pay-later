<?php
class WC_Talypayment_Gateway extends WC_Payment_Gateway {

    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct() {

        $this->id = 'talypayment';
        $this->image_baseurl = plugin_dir_url( dirname( __FILE__ ) ) . 'public/image/';
        $this->icon = $this->image_baseurl . 'logo.svg';
        $this->has_fields = true; 
        $this->method_title = 'Taly';
        $this->method_description = 'Making payment easier than ever Thats what Taly is all about.'; // will be displayed on the options page.

        $this->supports = array(
            'products',
            'default_credit_card_form'
        );

        // Method with all the options fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = 'Checkout with Taly';
        $this->description = 'Making payment easier than ever Thats what Taly is all about.';
        $this->enabled = $this->get_option( 'enabled' );
        // This action hook saves the settings.
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_' . $this->id , array( $this, 'talypaymentCallback' ) );


        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_talypayment_scripts' ) );
    }

    /**
     * Plugin Scripts added.
     */
    public function enqueue_talypayment_scripts() {
        wp_register_script( 'talypayment_scripts',plugin_dir_path( dirname( __FILE__ ) ) . 'admin/js/talypayment-admin.js' , array( 'jquery', 'jquery-payment' ), '1.0.0', true );
        wp_enqueue_script( 'talypayment_scripts' );
    }

    /**
     * Plugin options, we deal with it in Step 3 too.
     */
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable'),
                'label'       => __('Enable Taly Payment Gateway'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'username' => array(
                'title'       => __('User Name'),
                'type'        => 'text',
                'default'     => '',
                'desc_tip'    => true,
            ),		
            'password' => array(
                'title'       => __('Password'),
                'type'        => 'text',
                'default'     => '',
                'desc_tip'    => true
            ),	
            'test_mode_url' => array(
                'title'       => __('Test Mode URL'),
                'type'        => 'text',
                'default'     => 'https://api.dev-taly.io',
                'desc_tip'    => true
            ),	
            'live_mode_url' => array(
                'title'       => __('Live Mode URL'),
                'type'        => 'text',
                'default'     => 'https://api.taly.io',
                'desc_tip'    => true
            ),
            'paymentmethodmode' => array(
                'title'       => __('Payment Mode'),
                'label'       => __('Payment Mode'),
                'type'        => 'select',
                'placeholder' => __('Select Payment Mode'),
                'class'       => array('input-select'),
                'options'     => array(''=> 'Select Payment Mode','test'=>'Test Mode','live' => 'Live Mode'),
                'required'    => true,
            ),
            'show_pdp_page_text' => array(
                'title'       => __('Show/Hide Product Detail Page Text'),
                'label'       => __('Show/Hide Product Detail Page Text'),
                'type'        => 'checkbox',
                'default'    => 'yes',
            ),
            'pdp_page_text' => array(
                'title'       => __('Product Detail Page Text'),
                'type'        => 'text',
                'default'     => 'Or pay in #term# intrest-free installments of #amount# by',
                'desc_tip'    => true
            ),		
        );		
    }

    /**
     * Add Payment fields.
     */
    public function payment_fields() {

        // I will echo() the form, but you can close PHP tags and print it directly in HTML.
        echo "<fieldset id='wc-" . esc_attr( $this->id ) . "-cc-form' class='wc-credit-card-form wc-payment-form' style='background:transparent;'>";
        $disablePlaceOrder = false;
        if( 'talypayment' === esc_attr( $this->id ) ){
            $vr = $this->getAccessToken();
            if($vr){
            $plans = $this->getPlans($vr);
            if($plans && !isset($plans['errors'])){
            ?>
            <div id="paymentContainer" name="paymentContainer" class="paymentOptions">
            <?php
                $k=0;
                foreach($plans as $key=>$value){
                    if($k==0){$chkd='checked';}else{$chkd='';}
            ?>
            <div id="plan<?php echo esc_attr($value['id']); ?>" class="floatBlock <?=$chkd=='checked'?'active':''?>">
            <label for="<?php echo esc_attr($value['id']); ?>"><input id="<?php echo esc_attr($value['id']); ?>" name="taly_plan" type="radio" value="<?php echo esc_attr($value['id']); ?>" <?php echo esc_attr($chkd); ?> /><span class="inptlbl"><?php echo esc_html($value['name']); ?></span></label>
            </div>
            <?php
                    $k++;
                }
            ?>
            </div>	
            <div class="plan-details-box">
                <h4 class="payment_plan_text">Payment plan</h4>
            <?php
            $i=0;
            foreach($plans as $key=>$value){
                if($i==0){ $cls = 'activebox'; }else{ $cls =''; }
                
            ?>
            <div class="<?php echo esc_attr($cls); ?> md-stepper-horizontal orange <?php echo esc_attr($value['id']); ?>"><?php $this->getCalculatedInstallmentForPaymentPlans($value['id'],$vr); ?></div>
            <?php
                $i++;
                }	
            ?>					
            </div>    
            <?php
            }elseif(isset($plans['errors']) && isset($plans['message'])){  
                $disablePlaceOrder = true;
                ?>
                <div id="paymentContainer" name="paymentContainer" class="paymentOptions">
                    <div class="floatBlock active Disabled<?=$chkd=='checked'?'active':''?>">
                        <label><input type="radio"  <?php echo esc_attr($chkd); ?> /><span class="inptlbl"><?php echo esc_html($plans['message']); ?></span></label>
                    </div>
                </div>
                <?php
            }else{
            ?>
            <div class="tlydesc">We are facing some issue. please try again later.</div>
            <?php
            }
            }else{
                $disablePlaceOrder = true;
            ?>				
            <div class="tlydesc">We are facing some issue. please try again later.</div>
            <?php
            }
        }
        ?>

        <?php
        if($disablePlaceOrder)
        {
        ?>
            <script>jQuery("#place_order").attr("disabled",true);</script>
        <?php
        }
        else
        {
            ?>
            <script>jQuery("#place_order").attr("disabled",false);</script>
            <?php
        }
        ?>
        <div class="clear"></div></fieldset>
        <?php 
    }

    public function getCalculatedInstallmentForPaymentPlans($planId,$token){
        
        require plugin_dir_path( dirname( __FILE__ ) ) . 'includes/woo-calculatedinstallment.php';
    }
    /**
     * Callback for Process Payment.
     *
     * @since 1.0.0
     * @param string $orderid  Order Id.
     */
    public function process_payment( $order_id ) {
        
        $payment_method_mode = ! empty( $_POST['taly_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['taly_plan'] ) ) : '';
        $order = wc_get_order( $order_id );

        try {
            //code...
            if( ! empty( $payment_method_mode ) ) {
            
                global $woocommerce;
                $all_gateways = WC()->payment_gateways->payment_gateways();
                $allowed_gateways = $all_gateways['talypayment'];
                $settings = $allowed_gateways->settings;
                $username = $settings['username'];
                $password = $settings['password'];
                $paymentMode = $settings['paymentmethodmode'];
                if($paymentMode=='live'){
                    $baseUrl = $settings['live_mode_url'];
                    $redirectionurl = 'https://taly.io';
                }else{
                    $baseUrl = $settings['test_mode_url'];
                    $redirectionurl = 'https://dev-taly.io';
                }
                $amount = $order->total;
                $paymentMethodCurrency = get_woocommerce_currency();
                $redirecturl = WC()-> api_request_url( 'talypayment' );
                WC()->session->set('oid', $order_id );
            
                $access_token = $this->getAccessToken();

                $customerName = $_POST['billing_first_name'].' '.$_POST['billing_last_name'];
                $customerMobileNumber = $_POST['billing_phone'];

                $request = array(
                        'merchantOrderId' => 'W_'.strtotime("Y-m-d H:i:s").rand().'_'.$order_id,
                        'amount' => $amount,
                        'currency' => $paymentMethodCurrency,
                        'redirectUrl'=> $redirecturl,
                        'languageCode'=> 'en',
                        'paymentPlanId'=> $payment_method_mode,
                        'customerName'=>$customerName,
                        'customerMobileNumber'=>$customerMobileNumber
                );
            
                $request = json_encode($request,true);
                
                $curl = $baseUrl.'/accounts/payment/initiate';

                $headers = array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer '.$access_token,
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
                        
                        $redirecturl = $redirectionurl."/checkout/securecheckout/".esc_html($response['orderToken']);
                            WC()->session->set('otok', $response['orderToken'] );
                        return array(
                            'result' => 'success',
                            'redirect' => $redirecturl
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            throw new Exception( $e->getMessage(), 1);
        }
    }

    /**
     * Callback for Redirect Url.
     *
     * @since 1.0.0
     * @param string $paymentMethodCode Payment method code.
     * @param string $paymentMethodCurrency  Payment method currency.
     * @param string $orderid  Order Id.
     */

    public function getPlans($token){
        $request = array();	

        global $woocommerce;
        $all_gateways = WC()->payment_gateways->payment_gateways();
        $allowed_gateways = $all_gateways['talypayment'];
        $settings = $allowed_gateways->settings;
        $username = $settings['username'];
        $password = $settings['password'];
                
        $paymentMode = $settings['paymentmethodmode'];
        
        if($paymentMode=='live'){
            $baseUrl = $settings['live_mode_url'];
        }else{
            $baseUrl = $settings['test_mode_url'];
        }
        $curl = $baseUrl.'/accounts/payment/plans';
        
        $headers = array(
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer '.$token,
        );		

        $respon = wp_remote_post($curl, array(
                'method' => 'GET',
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
                return $response;
            }
        }		
    }			


    /**
     * Callback for Redirect Url.
     *
     * @since 1.0.0
     * @param string $paymentMethodCode Payment method code.
     * @param string $paymentMethodCurrency  Payment method currency.
     * @param string $orderid  Order Id.
     */
    
    public function getAccessToken(){
    global $woocommerce;
        $all_gateways = WC()->payment_gateways->payment_gateways();
        $allowed_gateways = $all_gateways['talypayment'];
        $settings = $allowed_gateways->settings;
        $username = $settings['username'];
        $password = $settings['password'];
        $paymentMode = $settings['paymentmethodmode'];
        
        if($paymentMode=='live'){
            $baseUrl = $settings['live_mode_url'];
        }else{
            $baseUrl = $settings['test_mode_url'];
        }
                
        $request = array(
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
                'scope'=> 'ui',
        );	

        
        $curl = $baseUrl.'/uaa/oauth/token';
        
        $headers = array(
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'User-Agent'    => get_bloginfo( 'name' ). ' ('.$baseUrl.')',
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

    /**
     * Callback for Order.
     *
     * @since 1.0.0
     * @param object $order             Main order.
     */
    public function talypaymentCallback($order_id) { 
        global $woocommerce, $post;
        

        $all_gateways = WC()->payment_gateways->payment_gateways();
        $allowed_gateways = $all_gateways['talypayment'];
        $settings = $allowed_gateways->settings;
        $username = $settings['username'];
        $password = $settings['password'];
        $paymentMode = $settings['paymentmethodmode'];
        
        if($paymentMode=='live'){
            $baseUrl = $settings['live_mode_url'];
        }else{
            $baseUrl = $settings['test_mode_url'];
        }    
        $order_id = WC()->session->get('oid');
        $order_token = WC()->session->get('otok');

        $token = $this->getAccessToken();
        $request = array();	

        $curl = $baseUrl.'/accounts/payment/info/'.$order_token;
        
        
        $headers = array(
            'Content-Type'  => 'application/x-www-form-urlencoded',
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
        if ( !is_wp_error( $respon ) ) {
            
            if(isset($respon['body'])){
                $response = json_decode($respon['body'],true);
                
                $order = wc_get_order($order_id);		
                $order_info = $order->get_data();
                
                if (isset($response['status']) && $response['status']=='CONFIRMED') {
                    $order->update_status( 'processing' ); // Processing.
                    // this is important part for empty cart.
                    $woocommerce->cart->empty_cart();
                    $url = $this->get_return_url( $order );	
                } else {
                    $order->update_status( 'pending' );
                    $url = wc_get_checkout_url();
                }
                
                $note = "Track Id: ".$order_token."\r\n Payment Status: ".$response['result'];
                $order->add_order_note($note);	
                wp_redirect( $url );
                exit;				        
                
            }
        }else{
                $url = wc_get_checkout_url();
                wp_redirect( esc_url( $url ) );
                exit;				
        }
    }
}