;(function( $ ) {
    'use strict';

    const checkout_form = $( 'form.checkout' );
    const form_card_payu_latam_sdk_pls = '#form-payu-latam-sdk';

    $( 'body' ).on( 'updated_checkout', function() {
        $('input[name="payment_method"]').change(function(){
            loadCard();
        }).change();
    } );

    $(document.body).on('checkout_error', function () {
        swal.close();
    });


    checkout_form.on( 'checkout_place_order', function() {

        const form_checkout_payment = $('form[name="checkout"] input[name="payment_method"]:checked');

        if(form_checkout_payment.val() === 'payu_latam_sdk_baloto_plspse'){

           let inputError = checkout_form.find("input[name=payu-latam-sdk-errorcard]");

           if( inputError.length )
                inputError.remove();

           let bank = checkout_form.find('select[name="banks_payu_latam_colombia"]').val();

           if(bank === '0')
               checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msgBank}">`);

           let person_type = checkout_form.find('select[name="person_type_payu_latam_colombia"]').val();

           if (person_type.length === 0)
               checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msgPersonType}">`);

           checkout_form.append($('<input name="banks_payu_latam_colombia" type="hidden" />' ).val( bank ));
           checkout_form.append($('<input name="person_type_payu_latam_colombia" type="hidden" />' ).val( person_type ));
        }

        if(form_checkout_payment.val() === 'payu_latam_sdk_pls'){

            let number_card = checkout_form.find('#payu-latam-sdk-number').val();
            let card_holder = checkout_form.find('#payu-latam-sdk-name').val();
            let card_expire = checkout_form.find('#payu-latam-sdk-expiry').val();
            let card_cvv = checkout_form.find('#payu-latam-sdk-cvc').val();
            let installments =  checkout_form.find('#payu-latam-sdk-installments');


            checkout_form.append($('<input name="payu-latam-sdk-number" type="hidden" />' ).val( number_card ));
            checkout_form.append($('<input name="payu-latam-sdk-name" type="hidden" />' ).val( card_holder ));
            checkout_form.append($('<input name="payu-latam-sdk-payment-method" type="hidden" />' ).val( getTypeCard() ));
            checkout_form.append($('<input name="payu-latam-sdk-cvc" type="hidden" />' ).val( card_cvv ));

            if (installments.length)
                checkout_form.append($('<input name="payu-latam-sdk-installments" type="hidden" />' ).val( installments.val() ));

            let inputError = checkout_form.find("input[name=payu-latam-sdk-errorcard]");

            if( inputError.length )
                inputError.remove();

            if (!number_card || !card_holder || getTypeCard() === undefined || !card_expire || !card_cvv){
                checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msgEmptyInputs}">`);
            }else if (!checkCard()){
                checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msgNoCard}">`);
            }else if ((installments.length && installments.val() === '')){
                checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msgNoInstallments}">`);
            }else if (!valid_credit_card(number_card)){
                checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msgNoCardValidate}">`);
            }

            if(card_expire){
                card_expire = card_expire.replace(/ /g, '');
                card_expire = card_expire.split('/');
                let month = card_expire[0];

                if (month.length === 1) month = `0${month}`;

                let date = new Date();
                let year = date.getFullYear();
                year = year.toString();
                let lenYear = year.substr(0, 2);
                let yearEnd = card_expire[1].length === 4 ? card_expire[1]  : lenYear + card_expire[1].substr(-2);
                card_expire = `${month}/${yearEnd}`;
                checkout_form.append($('<input name="payu-latam-sdk-expiry" type="hidden" />' ).val( card_expire ));

                if (!validateDate(yearEnd, month))
                    checkout_form.append(`<input type="hidden" name="payu-latam-sdk-errorcard" value="${payu_latam_sdk_pls.msgValidateDate}">`);
            }
        }

        swal.fire({
            title: payu_latam_sdk_pls.msgProcess,
            onOpen: () => {
                swal.showLoading()
            },
            allowOutsideClick: false
        });

    });

    function loadCard() {
        if (checkout_form.find(form_card_payu_latam_sdk_pls).is(":visible"))
        {
            new Card({
                form: document.querySelector(form_card_payu_latam_sdk_pls),
                container: '.card-wrapper',
                placeholders: {
                    number: '•••• •••• •••• ••••',
                    name: payu_latam_sdk_pls.placeholdersName,
                    expiry: '••/••••',
                    cvc: '•••'
                },
                messages: {
                    monthYear: 'mm/aa'
                }
            });
        }
    }

    function checkCard(){
        let countryCode = payu_latam_sdk_pls.country;
        let  isAcceptableCard = false;

        switch(true) {
            case (getTypeCard() === 'VISA' && countryCode !== 'PA'):
                isAcceptableCard = true;
                break;
            case (getTypeCard() === 'MASTERCARD'):
                isAcceptableCard = true;
                break;
            case (getTypeCard() === 'AMEX' && countryCode !== 'PA'):
                isAcceptableCard = true;
                break;
            case (getTypeCard() === 'DINERS' && (countryCode !== 'MX' || countryCode !== 'PA')):
                isAcceptableCard = true;
                break;
            case (getTypeCard() === 'ELO' && countryCode === 'BR'):
                isAcceptableCard = true;
                break;
            case (getTypeCard() === 'HIPERCARD' && countryCode === 'BR'):
                isAcceptableCard = true;
        }

        return isAcceptableCard;

    }

    function getTypeCard(){

        let cardType;

        const number_card = checkout_form.find('#payu-latam-sdk-number').val();

        if(number_card){
            cardType = Payment.fns.cardType(number_card);
            cardType = cardType.toUpperCase();
        }

        return cardType;
    }

    function valid_credit_card(value) {
        // accept only digits, dashes or spaces
        if (/[^0-9-\s]+/.test(value)) return false;

        // The Luhn Algorithm. It's so pretty.
        var nCheck = 0, nDigit = 0, bEven = false;
        value = value.replace(/\D/g, "");

        for (var n = value.length - 1; n >= 0; n--) {
            var cDigit = value.charAt(n);
            nDigit = parseInt(cDigit, 10);

            if (bEven) {
                if ((nDigit *= 2) > 9) nDigit -= 9;
            }

            nCheck += nDigit;
            bEven = !bEven;
        }

        return (nCheck % 10) === 0;
    }

    function validateDate(yearEnd, month){

        let date = new Date();
        let currentMonth = ("0" + (date.getMonth() + 1)).slice(-2);
        let year = date.getFullYear();

        return (parseInt(yearEnd) > year) || (parseInt(yearEnd) === year && month >= currentMonth);
    }

}(jQuery));