<?php


class WC_Payment_Payu_Latam_SDK_Baloto_PLSB extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'payu_latam_sdk_baloto_plsb';
        $this->icon = woo_payu_latam_sdk_pls()->plugin_url . 'assets/images/baloto.jpg';
        $this->method_title = __('Baloto', 'woo-payu-latam-sdk');
        $this->method_description = __('cash payment', 'woo-payu-latam-sdk');
        $this->order_button_text = __('Continue to payment', 'woo-payu-latam-sdk');
        $this->has_fields = false;
        $this->supports = ['products'];
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description  = $this->get_option( 'description' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function is_available()
    {
        $payu_latam_sdk = new WC_Payment_Payu_Latam_SDK_PLS();

        return parent::is_available()
            && woo_payu_latam_sdk_pls()->getDefaultCountry() === 'CO'
            && !$payu_latam_sdk->isTest;
    }

    public function init_form_fields()
    {

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woo-payu-latam-sdk'),
                'type' => 'checkbox',
                'label' => __('Enable Baloto', 'woo-payu-latam-sdk'),
                'default' => 'no'
            ],
            'title' => [
                'title' => __('Title', 'woo-payu-latam-sdk'),
                'type' => 'text',
                'description' => __('It corresponds to the title that the user sees during the checkout', 'woo-payu-latam-sdk'),
                'default' => __('Baloto', 'woo-payu-latam-sdk'),
                'desc_tip' => true
            ],
            'description' => [
                'title' => __('Description', 'woo-payu-latam-sdk'),
                'type' => 'textarea',
                'description' => __('It corresponds to the description that the user will see during the checkout', 'woo-payu-latam-sdk'),
                'default' => __('Baloto', 'woo-payu-latam-sdk'),
                'desc_tip' => true
            ]
        ];
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    public function process_payment($order_id)
    {

        $params = $_POST;
        $params['id_order'] = $order_id;
        $params['payu-latam-sdk-payment-method'] = 'BALOTO';

        $payment = new Payu_Latam_SDK_PLS();
        $data = $payment->doPayment($params, false);

        if($data['status']){
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return [
                'result' => 'success',
                'redirect' => $data['url']
            ];
        }else{
            wc_add_notice($data['message'], 'error' );
            woo_payu_latam_sdk_pls()->log($data['message']);
        }

        return parent::process_payment($order_id);
    }
}