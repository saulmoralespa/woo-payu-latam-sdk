<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 18/02/19
 * Time: 06:52 PM
 */

class Woo_Payu_Latam_SDK_Plugin
{
    /**
     * Filepath of main plugin file.
     *
     * @var string
     */
    public $file;
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version;
    /**
     * @var string
     */
    public $name;
    /**
     * Absolute plugin path.
     *
     * @var string
     */
    public $plugin_path;
    /**
     * Absolute plugin URL.
     *
     * @var string
     */
    public $plugin_url;
    /**
     * Absolute path to plugin includes dir.
     *
     * @var string
     */
    public $includes_path;
    /**
     * @var string
     */
    public $lib_path;
    /**
     * lib path
     *
     * @var WC_Logger
     */
    public $logger;
    /**
     * @var bool
     */
    private $_bootstrapped = false;

    public function __construct($file, $version, $name)
    {
        $this->file = $file;
        $this->version = $version;
        $this->name = $name;
        // Path.
        $this->plugin_path   = trailingslashit( plugin_dir_path( $this->file ) );
        $this->plugin_url    = trailingslashit( plugin_dir_url( $this->file ) );
        $this->includes_path = $this->plugin_path . trailingslashit( 'includes' );
        $this->lib_path = $this->plugin_path . trailingslashit( 'lib' );
        $this->logger = new WC_Logger();
    }

    public function run_payu_latam_sdk()
    {
        try{
            if ($this->_bootstrapped){
                throw new Exception( __( 'payU Latam SDK: can only be called once',  $this->nameClean(true)));
            }
            $this->_run();
            $this->_bootstrapped = true;
        }catch (Exception $e){
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                woo_payu_latam_sdk_pls_notices('payU Latam SDK:: ' . $e->getMessage());
            }
        }
    }

    protected function _run()
    {
        require_once ($this->includes_path . 'class-gateway-woo-payu-latam-sdk.php');
        require_once ($this->includes_path . 'class-woo-payu-latam-sdk.php');

        add_filter( 'plugin_action_links_' . plugin_basename( $this->file), array( $this, 'plugin_action_links' ) );
        add_filter( 'woocommerce_payment_gateways', array($this, 'woocommerce_payu_latam_sdk_add_gateway'));
        add_filter( 'woocommerce_billing_fields', array($this, 'custom_woocommerce_billing_fields'));
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'payu_latam_sdk_pls',array($this, 'payu_latam_sdk_pls_transaction_status'));
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();
        $plugin_links[] = '<a href="'.admin_url( 'admin.php?page=wc-settings&tab=checkout&section=payu_latam_sdk_pls').'">' . esc_html__( 'Settings', 'woo-payu-latam-sdk' ) . '</a>';
        $plugin_links[] = '<a href="https://saulmoralespa.github.io/woo-payu-latam-sdk/">' . esc_html__( 'Documentation', 'woo-payu-latam-sdk' ) . '</a>';
        return array_merge( $plugin_links, $links );
    }

    public function woocommerce_payu_latam_sdk_add_gateway($methods)
    {
        $methods[] = 'WC_Payment_Payu_Latam_SDK_PLS';
        return $methods;
    }

    public function custom_woocommerce_billing_fields($fields)
    {
        $woo_countries = new WC_Countries();
        $default_country = $woo_countries->get_base_country();

        if ($default_country !== 'BR' && $this->get_available_payment()) {
            $fields['billing_dni'] = array(
                'label' => __('DNI', 'woo-payu-latam-sdk'),
                'placeholder' => _x('Your DNI here....', 'placeholder', 'woo-payu-latam-sdk'),
                'required' => true,
                'clear' => false,
                'type' => 'number'
            );
        }

        return $fields;
    }

    public function nameClean($domain = false)
    {
        $name = ($domain) ? str_replace(' ', '-', $this->name)  : str_replace(' ', '', $this->name);
        return strtolower($name);
    }

    public function enqueue_scripts()
    {
        if (is_checkout() && $this->get_available_payment()){
            wp_enqueue_script( 'payu-latam-sdk-pls-sweet-alert', $this->plugin_url . 'assets/js/sweetalert2.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'payu-latam-sdk-pls', $this->plugin_url . 'assets/js/woo-payu-latam-sdk.js', array( 'jquery' ), $this->version, true );
            wp_enqueue_script( 'payu-latam-sdk-pls-card', $this->plugin_url . 'assets/js/card.js', array( 'jquery' ), $this->version, true );

            wp_localize_script( 'payu-latam-sdk-pls', 'payu_latam_sdk_pls', array(
                'country' => WC()->countries->get_base_country(),
                'msjNoCard' => __('The type of card is not accepted','woo-payu-latam-sdk'),
                'msjEmptyInputs' => __('Enter the card information','woo-payu-latam-sdk'),
                'msjProcess' => __('Please wait...','woo-payu-latam-sdk'),
                'msjReturn' => __('Redirecting to verify status...','woo-payu-latam-sdk')
            ) );
        }
    }

    public function log($message = '')
    {
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);

        $this->logger->add('woo-payu-latam-sdk', $message);
    }

    public function payu_latam_sdk_pls_transaction_status()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'payu_latam_sdk_pls_transactions';
        $rows = $wpdb->get_results( "SELECT id,orderid,transactionid FROM $table_name" );
        if (empty($rows))
            return;
    }

    public function get_available_payment()
    {
        $activated_ones = array_keys( WC()->payment_gateways->get_available_payment_gateways() );

        return in_array( 'payu_latam_sdk_pls', $activated_ones );
    }

}