<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 21/02/19
 * Time: 08:09 PM
 */

class WC_Payment_Payu_Latam_SDK_Boleto_PLSB extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'payu_latam_sdk_bolet_plsb';
        $this->icon = woo_payu_latam_sdk_pls()->plugin_url . 'assets/images/boleto.jpg';
        $this->method_title = __('Boleto Bancário', 'woo-payu-latam-sdk');
        $this->method_description = __('cash payment', 'woo-payu-latam-sdk');
        $this->description  = $this->get_option( 'description' );
        $this->order_button_text = __('Continue to payment', 'woo-payu-latam-sdk');
        $this->has_fields = false;
        $this->supports = array('products');
        $this->init_settings();
        $this->title = $this->get_option('title');
    }

    public function is_available()
    {
        $payu_latam_sdk = new WC_Payment_Payu_Latam_SDK_PLS();

        return parent::is_available()
            && woo_payu_latam_sdk_pls()->getDefaultCountry() === 'BR'
            && !$payu_latam_sdk->isTest;
    }

    public function process_payment($order_id)
    {

        $params = $_POST;
        $params['id_order'] = $order_id;
        $params['payu-latam-sdk-payment-method'] = 'BOLETO_BANCARIO';

        $payment = new Payu_Latam_SDK_PLS();
        $data = $payment->doPayment($params, false);

        if($data['status']){
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $data['url']
            );
        }else{
            wc_add_notice($data['message'], 'error' );
            woo_payu_latam_sdk_pls()->log($data['message']);
        }

        return parent::process_payment($order_id);
    }
}