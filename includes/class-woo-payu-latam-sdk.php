<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 19/02/19
 * Time: 09:36 AM
 */

class Payu_Latam_SDK_PLS extends WC_Payment_Payu_Latam_SDK_PLS
{
    public $testCheck;
    public $dataPayment = [];
    public $buyerName;
    public $paymentMethod;

    public function __construct()
    {
        parent::__construct();
    }

    public function doPayment(array $params = [], $test = true)
    {

        $this->testCheck = $test;

        $country = woo_payu_latam_sdk_pls()->getDefaultCountry();

        $parametersCard = $this->prepareDataCard();
        $this->paymentMethod = $parametersCard['payu-latam-sdk-payment-method'];
        $this->buyerName   = $parametersCard['card_name'];
        $this->dataPayment = $this->dataOrder();

        $buyerCNPJ = '';


        if (!empty($params)){

            $order_id = $params['id_order'];
            $order = new WC_Order($order_id);

            $this->dataPayment = $this->dataOrder($order);
            $this->paymentMethod = $params['payu-latam-sdk-payment-method'];

            if (!in_array($this->paymentMethod, $this->paymentsCash())){
                $parametersCard = $this->prepareDataCard($params);
                $this->buyerName = $parametersCard['card_name'];
            }else{
               $this->buyerName = $this->dataPayment['name'];
            }

        }

        if ($this->testCheck || (isset($params) && !$this->isCash())){
            $parameters = array_merge($this->paramsBasicPayu($parametersCard),
                $this->paramsBuyerPayu(false), $this->paramsPayerPayu(),
                $this->paramsLeftoverPayu(),
                [
                    PayUParameters::CREDIT_CARD_NUMBER => $parametersCard['card_number'],
                    PayUParameters::CREDIT_CARD_EXPIRATION_DATE => $parametersCard['card_expire'],
                    PayUParameters::CREDIT_CARD_SECURITY_CODE=> $parametersCard['cvc'],
                    PayUParameters::INSTALLMENTS_NUMBER => isset($params['payu-latam-sdk-installments']) ? $params['payu-latam-sdk-installments'] : $this->installments
                ]
            );
        }

        if ($this->testCheck || (isset($params) && !$this->isCash())
            && $country !== 'BR'){
            $parameters = array_merge(
                $parameters,
                [
                    PayUParameters::DEVICE_SESSION_ID => md5(session_id().microtime()),
                    PayUParameters::PAYER_COOKIE => md5(session_id().microtime()),
                    PayUParameters::USER_AGENT => $_SERVER['HTTP_USER_AGENT']
                ]
            );
        }

        if (isset($params) && $this->paymentMethod === 'BOLETO_BANCARIO'){
            $parameters = array_merge(
                $this->paramsBasicPayu(),
                $this->paramsBuyerPayu(false),
                $this->paramsPayerPayu(),
                $this->paramsLeftoverPayu(),
                $this->paramExpirePayu(),
                $this->paramsResponseUrl()
            );
        }

        if (isset($params) && ($this->paymentMethod === 'BALOTO' || $this->paymentMethod === 'EFECTY')){
            $parameters = array_merge(
                $this->paramsBasicPayu(),
                $this->paramsBuyerPayu(true),
                $this->paramsPayerPayu(),
                $this->paramsLeftoverPayu(),
                $this->paramExpirePayu(),
                $this->paramsResponseUrl()
            );
        }

        if ($this->paymentMethod === 'PSE'){
            $parameters = array_merge(
                $this->paramsBasicPayu(),
                $this->paramsBuyerPayu(true),
                $this->paramsPayerPayu(),
                $this->paramsLeftoverPayu(),
                $this->paramsResponseUrl(),
                [
                    PayUParameters::PAYER_COOKIE => md5(session_id().microtime()),
                    PayUParameters::USER_AGENT => $_SERVER['HTTP_USER_AGENT'],
                    PayUParameters::PSE_FINANCIAL_INSTITUTION_CODE => $params['banks_payu_latam_colombia'],
                    PayUParameters::PAYER_PERSON_TYPE => $params['person_type_payu_latam_colombia'],
                    PayUParameters::PAYER_DOCUMENT_TYPE => $params['billing_type_document'] ?? 'CC'
                ]
            );
        }

        if ($country === 'BR' && !empty($buyerCNPJ))
            $parameters = array_merge($parameters, array(PayUParameters::BUYER_CNPJ => $buyerCNPJ));
        if($country === 'CO')
            $parameters = array_merge($parameters, array(PayUParameters::TAX_VALUE => "0", PayUParameters::TAX_RETURN_BASE => "0"));

        if ($this->testCheck){
            $response = $this->executePayment($parameters);
        }else {
            $response = $this->executePayment($parameters, $order);
        }

        return $response;
    }


