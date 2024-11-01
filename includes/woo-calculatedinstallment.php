<?php

global $woocommerce;

    $currency = get_woocommerce_currency();
    
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
             
    $amount = $woocommerce->cart->total;		   

    $request = array(
            'paymentPlanId' => $planId,
            'amount' => round($amount,2),
            'currency' => $currency,
    );			

    $request = json_encode($request,true);			

    $curl = $baseUrl.'/accounts/payment/expectedInstallments';	
    
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

    if ( !is_wp_error( $respon ) ) {
        if(isset($respon['body'])){
            $response = json_decode($respon['body'],true);
            $i=0;
            foreach($response as $value){
            ?>
                <div class="md-step active done <?php if($i==0) echo 'active_installment';?> <?php echo 'installment_'.$i;?>">                
                <div class="md-step-circle"><span><?php echo esc_html($i); ?></span></div>                
                <div class="md-step-title"><span class="crncy"><?php echo esc_html($value['currency']).' '.esc_html($value['amount']); ?></span>
                    <span class="dte"><?php echo esc_html($value['dueDateDesc']); ?><br><span class="emi-1"><?php echo esc_html($value['nbOfInstallmentDesc']); ?></span>
                </div>                
                <div class="md-step-bar-left"></div>                
                <div class="md-step-bar-right"></div></div>               
                <?php $i++;
            }
        }
    }			