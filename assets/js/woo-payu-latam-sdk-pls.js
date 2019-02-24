;(function( $ ) {
    'use strict';

    const checkout_form = $( 'form.checkout' );
    const form_card_payu_latam_sdk_pls = '#form-payu-latam-sdk';

    $( 'body' ).on( 'updated_checkout', function() {

            if (checkout_form.find(form_card_payu_latam_sdk_pls).is(":visible"))
            {
                new Card({
                    form: document.querySelector(form_card_payu_latam_sdk_pls),
                    container: '.card-wrapper'
                });
            }

    } );

    $(document.body).on('checkout_error', function () {
        swal.close();
    });

    checkout_form.on( 'checkout_place_order', function() {

        if($('form[name="checkout"] input[name="payment_method"]:checked').val() === 'payu_latam_sdk_pls'){

            let number_card = checkout_form.find('#payu-latam-sdk-number').val();
            let card_holder = checkout_form.find('#payu-latam-sdk-name').val();
            let card_expire = checkout_form.find('#payu-latam-sdk-expiry').val();
            let card_cvv = checkout_form.find('#payu-latam-sdk-cvc').val();
            let installments =  checkout_form.find('#payu-latam-sdk-installments');

            checkout_form.append($('<input name="payu-latam-sdk-number" type="hidden" />' ).val( number_card ));
            checkout_form.append($('<input name="payu-latam-sdk-name" type="hidden" />' ).val( card_holder ));
            checkout_form.append($('<input name="payu-latam-sdk-payment-method" type="hidden" />' ).val( getTypeCard() ));
            checkout_form.append($('<input name="payu-latam-sdk-expiry" type="hidden" />' ).val( card_expire ));
            checkout_form.append($('<input name="payu-latam-sdk-cvc" type="hidden" />' ).val( card_cvv ));
            if (installments.length)
                checkout_form.append($('<input name="payu-latam-sdk-installments" type="hidden" />' ).val( installments.val() ));

            let inputError = checkout_form.find("input[name=payu-latam-sdk-errorcard]");

            if( inputError.length )
            {
                inputError.remove();
            }

            if (!number_card || !card_holder || getTypeCard(checkout_form) === null || !card_expire || !card_cvv){
                checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msjEmptyInputs}">`);
            }else if (!checkCard()){
                checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msjNoCard}">`);
            }else if ((installments.length && installments.val() === '')){
                checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msjNoInstallments}">`);
            }
        }

        swal.fire({
            title: payu_latam_sdk_pls.msjProcess,
            onOpen: () => {
                swal.showLoading()
            },
            allowOutsideClick: false
        });

    });

    function checkCard(){
        let countryCode = payu_latam_sdk_pls.country;
        let classCard = $(".jp-card-identified" ).attr( "class" );
        let inputCard = $("input[name=payu-latam-sdk-type]");

        let  isAcceptableCard = false;

        switch(true) {
            case (classCard.indexOf('visa') !== -1 && countryCode !== 'PA'):
                $(inputCard).val('VISA');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('mastercard') !== -1):
                $(inputCard).val('MASTERCARD');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('amex') !== -1 && countryCode !== 'PA'):
                $(inputCard).val('AMEX');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('diners') !== -1 && (countryCode !== 'MX' || countryCode !== 'PA') ):
                $(inputCard).val('DINERS');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('elo') !== -1 && countryCode === 'BR' ):
                $(inputCard).val('ELO');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('hipercard') !== -1 && countryCode === 'BR' ):
                $(inputCard).val('HIPERCARD');
                isAcceptableCard = true;
        }

        return isAcceptableCard;

    }

    function getTypeCard(){
        let classCard = checkout_form.find(".jp-card-identified" ).attr( "class" );

        if (typeof classCard === 'undefined')
            return null;

        let classTypeCard = classCard.split(' ');
        let typeCard = classTypeCard[1].split('jp-card-');

        return typeCard[1].toUpperCase();
    }

}(jQuery));