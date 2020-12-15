/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

$(document).ready(function() {
    $.novaPay.initCarrierExtraContent();

    function enableConfirmationButton() {
        $('#payment-confirmation').find('button[type="submit"]').attr('disabled', null);
    }

    function displayErrors(errors) {
        var $errorList = $('#novapay-form-error-list');

        $errorList.html('');

        for (var i = 0; i < errors.length; i++) {
            $errorList.append($('<li>' + errors[i] + '</li>'));
        }
        
        $('#novapay-form-errors').removeClass('novapay-hidden');
        $.novaPay.hideLoader();
        enableConfirmationButton();
    }

    $(document).on('submit', '#js-delivery', function(e) {
        var $information = $('#novapay-carrier-additional-information');
        if (!$information.length) {
            return;
        }
        
        var $deliveryOption = $('#delivery_option_' + $information.data('carrier_id'));
        if ($deliveryOption.is(':checked')) {
            if (!$('#client-city-description').data('reference') || !$('#client-warehouse-reference').val()) {
                e.preventDefault();
                $('#novapay-carrier-error').show();
                $([document.documentElement, document.body]).animate({
                    scrollTop: $deliveryOption.closest('.delivery-option').offset().top
                }, 500);
            }
        }
    }).on('submit', '#novapay-form', function(e) {
        e.preventDefault();
        $.novaPay.showLoader();

        var $form = $(this);

        $.post($form.attr('action') + '?ajax=1', $form.serialize()).then(function(response) {
            response = JSON.parse(response);
            if (typeof response.error !== 'undefined') {
                displayErrors([response.error]);
            } else if (typeof response.errors !== 'undefined') {
                displayErrors(response.errors);
            } else {
                document.location.href = response.url;
            }
        }).fail(function() {
            $.novaPay.hideLoader();
            enableConfirmationButton();
        });
    });
});
