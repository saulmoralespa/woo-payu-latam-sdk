<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 19/02/19
 * Time: 08:26 AM
 */

class WC_Payment_Payu_Latam_SDK_PLS extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'payu_latam_sdk_pls';
        $this->icon = woo_payu_latam_sdk_pls()->plugin_url . 'assets/images/logoPayU.png';
        $this->method_title = __('Payu Latam', 'woo-payu-latam-sdk');
        $this->method_description = __('Accept credit card and cash payments', 'woo-payu-latam-sdk');
        $this->description  = $this->get_option( 'description' );
        $this->order_button_text = __('Continue to payment', 'woo-payu-latam-sdk');
        $this->has_fields = true;
        $this->supports = ['products'];
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');

        $this->merchant_id  = $this->get_option( 'merchant_id' );
        $this->account_id  = $this->get_option( 'account_id' );
        $this->apikey  = $this->get_option( 'apikey' );
        $this->apilogin  = $this->get_option( 'apilogin' );
        $this->isTest = (boolean)$this->get_option('environment');
        $installments = (int)$this->get_option('installments');

        $this->cards_numbers = strpos($this->get_option('cards_numbers_data'), ',') ?
            explode(',', $this->get_option('cards_numbers_data')) :  [$this->get_option('cards_numbers_data')];

        $this->discount_rate_card_number = (int)$this->get_option('discount_rate_card_number');

        if ($installments < 1)
            $this->update_option('installments', '1');

        $this->installments = $this->get_option('installments');

        $this->currency = get_woocommerce_currency();
        $this->debug = $this->get_option('debug');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'confirmation_ipn'));
        add_filter( 'woocommerce_available_payment_gateways', array($this, 'woo_payu_latam_payment_gateway_disable_country') );

    }

    public function is_available()
    {
        return parent::is_available() &&
            !empty( $this->merchant_id ) &&
            !empty( $this->account_id ) &&
            !empty( $this->apikey ) &&
            !empty( $this->apilogin );
    }

    public function init_form_fields()
    {

        $this->form_fields = require( dirname( __FILE__ ) . '/admin/payu-settings.php' );
    }

    public function admin_options()
    {
        ?>
        <h3><?php echo $this->title; ?></h3>
        <p><?php echo $this->method_description; ?></p>
        <table class="form-table">
            <?php
            if(!empty($this->get_option('merchant_id')) && !empty($this->get_option('account_id')) && !empty($this->get_option('apikey')) && !empty($this->get_option('apilogin'))){
                $this->test_payu_latam_sdk();
            }else{
                $emptyFields = __('Could not perform any tests, because you have not entered all the required fields', 'woo-payu-latam-sdk');
                woo_payu_latam_sdk_pls_notices($emptyFields);
            }
            $this->generate_settings_html();
            ?>
        </table>
        <?php
    }

    public function payment_fields()
    {

        if ( $description = $this->get_description())
            echo wp_kses_post( wpautop( wptexturize( $description ) ) );

        ?>
        <div id="card-payu-latam-sdk">
            <div class='card-wrapper'></div>
            <div id="form-payu-latam-sdk">
                <label for="number" class="label"><?php echo __('Data of card','woo-payu-latam-sdk'); ?> *</label>
                <input placeholder="<?php echo __('Card number','woo-payu-latam-sdk'); ?>" type="text" name="payu-latam-sdk-number" id="payu-latam-sdk-number" required="" class="form-control">
                <input placeholder="<?php echo __('Cardholder','woo-payu-latam-sdk'); ?>" type="text" name="payu-latam-sdk-name" id="payu-latam-sdk-name" required="" class="form-control">
                <input type="hidden" name="payu-latam-sdk-type" id="payu-latam-sdk-type">
                <input placeholder="MM/YY" type="tel" name="payu-latam-sdk-expiry" id="payu-latam-sdk-expiry" required="" class="form-control" >
                <input placeholder="123" type="text" name="payu-latam-sdk-cvc" id="payu-latam-sdk-cvc" required="" class="form-control" maxlength="4">
                <?php if((int)$this->installments > 1):  ?>
                    <select name="payu-latam-sdk-installments" id="payu-latam-sdk-installments">
                        <option value="" selected><?php _e('Number of installments','woo-payu-latam-sdk'); ?></option>
                    <?php for ($i = 1; $i <= $this->installments; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
        <?php
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

        $payment = new Payu_Latam_SDK_PLS();
        $data = $payment->doPayment($params, false);

        if($data['status']){
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

    public function test_payu_latam_sdk()
    {
        $payment = new Payu_Latam_SDK_PLS();
        $payment->doPayment();
    }

    public function confirmation_ipn()
    {
        $body = file_get_contents('php://input');
        parse_str($body, $data);

        if (empty($data['extra1']))
            return;

        $order_id = $data['extra1'];
        $order = new WC_Order($order_id);

        $reference_code = $data['reference_sale'];
        $state_pol = $data['state_pol'];
        $signature_payu = $data['sign'];
        $value = $data['value'];
        $transaction_id = $data['transaction_id'];

        $amount = $this->formatted_amount($value, 1);

        $dataSign = [
            'referenceCode' =>  $reference_code,
            'amount' =>  $amount,
            'currency' => $order->get_currency(),
            'state_pol' => $state_pol
        ];

        $signatureOrder = $this->get_sign_validate($dataSign);

        woo_payu_latam_sdk_pls()->log("signatureOrder: $signatureOrder signature_payu: $signature_payu");

        if ($state_pol === '7' || $signatureOrder !== $signature_payu)
            return;

        if ($state_pol === '4'){
            $order->payment_complete($transaction_id);
            $order->add_order_note(sprintf(__('Successful payment (Transaction ID: %s)',
                'woo-payu-latam-sdk'), $transaction_id));
        }elseif ($state_pol !== '7'  && $state_pol !== '4'){
            $order->update_status('failed');
            if(!isset($data['error_message_bank']))
                $order->add_order_note($data['response_message_pol']);
        }

        header("HTTP/1.1 200 OK");
    }

    public function getStatusTransaction($transaction_id)
    {
        PayU::$apiKey = $this->apikey;
        PayU::$apiLogin = $this->apilogin;
        PayU::$merchantId = $this->merchant_id;
        PayU::$language = SupportedLanguages::ES;
        PayU::$isTest = $this->isTest;

        $urlReports = woo_payu_latam_sdk_pls()->createUrl($this->isTest, true);
        Environment::setReportsCustomUrl($urlReports);

        $parameters = array(PayUParameters::TRANSACTION_ID => $transaction_id);

        $response = null;

        try{
            $response = PayUReports::getTransactionResponse($parameters);
            $response = $response->state;
        }catch (PayUException $e){
            $response = null;
            woo_payu_latam_sdk_pls()->log('status transaction: ' . $e->getMessage());
        }

        return $response;
    }

    public function woo_payu_latam_payment_gateway_disable_country($available_gateways)
    {
        if ( is_admin() ) return $available_gateways;

        if ( WC()->customer->get_billing_country() !== 'CO' ) {
            unset( $available_gateways['payu_latam_sdk_baloto_plsb'] );
            unset( $available_gateways['payu_latam_sdk_efecty_plse'] );
            unset( $available_gateways['payu_latam_sdk_baloto_plspse'] );
        }
        return $available_gateways;
    }

    public function formatted_amount($amount, $decimals = 2)
    {
        $amount = number_format($amount, $decimals,'.','');
        return $amount;
    }

    public function get_sign_validate(array $data = [])
    {
        return md5(
            $this->apikey . "~" .
            $this->merchant_id . "~" .
            $data['referenceCode'] ."~".
            $data['amount']."~".
            $data['currency']. "~" .
            $data['state_pol']
        );
    }
}