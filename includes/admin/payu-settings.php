<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 19/02/19
 * Time: 09:04 AM
 */

$settings = get_option('woocommerce_payu_latam_sdk_pls_settings' );
$cards_numbers = strpos($settings['cards_numbers_data'], ',') ?
    explode(',', $settings['cards_numbers_data']) :  [$settings['cards_numbers_data']];

$options_select = !empty($cards_numbers) ? "

let vals = ['" . implode("','", $cards_numbers) . "'];
    
    vals.forEach(function(e){
    if(!s2.find('option:contains(' + e + ')').length) 
    s2.append($('<option>').text(e));
    });
    
    s2.val(vals).trigger(\"change\");
": '';

$pages = get_pages();

$pages_actual = [];

if (!empty($pages)){
    foreach ($pages as $page){
        $pages_actual[$page->ID] = $page->post_title;
    }
}

wc_enqueue_js( "
    jQuery( function( $ ) {
    
    $('form').submit(function(){
	
	let cards_numbers = $('#woocommerce_payu_latam_sdk_pls_cards_numbers').val();
	cards_numbers = cards_numbers ? cards_numbers.join(',') : '';
	$('#woocommerce_payu_latam_sdk_pls_cards_numbers_data').val(cards_numbers);
	});
    
    let s2 = $('#woocommerce_payu_latam_sdk_pls_cards_numbers').select2({
    tags: true,
    multiple: true,
    createTag: function (params) {
    let term = $.trim(params.term);
    term = term.replace(/ /g, '');

    if (term === '' || isNaN(term) || term.length !== 6) {
      return null;
    }

    return {
      id: term,
      text: term,
      newTag: true
    }
  }
    }); 
    
    " . $options_select . "
});	
");

$credentials = '<a target="_blank" href="' . esc_url('http://developers.payulatam.com/es/sdk/sandbox.html') . '">' . __( 'For tests use the credentials provided by payU latam', 'woo-payu-latam-sdk' ) . '</a>';

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
        'default' => __('Woo payU latam SDK', 'subscription-payu-latam'),
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __('Description', 'woo-payu-latam-sdk'),
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
    'api'          => array(
        'title'       => __( 'Credentials', 'woo-payu-latam-sdk'),
        'type'        => 'title',
        'description' => $credentials,
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
        )
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
    ),
    'installments_number' => array(
        'title'       => __( 'Installments number', 'woo-payu-latam-sdk'),
        'type'        => 'title',
        'description' => __('The maximum number of installments that the user can select', 'woo-payu-latam-sdk'),
    ),
    'installments' => array(
        'title' => __('Installments', 'woo-payu-latam-sdk'),
        'type' => 'number',
        'default' => '1',
    ),
    'discount_for_cards' => array(
        'title'       => __( 'Discount for card', 'woo-payu-latam-sdk'),
        'type'        => 'title',
        'description' => '',
    ),
    'cards_numbers' => array(
        'title' => __('Cards numbers', 'woo-payu-latam-sdk'),
        'type'        => 'select',
        'class'       => 'wc-enhanced-select',
        'description' => __('Add card numbers', 'woo-payu-latam-sdk'),
        'desc_tip' => true
    ),
    'cards_numbers_data' => array(
        'type'        => 'hidden'
    ),
    'discount_rate_card_number' => array(
        'title' => __('Discount percentage', 'woo-payu-latam-sdk'),
        'type'        => 'number',
        'description' => __('The discount percentage', 'woo-payu-latam-sdk'),
        'desc_tip' => true
    )
);