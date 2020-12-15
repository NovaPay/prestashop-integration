/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

$(document).ready(function() {
    function validateCarrierExtraData(e) {
        var $information = $('#novapay-carrier-additional-information');
        if (!$information.length) {
            return;
        }
        
        if ($('input.delivery_option_radio:checked').val() === $information.data('carrier_id') + ',') {
            if (!$('#client-city-description').data('reference') || !$('#client-warehouse-reference').val()) {
                e.preventDefault();
                $('#novapay-carrier-error').show();
                $([document.documentElement, document.body]).animate({
                    scrollTop: $information.offset().top
                }, 500);
            }
        }
    }

    $(document)
        .on('submit', 'form[name="carrier_area"]', validateCarrierExtraData)
        .on('click', '#opc_payment_methods-content a', validateCarrierExtraData);
});
