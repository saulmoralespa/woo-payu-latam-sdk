<?php


class WC_Payment_Payu_Latam_SDK_PSE_PLSPSE extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'payu_latam_sdk_baloto_plspse';
        $this->icon = woo_payu_latam_sdk_pls()->plugin_url . 'assets/images/pse.jpg';
        $this->method_title = __('PSE', 'woo-payu-latam-sdk');
        $this->method_description = __('Bank Payment', 'woo-payu-latam-sdk');
        $this->order_button_text = __('Continue to payment', 'woo-payu-latam-sdk');
        $this->has_fields = true;
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
                'label' => __('Enable PSE', 'woo-payu-latam-sdk'),
                'default' => 'no'
            ],
            'title' => [
                'title' => __('Title', 'woo-payu-latam-sdk'),
                'type' => 'text',
                'description' => __('It corresponds to the title that the user sees during the checkout', 'woo-payu-latam-sdk'),
                'default' => __('PSE', 'woo-payu-latam-sdk'),
                'desc_tip' => true
            ],
            'description' => [
                'title' => __('Description', 'woo-payu-latam-sdk'),
                'type' => 'textarea',
                'description' => __('It corresponds to the description that the user will see during the checkout', 'woo-payu-latam-sdk'),
                'default' => __('PSE', 'woo-payu-latam-sdk'),
                'desc_tip' => true
            ]
        ];
    }

    public function payment_fields()
    {
        $payment = new Payu_Latam_SDK_PLS();
        $banks = $payment->getBanks();

        if (!empty($banks)){
            echo "<select name='banks_payu_latam_colombia' class='wc-enhanced-select' style='display:block'>";
                foreach ($banks as $bank):
                    echo "<option value='$bank->pseCode'>$bank->description</option>";
                endforeach;
            echo "</select>";
        }

        echo "<select name='person_type_payu_latam_colombia' class='wc-enhanced-select' style='display:block' required>
                <option value='' selected>Seleccione el tipo de persona</option>
                <option value='N'>Natural</option>
                <option value='J'>Jurídica</option>
            </select>";
    }

    public function validate_fields()
    {
        if (isset($_POST['payu-latam-sdk-errorcard'])){
            wc_add_notice($_POST['payu-latam-sdk-errorcard'], 'error' );
            return false;
        }

        return true;
    }

    public function process_payment($order_id)
    {

        $params = $_POST;
        $params['id_order'] = $order_id;
        $params['payu-latam-sdk-payment-method'] = 'PSE';


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