    public function getIP()
    {
        return ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '::' ||
            !preg_match('/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m',
                $_SERVER['REMOTE_ADDR'])) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
    }

    public function prepareDataCard($params = null)
    {

        if ($params === null){
            $card_number = "5529998177229339";
            $card_type  = "MASTERCARD";
            $card_name = "Pedro Perez";
            $card_expire = date('Y/m', strtotime('+1 years'));
            $cvc = "808";
        }else{
            $card_number = $params['payu-latam-sdk-number'];
            $card_number = str_replace(' ','', $card_number);
            $card_name = $params['payu-latam-sdk-name'];
            $card_type = $params['payu-latam-sdk-payment-method'];
            $cvc = $params['payu-latam-sdk-cvc'];

            $card_expire = explode('/', $params['payu-latam-sdk-expiry']);
            $card_expire = $card_expire[1] . "/" . $card_expire[0];
        }

        $data = [
            'card_number' => $card_number,
            'card_name' => $card_name,
            'payu-latam-sdk-payment-method' => $card_type,
            'card_expire' => $card_expire,
            'cvc' => $cvc
        ];

        return $data;
    }

    public function saveTransactionId($order_id, $transactionId)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'payu_latam_sdk_pls_transactions';

        $wpdb->insert(
            $table_name,
            [
                'orderid' => $order_id,
                'transactionid' => $transactionId,
            ]
        );

    }

    public function dataOrder($order = null)
    {
        $data = [];

        if ($order === null){

            $data['reference'] = "payment_test" . time();
            $data['total'] = "100";
            $data['description'] = "payment test";
            $data['email'] = "buyer_test@test.com";
            $data['phone'] = "7563126";
            $data['city'] = "Medellin";
            $data['state'] = "Antioquia";
            $data['street'] = "calle 100";
            $data['street2'] = "5555487";
            $data['postalCode'] = "000000";
            $data['dni'] = woo_payu_latam_sdk_pls()->getDefaultCountry() === 'BR' ? "811.807.405-64" : "5415668464654";

        }else{
            $data['reference'] = $order->get_order_key() . '-' . time();
            $data['total'] = $order->get_total();
            $data['description'] = "Order " . $order->get_id();
            $data['name'] = $order->get_shipping_first_name() ? $order->get_shipping_first_name() . " " . $order->get_shipping_last_name() : $order->get_billing_first_name() . " " . $order->get_billing_last_name();
            $data['email'] = $order->get_billing_email();
            $data['phone'] = $order->get_billing_phone();
            $data['city'] = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
            $data['state'] =  $order->get_shipping_state() ?  $order->get_shipping_state() : $order->get_billing_state();
            $data['street'] = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1();
            $data['street2'] = $order->get_shipping_address_1() ? $order->get_shipping_address_1() . " " . $order->get_shipping_address_2() : $order->get_billing_address_1() . " " . $order->get_billing_address_2();
            $data['postalCode'] = empty($order->get_billing_postcode()) ? '000000' : $order->get_billing_postcode();
            $data['dni'] = empty(get_post_meta( $order->get_id(), '_billing_dni', true )) ? get_post_meta( $order->get_id(), '_billing_cpf', true ) : get_post_meta( $order->get_id(), '_billing_dni', true );
        }

        return $data;
    }

    public function paymentsCash()
    {
        return [
            //Argentina
            "COBRO_EXPRESS",
            "PAGOFACIL",
            "RAPIPAGO",
            "BAPRO",
            "RIPSA",
            //Brazil
            "BOLETO_BANCARIO",
            //Colombia
            "BALOTO",
            "EFECTY",
            "PSE",
            //Mexico
            "SANTANDER",
            "SCOTIABANK",
            "BANCOMER",
            "OXXO",
            "SEVEN_ELEVEN",
            "OTHERS_CASH_MX"
        ];
    }

    public function getCountryPayu()
    {
        $countryShop = woo_payu_latam_sdk_pls()->getDefaultCountry();
        $countryName = PayUCountries::CO;

        if ($countryShop === 'AR')
            $countryName = PayUCountries::AR;
        if ($countryShop === 'BR')
            $countryName = PayUCountries::BR;
        if ($countryShop === 'MX')
            $countryName = PayUCountries::MX;
        if ($countryShop === 'PA')
            $countryName = PayUCountries::PA;
        if ($countryShop === 'PE')
            $countryName = PayUCountries::PE;

        return $countryName;
    }


    public function getCurrency()
    {
        $country = woo_payu_latam_sdk_pls()->getDefaultCountry();
        $currency = ($country == 'CO' && $this->testCheck) ? 'USD' : $this->currency;

        return $currency;
    }

    public function getLanguagePayu()
    {
        $country = woo_payu_latam_sdk_pls()->getDefaultCountry();
        $lang = $country === 'BR' ?  SupportedLanguages::PT : SupportedLanguages::ES;

        return $lang;
    }

    public function paramsBasicPayu(array $paramsCard = array())
    {

        $six_fist_numbers_card = !empty($paramsCard) ? substr($paramsCard['card_number'], -0, 6) : '';
        $discount = $this->dataPayment['total'] - ($this->dataPayment['total'] * ($this->discount_rate_card_number / 100));
        $total = in_array($six_fist_numbers_card, $this->cards_numbers) ? $discount : $this->dataPayment['total'];

        $params = [
            PayUParameters::ACCOUNT_ID => $this->account_id,
            PayUParameters::REFERENCE_CODE => $this->dataPayment['reference'],
            PayUParameters::DESCRIPTION => $this->dataPayment['description'],
            PayUParameters::VALUE => $total,
            PayUParameters::CURRENCY => $this->getCurrency(),
            PayUParameters::NOTIFY_URL => $this->getUrlNotify()
        ];

        return $params;
    }

    public function paramsBuyerPayu($onlyEmail = true)
    {

        $params = [
            PayUParameters::BUYER_NAME => $this->buyerName,
            PayUParameters::BUYER_EMAIL => $this->dataPayment['email'],
            PayUParameters::BUYER_CONTACT_PHONE => $this->dataPayment['phone'],
            PayUParameters::BUYER_DNI => $this->dataPayment['dni'],
            PayUParameters::BUYER_STREET => $this->dataPayment['street'],
            PayUParameters::BUYER_STREET_2 => $this->dataPayment['street2'],
            PayUParameters::BUYER_CITY => $this->dataPayment['city'],
            PayUParameters::BUYER_STATE => $this->dataPayment['state'],
            PayUParameters::BUYER_COUNTRY => woo_payu_latam_sdk_pls()->getDefaultCountry(),
            PayUParameters::BUYER_POSTAL_CODE => $this->dataPayment['postalCode'],
            PayUParameters::BUYER_PHONE => $this->dataPayment['phone']
        ];

        if ($onlyEmail)
            return [
                PayUParameters::BUYER_EMAIL => $this->dataPayment['email']
            ];

        return $params;

    }

    public function paramsPayerPayu()
    {

        if (woo_payu_latam_sdk_pls()->getDefaultCountry() === 'BR'
            && !$this->isCash())
            return [
                PayUParameters::PAYER_NAME => ($this->testCheck || $this->isTest) ? "APPROVED" :  $this->buyerName,
            ];

        $params = [
            PayUParameters::PAYER_NAME => ($this->testCheck || $this->isTest) ? "APPROVED" :  $this->buyerName,
            PayUParameters::PAYER_EMAIL => $this->dataPayment['email'],
            PayUParameters::PAYER_CONTACT_PHONE => $this->dataPayment['phone'],
            PayUParameters::PAYER_DNI => $this->dataPayment['dni'],
            PayUParameters::PAYER_STREET => $this->dataPayment['street'],
            PayUParameters::PAYER_STREET_2 => $this->dataPayment['street2'],
            PayUParameters::PAYER_CITY => $this->dataPayment['city'],
            PayUParameters::PAYER_STATE => $this->dataPayment['state'],
            PayUParameters::PAYER_COUNTRY => woo_payu_latam_sdk_pls()->getDefaultCountry(),
            PayUParameters::PAYER_POSTAL_CODE => $this->dataPayment['postalCode'],
            PayUParameters::PAYER_PHONE => $this->dataPayment['phone']
        ];

        if (woo_payu_latam_sdk_pls()->getDefaultCountry() === 'CO'
            && $this->isCash() && $this->paymentMethod !== 'PSE'){
            return [
                PayUParameters::PAYER_NAME => ($this->testCheck || $this->isTest) ? "APPROVED" :  $this->buyerName,
                PayUParameters::PAYER_DNI => $this->dataPayment['dni']
            ];
        }

        if (woo_payu_latam_sdk_pls()->getDefaultCountry() === 'BR'
            && $this->isCash()){
            unset($params[1]);
            unset($params[2]);
            unset($params[3]);
            unset($params[10]);
        }

        return $params;
    }


    public function paramsLeftoverPayu()
    {
        $params = array(
            PayUParameters::COUNTRY => $this->getCountryPayu(),
            PayUParameters::PAYMENT_METHOD => $this->paymentMethod,
            PayUParameters::IP_ADDRESS => $this->getIP()
        );

        return $params;
    }


    public function paramExpirePayu()
    {
        return [
            PayUParameters::EXPIRATION_DATE => $this->dateExpire()
        ];
    }

    public function paramsResponseUrl()
    {
        return [
            PayUParameters::RESPONSE_URL => !empty($this->response_page) ? get_page_link($this->response_page) : get_bloginfo( 'url' )
        ];
    }

    public function isCash()
    {
        return in_array($this->paymentMethod, $this->paymentsCash());
    }

    public function dateExpire()
    {

        $today = $this->dateCurrent();
        $day = $this->getDay();

        $addDay = 1;

        if ($day == 5)
            $addDay += 2;
        if ($day == 6)
            $addDay += 1;

        $today = strtotime ( "+$addDay days" , strtotime ( $today ) );
        $today = date ( PayUConfig::PAYU_DATE_FORMAT , $today );

        return $today;

    }

    public function getDay()
    {
        $today = $this->dateCurrent();
        $weekDay = date('w', strtotime($today));
        return $weekDay;
    }

    public function dateCurrent()
    {
        $dateCurrent = date(PayUConfig::PAYU_DATE_FORMAT, current_time( 'timestamp' ));

        return $dateCurrent;
    }

    public function getUrlNotify()
    {
        $url = trailingslashit(get_bloginfo( 'url' )) . trailingslashit('wc-api') . strtolower(get_parent_class($this));
        return $url;
    }

    public function executePayment(array $parameters, $order = null)
    {
        woo_payu_latam_sdk_pls()->log($parameters);
        woo_payu_latam_sdk_pls()->log($order);
        $this->credentialsPayu();

        try{
            $response = PayUPayments::doAuthorizationAndCapture($parameters);
            woo_payu_latam_sdk_pls()->log($response);

            if (!$this->testCheck){

                $aprovved = false;
                $redirect_url  = '';
                $messge_status = '';

                if ($response->transactionResponse->state == "APPROVED") {
                    $aprovved   = true;
                    $transactionId = $response->transactionResponse->transactionId;
                    $order->payment_complete($transactionId);
                    $order->add_order_note(sprintf(__('Successful payment (Transaction ID: %s)',
                        'woo-payu-latam-sdk'), $transactionId));
                    $message   = sprintf(__('Successful payment (Transaction ID: %s)', 'woo-payu-latam-sdk'),
                        $transactionId);
                    $messageClass  = 'woocommerce-message';
                    $redirect_url = add_query_arg(['msg'=> urlencode($message), 'type'=> $messageClass], $order->get_checkout_order_received_url());
                    wc_reduce_stock_levels($order->get_id());
                } elseif ($response->transactionResponse->state == "PENDING") {
                    $transactionId = $response->transactionResponse->transactionId;
                    $this->saveTransactionId($order->get_id(), $transactionId);
                    $order->update_status('on-hold');
                    $order->add_order_note(sprintf(__('Pending approval (Transaction ID: %s)',
                        'woo-payu-latam-sdk'), $transactionId));

                    if (!$this->isCash()){
                        $message = sprintf(__('Payment pending (Transaction ID: %s)', 'woo-payu-latam-sdk'),
                            $transactionId);
                        $messageClass  = 'woocommerce-info';
                        $redirect_url = add_query_arg(['msg' => urlencode($message), 'type' => $messageClass],
                            $order->get_checkout_order_received_url());
                    }else{
                        $redirect_url = $response->transactionResponse->extraParameters->URL_BOLETO_BANCARIO ??
                            $response->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_HTML ?? $response->transactionResponse->extraParameters->BANK_URL;
                        $aprovved = true;
                    }
                } elseif ($response->transactionResponse->state === "DECLINED") {
                    $transactionId = $response->transactionResponse->transactionId;
                    $message   = __('Payment declined', 'woo-payu-latam-sdk');
                    $messageClass  = 'woocommerce-error';
                    $order->update_status('failed');
                    $order->add_order_note(sprintf(__('Payment declined (Transaction ID: %s)',
                        'woo-payu-latam-sdk'), $transactionId));
                    $redirect_url = add_query_arg(['msg' => urlencode($message), 'type' => $messageClass],
                        $order->get_checkout_order_received_url());
                    $messge_status = $response->transactionResponse->paymentNetworkResponseErrorMessage ?? __('Declined transaction', 'woo-payu-latam-sdk');
                } elseif ($response->transactionResponse->state == "EXPIRED") {
                    $transactionId = $response->transactionResponse->transactionId;
                    $message       = __('Payment expired', 'woo-payu-latam-sdk');
                    $messageClass  = 'woocommerce-error';
                    $order->update_status('failed');
                    $order->add_order_note(sprintf(__('Payment expired (Transaction ID: %s)', 'woo-payu-latam-sdk'),
                        $transactionId));
                    $redirect_url = add_query_arg(['msg' => urlencode($message), 'type' => $messageClass],
                        $order->get_checkout_order_received_url());
                    $messge_status = __('Expired transaction',
                        'woo-payu-latam-sdk');
                }

                return ['status' => $aprovved, 'message' => $messge_status, 'url' => $redirect_url];

            }

        }catch (PayUException $ex){
            if($this->testCheck){
                woo_payu_latam_sdk_pls()->log($ex->getMessage());
                $warning = sprintf(__('payU Latam SDK: Check that you have entered correctly merchant id, account id, Api Key, Apilogin. To perform tests use the credentials provided by payU %s Message error: %s code error: %s',
                    'woo-payu-latam-sdk'), '<a target="_blank" href="http://developers.payulatam.com/es/sdk/sandbox.html">' . __('Click here to see', 'woo-payu-latam-sdk') . '</a>', $ex->getMessage(), $ex->getCode());
                woo_payu_latam_sdk_pls_notices($warning);
            }else{
                woo_payu_latam_sdk_pls()->log($ex->getMessage());
                woo_payu_latam_sdk_pls()->log($parameters);
                return ['status' => false, 'message' => $ex->getMessage()];
            }
        }

        return ['status' => false, 'message' => __('Not processed payment')];
    }

    public function getBanks()
    {
        $this->credentialsPayu();

        $parameters = array(
            PayUParameters::PAYMENT_METHOD => "PSE",
            PayUParameters::COUNTRY => PayUCountries::CO,
        );

        $banks = [];

        try{
            $array = PayUPayments::getPSEBanks($parameters);
            $banks = $array->banks;
        }catch (PayUException $ex){
            woo_payu_latam_sdk_pls()->log($ex->getMessage());
        }

        return $banks;
    }

    protected function credentialsPayu()
    {
        PayU::$apiKey = $this->apikey;
        PayU::$apiLogin = $this->apilogin;
        PayU::$merchantId = $this->merchant_id;
        PayU::$language = $this->getLanguagePayu();
        PayU::$isTest = ($this->testCheck) ? true : $this->isTest;
        $urlPayment = woo_payu_latam_sdk_pls()->createUrl($this->isTest);
        Environment::setPaymentsCustomUrl($urlPayment);
    }

}