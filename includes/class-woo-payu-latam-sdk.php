<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 19/02/19
 * Time: 09:36 AM
 */

class Payu_Latam_SDK_PLS extends WC_Payment_Payu_Latam_SDK_PLS
{
    public function __construct()
    {
        parent::__construct();
        require_once (woo_payu_latam_sdk_pls()->plugin_path . 'lib/PayU.php');
    }

    public function executePayment(array $params = array(), $test = true)
    {
        $country = WC()->countries->get_base_country();
        $lang = $country === 'BR' ?  SupportedLanguages::PT : SupportedLanguages::ES;
        $country = WC()->countries->get_base_country();
        $reference = $reference = "payment_test" . time();
        $total = "100";
        $productinfo = "payment test";
        $currency = ($country == 'CO' && $test) ? 'USD' : $this->currency;
        $card_number = "5529998177229339";
        $card_type  = "MASTERCARD";
        $card_name = "Pedro Perez";
        $card_expire = date('Y/m', strtotime('+1 years'));
        $cvc = "808";
        $email = "buyer_test@test.com";
        $phone = "7563126";
        $city = "Medellin";
        $state = "Antioquia";
        $street = "calle 100";
        $street2 = "5555487";
        $postalCode = "000000";
        $dni = $country === 'BR' ? "811.807.405-64" : "5415668464654";

        $buyerCNPJ = '';

        $countryName = PayUCountries::CO;
        if ($country === 'AR')
            $countryName = PayUCountries::AR;
        if ($country === 'BR')
            $countryName = PayUCountries::BR;
        if ($country === 'MX')
            $countryName = PayUCountries::MX;
        if ($country === 'PA')
            $countryName = PayUCountries::PA;
        if ($country === 'PE')
            $countryName = PayUCountries::PE;

        if (!empty($params)){

            $order_id    = $params['id_order'];
            $order       = new WC_Order($order_id);

            $params = $this->prepareDataCard($params);
            $card_number = $params['card_number'];
            $card_name   = $params['card_name'];
            $card_type   = $params['card_type'];
            $card_expire = $params['card_expire'];
            $cvc         = $params['cvc'];
            $reference = $order->get_order_key() . '-' . time();
            $total = $order->get_total();
            $productinfo = "Order $order_id";
            $email = $order->get_billing_email();
            $phone = $order->get_billing_phone();
            $city = $order->get_billing_city();
            $state = $order->get_billing_state();
            $street = $order->get_billing_address_1();
            $street2 = empty($order->get_billing_address_2()) ? $order->get_billing_address_1() : $order->get_billing_address_2();
            $postalCode = empty($order->get_billing_postcode()) ? '000000' : $order->get_billing_postcode();
            $dni = empty(get_post_meta( $order->get_id(), '_billing_dni', true )) ? get_post_meta( $order->get_id(), '_billing_cpf', true ) : get_post_meta( $order->get_id(), '_billing_dni', true );
        }

        PayU::$apiKey = $this->apikey;
        PayU::$apiLogin = $this->apilogin;
        PayU::$merchantId = $this->merchant_id;
        PayU::$language = $lang;
        PayU::$isTest = ($test) ? true : $this->isTest;
        Environment::setPaymentsCustomUrl($this->createUrl());
        Environment::setReportsCustomUrl($this->createUrl(true));

        $parameters = array(
            //Ingrese aquí el identificador de la cuenta.
            PayUParameters::ACCOUNT_ID => $this->account_id,
            //Ingrese aquí el código de referencia.
            PayUParameters::REFERENCE_CODE => $reference,
            //Ingrese aquí la descripción.
            PayUParameters::DESCRIPTION => $productinfo,
            // -- Valores --
            //Ingrese aquí el valor de la transacción.
            PayUParameters::VALUE => $total,
            //Ingrese aquí la moneda.
            PayUParameters::CURRENCY => $currency,
            // -- Comprador
            //Ingrese aquí el nombre del comprador.
            PayUParameters::BUYER_NAME => $card_name,
            //Ingrese aquí el email del comprador.
            PayUParameters::BUYER_EMAIL => $email,
            //Ingrese aquí el teléfono de contacto del comprador.
            PayUParameters::BUYER_CONTACT_PHONE => $phone,
            //Ingrese aquí el documento de contacto del comprador.
            PayUParameters::BUYER_DNI => $dni,
            //Ingrese aquí la dirección del comprador.
            PayUParameters::BUYER_STREET => $street,
            PayUParameters::BUYER_STREET_2 => $street2,
            PayUParameters::BUYER_CITY => $city,
            PayUParameters::BUYER_STATE => $state,
            PayUParameters::BUYER_COUNTRY => $country,
            PayUParameters::BUYER_POSTAL_CODE => $postalCode,
            PayUParameters::BUYER_PHONE => $phone,
            // -- pagador --
            //Ingrese aquí el nombre del pagador.
            PayUParameters::PAYER_NAME => ($test || $this->isTest) ? "APPROVED" :  $card_name,
            // -- Datos de la tarjeta de crédito --
            //Ingrese aquí el número de la tarjeta de crédito
            PayUParameters::CREDIT_CARD_NUMBER => $card_number,
            //Ingrese aquí la fecha de vencimiento de la tarjeta de crédito
            PayUParameters::CREDIT_CARD_EXPIRATION_DATE => $card_expire,
            //Ingrese aquí el código de seguridad de la tarjeta de crédito
            PayUParameters::CREDIT_CARD_SECURITY_CODE=> $cvc,
            //Ingrese aquí el nombre de la tarjeta de crédito
            //VISA||MASTERCARD||AMEX||DINERS
            PayUParameters::PAYMENT_METHOD => $card_type,
            //Ingrese aquí el número de cuotas.
            PayUParameters::INSTALLMENTS_NUMBER => "1",
            //Ingrese aquí el nombre del pais.
            PayUParameters::COUNTRY => $countryName,
            //IP del pagadador
            PayUParameters::IP_ADDRESS => $this->getIP()

        );


        if ($country === 'BR' && !empty($buyerCNPJ))
            $parameters = array_merge($parameters, array(PayUParameters::BUYER_CNPJ => $buyerCNPJ));
        if($country === 'CO')
            $parameters = array_merge($parameters, array(PayUParameters::TAX_VALUE => "0", PayUParameters::TAX_RETURN_BASE => "0"));
        if ($country !== 'BR'){
            $parameters = array_merge(
                $parameters,
                array(
                    PayUParameters::PAYER_EMAIL => $email,
                    PayUParameters::PAYER_CONTACT_PHONE => $phone,
                    PayUParameters::PAYER_DNI => $dni,
                    PayUParameters::PAYER_STREET => $street,
                    PayUParameters::PAYER_STREET_2 => $street2,
                    PayUParameters::PAYER_CITY => $city,
                    PayUParameters::PAYER_STATE => $state,
                    PayUParameters::PAYER_COUNTRY => $country,
                    PayUParameters::PAYER_POSTAL_CODE => $postalCode,
                    PayUParameters::PAYER_PHONE => $phone,

                    PayUParameters::DEVICE_SESSION_ID => md5(session_id().microtime()),
                    PayUParameters::PAYER_COOKIE => md5(session_id().microtime()),
                    PayUParameters::USER_AGENT => $_SERVER['HTTP_USER_AGENT']
                )
            );
        }

        try{
            $response = PayUPayments::doAuthorizationAndCapture($parameters);

            if (!$test){

                $aprovved = false;
                $redirect_url  = '';

                if ($response->transactionResponse->state == "APPROVED") {
                    $aprovved   = true;
                    $transactionId = $response->transactionResponse->transactionId;
                    $order->payment_complete($transactionId);
                    $order->add_order_note(sprintf(__('Successful payment (Transaction ID: %s)',
                        'suscription-payu-latam'), $transactionId));
                    $message   = sprintf(__('Successful payment (Transaction ID: %s)', 'woo-payu-latam-sdk'),
                        $transactionId);
                    $messageClass  = 'woocommerce-message';
                    $redirect_url = add_query_arg( array('msg'=> urlencode($message), 'type'=> $messageClass), $order->get_checkout_order_received_url() );
                } elseif ($response->transactionResponse->state == "PENDING") {
                    $transactionId = $response->transactionResponse->transactionId;
                    $this->saveTransactionId($order_id, $transactionId);
                    $message       = sprintf(__('Payment pending (Transaction ID: %s)', 'woo-payu-latam-sdk'),
                        $transactionId);
                    $messageClass  = 'woocommerce-info';
                    $order->update_status('on-hold');
                    $order->add_order_note(sprintf(__('Pending approval (Transaction ID: %s)',
                        'woo-payu-latam-sdk'), $transactionId));
                    $redirect_url = add_query_arg(array('msg' => urlencode($message), 'type' => $messageClass),
                        $order->get_checkout_order_received_url());
                } elseif ($response->transactionResponse->state == "DECLINED") {
                    $transactionId = $response->transactionResponse->transactionId;
                    $message   = __('Payment declined', 'woo-payu-latam-sdk');
                    $messageClass  = 'woocommerce-error';
                    $order->update_status('failed');
                    $order->add_order_note(sprintf(__('Payment declined (Transaction ID: %s)',
                        'woo-payu-latam-sdk'), $transactionId));
                    $redirect_url = add_query_arg(array('msg' => urlencode($message), 'type' => $messageClass),
                        $order->get_checkout_order_received_url());
                } elseif ($response->transactionResponse->state == "EXPIRED") {
                    $transactionId = $response->transactionResponse->transactionId;
                    $message       = __('Payment expired', 'woo-payu-latam-sdk');
                    $messageClass  = 'woocommerce-error';
                    $order->update_status('failed');
                    $order->add_order_note(sprintf(__('Payment expired (Transaction ID: %s)', 'woo-payu-latam-sdk'),
                        $transactionId));
                    $redirect_url = add_query_arg(array('msg' => urlencode($message), 'type' => $messageClass),
                        $order->get_checkout_order_received_url());
                }

                return array('status' => $aprovved, 'url' => $redirect_url);

            }

        }catch (PayUException $ex){
            if($test){
                woo_payu_latam_sdk_pls()->log($ex->getMessage());
                $warning = sprintf(__('payU Latam SDK: Check that you have entered correctly merchant id, account id, Api Key, Apilogin. To perform tests use the credentials provided by payU %s Message error: %s code error: %s',
                    'woo-payu-latam-sdk'), '<a target="_blank" href="http://developers.payulatam.com/es/sdk/sandbox.html">' . __('Click here to see', 'woo-payu-latam-sdk') . '</a>', $ex->getMessage(), $ex->getCode());
                woo_payu_latam_sdk_pls_notices($warning);
            }else{
                woo_payu_latam_sdk_pls()->log($ex->getMessage());
                woo_payu_latam_sdk_pls()->log($parameters);
                return array('status' => false, 'message' => $ex->getMessage());
            }
        }

        return array('status' => false, 'message' => __('Not processed payment'));
    }


