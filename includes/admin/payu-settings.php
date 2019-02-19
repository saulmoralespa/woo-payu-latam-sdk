<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 19/02/19
 * Time: 09:04 AM
 */

return array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'woo-payu-latam-sdk'),
        'type' => 'checkbox',
        'label' => __('Enable Payu Latam SDK', 'woo-payu-latam-sdk'),
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'woo-payu-latam-sdk'),
        'type' => 'text',
        'description' => __('It corresponds to the title that the user sees during the checkout', 'woo-payu-latam-sdk'),
        'default' => __('payU Latam', 'subscription-payu-latam'),
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __('Description', 'subscription-payu-latam'),
        'type' => 'textarea',
        'description' => __('It corresponds to the description that the user will see during the checkout', 'woo-payu-latam-sdk'),
        'default' => __('Accept credit card and cash payments', 'woo-payu-latam-sdk'),
        'desc_tip' => true,
    ),
    'debug' => array(
        'title' => __('Debug', 'woo-payu-latam-sdk'),
        'type' => 'checkbox',
        'label' => __('Debug records, it is saved in payment log', 'woo-payu-latam-sdk'),
        'default' => 'no'
    ),
    'environment' => array(
        'title' => __('Environment', 'woo-payu-latam-sdk'),
        'type'        => 'select',
        'class'       => 'wc-enhanced-select',
        'description' => __('Enable to run tests', 'woo-payu-latam-sdk'),
        'desc_tip' => true,
        'default' => true,
        'options'     => array(
            false    => __( 'Production', 'woo-payu-latam-sdk' ),
            true => __( 'Test', 'woo-payu-latam-sdk' ),
        ),
    ),
    'merchant_id' => array(
        'title' => __('Merchant id', 'woo-payu-latam-sdk'),
        'type'        => 'text',
        'description' => __('Merchant id, you find it in the payu account', 'woo-payu-latam-sdk'),
        'desc_tip' => true,
        'default' => '',
    ),
    'account_id' => array(
        'title' => __('Account id', 'woo-payu-latam-sdk'),
        'type'        => 'text',
        'description' => __('account id, you find it in the payu account', 'woo-payu-latam-sdk'),
        'desc_tip' => true,
        'default' => '',
    ),
    'apikey' => array(
        'title' => __('Apikey', 'woo-payu-latam-sdk'),
        'type' => 'text',
        'description' => __('apikey, you find it in the payu account', 'woo-payu-latam-sdk'),
        'default' => '',
        'desc_tip' => true,
        'placeholder' => ''
    ),
    'apilogin' => array(
        'title' => __('Apilogin', 'woo-payu-latam-sdk'),
        'type' => 'text',
        'description' => __('apilogin, you find it in the payu account', 'woo-payu-latam-sdk'),
        'default' => '',
        'desc_tip' => true,
        'placeholder' => ''
    )
);