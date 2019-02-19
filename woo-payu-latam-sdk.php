<?php
/*
Plugin Name: payU Latam SDK
Description: payU latam  use sdk.
Version: 1.0.0
Author: Saul Morales Pacheco
Author URI: https://saulmoralespa.com
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: woo-payu-latam-sdk
Domain Path: /languages/
WC tested up to: 3.5
WC requires at least: 2.6
*/

if (!defined( 'ABSPATH' )) exit;

if(!defined('WOO_PAYU_LATAM_SDK_PLS_VERSION')){
    define('WOO_PAYU_LATAM_SDK_PLS_VERSION', '1.0.0');
}

if(!defined('WOO_PAYU_LATAM_SDK_PLS_NAME')){
    define('WOO_PAYU_LATAM_SDK_PLS_NAME', 'woo payu latam sdk');
}

add_action('plugins_loaded','woo_payu_latam_sdk_pls_init',0);

function woo_payu_latam_sdk_pls_init(){

    load_plugin_textdomain('woo-payu-latam-sdk', FALSE, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!requeriments_woo_payu_latam_sdk_pls()) {
        return;
    }

    woo_payu_latam_sdk_pls()->run_payu_latam_sdk();
}

function woo_payu_latam_sdk_pls_notices( $notice ) {
    ?>
    <div class="error notice">
        <p><?php echo $notice; ?></p>
    </div>
    <?php
}

function requeriments_woo_payu_latam_sdk_pls(){

    if ( version_compare( '5.6.0', PHP_VERSION, '>' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $php = __('payU Latam SDK: Requires php version 5.6.0 or higher', 'woo-payu-latam-sdk');

            add_action('admin_notices', function() use($php) {
                woo_payu_subscriptions_reports_notices($php);
            });
        }
        return false;
    }

    $openssl_warning = __( 'payU Latam SDK: Requires OpenSSL >= 1.0.1 to be installed on your server', 'woo-payu-latam-sdk' );

    if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action('admin_notices', function() use($openssl_warning) {
                woo_payu_subscriptions_reports_notices($openssl_warning);
            });
        }
        return false;
    }

    preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
    if ( empty( $matches[1] ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action('admin_notices', function() use($openssl_warning) {
                woo_payu_subscriptions_reports_notices($openssl_warning);
            });
        }
        return false;
    }

    if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            add_action('admin_notices', function() use($openssl_warning) {
                woo_payu_subscriptions_reports_notices($openssl_warning);
            });
        }
        return false;
    }

    if ( !in_array(
        'woocommerce/woocommerce.php',
        apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
        true
    ) ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $woo = __( 'payU Latam SDK: Woocommerce must be installed and active.', 'woo-payu-latam-sdk' );
            add_action('admin_notices', function() use($woo) {
                woo_payu_subscriptions_reports_notices($woo);
            });
        }
        return false;
    }

    if (!in_array(get_woocommerce_currency(), array('USD','BRL','COP','MXN','PEN'))){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $currency = __('payU Latam SDK: Requires one of these currencies USD, BRL, COP, MXN, PEN ', 'woo-payu-latam-sdk' )
                . sprintf(__('%s', 'woo-payu-latam-sdk' ), '<a href="' . admin_url()
                    . 'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">'
                    . __('Click here to configure', 'woo-payu-latam-sdk') . '</a>' );
            add_action('admin_notices', function() use($currency) {
                woo_payu_subscriptions_reports_notices($currency);
            });
        }
        return false;
    }

    $woo_countries = new WC_Countries();
    $default_country = $woo_countries->get_base_country();
    if (!in_array($default_country, array('AR', 'BR','CO','MX','PA','PE'))){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $country = __('payU Latam SDK: It requires that the country of the store be some of these countries
             Argentina, Brazil, Colombia, Mexico, Panama and Peru ', 'woo-payu-latam-sdk' )
                . sprintf(__('%s', 'woo-payu-latam-sdk' ), '<a href="' . admin_url()
                    . 'admin.php?page=wc-settings&tab=general#s2id_woocommerce_currency">'
                    .  __('Click here to configure', 'woo-payu-latam-sdk') . '</a>' );
            add_action('admin_notices', function() use($country) {
                woo_payu_subscriptions_reports_notices($country);
            });
        }
        return false;
    }

    if ($default_country === 'BR' && !in_array(
            'woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php',
            apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
            true
        )){
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            $fieldsbrazil = __('payU Latam SDK: the plugin is required ', 'woo-payu-latam-sdk' )
                . sprintf(__('%s', 'woo-payu-latam-sdk' ),
                    '<a href="https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">'
                    . __('WooCommerce Extra Checkout Fields for Brazil', 'woo-payu-latam-sdk') . '</a>' );
            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                add_action('admin_notices', function() use($fieldsbrazil) {
                    woo_payu_subscriptions_reports_notices($fieldsbrazil);
                });
            }
        }
        return false;
    }

    return true;
}

function woo_payu_latam_sdk_pls(){

    static $plugin;
    if (!isset($plugin)){
        require_once('includes/class-woo-payu-latam-sdk-plugin.php');
        $plugin = new Woo_Payu_Latam_SDK_Plugin(__FILE__, WOO_PAYU_LATAM_SDK_PLS_VERSION, WOO_PAYU_LATAM_SDK_PLS_NAME);
    }
    return $plugin;
}