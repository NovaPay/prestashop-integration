/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

$(document).ready(function() {
    (function() {
        var $input = $('#merchant_private_key');
        if ($input.length) {
            var $formGroup = $input.closest('.form-group');
            var $controlLabel = $formGroup.find('.control-label');
        
            if (!$controlLabel.hasClass('required'))
                $formGroup.hide();
    
            $(document).on('click', '#show_merchant_private_key_input', () => {
                $formGroup.show();
            });
        }
    }());

    $(document).on('click', '#novapay_use_custom_capture_amount', function() {
        if ($(this).is(':checked'))
            $('input[name="novapay_custom_capture_amount"]').parent().removeClass('novapay-hidden');
        else
            $('input[name="novapay_custom_capture_amount"]').parent().addClass('novapay-hidden');
    });
});