    public function createUrl($reports = false)
    {
        if ($this->isTest){
            $url = "https://sandbox.api.payulatam.com/";
        }else{
            $url = "https://api.payulatam.com/";
        }
        if ($reports){
            $url .= 'reports-api/4.0/service.cgi';
        }
        else{
            $url .= 'payments-api/4.0/service.cgi';
        }
        return $url;
    }


    public function getIP()
    {
        return ($_SERVER['REMOTE_ADDR'] == '::1' || $_SERVER['REMOTE_ADDR'] == '::' ||
            !preg_match('/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m',
                $_SERVER['REMOTE_ADDR'])) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
    }

    public function prepareDataCard($params)
    {
        $card_number = $params['payu-latam-sdk-number'];
        $card_number = str_replace(' ','', $card_number);
        $card_name = $params['payu-latam-sdk-name'];
        $card_type = $params['payu-latam-sdk-type'];
        $card_expire = $params['payu-latam-sdk-expiry'];
        $cvc = $params['payu-latam-sdk-cvc'];

        $year = date('Y');
        $lenyear = substr($year, 0,2);
        $expires = str_replace(' ', '', $card_expire);
        $expire = explode('/', $expires);
        $mes = $expire[0];
        if (strlen($mes) == 1) $mes = '0' . $mes;

        $yearFinal =  strlen($expire[1]) == 4 ? $expire[1] :  $lenyear . substr($expire[1], -2);
        $datecaduce = $yearFinal . "/" . $mes;

        $data = array(
            'card_number' => $card_number,
            'card_name' => $card_name,
            'card_type' => $card_type,
            'card_expire' => $datecaduce,
            'cvc' => $cvc
        );

        return $data;
    }

    public function saveTransactionId($order_id, $transactionId)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'payu_latam_sdk_pls_transactions';

        $wpdb->insert(
            $table_name,
            array(
                'orderid' => $order_id,
                'transactionid' => $transactionId,
            )
        );

    }

}